<?php

namespace App\Filament\Resources\Internships\Schemas;

use App\Enums\UserRole;
use App\Models\Student;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class InternshipForm
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
