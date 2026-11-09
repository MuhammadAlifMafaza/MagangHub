<?php

namespace App\Filament\Dosen\Resources\GuidanceSlots;

use App\Actions\CancelGuidanceSlot;
use App\Actions\CreateGuidanceSlot;
use App\Actions\UpdateGuidanceSlot;
use App\Enums\GuidanceBookingStatus;
use App\Enums\GuidanceSlotStatus;
use App\Filament\Dosen\Resources\GuidanceSlots\Pages\ManageGuidanceSlots;
use App\Models\GuidanceSlot;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class GuidanceSlotResource extends Resource
{
    protected static ?string $model = GuidanceSlot::class;

    protected static ?string $navigationLabel = 'Slot Bimbingan Dosen';

    protected static ?string $modelLabel = 'slot bimbingan dosen';

    protected static ?string $pluralModelLabel = 'slot bimbingan dosen';

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
                TextColumn::make('starts_at')->label('Mulai')->dateTime('d M Y, H:i')->sortable(),
                TextColumn::make('ends_at')->label('Selesai')->dateTime('d M Y, H:i')->sortable(),
                TextColumn::make('location')->label('Lokasi')->placeholder('Belum ditentukan')->limit(32),
                TextColumn::make('booked_count')->label('Terisi')
                    ->formatStateUsing(fn (int $state, GuidanceSlot $record): string => "{$state}/{$record->capacity}"),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (GuidanceSlot $record): string => $record->availabilityLabel())
                    ->color(fn (GuidanceSlot $record): string => $record->availabilityColor()),
            ])
            ->recordActions([
                Action::make('viewSlot')
                    ->label('Detail')
                    ->icon(Heroicon::OutlinedEye)
                    ->modalHeading('Detail slot bimbingan dosen')
                    ->modalWidth('2xl')
                    ->authorize('view')
                    ->schema([
                        TextEntry::make('starts_at')->label('Waktu mulai')->dateTime('d M Y, H:i'),
                        TextEntry::make('ends_at')->label('Waktu selesai')->dateTime('d M Y, H:i'),
                        TextEntry::make('capacity')->label('Kapasitas'),
                        TextEntry::make('booked_count')->label('Pemesanan aktif')
                            ->formatStateUsing(fn (int $state, GuidanceSlot $record): string => "{$state} dari {$record->capacity}"),
                        TextEntry::make('location')->label('Lokasi atau tautan')->placeholder('Belum ditentukan'),
                        TextEntry::make('status')->label('Ketersediaan')
                            ->formatStateUsing(fn (GuidanceSlot $record): string => $record->availabilityLabel()),
                        TextEntry::make('cancel_reason')->label('Alasan pembatalan')->placeholder('—'),
                    ])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
                Action::make('editSlot')
                    ->label('Ubah')
                    ->icon(Heroicon::PencilSquare)
                    ->modalHeading('Ubah slot bimbingan dosen')
                    ->modalSubmitActionLabel('Simpan perubahan')
                    ->modalWidth('2xl')
                    ->visible(fn (GuidanceSlot $record): bool => $record->status === GuidanceSlotStatus::Open)
                    ->fillForm(fn (GuidanceSlot $record): array => [
                        'starts_at' => $record->starts_at,
                        'ends_at' => $record->ends_at,
                        'capacity' => $record->capacity,
                        'location' => $record->location,
                    ])
                    ->schema(self::slotFormSchema())
                    ->action(function (GuidanceSlot $record, array $data, UpdateGuidanceSlot $updateGuidanceSlot): void {
                        Gate::authorize('update', $record);
                        $updateGuidanceSlot->handle($record, auth()->user()->lecturer, $data);
                    }),
                Action::make('cancelSlot')
                    ->label('Batalkan')
                    ->color('danger')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->requiresConfirmation()
                    ->visible(fn (GuidanceSlot $record): bool => $record->status === GuidanceSlotStatus::Open)
                    ->schema([Textarea::make('reason')->label('Alasan pembatalan')->required()->maxLength(255)])
                    ->action(function (GuidanceSlot $record, array $data, CancelGuidanceSlot $cancelGuidanceSlot): void {
                        Gate::authorize('update', $record);
                        $cancelGuidanceSlot->handle($record, auth()->user()->lecturer, $data['reason']);
                    }),
            ])
            ->headerActions([
                Action::make('createSlot')
                    ->label('Buat slot')
                    ->icon(Heroicon::Plus)
                    ->modalHeading('Buat slot bimbingan dosen')
                    ->modalSubmitActionLabel('Simpan slot')
                    ->modalWidth('2xl')
                    ->schema(self::slotFormSchema())
                    ->action(function (array $data, CreateGuidanceSlot $createGuidanceSlot): void {
                        Gate::authorize('create', GuidanceSlot::class);
                        $createGuidanceSlot->handle(auth()->user()->lecturer, $data);
                    }),
            ])
            ->toolbarActions([]);
    }

    /** @return array<int, Section> */
    private static function slotFormSchema(): array
    {
        return [
            Section::make('Jadwal')
                ->columns(2)
                ->schema([
                    DateTimePicker::make('starts_at')->label('Waktu mulai')->timezone(config('magang.timezone'))->seconds(false)->required(),
                    DateTimePicker::make('ends_at')->label('Waktu selesai')->timezone(config('magang.timezone'))->seconds(false)->required(),
                ]),
            Section::make('Kapasitas dan lokasi')
                ->columns(2)
                ->schema([
                    TextInput::make('capacity')->label('Kapasitas')->numeric()->minValue(1)->maxValue(255)
                        ->default(config('magang.guidance.default_capacity'))->required()
                        ->helperText('Jumlah maksimum mahasiswa yang dapat memesan slot ini.'),
                    TextInput::make('location')->label('Lokasi atau tautan')->maxLength(255)
                        ->helperText('Contoh: nama ruangan atau tautan pertemuan daring.'),
                ]),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $lecturer = auth()->user()?->lecturer;
        abort_unless($lecturer, 403);

        return parent::getEloquentQuery()
            ->where('lecturer_id', $lecturer->id)
            ->withCount(['bookings as booked_count' => fn (Builder $query) => $query->where('status', GuidanceBookingStatus::Booked)]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageGuidanceSlots::route('/')];
    }
}
