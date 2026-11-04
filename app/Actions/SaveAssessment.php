<?php

namespace App\Actions;

use App\Enums\AssessmentStatus;
use App\Enums\ReviewStatus;
use App\Models\Assessment;
use App\Models\Lecturer;
use App\Models\WeeklyReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveAssessment
{
    public function handle(Lecturer $lecturer, WeeklyReport $report, int|float|string $score, ?string $notes = null): Assessment
    {
        if (! is_numeric($score) || $score < 0 || $score > 100) {
            throw ValidationException::withMessages(['score' => 'Nilai harus berupa angka 0–100.']);
        }

        return DB::transaction(function () use ($lecturer, $report, $score, $notes): Assessment {
            $report = WeeklyReport::query()->with('reportPeriod.internship')->whereKey($report->id)->lockForUpdate()->firstOrFail();
            $assigned = $report->reportPeriod->internship->student->supervisorAssignments()
                ->where('lecturer_id', $lecturer->id)->where('status', 'active')->exists();
            abort_unless($assigned, 403);
            abort_unless($report->review_status === ReviewStatus::Approved, 409);

            $assessment = Assessment::query()->where('weekly_report_id', $report->id)->lockForUpdate()->first();
            abort_if($assessment?->status === AssessmentStatus::Published, 409, 'Nilai yang telah dipublikasikan bersifat final.');

            return Assessment::updateOrCreate(
                ['weekly_report_id' => $report->id],
                ['lecturer_id' => $lecturer->id, 'score' => $score, 'notes' => $notes, 'status' => AssessmentStatus::Draft, 'published_at' => null],
            );
        });
    }
}
