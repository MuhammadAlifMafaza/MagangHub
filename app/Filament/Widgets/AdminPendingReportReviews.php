<?php

namespace App\Filament\Widgets;

use App\Enums\ReviewStatus;
use App\Models\WeeklyReport;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AdminPendingReportReviews extends Widget
{
    protected string $view = 'filament.widgets.admin-pending-report-reviews';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array{reports: Collection<int, WeeklyReport>, recapUrl: string}
     */
    protected function getViewData(): array
    {
        return [
            'reports' => WeeklyReport::query()
                ->with(['reportPeriod.internship.student.user', 'latestVersion'])
                ->where('review_status', ReviewStatus::PendingReview)
                ->whereHas('reportPeriod', fn (Builder $periods): Builder => $periods
                    ->whereDate('starts_on', '<=', today())
                    ->whereDate('ends_on', '>=', today())
                    ->whereHas('internship', fn (Builder $internships): Builder => $internships->where('status', 'active')))
                ->latest('updated_at')
                ->latest('id')
                ->limit(8)
                ->get(),
            'recapUrl' => route('filament.admin.pages.administrative-recap'),
        ];
    }
}
