<?php

namespace App\Models;

use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $user_id
 * @property-read User $user
 * @property-read HasOne<SupervisorAssignment, $this> $activeSupervisorAssignment
 */
#[Fillable(['user_id', 'nim', 'study_program', 'cohort_year', 'phone'])]
class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Internship, $this> */
    public function internships(): HasMany
    {
        return $this->hasMany(Internship::class);
    }

    /** @return HasMany<SupervisorAssignment, $this> */
    public function supervisorAssignments(): HasMany
    {
        return $this->hasMany(SupervisorAssignment::class);
    }

    /** @return HasMany<GuidanceBooking, $this> */
    public function guidanceBookings(): HasMany
    {
        return $this->hasMany(GuidanceBooking::class);
    }

    /** @return HasOne<SupervisorAssignment, $this> */
    public function activeSupervisorAssignment(): HasOne
    {
        return $this->hasOne(SupervisorAssignment::class)->where('status', 'active');
    }
}
