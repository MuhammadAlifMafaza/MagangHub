<?php

namespace App\Actions;

use App\Enums\ReportReviewDecision;
use App\Enums\ReviewStatus;
use App\Models\Lecturer;
use App\Models\ReportReview;
use App\Models\SupervisorAssignment;
use App\Models\WeeklyReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewReport
{
    public function handle(Lecturer $lecturer, WeeklyReport $report, ReportReviewDecision $decision, ?string $feedback): ReportReview
    {
        $review = DB::transaction(function () use ($lecturer, $report, $decision, $feedback): ReportReview {
            $report = WeeklyReport::query()->with('reportPeriod.internship')->whereKey($report->id)->lockForUpdate()->firstOrFail();
            $assignment = SupervisorAssignment::query()
                ->where('student_id', $report->reportPeriod->internship->student_id)
                ->where('lecturer_id', $lecturer->id)
                ->coveringInstant($report->reportPeriod->starts_on->copy()->startOfDay())
                ->exists();
            abort_unless($assignment, 403);
            abort_unless($report->review_status === ReviewStatus::PendingReview, 409);

            if ($decision === ReportReviewDecision::RevisionRequested && blank($feedback)) {
                throw ValidationException::withMessages(['feedback' => 'Umpan balik wajib diisi saat meminta revisi.']);
            }

            $version = $report->versions()->orderByDesc('version_number')->lockForUpdate()->firstOrFail();
            abort_if($version->review()->exists(), 409, 'Versi ini sudah diperiksa.');

            $review = $report->reviews()->create([
                'report_version_id' => $version->id,
                'lecturer_id' => $lecturer->id,
                'decision' => $decision,
                'feedback' => $feedback,
                'reviewed_at' => now(),
            ]);
            $report->update(['review_status' => $decision === ReportReviewDecision::Approved ? ReviewStatus::Approved : ReviewStatus::RevisionRequested]);

            return $review;
        });

        $studentUser = $review->weeklyReport->reportPeriod->internship->student->user;
        $isApproved = $decision === ReportReviewDecision::Approved;

        app(SendWorkflowNotification::class)->handle(
            $studentUser,
            $isApproved ? 'report_approved' : 'report_revision_requested',
            $isApproved ? 'Laporan disetujui' : 'Laporan perlu revisi',
            $isApproved ? 'Laporan mingguan Anda telah disetujui.' : 'Dosen memberikan catatan revisi: '.(string) $feedback,
            route('mahasiswa.laporan.show', $review->weeklyReport, absolute: false),
            $isApproved ? 'Lihat laporan' : 'Buka laporan',
        );

        return $review;
    }
}
