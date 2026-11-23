<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
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
        $accountFields = [
            TextInput::make('name')->label('Nama lengkap')->required()->maxLength(255),
            TextInput::make('email')->label('Alamat email')->email()->required()->maxLength(255)
                ->rules([Rule::unique('users', 'email')->ignore($record->id)]),
        ];

        $profileFields = [];

        if ($record->role === UserRole::Student) {
            $profileFields = [
                TextInput::make('nim')->label('NIM')->required()->maxLength(20)
                    ->rules([Rule::unique('students', 'nim')->ignore($record->student?->id)]),
                TextInput::make('study_program')->label('Program studi')->required()->maxLength(100),
                TextInput::make('cohort_year')->label('Angkatan')->numeric()->minValue(1900)->maxValue(2100),
                TextInput::make('phone')->label('Nomor telepon')->tel()->maxLength(30),
            ];
        } elseif ($record->role === UserRole::Lecturer) {
            $profileFields = [
                TextInput::make('nidn')->label('NIDN')->required()->maxLength(20)
                    ->rules([Rule::unique('lecturers', 'nidn')->ignore($record->lecturer?->id)]),
                TextInput::make('department')->label('Departemen')->maxLength(100),
                TextInput::make('phone')->label('Nomor telepon')->tel()->maxLength(30),
            ];
        }

        return [
            Section::make('Informasi akun')
                ->description('Nama dan alamat email untuk masuk ke MagangHub.')
                ->columns(2)
                ->schema($accountFields),
            ...($profileFields === [] ? [] : [
                Section::make($record->role === UserRole::Student ? 'Informasi mahasiswa' : 'Informasi dosen')
                    ->columns(2)
                    ->schema($profileFields),
            ]),
        ];
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
                Section::make('Informasi akun')
                    ->description('Isi identitas masuk dan tentukan peran pengguna.')
                    ->columns(2)
                    ->schema([
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
                    ]),
                Section::make('Informasi mahasiswa')
                    ->description('Lengkapi data akademik mahasiswa.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('nim')->label('NIM')->required()->maxLength(20)->unique('students', 'nim'),
                        TextInput::make('study_program')->label('Program studi')->required()->maxLength(100),
                        TextInput::make('cohort_year')->label('Angkatan')->numeric()->minValue(1900)->maxValue(2100),
                        TextInput::make('phone')->label('Nomor telepon')->tel()->maxLength(30),
                    ])
                    ->visible(fn (Get $get): bool => $get('role') === UserRole::Student->value),
                Section::make('Informasi dosen')
                    ->description('Lengkapi identitas dosen pembimbing.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('nidn')->label('NIDN')->required()->maxLength(20)->unique('lecturers', 'nidn'),
                        TextInput::make('department')->label('Departemen')->maxLength(100),
                        TextInput::make('phone')->label('Nomor telepon')->tel()->maxLength(30),
                    ])
                    ->visible(fn (Get $get): bool => $get('role') === UserRole::Lecturer->value),
            ]);
    }
}
