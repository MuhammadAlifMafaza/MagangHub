<?php

namespace App\Filament\Resources\SupervisorAssignments\Pages;

use App\Actions\AssignSupervisor;
use App\Filament\Resources\SupervisorAssignments\SupervisorAssignmentResource;
use App\Models\Lecturer;
use App\Models\Student;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSupervisorAssignment extends CreateRecord
{
    protected static string $resource = SupervisorAssignmentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(AssignSupervisor::class)->handle(
            Student::query()->findOrFail($data['student_id']),
            Lecturer::query()->findOrFail($data['lecturer_id']),
            auth()->user(),
        );
    }
}
