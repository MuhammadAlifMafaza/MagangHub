<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Database\Factories\WeeklyReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $report_period_id
 * @property ReviewStatus $review_status
 * @property-read ReportPeriod $reportPeriod
 * @property-read ReportVersion|null $firstVersion
 * @property-read ReportVersion|null $latestVersion
 * @property-read Assessment|null $assessment
 */
#[Fillable(['report_period_id', 'review_status'])]
class WeeklyReport extends Model
{
    /** @use HasFactory<WeeklyReportFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['review_status' => ReviewStatus::class];
    }

    /** @return BelongsTo<ReportPeriod, $this> */
    public function reportPeriod(): BelongsTo
    {
        return $this->belongsTo(ReportPeriod::class);
    }

    /** @return HasMany<ReportVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(ReportVersion::class);
    }

    /** @return HasOne<ReportVersion, $this> */
    public function latestVersion(): HasOne
    {
        return $this->hasOne(ReportVersion::class)->ofMany('version_number', 'max');
    }

    /** @return HasOne<ReportVersion, $this> */
    public function firstVersion(): HasOne
    {
        return $this->hasOne(ReportVersion::class)->ofMany('version_number', 'min');
    }

    /** @return HasMany<ReportReview, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(ReportReview::class);
    }

    /** @return HasOne<Assessment, $this> */
    public function assessment(): HasOne
    {
        return $this->hasOne(Assessment::class);
    }

    /** @param Builder<WeeklyReport> $query
     * @return Builder<WeeklyReport>
     */
    public function scopePendingReview(Builder $query): Builder
    {
        return $query->where('review_status', ReviewStatus::PendingReview);
    }
}
