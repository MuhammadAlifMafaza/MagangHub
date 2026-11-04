<?php

namespace App\Models;

use App\Enums\ReportCollectionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['internship_id', 'week_number', 'starts_on', 'ends_on', 'due_at'])]
class ReportPeriod extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'due_at' => 'datetime',
        ];
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

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
