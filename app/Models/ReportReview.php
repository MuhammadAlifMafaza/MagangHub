<?php

namespace App\Models;

use App\Enums\ReportReviewDecision;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['weekly_report_id', 'report_version_id', 'lecturer_id', 'decision', 'feedback', 'reviewed_at'])]
class ReportReview extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['decision' => ReportReviewDecision::class, 'reviewed_at' => 'datetime'];
    }

    public function weeklyReport(): BelongsTo
    {
        return $this->belongsTo(WeeklyReport::class);
    }

    public function reportVersion(): BelongsTo
    {
        return $this->belongsTo(ReportVersion::class);
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class);
    }
}
