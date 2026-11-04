<?php

namespace App\Actions;

use App\Enums\InternshipStatus;
use App\Models\Internship;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateInternship
{
    public function __construct(private GenerateReportPeriods $generateReportPeriods) {}

    /**
     * @param  array{company_name: string, company_supervisor_name?: ?string, start_date: string, end_date: string}  $data
     */
    public function handle(Student $student, array $data): Internship
    {
        if ($data['end_date'] < $data['start_date']) {
            throw ValidationException::withMessages([
                'end_date' => 'Tanggal selesai harus sama dengan atau setelah tanggal mulai.',
            ]);
        }

        return DB::transaction(function () use ($student, $data): Internship {
            $lockedStudent = Student::query()->lockForUpdate()->findOrFail($student->id);

            if (config('magang.internships.one_active_per_student') && $lockedStudent->internships()->where('status', InternshipStatus::Active)->exists()) {
                throw ValidationException::withMessages([
                    'student_id' => 'Mahasiswa sudah memiliki magang aktif.',
                ]);
            }

            $internship = $lockedStudent->internships()->create([
                ...$data,
                'status' => InternshipStatus::Active,
            ]);

            $this->generateReportPeriods->handle($internship);

            return $internship;
        });
    }
}
