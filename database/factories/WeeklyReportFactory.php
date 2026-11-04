<?php

namespace Database\Factories;

use App\Enums\ReviewStatus;
use App\Models\ReportPeriod;
use App\Models\WeeklyReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WeeklyReport> */
class WeeklyReportFactory extends Factory
{
    protected $model = WeeklyReport::class;

    public function definition(): array
    {
        return ['report_period_id' => ReportPeriod::factory(), 'review_status' => ReviewStatus::PendingReview];
    }
}
