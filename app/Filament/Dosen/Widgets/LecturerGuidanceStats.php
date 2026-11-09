<?php

namespace App\Filament\Dosen\Widgets;

use App\Enums\AssessmentStatus;
use App\Enums\ReviewStatus;
use App\Models\Assessment;
use App\Models\GuidanceBooking;
use App\Models\GuidanceSlot;
use App\Models\WeeklyReport;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class LecturerGuidanceStats extends Widget
{
    protected string $view = 'filament.dosen.widgets.lecturer-guidance-stats';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $lecturer = Auth::user()?->lecturer;

        if ($lecturer === null) {
            return [
                'stats' => [
                    'upcoming_slots' => 0,
                    'upcoming_bookings' => 0,
                    'active_students' => 0,
                    'draft_assessments' => 0,
                ],
                'reportStatuses' => [],
            ];
        }

        $lecturerId = $lecturer->getKey();

        $reportStatusCounts = WeeklyReport::query()
            ->whereHas('reportPeriod.internship.student.supervisorAssignments', fn (Builder $query): Builder => $query
                ->where('lecturer_id', $lecturerId)
                ->where('status', 'active'))
            ->selectRaw('review_status, COUNT(*) as aggregate')
            ->groupBy('review_status')
            ->pluck('aggregate', 'review_status');

        $reportStatuses = collect(ReviewStatus::cases())
            ->map(fn (ReviewStatus $status): array => [
                'label' => $status->label(),
                'color' => $status->color(),
                'count' => (int) $reportStatusCounts->get($status->value, 0),
            ])
            ->all();

        return [
            'stats' => [
                'upcoming_slots' => GuidanceSlot::query()
                    ->where('lecturer_id', $lecturerId)
                    ->where('status', 'open')
                    ->where('starts_at', '>=', now())
                    ->count(),
                'upcoming_bookings' => GuidanceBooking::query()
                    ->where('status', 'booked')
                    ->whereHas('slot', fn (Builder $query): Builder => $query
                        ->where('lecturer_id', $lecturerId)
                        ->where('starts_at', '>=', now()))
                    ->count(),
                'active_students' => $lecturer->supervisorAssignments()
                    ->where('status', 'active')
                    ->distinct('student_id')
                    ->count('student_id'),
                'draft_assessments' => Assessment::query()
                    ->where('lecturer_id', $lecturerId)
                    ->where('status', AssessmentStatus::Draft)
                    ->whereHas('weeklyReport.reportPeriod.internship.student.supervisorAssignments', fn (Builder $query): Builder => $query
                        ->where('lecturer_id', $lecturerId)
                        ->where('status', 'active'))
                    ->count(),
            ],
            'reportStatuses' => $reportStatuses,
        ];
    }
}
