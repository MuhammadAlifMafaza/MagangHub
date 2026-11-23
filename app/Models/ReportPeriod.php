<?php

namespace App\Models;

use App\Enums\ReportCollectionStatus;
use Database\Factories\ReportPeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $internship_id
 * @property int $week_number
 * @property Carbon $starts_on
 * @property Carbon $ends_on
 * @property Carbon $due_at
 * @property bool $weekly_report_exists
 * @property bool|null $first_version_is_late
 * @property-read Internship $internship
 * @property-read WeeklyReport|null $weeklyReport
 */
#[Fillable(['internship_id', 'week_number', 'starts_on', 'ends_on', 'due_at'])]
class ReportPeriod extends Model
{
    /** @use HasFactory<ReportPeriodFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'due_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Internship, $this> */
    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    /** @return HasOne<WeeklyReport, $this> */
    public function weeklyReport(): HasOne
    {
        return $this->hasOne(WeeklyReport::class);
    }

    public function collectionStatus(): ReportCollectionStatus
    {
        $firstVersion = $this->weeklyReport?->firstVersion;

        if ($firstVersion !== null) {
            return $firstVersion->is_late
                ? ReportCollectionStatus::SubmittedLate
                : ReportCollectionStatus::Submitted;
        }

        if ($this->starts_on->isAfter(today(config('magang.timezone')))) {
            return ReportCollectionStatus::NotOpened;
        }

        return $this->due_at->isBefore(now(config('magang.timezone')))
            ? ReportCollectionStatus::NotSubmittedLate
            : ReportCollectionStatus::NotSubmitted;
    }
}
