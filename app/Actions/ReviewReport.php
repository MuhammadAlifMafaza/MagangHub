<?php

namespace App\Actions;

use App\Enums\ReportReviewDecision;
use App\Enums\ReviewStatus;
use App\Models\Lecturer;
use App\Models\ReportReview;
use App\Models\WeeklyReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewReport
{
    public function handle(Lecturer $lecturer, WeeklyReport $report, ReportReviewDecision $decision, ?string $feedback): ReportReview
    {
        return DB::transaction(function () use ($lecturer, $report, $decision, $feedback): ReportReview {
            $report = WeeklyReport::query()->with('reportPeriod.internship')->whereKey($report->id)->lockForUpdate()->firstOrFail();
            $assignment = $report->reportPeriod->internship->student->supervisorAssignments()
                ->where('lecturer_id', $lecturer->id)->where('status', 'active')->exists();
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
    }
}
