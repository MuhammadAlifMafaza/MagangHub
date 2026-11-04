<?php

use App\Enums\ReviewStatus;
use App\Models\Internship;
use App\Models\ReportPeriod;
use App\Models\Student;
use App\Models\WeeklyReport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('returns a successful response', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});

test('a student can submit a report through the HTTP endpoint', function () {
    Storage::fake('local');
    $student = Student::factory()->create();
    $period = ReportPeriod::factory()->create([
        'internship_id' => Internship::factory()->create(['student_id' => $student->id]),
        'starts_on' => now()->subDay(),
    ]);

    $response = $this->actingAs($student->user)->post(route('mahasiswa.laporan.store', $period), [
        'file' => UploadedFile::fake()->createWithContent('weekly.pdf', "%PDF-1.4\ncontent"),
        'student_note' => 'Laporan minggu ini',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('weekly_reports', [
        'report_period_id' => $period->id,
        'review_status' => ReviewStatus::PendingReview->value,
    ]);
    $this->assertDatabaseHas('report_versions', [
        'weekly_report_id' => WeeklyReport::query()->where('report_period_id', $period->id)->value('id'),
        'student_note' => 'Laporan minggu ini',
    ]);
});

test('a missing report file returns a localized validation error scoped to its period', function () {
    $student = Student::factory()->create();
    $period = ReportPeriod::factory()->create([
        'internship_id' => Internship::factory()->create(['student_id' => $student->id]),
        'starts_on' => now()->subDay(),
    ]);

    $response = $this->actingAs($student->user)->post(route('mahasiswa.laporan.store', $period), [
        'period_id' => $period->id,
        'student_note' => 'Catatan untuk periode ini',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['file' => 'Berkas laporan wajib diunggah.']);
    $response->assertSessionHas('_old_input', fn (array $oldInput): bool => $oldInput['period_id'] === $period->id);
    $this->assertDatabaseMissing('weekly_reports', ['report_period_id' => $period->id]);
});

test('a student cannot submit a report for another student through the HTTP endpoint', function () {
    $student = Student::factory()->create();
    $otherStudent = Student::factory()->create();
    $period = ReportPeriod::factory()->create([
        'internship_id' => Internship::factory()->create(['student_id' => $otherStudent->id]),
        'starts_on' => now()->subDay(),
    ]);

    $this->actingAs($student->user)
        ->post(route('mahasiswa.laporan.store', $period), [
            'file' => UploadedFile::fake()->createWithContent('weekly.pdf', "%PDF-1.4\ncontent"),
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('weekly_reports', ['report_period_id' => $period->id]);
});
