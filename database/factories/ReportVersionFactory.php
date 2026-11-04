<?php

namespace Database\Factories;

use App\Models\ReportVersion;
use App\Models\User;
use App\Models\WeeklyReport;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ReportVersion> */
class ReportVersionFactory extends Factory
{
    protected $model = ReportVersion::class;

    public function definition(): array
    {
        $path = 'reports/'.Str::uuid().'.pdf';

        return [
            'weekly_report_id' => WeeklyReport::factory(), 'version_number' => 1, 'original_name' => 'report.pdf',
            'stored_path' => $path, 'disk' => 'local', 'extension' => 'pdf', 'mime_type' => 'application/pdf',
            'size_bytes' => 1024, 'checksum_sha256' => hash('sha256', $path), 'student_note' => null,
            'submitted_at' => now(), 'is_late' => false, 'uploaded_by' => User::factory(),
        ];
    }
}
