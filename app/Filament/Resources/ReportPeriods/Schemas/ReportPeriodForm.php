<?php

namespace App\Filament\Resources\ReportPeriods\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Schema;

class ReportPeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DateTimePicker::make('due_at')
                    ->label('Batas pengumpulan')
                    ->timezone(config('magang.timezone'))
                    ->seconds(true)
                    ->required(),
            ]);
    }
}
