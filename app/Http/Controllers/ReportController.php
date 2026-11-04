<?php

namespace App\Http\Controllers;

use App\Actions\PublishAssessment;
use App\Actions\ReviewReport;
use App\Actions\SaveAssessment;
use App\Actions\SubmitReportVersion;
use App\Enums\ReportReviewDecision;
use App\Http\Requests\ReviewReportRequest;
use App\Http\Requests\SaveAssessmentRequest;
use App\Http\Requests\SubmitReportRequest;
use App\Models\Assessment;
use App\Models\ReportPeriod;
use App\Models\ReportVersion;
use App\Models\WeeklyReport;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController
{
    public function periods(): View
    {
        $student = request()->user()->student;
        abort_if($student === null, 403);

        $periods = ReportPeriod::query()->whereHas('internship', fn ($query) => $query->where('student_id', $student->id))
            ->with(['weeklyReport.latestVersion.review', 'weeklyReport.assessment' => fn ($query) => $query->published()])
            ->orderBy('starts_on')->get();

        return view('reports.periods', compact('periods'));
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
        abort_unless(Storage::disk($version->disk)->exists($version->stored_path), 404);

        return Storage::disk($version->disk)->download($version->stored_path, $version->original_name, [
            'X-Content-Type-Options' => 'nosniff',
            'Content-Type' => $version->mime_type,
        ]);
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

    public function show(WeeklyReport $report): View
    {
        Gate::authorize('view', $report);
        $report->load(['versions.review', 'reviews.lecturer.user', 'assessment' => fn ($query) => request()->user()->role->value === 'student' ? $query->published() : $query]);

        return view('reports.show', compact('report'));
    }
}
