<?php

namespace App\Actions;

use App\Enums\InternshipStatus;
use App\Models\Internship;
use App\Models\ReportPeriod;
use App\Models\Student;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateInternship
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $actor, Internship $internship, array $data): Internship
    {
        Gate::forUser($actor)->authorize('update', $internship);

        $validated = Validator::make($data, [
            'company_name' => ['required', 'string', 'max:150'],
            'company_supervisor_name' => ['nullable', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'string', 'in:active,completed'],
        ], [
            'end_date.after_or_equal' => 'Tanggal selesai harus sama dengan atau setelah tanggal mulai.',
        ])->validate();

        return DB::transaction(function () use ($internship, $validated): Internship {
            $lockedStudent = Student::query()->lockForUpdate()->findOrFail($internship->student_id);
            $lockedInternship = Internship::query()->whereKey($internship->id)->lockForUpdate()->firstOrFail();

            if ($validated['status'] === InternshipStatus::Active->value && config('magang.internships.one_active_per_student')) {
                $hasOtherActiveInternship = $lockedStudent->internships()
                    ->where('id', '!=', $lockedInternship->id)
                    ->where('status', InternshipStatus::Active)
                    ->exists();

                if ($hasOtherActiveInternship) {
                    throw ValidationException::withMessages([
                        'status' => 'Mahasiswa sudah memiliki magang aktif lainnya.',
                    ]);
                }
            }

            $lockedInternship->update($validated);
            $this->synchronizeUnreportedPeriods($lockedInternship);

            return $lockedInternship->refresh();
        });
    }

    private function synchronizeUnreportedPeriods(Internship $internship): void
    {
        $timezone = config('magang.timezone');
        $periodStart = CarbonImmutable::parse($internship->start_date, $timezone)->startOfDay();
        $internshipEnd = CarbonImmutable::parse($internship->end_date, $timezone)->startOfDay();
        $dueTime = config('magang.report_periods.due_time');
        $periodsByWeek = [];
        $weekNumber = 1;

        while ($periodStart->lessThanOrEqualTo($internshipEnd)) {
            $periodEnd = $periodStart->addDays(6)->min($internshipEnd);
            $periodsByWeek[$weekNumber] = [
                'starts_on' => $periodStart->toDateString(),
                'ends_on' => $periodEnd->toDateString(),
                'due_at' => $periodEnd->setTimeFromTimeString($dueTime),
            ];
            $periodStart = $periodEnd->addDay();
            $weekNumber++;
        }

        $existingPeriods = $internship->reportPeriods()
            ->withExists('weeklyReport')
            ->lockForUpdate()
            ->get()
            ->keyBy('week_number');

        foreach ($existingPeriods as $existingPeriod) {
            if ($existingPeriod->weekly_report_exists) {
                unset($periodsByWeek[$existingPeriod->week_number]);

                continue;
            }

            $periodData = $periodsByWeek[$existingPeriod->week_number] ?? null;

            if ($periodData === null) {
                $existingPeriod->delete();

                continue;
            }

            $existingPeriod->update($periodData);
            unset($periodsByWeek[$existingPeriod->week_number]);
        }

        foreach ($periodsByWeek as $weekNumber => $periodData) {
            ReportPeriod::query()->create([
                'internship_id' => $internship->id,
                'week_number' => $weekNumber,
                ...$periodData,
            ]);
        }
    }
}
