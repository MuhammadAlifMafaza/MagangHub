<?php

namespace App\Filament\Resources\SupervisorAssignments\Pages;

use App\Actions\AssignSupervisor;
use App\Filament\Resources\SupervisorAssignments\SupervisorAssignmentResource;
use App\Models\Lecturer;
use App\Models\Student;
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
        return app(AssignSupervisor::class)->handle(
            Student::query()->findOrFail($data['student_id']),
            Lecturer::query()->findOrFail($data['lecturer_id']),
            auth()->user(),
        );
    }
}
