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
use App\Models\ReportVersion;
use App\Models\Student;
use App\Models\SupervisorAssignment;
use App\Models\User;
use App\Models\WeeklyReport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class GuidanceDemoSeeder extends Seeder
{
    private const DEMO_REPORT_NOTE = 'Data fiktif untuk demonstrasi lokal.';

    private const LEGACY_DEMO_PDF = "%PDF-1.4\nMagangHub demo report\n%%EOF\n";

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
        $this->repairLegacyDemoVersions($student);

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

        $version = $report->versions()->where('version_number', 1)->first();

        if ($version === null) {
            $path = 'reports/'.$student->id.'/'.$period->id.'/'.Str::uuid().'.pdf';
            $contents = $this->demoPdfContents($period->week_number);
            $this->storeVerifiedDemoPdf($path, $contents);

            try {
                $report->versions()->create([
                    'version_number' => 1,
                    'original_name' => 'Laporan Demo Minggu '.$period->week_number.'.pdf',
                    'stored_path' => $path,
                    'disk' => 'local',
                    'extension' => 'pdf',
                    'mime_type' => 'application/pdf',
                    'size_bytes' => strlen($contents),
                    'checksum_sha256' => hash('sha256', $contents),
                    'student_note' => self::DEMO_REPORT_NOTE,
                    'submitted_at' => now(),
                    'is_late' => $isLate,
                    'uploaded_by' => $student->user_id,
                ]);
            } catch (\Throwable $exception) {
                Storage::disk('local')->delete($path);

                throw $exception;
            }
        } elseif ($this->isLegacyDemoVersion($version, $student, $period)) {
            $this->repairLegacyDemoVersion($version, $student, $period);
        }

        return $report->refresh();
    }

    private function demoPdfContents(int $weekNumber): string
    {
        $stream = "BT\n/F1 18 Tf\n72 720 Td\n(MagangHub - Laporan Demo) Tj\n0 -28 Td\n(Minggu ke-{$weekNumber}) Tj\nET\n";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}endstream",
        ];
        $contents = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($contents);
            $contents .= ($index + 1)." 0 obj\n{$object}\nendobj\n";
        }

        $crossReferenceOffset = strlen($contents);
        $contents .= "xref\n0 6\n0000000000 65535 f \n";

        foreach (array_slice($offsets, 1) as $offset) {
            $contents .= sprintf("%010d 00000 n \n", $offset);
        }

        return $contents."trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n{$crossReferenceOffset}\n%%EOF\n";
    }

    private function isLegacyDemoVersion(ReportVersion $version, Student $student, ReportPeriod $period): bool
    {
        if (
            $version->uploaded_by !== $student->user_id ||
            $period->internship->student_id !== $student->id ||
            $version->student_note !== self::DEMO_REPORT_NOTE ||
            $version->disk !== 'local' ||
            $version->extension !== 'pdf' ||
            $version->mime_type !== 'application/pdf' ||
            $version->size_bytes !== strlen(self::LEGACY_DEMO_PDF) ||
            $version->checksum_sha256 !== hash('sha256', self::LEGACY_DEMO_PDF) ||
            dirname($version->stored_path) !== 'reports/'.$student->id.'/'.$period->id
        ) {
            return false;
        }

        $disk = Storage::disk('local');

        if (! $disk->exists($version->stored_path)) {
            return true;
        }

        $contents = $disk->get($version->stored_path);

        return is_string($contents) && hash_equals(self::LEGACY_DEMO_PDF, $contents);
    }

    private function repairLegacyDemoVersions(Student $student): void
    {
        $versions = ReportVersion::query()
            ->where('uploaded_by', $student->user_id)
            ->where('student_note', self::DEMO_REPORT_NOTE)
            ->with('weeklyReport.reportPeriod.internship')
            ->get();

        foreach ($versions as $version) {
            $period = $version->weeklyReport?->reportPeriod;

            if ($period instanceof ReportPeriod) {
                $this->repairLegacyDemoVersion($version, $student, $period);
            }
        }
    }

    private function repairLegacyDemoVersion(ReportVersion $version, Student $student, ReportPeriod $period): void
    {
        if (! $this->isLegacyDemoVersion($version, $student, $period)) {
            return;
        }

        $contents = $this->demoPdfContents($period->week_number);
        $this->storeVerifiedDemoPdf($version->stored_path, $contents);

        $version->update([
            'size_bytes' => strlen($contents),
            'checksum_sha256' => hash('sha256', $contents),
        ]);
    }

    private function storeVerifiedDemoPdf(string $path, string $contents): void
    {
        $disk = Storage::disk('local');

        try {
            if ($disk->put($path, $contents) === false || ! $disk->exists($path)) {
                throw new RuntimeException('Demo report file could not be stored.');
            }

            $storedContents = $disk->get($path);

            if (! is_string($storedContents) || ! hash_equals(hash('sha256', $contents), hash('sha256', $storedContents))) {
                throw new RuntimeException('Stored demo report file failed integrity verification.');
            }
        } catch (\Throwable $exception) {
            if ($disk->exists($path)) {
                $disk->delete($path);
            }

            throw $exception;
        }
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
