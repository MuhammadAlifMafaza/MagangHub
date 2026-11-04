<?php

namespace App\Filament\Dosen\Resources\GuidanceSlots\Pages;

use App\Filament\Dosen\Resources\GuidanceSlots\GuidanceSlotResource;
use Filament\Resources\Pages\ManageRecords;

class ManageGuidanceSlots extends ManageRecords
{
    protected static string $resource = GuidanceSlotResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
