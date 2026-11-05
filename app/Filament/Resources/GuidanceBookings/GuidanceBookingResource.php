<?php

namespace App\Filament\Resources\GuidanceBookings;

use App\Actions\CancelGuidanceBookingByStaff;
use App\Enums\GuidanceBookingStatus;
use App\Filament\Resources\GuidanceBookings\Pages\ManageGuidanceBookings;
use App\Models\GuidanceBooking;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class GuidanceBookingResource extends Resource
{
    protected static ?string $model = GuidanceBooking::class;

    protected static ?string $navigationLabel = 'Pemesanan Bimbingan';

    protected static ?string $modelLabel = 'pemesanan bimbingan';

    protected static ?string $pluralModelLabel = 'pemesanan bimbingan';

    protected static string|\UnitEnum|null $navigationGroup = 'Pemantauan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.user.name')->label('Mahasiswa')->searchable()->sortable(),
                TextColumn::make('student.nim')->label('NIM')->searchable(),
                TextColumn::make('slot.lecturer.user.name')->label('Dosen Pembimbing')->searchable()->sortable(),
                TextColumn::make('slot.starts_at')->label('Jadwal')->dateTime('d M Y, H:i')->sortable(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('cancel_reason')->label('Alasan pembatalan')->limit(40),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options([
                    GuidanceBookingStatus::Booked->value => 'Dipesan',
                    GuidanceBookingStatus::Cancelled->value => 'Dibatalkan',
                    GuidanceBookingStatus::Completed->value => 'Selesai',
                    GuidanceBookingStatus::NoShow->value => 'Tidak Hadir',
                ]),
            ])
            ->recordActions([
                Action::make('cancelBooking')
                    ->label('Batalkan pemesanan')
                    ->color('danger')
                    ->visible(fn (GuidanceBooking $record): bool => $record->status === GuidanceBookingStatus::Booked)
                    ->schema([
                        Textarea::make('reason')->label('Alasan pembatalan')->required()->maxLength(255),
                    ])
                    ->requiresConfirmation()
                    ->action(function (GuidanceBooking $record, array $data): void {
                        Gate::authorize('cancelByAdmin', $record);
                        app(CancelGuidanceBookingByStaff::class)->handle($record, auth()->user(), $data['reason']);
                    }),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['student.user', 'slot.lecturer.user']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageGuidanceBookings::route('/'),
        ];
    }
}
