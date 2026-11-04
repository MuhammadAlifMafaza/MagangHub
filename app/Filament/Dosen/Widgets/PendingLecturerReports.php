<?php

namespace App\Filament\Dosen\Widgets;

use App\Enums\ReviewStatus;
use App\Models\WeeklyReport;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PendingLecturerReports extends Widget
{
    protected string $view = 'filament.dosen.widgets.pending-lecturer-reports';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $lecturerId = Auth::user()?->lecturer?->getKey();

        return [
            'reports' => WeeklyReport::query()
                ->with(['reportPeriod.internship.student.user', 'latestVersion'])
                ->where('review_status', ReviewStatus::PendingReview)
                ->whereHas('reportPeriod.internship.student.supervisorAssignments', fn (Builder $query) => $query
                    ->where('lecturer_id', $lecturerId)->where('status', 'active'))
                ->latest('updated_at')
                ->limit(10)
                ->get(),
        ];
    }
}
