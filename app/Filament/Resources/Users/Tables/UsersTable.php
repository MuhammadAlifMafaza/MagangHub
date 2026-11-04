<?php

namespace App\Filament\Resources\Users\Tables;

use App\Actions\ResetAccountPassword;
use App\Actions\UpdateAccount;
use App\Enums\UserRole;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Password;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('email')->label('Email')->searchable()->sortable(),
                TextColumn::make('role')->label('Peran')->formatStateUsing(fn (UserRole $state): string => match ($state) {
                    UserRole::Admin => 'Admin',
                    UserRole::Lecturer => 'Dosen',
                    UserRole::Student => 'Mahasiswa',
                })->badge(),
                TextColumn::make('is_active')->label('Status')->formatStateUsing(fn (bool $state): string => $state ? 'Aktif' : 'Nonaktif')->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                TextColumn::make('must_change_password')->label('Password sementara')->formatStateUsing(fn (bool $state): string => $state ? 'Wajib diganti' : 'Sudah diganti')->badge()
                    ->color(fn (bool $state): string => $state ? 'info' : 'success'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('editProfile')
                    ->label('Ubah data')
                    ->schema(fn (User $record): array => UserForm::editActionSchema($record))
                    ->fillForm(fn (User $record): array => UserForm::editActionData($record))
                    ->action(fn (User $record, array $data): User => app(UpdateAccount::class)
                        ->handle(auth()->user(), $record, $data)),
                Action::make('toggleActive')
                    ->label(fn (User $record): string => $record->is_active ? 'Nonaktifkan akun' : 'Aktifkan akun')
                    ->color(fn (User $record): string => $record->is_active ? 'danger' : 'success')
                    ->visible(fn (User $record): bool => $record->id !== auth()->id())
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        Gate::authorize('toggleAccount', $record);
                        $record->forceFill(['is_active' => ! $record->is_active])->save();
                    }),
                Action::make('resetPassword')
                    ->label('Atur ulang kata sandi')
                    ->schema([
                        TextInput::make('password')
                            ->label('Kata sandi sementara')
                            ->password()
                            ->required()
                            ->rule(Password::default()),
                    ])
                    ->action(fn (User $record, array $data) => app(ResetAccountPassword::class)
                        ->handle(auth()->user(), $record, $data['password'])),
            ])
            ->toolbarActions([]);
    }
}
