<?php

namespace App\Filament\Resources\Internships;

use App\Filament\Resources\Internships\Pages\CreateInternship;
use App\Filament\Resources\Internships\Pages\ListInternships;
use App\Filament\Resources\Internships\Schemas\InternshipForm;
use App\Filament\Resources\Internships\Tables\InternshipsTable;
use App\Models\Internship;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InternshipResource extends Resource
{
    protected static ?string $model = Internship::class;

    protected static ?string $navigationLabel = 'Data Magang';

    protected static ?string $modelLabel = 'magang';

    protected static ?string $pluralModelLabel = 'data magang';

    protected static string|\UnitEnum|null $navigationGroup = 'Data Akademik';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return InternshipForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InternshipsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['student.user']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInternships::route('/'),
            'create' => CreateInternship::route('/create'),
        ];
    }
}
