<?php

namespace App\Filament\Resources\ReportPeriods\Tables;

use App\Models\ReportPeriod;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReportPeriodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('internship.student.user.name')->label('Mahasiswa')->searchable()->sortable(),
                TextColumn::make('internship.student.nim')->label('NIM')->searchable(),
                TextColumn::make('week_number')->label('Minggu ke-')->sortable(),
                TextColumn::make('starts_on')->label('Mulai')->date('d M Y')->sortable(),
                TextColumn::make('ends_on')->label('Selesai')->date('d M Y')->sortable(),
                TextColumn::make('due_at')->label('Batas pengumpulan')->dateTime('d M Y, H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('week_number')->label('Minggu')->options(fn (): array => ReportPeriod::query()
                    ->select('week_number')
                    ->distinct()
                    ->orderBy('week_number')
                    ->pluck('week_number', 'week_number')
                    ->all())
                    ->searchable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
