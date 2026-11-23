<?php

namespace App\Filament\Resources\SupervisorAssignments\Pages;

use App\Actions\AssignSupervisor;
use App\Filament\Resources\SupervisorAssignments\SupervisorAssignmentResource;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class CreateSupervisorAssignment extends CreateRecord
{
    protected static string $resource = SupervisorAssignmentResource::class;

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->icon(Heroicon::OutlinedPlus);
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()->icon(Heroicon::OutlinedPlus);
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->icon(Heroicon::OutlinedXMark);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $studentId = $data['student_id'] ?? null;
        $lecturerId = $data['lecturer_id'] ?? null;
        $actor = auth()->user();
        abort_unless((is_int($studentId) || is_string($studentId)) && (is_int($lecturerId) || is_string($lecturerId)), 422);
        abort_unless($actor instanceof User, 401);

        return app(AssignSupervisor::class)->handle(
            Student::query()->findOrFail($studentId),
            Lecturer::query()->findOrFail($lecturerId),
            $actor,
        );
    }
}
