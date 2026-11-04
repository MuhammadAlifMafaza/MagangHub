<?php

namespace App\Filament\Resources\GuidanceBookings\Pages;

use App\Filament\Resources\GuidanceBookings\GuidanceBookingResource;
use Filament\Resources\Pages\ManageRecords;

class ManageGuidanceBookings extends ManageRecords
{
    protected static string $resource = GuidanceBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
