<?php

namespace Database\Factories;

use App\Enums\ReportReviewDecision;
use App\Models\Lecturer;
use App\Models\ReportReview;
use App\Models\ReportVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ReportReview> */
class ReportReviewFactory extends Factory
{
    protected $model = ReportReview::class;

    public function definition(): array
    {
        return [
            'weekly_report_id' => fn (array $attributes) => ReportVersion::find($attributes['report_version_id'])->weekly_report_id,
            'report_version_id' => ReportVersion::factory(), 'lecturer_id' => Lecturer::factory(),
            'decision' => ReportReviewDecision::Approved, 'feedback' => null, 'reviewed_at' => now(),
        ];
    }
}
