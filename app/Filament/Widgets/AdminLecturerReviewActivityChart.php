<?php

namespace App\Filament\Widgets;

use App\Models\ReportReview;
use Filament\Widgets\ChartWidget;

class AdminLecturerReviewActivityChart extends ChartWidget
{
    protected static ?int $sort = 4;

    protected ?string $heading = 'Pemeriksaan per dosen';

    protected ?string $description = 'Jumlah keputusan pemeriksaan dalam 8 minggu terakhir, untuk program magang aktif.';

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '280px';

    protected ?string $emptyStateHeading = 'Belum ada pemeriksaan';

    protected ?string $emptyStateDescription = 'Belum ada keputusan pemeriksaan tercatat dalam 8 minggu terakhir.';

    /**
     * @return array{
     *     labels: array<string>,
     *     datasets: array<array{
     *         label: string,
     *         data: array<int>,
     *         backgroundColor: string,
     *         borderRadius: int
     *     }>
     * }
     */
    protected function getData(): array
    {
        $periodStart = today()->startOfWeek()->subWeeks(7);
        $periodEnd = now();

        $reviews = ReportReview::query()
            ->join('weekly_reports', 'report_reviews.weekly_report_id', '=', 'weekly_reports.id')
            ->join('report_periods', 'weekly_reports.report_period_id', '=', 'report_periods.id')
            ->join('internships', 'report_periods.internship_id', '=', 'internships.id')
            ->join('lecturers', 'report_reviews.lecturer_id', '=', 'lecturers.id')
            ->join('users', 'lecturers.user_id', '=', 'users.id')
            ->where('internships.status', 'active')
            ->whereBetween('report_reviews.reviewed_at', [$periodStart, $periodEnd])
            ->select('report_reviews.lecturer_id')
            ->selectRaw('users.name as lecturer_name, COUNT(report_reviews.id) as review_count')
            ->groupBy('report_reviews.lecturer_id', 'users.name')
            ->orderByDesc('review_count')
            ->orderBy('users.name')
            ->limit(6)
            ->get();

        if ($reviews->isEmpty()) {
            return [];
        }

        return [
            'labels' => $reviews->pluck('lecturer_name')->all(),
            'datasets' => [
                [
                    'label' => 'Pemeriksaan',
                    'data' => $reviews->pluck('review_count')->map(fn (mixed $count): int => (int) $count)->all(),
                    'backgroundColor' => '#1C3155',
                    'borderRadius' => 4,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                ],
                'y' => ['grid' => ['display' => false]],
            ],
        ];
    }
}
