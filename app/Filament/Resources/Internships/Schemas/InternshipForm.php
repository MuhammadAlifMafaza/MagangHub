<?php

namespace App\Filament\Resources\Internships\Schemas;

use App\Enums\UserRole;
use App\Models\Student;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InternshipForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('student_id')
                    ->label('Mahasiswa')
                    ->options(fn (): array => Student::query()
                        ->with('user:id,name,role,is_active')
                        ->whereHas('user', fn ($query) => $query
                            ->where('role', UserRole::Student)
                            ->where('is_active', true))
                        ->get()
                        ->mapWithKeys(fn (Student $student): array => [
                            $student->id => "{$student->user->name} ({$student->nim})",
                        ])
                        ->all())
                    ->searchable()
                    ->required(),
                TextInput::make('company_name')
                    ->label('Nama instansi')
                    ->maxLength(150)
                    ->required(),
                TextInput::make('company_supervisor_name')
                    ->label('Nama pembimbing lapangan')
                    ->maxLength(100),
                DatePicker::make('start_date')
                    ->label('Tanggal mulai')
                    ->native(true)
                    ->required(),
                DatePicker::make('end_date')
                    ->label('Tanggal selesai')
                    ->native(true)
                    ->afterOrEqual('start_date')
                    ->required(),
            ]);
    }
}
