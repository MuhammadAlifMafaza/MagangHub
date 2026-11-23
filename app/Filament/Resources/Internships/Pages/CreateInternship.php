<?php

namespace App\Filament\Resources\Internships\Pages;

use App\Actions\CreateInternship as CreateInternshipAction;
use App\Filament\Resources\Internships\InternshipResource;
use App\Models\Student;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class CreateInternship extends CreateRecord
{
    protected static string $resource = InternshipResource::class;

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
        abort_unless(is_int($studentId) || is_string($studentId), 422);
        $student = Student::query()->findOrFail($studentId);

        return app(CreateInternshipAction::class)->handle($student, [
            'company_name' => (string) $data['company_name'],
            'company_supervisor_name' => isset($data['company_supervisor_name']) ? (string) $data['company_supervisor_name'] : null,
            'start_date' => (string) $data['start_date'],
            'end_date' => (string) $data['end_date'],
        ]);
    }
}
