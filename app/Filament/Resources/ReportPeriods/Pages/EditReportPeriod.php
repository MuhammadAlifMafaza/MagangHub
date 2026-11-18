<?php

namespace App\Filament\Resources\ReportPeriods\Pages;

use App\Filament\Resources\ReportPeriods\ReportPeriodResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditReportPeriod extends EditRecord
{
    protected static string $resource = ReportPeriodResource::class;

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
