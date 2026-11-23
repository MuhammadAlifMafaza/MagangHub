<?php

namespace App\Filament\Pages;

use App\Actions\PrepareReportDetails;
use App\Enums\UserRole;
use App\Filament\Dosen\Resources\ReportReviews\ReportReviewResource;
use App\Models\WeeklyReport;
use Filament\Pages\Page;
use Livewire\Attributes\Locked;

class ReportDetails extends Page
{
    protected static ?string $slug = 'laporan/{report}';

    protected static ?string $title = 'Detail Laporan';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.report-details';

    #[Locked]
    public int $reportId;

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::Admin, UserRole::Lecturer], true);
    }

    public function mount(int $report): void
    {
        $this->reportId = $report;
    }

    public function getBreadcrumbs(): array
    {
        $parentBreadcrumb = match (auth()->user()->role) {
            UserRole::Admin => [AdministrativeRecap::getUrl(panel: 'admin') => 'Rekap administratif'],
            UserRole::Lecturer => [ReportReviewResource::getUrl('index', panel: 'dosen') => 'Pemeriksaan laporan'],
            UserRole::Student => abort(403),
        };

        return [...$parentBreadcrumb, 'Detail laporan'];
    }

    protected function getViewData(): array
    {
        $report = WeeklyReport::query()->findOrFail($this->reportId);

        return app(PrepareReportDetails::class)->handle(auth()->user(), $report);
    }
}
