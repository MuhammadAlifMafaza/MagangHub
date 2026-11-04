<?php

namespace App\Models;

use App\Enums\AssessmentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['weekly_report_id', 'lecturer_id', 'score', 'notes', 'status', 'published_at'])]
class Assessment extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['score' => 'decimal:2', 'status' => AssessmentStatus::class, 'published_at' => 'datetime'];
    }

    public function weeklyReport(): BelongsTo
    {
        return $this->belongsTo(WeeklyReport::class);
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', AssessmentStatus::Published);
    }
}
