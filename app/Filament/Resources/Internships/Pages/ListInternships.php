<?php

namespace App\Filament\Resources\Internships\Pages;

use App\Filament\Resources\Internships\InternshipResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInternships extends ListRecords
{
    protected static string $resource = InternshipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
