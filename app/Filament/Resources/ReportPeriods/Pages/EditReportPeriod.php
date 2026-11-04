<?php

namespace App\Filament\Resources\ReportPeriods\Pages;

use App\Filament\Resources\ReportPeriods\ReportPeriodResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReportPeriod extends EditRecord
{
    protected static string $resource = ReportPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
