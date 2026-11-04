<?php

namespace App\Filament\Resources\SupervisorAssignments;

use App\Filament\Resources\SupervisorAssignments\Pages\CreateSupervisorAssignment;
use App\Filament\Resources\SupervisorAssignments\Pages\ListSupervisorAssignments;
use App\Filament\Resources\SupervisorAssignments\Schemas\SupervisorAssignmentForm;
use App\Filament\Resources\SupervisorAssignments\Tables\SupervisorAssignmentsTable;
use App\Models\SupervisorAssignment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupervisorAssignmentResource extends Resource
{
    protected static ?string $model = SupervisorAssignment::class;

    protected static ?string $navigationLabel = 'Penugasan Pembimbing';

    protected static ?string $modelLabel = 'penugasan pembimbing';

    protected static ?string $pluralModelLabel = 'penugasan pembimbing';

    protected static string|\UnitEnum|null $navigationGroup = 'Data Akademik';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SupervisorAssignmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupervisorAssignmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['student.user', 'lecturer.user']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupervisorAssignments::route('/'),
            'create' => CreateSupervisorAssignment::route('/create'),
        ];
    }
}
