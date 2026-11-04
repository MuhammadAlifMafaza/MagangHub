<?php

namespace App\Filament\Resources\ReportPeriods;

use App\Filament\Resources\ReportPeriods\Pages\EditReportPeriod;
use App\Filament\Resources\ReportPeriods\Pages\ListReportPeriods;
use App\Filament\Resources\ReportPeriods\Schemas\ReportPeriodForm;
use App\Filament\Resources\ReportPeriods\Tables\ReportPeriodsTable;
use App\Models\ReportPeriod;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReportPeriodResource extends Resource
{
    protected static ?string $model = ReportPeriod::class;

    protected static ?string $navigationLabel = 'Periode Laporan';

    protected static ?string $modelLabel = 'periode laporan';

    protected static ?string $pluralModelLabel = 'periode laporan';

    protected static string|\UnitEnum|null $navigationGroup = 'Data Akademik';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ReportPeriodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReportPeriodsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['internship.student.user']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReportPeriods::route('/'),
            'edit' => EditReportPeriod::route('/{record}/edit'),
        ];
    }
}
