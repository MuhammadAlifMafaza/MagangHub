<?php

namespace App\Models;

use App\Enums\SupervisorAssignmentStatus;
use Carbon\CarbonInterface;
use Database\Factories\SupervisorAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $student_id
 * @property int $lecturer_id
 * @property SupervisorAssignmentStatus $status
 */
#[Fillable(['student_id', 'lecturer_id', 'status', 'assigned_by', 'assigned_at', 'ended_at', 'end_reason'])]
class SupervisorAssignment extends Model
{
    /** @use HasFactory<SupervisorAssignmentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => SupervisorAssignmentStatus::class,
            'assigned_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /** @param Builder<SupervisorAssignment> $query
     * @return Builder<SupervisorAssignment>
     */
    public function scopeCoveringPeriodStart(Builder $query): Builder
    {
        $query
            ->where(fn (Builder $assignment): Builder => $assignment
                ->where('status', SupervisorAssignmentStatus::Active)
                ->orWhere(fn (Builder $endedAssignment): Builder => $endedAssignment
                    ->where('status', SupervisorAssignmentStatus::Ended)
                    ->whereNotNull('ended_at')));

        if (config('database.default') === 'sqlite') {
            return $query
                ->whereRaw('supervisor_assignments.assigned_at <= datetime(report_periods.starts_on)')
                ->where(fn (Builder $assignment): Builder => $assignment
                    ->whereNull('supervisor_assignments.ended_at')
                    ->orWhereRaw('supervisor_assignments.ended_at >= datetime(report_periods.starts_on)'));
        }

        if (config('database.default') === 'pgsql') {
            return $query
                ->whereRaw('supervisor_assignments.assigned_at <= CAST(report_periods.starts_on AS TIMESTAMP)')
                ->where(fn (Builder $assignment): Builder => $assignment
                    ->whereNull('supervisor_assignments.ended_at')
                    ->orWhereRaw('supervisor_assignments.ended_at >= CAST(report_periods.starts_on AS TIMESTAMP)'));
        }

        return $query
            ->whereRaw('supervisor_assignments.assigned_at <= CAST(report_periods.starts_on AS DATETIME)')
            ->where(fn (Builder $assignment): Builder => $assignment
                ->whereNull('supervisor_assignments.ended_at')
                ->orWhereRaw('supervisor_assignments.ended_at >= CAST(report_periods.starts_on AS DATETIME)'));
    }

    /** @param Builder<SupervisorAssignment> $query
     * @return Builder<SupervisorAssignment>
     */
    public function scopeCoveringInstant(Builder $query, CarbonInterface $instant): Builder
    {
        return $query
            ->where(fn (Builder $assignment): Builder => $assignment
                ->where('status', SupervisorAssignmentStatus::Active)
                ->orWhere(fn (Builder $endedAssignment): Builder => $endedAssignment
                    ->where('status', SupervisorAssignmentStatus::Ended)
                    ->whereNotNull('ended_at')))
            ->where('assigned_at', '<=', $instant)
            ->where(fn (Builder $assignment): Builder => $assignment
                ->whereNull('ended_at')
                ->orWhere('ended_at', '>=', $instant));
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<Lecturer, $this> */
    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
