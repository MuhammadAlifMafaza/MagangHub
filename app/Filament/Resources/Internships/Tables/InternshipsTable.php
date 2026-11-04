<?php

namespace App\Filament\Resources\Internships\Tables;

use App\Actions\UpdateInternship;
use App\Enums\InternshipStatus;
use App\Models\Internship;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InternshipsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.user.name')->label('Mahasiswa')->searchable()->sortable(),
                TextColumn::make('student.nim')->label('NIM')->searchable(),
                TextColumn::make('company_name')->label('Instansi')->searchable()->sortable(),
                TextColumn::make('start_date')->label('Mulai')->date('d M Y')->sortable(),
                TextColumn::make('end_date')->label('Selesai')->date('d M Y')->sortable(),
                TextColumn::make('status')->label('Status')->badge(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Ubah data')
                    ->schema([
                        TextInput::make('company_name')->label('Nama instansi')->required()->maxLength(150),
                        TextInput::make('company_supervisor_name')->label('Nama pembimbing lapangan')->maxLength(100),
                        DatePicker::make('start_date')->label('Tanggal mulai')->native(true)->required(),
                        DatePicker::make('end_date')->label('Tanggal selesai')->native(true)->afterOrEqual('start_date')->required(),
                        Select::make('status')->label('Status')->options([
                            InternshipStatus::Active->value => 'Aktif',
                            InternshipStatus::Completed->value => 'Selesai',
                        ])->required(),
                    ])
                    ->action(fn (Internship $record, array $data): Internship => app(UpdateInternship::class)
                        ->handle(auth()->user(), $record, $data)),
            ])
            ->toolbarActions([]);
    }
}
