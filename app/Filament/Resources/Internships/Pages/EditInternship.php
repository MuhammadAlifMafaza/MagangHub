<?php

namespace App\Filament\Resources\Internships\Pages;

use App\Filament\Resources\Internships\InternshipResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditInternship extends EditRecord
{
    protected static string $resource = InternshipResource::class;

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()->icon(Heroicon::OutlinedCheck);
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->icon(Heroicon::OutlinedXMark);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->icon(Heroicon::OutlinedTrash),
        ];
    }
}
