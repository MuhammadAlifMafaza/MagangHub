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
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class GuidanceSlotResource extends Resource
{
    protected static ?string $model = GuidanceSlot::class;

    protected static ?string $navigationLabel = 'Slot Pembimbingan';

    protected static ?string $modelLabel = 'slot pembimbingan';

    protected static ?string $pluralModelLabel = 'slot pembimbingan';

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
                Action::make('editSlot')
                    ->label('Ubah')
                    ->icon(Heroicon::PencilSquare)
                    ->visible(fn (GuidanceSlot $record): bool => $record->status === GuidanceSlotStatus::Open)
                    ->fillForm(fn (GuidanceSlot $record): array => [
                        'starts_at' => $record->starts_at,
                        'ends_at' => $record->ends_at,
                        'capacity' => $record->capacity,
                        'location' => $record->location,
                    ])
                    ->schema(self::slotFormSchema())
                    ->action(function (GuidanceSlot $record, array $data, UpdateGuidanceSlot $action): void {
                        Gate::authorize('update', $record);
                        $action->handle($record, auth()->user()->lecturer, $data);
                    }),
                Action::make('cancelSlot')
                    ->label('Batalkan')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (GuidanceSlot $record): bool => $record->status === GuidanceSlotStatus::Open)
                    ->schema([Textarea::make('reason')->label('Alasan pembatalan')->required()->maxLength(255)])
                    ->action(function (GuidanceSlot $record, array $data, CancelGuidanceSlot $action): void {
                        Gate::authorize('update', $record);
                        $action->handle($record, auth()->user()->lecturer, $data['reason']);
                    }),
            ])
            ->headerActions([
                Action::make('createSlot')
                    ->label('Buat slot')
                    ->icon(Heroicon::Plus)
                    ->schema(self::slotFormSchema())
                    ->action(function (array $data, CreateGuidanceSlot $action): void {
                        Gate::authorize('create', GuidanceSlot::class);
                        $action->handle(auth()->user()->lecturer, $data);
                    }),
            ])
            ->toolbarActions([]);
    }

    /** @return array<int, DateTimePicker|TextInput> */
    private static function slotFormSchema(): array
    {
        return [
            DateTimePicker::make('starts_at')->label('Waktu mulai')->timezone(config('magang.timezone'))->seconds(false)->required(),
            DateTimePicker::make('ends_at')->label('Waktu selesai')->timezone(config('magang.timezone'))->seconds(false)->required(),
            TextInput::make('capacity')->label('Kapasitas')->numeric()->minValue(1)->maxValue(255)
                ->default(config('magang.guidance.default_capacity'))->required(),
            TextInput::make('location')->label('Lokasi atau tautan')->maxLength(255),
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
