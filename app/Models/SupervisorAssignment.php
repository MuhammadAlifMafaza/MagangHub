<?php

namespace App\Models;

use App\Enums\SupervisorAssignmentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['student_id', 'lecturer_id', 'status', 'assigned_by', 'assigned_at', 'ended_at', 'end_reason'])]
class SupervisorAssignment extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => SupervisorAssignmentStatus::class,
            'assigned_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
