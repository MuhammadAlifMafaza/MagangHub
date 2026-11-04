<?php

namespace App\Filament\Resources\ReportPeriods\Pages;

use App\Filament\Resources\ReportPeriods\ReportPeriodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReportPeriods extends ListRecords
{
    protected static string $resource = ReportPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
