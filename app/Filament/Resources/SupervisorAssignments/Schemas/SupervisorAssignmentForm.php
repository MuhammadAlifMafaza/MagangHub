<?php

namespace App\Filament\Resources\SupervisorAssignments\Schemas;

use App\Enums\UserRole;
use App\Models\Lecturer;
use App\Models\Student;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class SupervisorAssignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('student_id')
                    ->label('Mahasiswa')
                    ->getSearchResultsUsing(static function (string $search): array {
                        if (blank(trim($search))) {
                            return [];
                        }

                        return Student::query()
                            ->with('user:id,name,role,is_active')
                            ->whereHas('user', fn (Builder $users): Builder => $users
                                ->where('role', UserRole::Student)
                                ->where('is_active', true))
                            ->where(fn (Builder $matches): Builder => $matches
                                ->whereLike('nim', "%{$search}%")
                                ->orWhereHas('user', fn (Builder $users): Builder => $users->whereLike('name', "%{$search}%")))
                            ->orderBy('id')
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn (Student $student): array => [
                                $student->id => "{$student->user->name} ({$student->nim})",
                            ])
                            ->all();
                    })
                    ->getOptionLabelUsing(static function (string|int|null $value): ?string {
                        if (blank($value)) {
                            return null;
                        }

                        $student = Student::query()
                            ->with('user:id,name,role,is_active')
                            ->whereHas('user', fn (Builder $users): Builder => $users
                                ->where('role', UserRole::Student)
                                ->where('is_active', true))
                            ->find($value);

                        return $student === null ? null : "{$student->user->name} ({$student->nim})";
                    })
                    ->searchable()
                    ->optionsLimit(50)
                    ->noSearchResultsMessage('Tidak ada mahasiswa yang cocok.')
                    ->required(),
                Select::make('lecturer_id')
                    ->label('Dosen pembimbing')
                    ->getSearchResultsUsing(static function (string $search): array {
                        if (blank(trim($search))) {
                            return [];
                        }

                        return Lecturer::query()
                            ->with('user:id,name,role,is_active')
                            ->whereHas('user', fn (Builder $users): Builder => $users
                                ->where('role', UserRole::Lecturer)
                                ->where('is_active', true)
                                ->whereLike('name', "%{$search}%"))
                            ->orderBy('id')
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn (Lecturer $lecturer): array => [
                                $lecturer->id => $lecturer->user->name,
                            ])
                            ->all();
                    })
                    ->getOptionLabelUsing(static function (string|int|null $value): ?string {
                        if (blank($value)) {
                            return null;
                        }

                        return Lecturer::query()
                            ->with('user:id,name,role,is_active')
                            ->whereHas('user', fn (Builder $users): Builder => $users
                                ->where('role', UserRole::Lecturer)
                                ->where('is_active', true))
                            ->find($value)
                            ?->user
                            ?->name;
                    })
                    ->searchable()
                    ->optionsLimit(50)
                    ->noSearchResultsMessage('Tidak ada dosen yang cocok.')
                    ->required(),
            ]);
    }
}
