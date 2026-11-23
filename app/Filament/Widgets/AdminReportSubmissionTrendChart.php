<?php

namespace App\Filament\Widgets;

use App\Models\ReportPeriod;
use App\Models\ReportVersion;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Query\JoinClause;

class AdminReportSubmissionTrendChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    protected ?string $heading = 'Ketepatan pengumpulan laporan';

    protected ?string $description = 'Jumlah periode berdasarkan tenggat selama 8 minggu kalender terakhir. Tenggat yang belum tiba tidak dihitung sebagai terlambat.';

    protected ?string $maxHeight = '280px';

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
        $currentWeekStart = CarbonImmutable::now(config('magang.timezone'))->startOfWeek();
        $weekStarts = array_map(
            fn (int $weeksAgo): CarbonImmutable => $currentWeekStart->subWeeks($weeksAgo),
            range(7, 0),
        );
        $rangeStart = $weekStarts[0];
        $rangeEnd = $currentWeekStart->copy()->addWeek();

        $firstVersionNumbers = ReportVersion::query()
            ->select('weekly_report_id')
            ->selectRaw('MIN(version_number) as version_number')
            ->groupBy('weekly_report_id');

        $periods = ReportPeriod::query()
            ->join('internships', 'report_periods.internship_id', '=', 'internships.id')
            ->leftJoin('weekly_reports', 'weekly_reports.report_period_id', '=', 'report_periods.id')
            ->leftJoinSub($firstVersionNumbers, 'first_version_numbers', fn (JoinClause $join): JoinClause => $join
                ->on('first_version_numbers.weekly_report_id', '=', 'weekly_reports.id'))
            ->leftJoin('report_versions as first_versions', fn (JoinClause $join): JoinClause => $join
                ->on('first_versions.weekly_report_id', '=', 'first_version_numbers.weekly_report_id')
                ->on('first_versions.version_number', '=', 'first_version_numbers.version_number'))
            ->where('internships.status', 'active')
            ->where('report_periods.due_at', '>=', $rangeStart)
            ->where('report_periods.due_at', '<', $rangeEnd)
            ->select([
                'report_periods.due_at',
                'first_versions.is_late as first_version_is_late',
            ])
            ->get();

        $weeklyCounts = [];

        foreach ($weekStarts as $weekStart) {
            $weeklyCounts[$weekStart->toDateString()] = [
                'onTime' => 0,
                'late' => 0,
                'missing' => 0,
            ];
        }

        foreach ($periods as $period) {
            $weekKey = $period->due_at->copy()->startOfWeek()->toDateString();

            if (! array_key_exists($weekKey, $weeklyCounts)) {
                continue;
            }

            if ($period->first_version_is_late !== null) {
                $bucket = (bool) $period->first_version_is_late ? 'late' : 'onTime';
                $weeklyCounts[$weekKey][$bucket]++;

                continue;
            }

            if ($period->due_at->isBefore(now())) {
                $weeklyCounts[$weekKey]['missing']++;
            }
        }

        $monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        return [
            'labels' => array_map(
                function (CarbonImmutable|string $weekStart) use ($monthLabels): string {
                    $date = CarbonImmutable::parse($weekStart);

                    return $date->format('d').' '.$monthLabels[(int) $date->format('n') - 1];
                },
                $weekStarts,
            ),
            'datasets' => [
                [
                    'label' => 'Tepat waktu',
                    'data' => array_column($weeklyCounts, 'onTime'),
                    'backgroundColor' => '#15803D',
                    'borderRadius' => 4,
                ],
                [
                    'label' => 'Terlambat',
                    'data' => array_column($weeklyCounts, 'late'),
                    'backgroundColor' => '#B45309',
                    'borderRadius' => 4,
                ],
                [
                    'label' => 'Belum dikumpulkan',
                    'data' => array_column($weeklyCounts, 'missing'),
                    'backgroundColor' => '#B91C1C',
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
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => ['position' => 'bottom'],
            ],
            'scales' => [
                'x' => ['stacked' => true, 'grid' => ['display' => false]],
                'y' => [
                    'stacked' => true,
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                ],
            ],
        ];
    }
}
