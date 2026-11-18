<?php

namespace App\Http\Controllers;

use App\Actions\PrepareReportDetails;
use App\Actions\PublishAssessment;
use App\Actions\ReviewReport;
use App\Actions\SaveAssessment;
use App\Actions\SubmitReportVersion;
use App\Enums\ReportReviewDecision;
use App\Enums\ReviewStatus;
use App\Enums\UserRole;
use App\Filament\Pages\ReportDetails as ReportDetailsPage;
use App\Http\Requests\ReviewReportRequest;
use App\Http\Requests\SaveAssessmentRequest;
use App\Http\Requests\SubmitReportRequest;
use App\Models\Assessment;
use App\Models\ReportPeriod;
use App\Models\ReportVersion;
use App\Models\WeeklyReport;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController
{
    public function periods(): View
    {
        $student = request()->user()->student;
        abort_if($student === null, 403);

        $periods = ReportPeriod::query()->whereHas('internship', fn ($query) => $query->where('student_id', $student->id))
            ->with([
                'weeklyReport.firstVersion',
                'weeklyReport.latestVersion.review',
                'weeklyReport.assessment' => fn ($query) => $query->published(),
            ])
            ->orderBy('starts_on')->get();

        return view('reports.periods', compact('periods'));
    }

    public function studentPeriod(ReportPeriod $period, PrepareReportDetails $prepareReportDetails): View
    {
        $user = request()->user();
        abort_unless($user->role === UserRole::Student, 403);
        Gate::authorize('viewPeriod', [WeeklyReport::class, $period]);

        $period->loadMissing('weeklyReport.firstVersion');
        $report = $period->weeklyReport;
        $fileAvailability = collect();

        if ($report !== null) {
            $details = $prepareReportDetails->handle($user, $report);
            $report = $details['report'];
            $fileAvailability = $details['fileAvailability'];
            $period->setRelation('weeklyReport', $report);
        }

        $canUpload = $this->canStudentUploadPeriod($period, $report);

        return view('reports.show', compact('period', 'report', 'fileAvailability', 'canUpload'));
    }

    public function submit(SubmitReportRequest $request, ReportPeriod $period, SubmitReportVersion $action): RedirectResponse
    {
        Gate::authorize('submit', [WeeklyReport::class, $period]);
        $action->handle($request->user()->student, $period, $request->file('file'), $request->validated('student_note'));

        return back()->with('status', 'Laporan berhasil dikirim.');
    }

    public function review(ReviewReportRequest $request, WeeklyReport $report, ReviewReport $action): RedirectResponse
    {
        $action->handle($request->user()->lecturer, $report, ReportReviewDecision::from($request->validated('decision')), $request->validated('feedback'));

        return back()->with('status', 'Pemeriksaan laporan berhasil disimpan.');
    }

    public function saveAssessment(SaveAssessmentRequest $request, WeeklyReport $report, SaveAssessment $action): RedirectResponse
    {
        $assessment = $action->handle($request->user()->lecturer, $report, $request->validated('score'), $request->validated('notes'));

        return back()->with('status', 'Draf penilaian berhasil disimpan.');
    }

    public function publishAssessment(Assessment $assessment, PublishAssessment $action): RedirectResponse
    {
        Gate::authorize('update', $assessment);
        abort_unless($assessment->lecturer_id === request()->user()->lecturer?->id, 403);
        $action->handle($assessment);

        return back()->with('status', 'Penilaian berhasil dipublikasikan.');
    }

    public function download(ReportVersion $version): Response|StreamedResponse
    {
        Gate::authorize('download', $version);
        abort_unless($this->isStoredReportFileAvailable($version), 404);

        return Storage::disk($version->disk)->download($version->stored_path, $version->original_name, [
            'X-Content-Type-Options' => 'nosniff',
            'Content-Type' => $version->mime_type,
        ]);
    }

    public function preview(Request $request, ReportVersion $version): Response|StreamedResponse
    {
        Gate::authorize('download', $version);
        abort_unless($version->mime_type === 'application/pdf', 415);
        abort_unless($this->isStoredReportFileAvailable($version), 404);

        $disk = Storage::disk($version->disk);
        $size = $version->size_bytes;
        $range = $this->requestedByteRange($request->header('Range'), $size);

        if ($range === false) {
            return response('', 416, [
                'Accept-Ranges' => 'bytes',
                'Content-Range' => "bytes */{$size}",
            ]);
        }

        [$start, $end] = $range ?? [0, $size - 1];
        $contentLength = $size === 0 ? 0 : $end - $start + 1;
        $stream = $disk->readStream($version->stored_path);
        abort_unless(is_resource($stream), 404);

        $headers = [
            'Content-Type' => 'application/pdf',
            'Content-Length' => (string) $contentLength,
            'Content-Disposition' => (new ResponseHeaderBag)->makeDisposition('inline', $version->original_name, 'report.pdf'),
            'Accept-Ranges' => 'bytes',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ];

        if ($range !== null) {
            $headers['Content-Range'] = "bytes {$start}-{$end}/{$size}";
        }

        return response()->stream(function () use ($stream, $start, $contentLength): void {
            try {
                if ($start > 0 && @fseek($stream, $start) !== 0) {
                    $bytesToSkip = $start;

                    while ($bytesToSkip > 0 && ! feof($stream)) {
                        $chunk = fread($stream, min(8192, $bytesToSkip));

                        if ($chunk === false || $chunk === '') {
                            return;
                        }

                        $bytesToSkip -= strlen($chunk);
                    }
                }

                $remaining = $contentLength;

                while ($remaining > 0 && ! feof($stream)) {
                    $chunk = fread($stream, min(8192, $remaining));

                    if ($chunk === false || $chunk === '') {
                        break;
                    }

                    echo $chunk;
                    $remaining -= strlen($chunk);
                }
            } finally {
                fclose($stream);
            }
        }, $range === null ? 200 : 206, $headers);
    }

    public function lecturerQueue(): View
    {
        $lecturer = request()->user()->lecturer;
        abort_if($lecturer === null, 403);
        $reports = WeeklyReport::query()->pendingReview()
            ->whereHas('reportPeriod.internship.student.supervisorAssignments', fn ($query) => $query->where('lecturer_id', $lecturer->id)->where('status', 'active'))
            ->with(['reportPeriod.internship.student.user', 'latestVersion'])
            ->oldest('created_at')->paginate(20);

        return view('reports.queue', compact('reports'));
    }

    public function show(WeeklyReport $report): RedirectResponse
    {
        Gate::authorize('view', $report);

        return match (request()->user()->role) {
            UserRole::Admin => redirect()->to(ReportDetailsPage::getUrl(['report' => $report->getKey()], panel: 'admin')),
            UserRole::Lecturer => redirect()->to(ReportDetailsPage::getUrl(['report' => $report->getKey()], panel: 'dosen')),
            UserRole::Student => redirect()->route('mahasiswa.laporan.show', $report),
        };
    }

    public function studentShow(WeeklyReport $report, PrepareReportDetails $prepareReportDetails): View
    {
        $user = request()->user();
        abort_unless($user->role === UserRole::Student, 403);

        $details = $prepareReportDetails->handle($user, $report);
        $report = $details['report'];
        $period = $report->reportPeriod;
        $period->setRelation('weeklyReport', $report);
        $canUpload = $this->canStudentUploadPeriod($period, $report);

        return view('reports.show', array_merge($details, compact('period', 'canUpload')));
    }

    private function canStudentUploadPeriod(ReportPeriod $period, ?WeeklyReport $report): bool
    {
        if ($report === null) {
            return $period->starts_on->isPast();
        }

        return $report->review_status === ReviewStatus::RevisionRequested
            || ($report->review_status === ReviewStatus::PendingReview && $report->latestVersion?->review === null);
    }

    private function isStoredReportFileAvailable(ReportVersion $version): bool
    {
        $disk = Storage::disk($version->disk);

        return $disk->exists($version->stored_path) && $disk->size($version->stored_path) === $version->size_bytes;
    }

    /**
     * @return array{0: int, 1: int}|false|null
     */
    private function requestedByteRange(?string $rangeHeader, int $size): array|false|null
    {
        if ($rangeHeader === null || ! str_starts_with(strtolower($rangeHeader), 'bytes=')) {
            return null;
        }

        $rangeValue = substr($rangeHeader, 6);

        if (str_contains($rangeValue, ',') || preg_match('/^(\d*)-(\d*)$/D', $rangeValue, $matches) !== 1) {
            return null;
        }

        if ($matches[1] === '' && $matches[2] === '') {
            return false;
        }

        if ($matches[1] === '') {
            $suffixLength = (int) $matches[2];

            if ($suffixLength <= 0 || $size === 0) {
                return false;
            }

            return [max(0, $size - $suffixLength), $size - 1];
        }

        $start = (int) $matches[1];
        $end = $matches[2] === '' ? $size - 1 : min((int) $matches[2], $size - 1);

        if ($start >= $size || $end < $start) {
            return false;
        }

        return [$start, $end];
    }
}
