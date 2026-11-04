<?php

namespace App\Filament\Resources\SupervisorAssignments\Pages;

use App\Filament\Resources\SupervisorAssignments\SupervisorAssignmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupervisorAssignments extends ListRecords
{
    protected static string $resource = SupervisorAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
