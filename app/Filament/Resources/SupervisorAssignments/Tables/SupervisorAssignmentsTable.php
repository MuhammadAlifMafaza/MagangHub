<?php

namespace App\Filament\Resources\SupervisorAssignments\Tables;

use App\Actions\EndSupervisorAssignment;
use App\Enums\SupervisorAssignmentStatus;
use App\Models\SupervisorAssignment;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SupervisorAssignmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.user.name')->label('Mahasiswa')->searchable()->sortable(),
                TextColumn::make('student.nim')->label('NIM')->searchable(),
                TextColumn::make('lecturer.user.name')->label('Dosen Pembimbing')->searchable()->sortable(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('assigned_at')->label('Ditugaskan pada')->dateTime('d M Y, H:i')->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('endAssignment')
                    ->label('Akhiri Penugasan')
                    ->color('danger')
                    ->visible(fn (SupervisorAssignment $record): bool => $record->status === SupervisorAssignmentStatus::Active)
                    ->schema([
                        Textarea::make('end_reason')
                            ->label('Alasan mengakhiri penugasan')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->requiresConfirmation()
                    ->action(fn (SupervisorAssignment $record, array $data) => app(EndSupervisorAssignment::class)
                        ->handle($record, auth()->user(), $data['end_reason'])),
            ])
            ->toolbarActions([]);
    }
}
