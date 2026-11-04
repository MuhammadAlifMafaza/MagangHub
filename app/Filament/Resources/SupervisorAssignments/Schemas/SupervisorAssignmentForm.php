<?php

namespace App\Filament\Resources\SupervisorAssignments\Schemas;

use App\Enums\UserRole;
use App\Models\Lecturer;
use App\Models\Student;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class SupervisorAssignmentForm
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
                Select::make('lecturer_id')
                    ->label('Dosen pembimbing')
                    ->options(fn (): array => Lecturer::query()
                        ->with('user:id,name,role,is_active')
                        ->whereHas('user', fn ($query) => $query
                            ->where('role', UserRole::Lecturer)
                            ->where('is_active', true))
                        ->get()
                        ->mapWithKeys(fn (Lecturer $lecturer): array => [
                            $lecturer->id => $lecturer->user->name,
                        ])
                        ->all())
                    ->searchable()
                    ->required(),
            ]);
    }
}
