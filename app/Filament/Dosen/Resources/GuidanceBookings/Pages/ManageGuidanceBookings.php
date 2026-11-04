<?php

namespace App\Filament\Dosen\Resources\GuidanceBookings\Pages;

use App\Filament\Dosen\Resources\GuidanceBookings\GuidanceBookingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageGuidanceBookings extends ManageRecords
{
    protected static string $resource = GuidanceBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
