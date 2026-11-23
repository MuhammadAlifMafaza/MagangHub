<?php

namespace App\Filament\Dosen\Resources\GuidanceBookings;

use App\Actions\CancelGuidanceBookingByStaff;
use App\Actions\RecordGuidanceBookingOutcome;
use App\Enums\GuidanceBookingStatus;
use App\Filament\Dosen\Resources\GuidanceBookings\Pages\ManageGuidanceBookings;
use App\Models\GuidanceBooking;
use App\Models\Lecturer;
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

    protected static ?string $modelLabel = 'pemesanan mahasiswa';

    protected static ?string $pluralModelLabel = 'pemesanan mahasiswa';

    protected static string|\UnitEnum|null $navigationGroup = 'Pembimbingan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('slot.starts_at')->label('Jadwal')->dateTime('d M Y, H:i')->sortable(),
                TextColumn::make('student.user.name')->label('Mahasiswa')->searchable()->sortable(),
                TextColumn::make('student.nim')->label('NIM')->searchable(),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (GuidanceBookingStatus $state): string => $state->label())
                    ->color(fn (GuidanceBookingStatus $state): string => $state->color()),
                TextColumn::make('lecturer_notes')->label('Catatan')->limit(50)->placeholder('—'),
                TextColumn::make('cancel_reason')->label('Alasan pembatalan')->limit(40)->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status pemesanan')
                    ->options([
                        GuidanceBookingStatus::Booked->value => 'Dipesan',
                        GuidanceBookingStatus::Cancelled->value => 'Dibatalkan',
                        GuidanceBookingStatus::Completed->value => 'Selesai',
                        GuidanceBookingStatus::NoShow->value => 'Tidak hadir',
                    ]),
            ])
            ->recordActions([
                Action::make('completed')
                    ->label('Tandai selesai')
                    ->color('success')
                    ->icon(Heroicon::CheckCircle)
                    ->modalHeading('Selesaikan pemesanan bimbingan')
                    ->modalSubmitActionLabel('Tandai selesai')
                    ->visible(fn (GuidanceBooking $record): bool => $record->status === GuidanceBookingStatus::Booked && $record->slot->starts_at->isPast())
                    ->schema([Textarea::make('notes')->label('Catatan bimbingan')->maxLength(5000)])
                    ->action(function (GuidanceBooking $record, array $data, RecordGuidanceBookingOutcome $recordGuidanceBookingOutcome): void {
                        Gate::authorize('recordOutcome', $record);
                        $recordGuidanceBookingOutcome->handle($record, self::authenticatedLecturer(), GuidanceBookingStatus::Completed, $data['notes'] ?? null);
                    }),
                Action::make('noShow')
                    ->label('Tandai tidak hadir')
                    ->color('warning')
                    ->icon(Heroicon::OutlinedUserMinus)
                    ->modalHeading('Catat mahasiswa tidak hadir')
                    ->modalSubmitActionLabel('Simpan catatan')
                    ->visible(fn (GuidanceBooking $record): bool => $record->status === GuidanceBookingStatus::Booked && $record->slot->starts_at->isPast())
                    ->schema([Textarea::make('notes')->label('Catatan bimbingan')->maxLength(5000)])
                    ->action(function (GuidanceBooking $record, array $data, RecordGuidanceBookingOutcome $recordGuidanceBookingOutcome): void {
                        Gate::authorize('recordOutcome', $record);
                        $recordGuidanceBookingOutcome->handle($record, self::authenticatedLecturer(), GuidanceBookingStatus::NoShow, $data['notes'] ?? null);
                    }),
                Action::make('cancelBooking')
                    ->label('Batalkan')
                    ->color('danger')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->modalHeading('Batalkan pemesanan mahasiswa')
                    ->modalSubmitActionLabel('Batalkan pemesanan')
                    ->requiresConfirmation()
                    ->visible(fn (GuidanceBooking $record): bool => $record->status === GuidanceBookingStatus::Booked)
                    ->schema([Textarea::make('reason')->label('Alasan pembatalan')->required()->maxLength(255)])
                    ->action(function (GuidanceBooking $record, array $data, CancelGuidanceBookingByStaff $cancelGuidanceBooking): void {
                        Gate::authorize('cancelByLecturer', $record);
                        $cancelGuidanceBooking->handle($record, auth()->user(), $data['reason']);
                    }),
            ])
            ->emptyStateIcon(Heroicon::OutlinedCalendarDays)
            ->emptyStateHeading('Belum ada pemesanan mahasiswa')
            ->emptyStateDescription('Pemesanan slot bimbingan oleh mahasiswa akan muncul di sini.')
            ->toolbarActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        $lecturer = auth()->user()?->lecturer;
        abort_unless($lecturer !== null, 403);

        return parent::getEloquentQuery()
            ->whereHas('slot', fn (Builder $query) => $query->where('lecturer_id', $lecturer->id))
            ->with(['slot', 'student.user']);
    }

    public static function getPages(): array
    {
        return ['index' => ManageGuidanceBookings::route('/')];
    }

    private static function authenticatedLecturer(): Lecturer
    {
        $lecturer = auth()->user()?->lecturer;
        abort_unless($lecturer !== null, 403);

        return $lecturer;
    }
}
