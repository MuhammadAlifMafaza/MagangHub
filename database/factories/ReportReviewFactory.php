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
            'weekly_report_id' => function (array $attributes): int {
                $reportVersionId = $attributes['report_version_id'] ?? null;

                if ($reportVersionId instanceof ReportVersion) {
                    return $reportVersionId->weekly_report_id;
                }

                if (! is_int($reportVersionId) && ! is_string($reportVersionId)) {
                    throw new \LogicException('The report version factory did not provide a valid report version ID.');
                }

                return ReportVersion::query()->whereKey($reportVersionId)->firstOrFail()->weekly_report_id;
            },
            'report_version_id' => ReportVersion::factory(), 'lecturer_id' => Lecturer::factory(),
            'decision' => ReportReviewDecision::Approved, 'feedback' => null, 'reviewed_at' => now(),
        ];
    }
}
