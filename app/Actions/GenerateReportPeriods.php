<?php

namespace App\Actions;

use App\Models\Internship;
use App\Models\ReportPeriod;
use Carbon\CarbonImmutable;

class GenerateReportPeriods
{
    public function handle(Internship $internship): void
    {
        $timezone = config('magang.timezone');
        $periodStart = CarbonImmutable::parse($internship->start_date, $timezone)->startOfDay();
        $internshipEnd = CarbonImmutable::parse($internship->end_date, $timezone)->startOfDay();
        $dueTime = config('magang.report_periods.due_time');
        $weekNumber = 1;

        while ($periodStart->lessThanOrEqualTo($internshipEnd)) {
            $periodEnd = $periodStart->addDays(6)->min($internshipEnd);

            ReportPeriod::query()->create([
                'internship_id' => $internship->id,
                'week_number' => $weekNumber,
                'starts_on' => $periodStart->toDateString(),
                'ends_on' => $periodEnd->toDateString(),
                'due_at' => $periodEnd->setTimeFromTimeString($dueTime),
            ]);

            $periodStart = $periodEnd->addDay();
            $weekNumber++;
        }
    }
}
