<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['report_period_id', 'review_status'])]
class WeeklyReport extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['review_status' => ReviewStatus::class];
    }

    public function reportPeriod(): BelongsTo
    {
        return $this->belongsTo(ReportPeriod::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ReportVersion::class);
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(ReportVersion::class)->ofMany('version_number', 'max');
    }

    public function firstVersion(): HasOne
    {
        return $this->hasOne(ReportVersion::class)->ofMany('version_number', 'min');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ReportReview::class);
    }

    public function assessment(): HasOne
    {
        return $this->hasOne(Assessment::class);
    }

    public function scopePendingReview(Builder $query): Builder
    {
        return $query->where('review_status', ReviewStatus::PendingReview);
    }
}
