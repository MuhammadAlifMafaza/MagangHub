<?php

namespace App\Filament\Resources\Internships\Pages;

use App\Filament\Resources\Internships\InternshipResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListInternships extends ListRecords
{
    protected static string $resource = InternshipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon(Heroicon::OutlinedPlus),
        ];
    }
}
