<?php

namespace App\Filament\Dosen\Resources\GuidanceBookings;

use App\Actions\CancelGuidanceBookingByStaff;
use App\Actions\RecordGuidanceBookingOutcome;
use App\Enums\GuidanceBookingStatus;
use App\Filament\Dosen\Resources\GuidanceBookings\Pages\ManageGuidanceBookings;
use App\Models\GuidanceBooking;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class GuidanceBookingResource extends Resource
{
    protected static ?string $model = GuidanceBooking::class;

    protected static ?string $navigationLabel = 'Pemesanan Bimbingan';

    protected static ?string $modelLabel = 'pemesanan bimbingan';

    protected static ?string $pluralModelLabel = 'pemesanan bimbingan';

    protected static string|\UnitEnum|null $navigationGroup = 'Pembimbingan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

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
            ->recordActions([
                Action::make('completed')
                    ->label('Tandai selesai')
                    ->color('success')
                    ->icon(Heroicon::CheckCircle)
                    ->visible(fn (GuidanceBooking $record): bool => $record->status === GuidanceBookingStatus::Booked && $record->slot->starts_at->isPast())
                    ->schema([Textarea::make('notes')->label('Catatan bimbingan')->maxLength(5000)])
                    ->action(function (GuidanceBooking $record, array $data, RecordGuidanceBookingOutcome $action): void {
                        Gate::authorize('recordOutcome', $record);
                        $action->handle($record, auth()->user()->lecturer, GuidanceBookingStatus::Completed, $data['notes'] ?? null);
                    }),
                Action::make('noShow')
                    ->label('Tandai tidak hadir')
                    ->color('warning')
                    ->visible(fn (GuidanceBooking $record): bool => $record->status === GuidanceBookingStatus::Booked && $record->slot->starts_at->isPast())
                    ->schema([Textarea::make('notes')->label('Catatan bimbingan')->maxLength(5000)])
                    ->action(function (GuidanceBooking $record, array $data, RecordGuidanceBookingOutcome $action): void {
                        Gate::authorize('recordOutcome', $record);
                        $action->handle($record, auth()->user()->lecturer, GuidanceBookingStatus::NoShow, $data['notes'] ?? null);
                    }),
                Action::make('cancelBooking')
                    ->label('Batalkan')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (GuidanceBooking $record): bool => $record->status === GuidanceBookingStatus::Booked)
                    ->schema([Textarea::make('reason')->label('Alasan pembatalan')->required()->maxLength(255)])
                    ->action(function (GuidanceBooking $record, array $data, CancelGuidanceBookingByStaff $action): void {
                        Gate::authorize('cancelByLecturer', $record);
                        $action->handle($record, auth()->user(), $data['reason']);
                    }),
            ])
            ->toolbarActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        $lecturer = auth()->user()?->lecturer;
        abort_unless($lecturer, 403);

        return parent::getEloquentQuery()
            ->whereHas('slot', fn (Builder $query) => $query->where('lecturer_id', $lecturer->id))
            ->with(['slot', 'student.user']);
    }

    public static function getPages(): array
    {
        return ['index' => ManageGuidanceBookings::route('/')];
    }
}
