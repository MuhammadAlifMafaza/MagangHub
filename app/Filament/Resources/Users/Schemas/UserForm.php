<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    /**
     * @return array<int, mixed>
     */
    public static function editActionSchema(User $record): array
    {
        $schema = [
            TextInput::make('name')->label('Nama lengkap')->required()->maxLength(255),
            TextInput::make('email')->label('Alamat email')->email()->required()->maxLength(255)
                ->rules([Rule::unique('users', 'email')->ignore($record->id)]),
        ];

        if ($record->role === UserRole::Student) {
            $schema = [
                ...$schema,
                TextInput::make('nim')->label('NIM')->required()->maxLength(20)
                    ->rules([Rule::unique('students', 'nim')->ignore($record->student?->id)]),
                TextInput::make('study_program')->label('Program studi')->required()->maxLength(100),
                TextInput::make('cohort_year')->label('Angkatan')->numeric()->minValue(1900)->maxValue(2100),
                TextInput::make('phone')->label('Nomor telepon')->tel()->maxLength(30),
            ];
        } elseif ($record->role === UserRole::Lecturer) {
            $schema = [
                ...$schema,
                TextInput::make('nidn')->label('NIDN')->required()->maxLength(20)
                    ->rules([Rule::unique('lecturers', 'nidn')->ignore($record->lecturer?->id)]),
                TextInput::make('department')->label('Departemen')->maxLength(100),
                TextInput::make('phone')->label('Nomor telepon')->tel()->maxLength(30),
            ];
        }

        return $schema;
    }

    /**
     * @return array<string, mixed>
     */
    public static function editActionData(User $record): array
    {
        return [
            ...$record->only(['name', 'email']),
            ...($record->role === UserRole::Student ? $record->student?->only(['nim', 'study_program', 'cohort_year', 'phone']) ?? [] : []),
            ...($record->role === UserRole::Lecturer ? $record->lecturer?->only(['nidn', 'department', 'phone']) ?? [] : []),
        ];
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Nama lengkap')->required()->maxLength(255),
                TextInput::make('email')->label('Alamat email')->email()->required()->unique('users', 'email')->maxLength(255),
                TextInput::make('password')->label('Kata sandi sementara')->password()->required()->rule(Password::default()),
                Select::make('role')
                    ->label('Peran')
                    ->options([
                        UserRole::Admin->value => 'Admin',
                        UserRole::Lecturer->value => 'Dosen',
                        UserRole::Student->value => 'Mahasiswa',
                    ])
                    ->live()
                    ->required(),
                TextInput::make('nim')->label('NIM')->required()->maxLength(20)->unique('students', 'nim')->visible(fn (Get $get): bool => $get('role') === UserRole::Student->value),
                TextInput::make('study_program')->label('Program studi')->required()->maxLength(100)->visible(fn (Get $get): bool => $get('role') === UserRole::Student->value),
                TextInput::make('cohort_year')->label('Angkatan')->numeric()->minValue(1900)->maxValue(2100)->visible(fn (Get $get): bool => $get('role') === UserRole::Student->value),
                TextInput::make('nidn')->label('NIDN')->required()->maxLength(20)->unique('lecturers', 'nidn')->visible(fn (Get $get): bool => $get('role') === UserRole::Lecturer->value),
                TextInput::make('department')->label('Departemen')->maxLength(100)->visible(fn (Get $get): bool => $get('role') === UserRole::Lecturer->value),
                TextInput::make('phone')->label('Nomor telepon')->tel()->maxLength(30)
                    ->visible(fn (Get $get): bool => in_array($get('role'), [UserRole::Student->value, UserRole::Lecturer->value], true)),
            ]);
    }
}
