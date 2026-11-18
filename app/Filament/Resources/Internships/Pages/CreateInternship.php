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
        $student = Student::query()->findOrFail($data['student_id']);
        unset($data['student_id']);

        return app(CreateInternshipAction::class)->handle($student, $data);
    }
}
