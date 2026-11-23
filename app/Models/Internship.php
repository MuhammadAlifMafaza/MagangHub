<?php

namespace App\Models;

use App\Enums\InternshipStatus;
use Database\Factories\InternshipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $student_id
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property InternshipStatus $status
 * @property-read Student $student
 */
#[Fillable(['student_id', 'company_name', 'company_supervisor_name', 'start_date', 'end_date', 'status'])]
class Internship extends Model
{
    /** @use HasFactory<InternshipFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => InternshipStatus::class,
        ];
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return HasMany<ReportPeriod, $this> */
    public function reportPeriods(): HasMany
    {
        return $this->hasMany(ReportPeriod::class);
    }
}
