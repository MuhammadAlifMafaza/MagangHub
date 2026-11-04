<?php

namespace App\Filament\Resources\SupervisorAssignments\Pages;

use App\Filament\Resources\SupervisorAssignments\SupervisorAssignmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSupervisorAssignment extends EditRecord
{
    protected static string $resource = SupervisorAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
