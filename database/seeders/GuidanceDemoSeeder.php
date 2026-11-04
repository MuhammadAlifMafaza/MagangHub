<?php

namespace Database\Seeders;

use App\Actions\CreateInternship;
use App\Actions\PublishAssessment;
use App\Actions\ReviewReport;
use App\Actions\SaveAssessment;
use App\Enums\AssessmentStatus;
use App\Enums\GuidanceBookingStatus;
use App\Enums\InternshipStatus;
use App\Enums\ReportReviewDecision;
use App\Enums\ReviewStatus;
use App\Enums\SupervisorAssignmentStatus;
use App\Enums\UserRole;
use App\Models\Assessment;
use App\Models\GuidanceBooking;
use App\Models\GuidanceSlot;
use App\Models\Internship;
use App\Models\Lecturer;
use App\Models\ReportPeriod;
use App\Models\Student;
use App\Models\SupervisorAssignment;
use App\Models\User;
use App\Models\WeeklyReport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GuidanceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = $this->user('Admin Demo', 'admin.demo@maganghub.test', UserRole::Admin);
        $lecturerUser = $this->user('Dosen Demo', 'dosen.demo@maganghub.test', UserRole::Lecturer);
        $studentUser = $this->user('Mahasiswa Demo', 'mahasiswa.demo@maganghub.test', UserRole::Student);
        $lecturer = Lecturer::query()->firstOrCreate(['user_id' => $lecturerUser->id], [
            'nidn' => 'DEMO000001',
            'department' => 'Informatika',
            'phone' => '080000000001',
        ]);
        $student = Student::query()->firstOrCreate(['user_id' => $studentUser->id], [
            'nim' => 'DEMO000001',
            'study_program' => 'Informatika',
            'cohort_year' => now()->year,
            'phone' => '080000000002',
        ]);

        SupervisorAssignment::query()->firstOrCreate([
            'student_id' => $student->id,
            'lecturer_id' => $lecturer->id,
            'status' => SupervisorAssignmentStatus::Active,
        ], ['assigned_by' => $admin->id]);

        $internship = Internship::query()->where('student_id', $student->id)->where('status', InternshipStatus::Active)->first();
        if ($internship === null) {
            $internship = app(CreateInternship::class)->handle($student, [
                'company_name' => 'Perusahaan Demo MagangHub',
                'company_supervisor_name' => 'Pembimbing Lapangan Demo',
                'start_date' => now()->subDays(120)->toDateString(),
                'end_date' => now()->addDays(180)->toDateString(),
            ]);
        }

        $periods = $internship->reportPeriods()->orderBy('week_number')->get();
        $historicalPeriod = $periods->first();
        $currentPeriod = $periods->first(fn (ReportPeriod $period): bool => $period->starts_on->lte(now()) && $period->ends_on->toDateString() >= now()->toDateString()) ?? $periods->last();

        if ($historicalPeriod !== null) {
            $approvedReport = $this->seedReport($historicalPeriod, $student, true);
            if ($approvedReport->review_status !== ReviewStatus::Approved) {
                app(ReviewReport::class)->handle($lecturer, $approvedReport, ReportReviewDecision::Approved, null);
            }

            $assessment = Assessment::query()->where('weekly_report_id', $approvedReport->id)->first();
            if ($assessment === null) {
                $assessment = app(SaveAssessment::class)->handle($lecturer, $approvedReport, '88.50', 'Hasil demo yang sudah dipublikasikan.');
            }

            if ($assessment->status === AssessmentStatus::Draft) {
                app(PublishAssessment::class)->handle($assessment);
            }
        }

        if ($currentPeriod !== null && $currentPeriod->id !== $historicalPeriod?->id) {
            $this->seedReport($currentPeriod, $student, false);
        }

        $slot = GuidanceSlot::query()->where('lecturer_id', $lecturer->id)
            ->where('location', 'Ruang Demo')->where('starts_at', '>', now())->oldest('starts_at')->first();
        if ($slot === null) {
            $start = now()->addDays(2)->setTime(9, 0, 0);
            $slot = GuidanceSlot::query()->create([
                'lecturer_id' => $lecturer->id,
                'starts_at' => $start,
                'ends_at' => $start->copy()->addMinutes(30),
                'capacity' => 1,
                'location' => 'Ruang Demo',
                'status' => 'open',
            ]);
        }

        if (! $slot->bookings()->where('student_id', $student->id)->where('status', GuidanceBookingStatus::Booked)->exists()) {
            GuidanceBooking::query()->create([
                'guidance_slot_id' => $slot->id,
                'student_id' => $student->id,
                'status' => GuidanceBookingStatus::Booked,
            ]);
        }
    }

    private function seedReport(ReportPeriod $period, Student $student, bool $isLate): WeeklyReport
    {
        $report = WeeklyReport::query()->firstOrCreate(
            ['report_period_id' => $period->id],
            ['review_status' => ReviewStatus::PendingReview],
        );

        if (! $report->versions()->where('version_number', 1)->exists()) {
            $path = 'reports/'.$student->id.'/'.$period->id.'/'.Str::uuid().'.pdf';
            $contents = "%PDF-1.4\nMagangHub demo report\n%%EOF\n";
            Storage::disk('local')->put($path, $contents);

            $report->versions()->create([
                'version_number' => 1,
                'original_name' => 'Laporan Demo Minggu '.$period->week_number.'.pdf',
                'stored_path' => $path,
                'disk' => 'local',
                'extension' => 'pdf',
                'mime_type' => 'application/pdf',
                'size_bytes' => strlen($contents),
                'checksum_sha256' => hash('sha256', $contents),
                'student_note' => 'Data fiktif untuk demonstrasi lokal.',
                'submitted_at' => now(),
                'is_late' => $isLate,
                'uploaded_by' => $student->user_id,
            ]);
        }

        return $report->refresh();
    }

    private function user(string $name, string $email, UserRole $role): User
    {
        $user = User::query()->where('email', $email)->first();

        if ($user !== null) {
            return $user;
        }

        return User::factory()->create(['name' => $name, 'email' => $email, 'role' => $role]);
    }
}
