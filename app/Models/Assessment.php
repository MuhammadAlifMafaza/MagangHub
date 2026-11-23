<?php

namespace App\Models;

use App\Enums\AssessmentStatus;
use Database\Factories\AssessmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $weekly_report_id
 * @property int $lecturer_id
 * @property AssessmentStatus $status
 * @property-read WeeklyReport $weeklyReport
 */
#[Fillable(['weekly_report_id', 'lecturer_id', 'score', 'notes', 'status', 'published_at'])]
class Assessment extends Model
{
    /** @use HasFactory<AssessmentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['score' => 'decimal:2', 'status' => AssessmentStatus::class, 'published_at' => 'datetime'];
    }

    /** @return BelongsTo<WeeklyReport, $this> */
    public function weeklyReport(): BelongsTo
    {
        return $this->belongsTo(WeeklyReport::class);
    }

    /** @return BelongsTo<Lecturer, $this> */
    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class);
    }

    /** @param Builder<Assessment> $query
     * @return Builder<Assessment>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', AssessmentStatus::Published);
    }
}
