<?php

namespace App\Models;

use Database\Factories\ReportVersionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $weekly_report_id
 * @property int $version_number
 * @property bool $is_late
 * @property int $size_bytes
 * @property-read WeeklyReport $weeklyReport
 * @property-read ReportReview|null $review
 */
#[Fillable(['weekly_report_id', 'version_number', 'original_name', 'stored_path', 'disk', 'extension', 'mime_type', 'size_bytes', 'checksum_sha256', 'student_note', 'submitted_at', 'is_late', 'uploaded_by'])]
class ReportVersion extends Model
{
    /** @use HasFactory<ReportVersionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime', 'is_late' => 'boolean', 'size_bytes' => 'integer'];
    }

    /** @return BelongsTo<WeeklyReport, $this> */
    public function weeklyReport(): BelongsTo
    {
        return $this->belongsTo(WeeklyReport::class);
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** @return HasOne<ReportReview, $this> */
    public function review(): HasOne
    {
        return $this->hasOne(ReportReview::class);
    }
}
