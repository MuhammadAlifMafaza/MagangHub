<?php

use App\Enums\ReviewStatus;
use App\Models\Internship;
use App\Models\ReportPeriod;
use App\Models\ReportVersion;
use App\Models\Student;
use App\Models\WeeklyReport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('student report periods appear in one list with current periods first and upcoming periods hidden', function () {
    $this->travelTo('2026-03-15 12:00:00');

    $student = Student::factory()->create();
    $internship = Internship::factory()->create([
        'student_id' => $student->id,
        'start_date' => '2026-02-01',
        'end_date' => '2026-04-30',
    ]);
    $revisionPeriod = ReportPeriod::factory()->create([
        'internship_id' => $internship->id,
        'week_number' => 1,
        'starts_on' => '2026-02-01',
        'ends_on' => '2026-02-07',
        'due_at' => '2026-02-07 23:59:59',
    ]);
    $revisionReport = WeeklyReport::factory()->create([
        'report_period_id' => $revisionPeriod->id,
        'review_status' => ReviewStatus::RevisionRequested,
    ]);
    ReportVersion::factory()->create([
        'weekly_report_id' => $revisionReport->id,
        'uploaded_by' => $student->user_id,
    ]);
    $currentPeriod = ReportPeriod::factory()->create([
        'internship_id' => $internship->id,
        'week_number' => 2,
        'starts_on' => '2026-03-15',
        'ends_on' => '2026-03-21',
        'due_at' => '2026-03-21 23:59:59',
    ]);

    $historyPeriodIds = [];

    foreach (range(0, 4) as $index) {
        $startsOn = now()->startOfDay()->subWeeks($index + 1);
        $period = ReportPeriod::factory()->create([
            'internship_id' => $internship->id,
            'week_number' => $index + 3,
            'starts_on' => $startsOn->toDateString(),
            'ends_on' => $startsOn->copy()->addDays(6)->toDateString(),
            'due_at' => $startsOn->copy()->addDays(6)->setTime(23, 59, 59),
        ]);
        $report = WeeklyReport::factory()->create([
            'report_period_id' => $period->id,
            'review_status' => $index === 0 ? ReviewStatus::PendingReview : ReviewStatus::Approved,
        ]);
        ReportVersion::factory()->create([
            'weekly_report_id' => $report->id,
            'uploaded_by' => $student->user_id,
        ]);
        $historyPeriodIds[] = $period->id;
    }

    $upcomingPeriod = ReportPeriod::factory()->create([
        'internship_id' => $internship->id,
        'week_number' => 8,
        'starts_on' => '2026-03-16',
        'ends_on' => '2026-03-22',
        'due_at' => '2026-03-22 23:59:59',
    ]);

    $response = $this->actingAs($student->user)
        ->get(route('mahasiswa.laporan.index'));

    $response->assertOk()
        ->assertViewIs('reports.periods')
        ->assertSeeInOrder([
            'id="period-'.$currentPeriod->id.'"',
            'id="period-'.$historyPeriodIds[0].'"',
            'id="period-'.$historyPeriodIds[1].'"',
            'id="period-'.$historyPeriodIds[2].'"',
            'id="period-'.$historyPeriodIds[3].'"',
            'id="period-'.$historyPeriodIds[4].'"',
            'id="period-'.$revisionPeriod->id.'"',
        ], false)
        ->assertSeeInOrder([
            'Total periode',
            '>7</p>',
            'Belum dikirim',
            '>1</p>',
            'Menunggu pemeriksaan',
            '>1</p>',
            'Perlu revisi',
            '>1</p>',
        ], false)
        ->assertSee('id="periods-heading"', false)
        ->assertDontSee('periods-action-heading', false)
        ->assertDontSee('periods-history-heading', false)
        ->assertDontSee('periods-upcoming-heading', false)
        ->assertDontSee('Tampilkan 1 periode sebelumnya')
        ->assertSee('Perlu Revisi')
        ->assertSee('Menunggu Diperiksa')
        ->assertSee('Disetujui')
        ->assertSee('href="'.route('mahasiswa.laporan.period', $revisionPeriod).'"', false)
        ->assertSee('href="'.route('mahasiswa.laporan.period', $currentPeriod).'"', false)
        ->assertDontSee('id="period-'.$upcomingPeriod->id.'"', false)
        ->assertDontSee('href="'.route('mahasiswa.laporan.period', $upcomingPeriod).'"', false)
        ->assertDontSee('upload-form-', false)
        ->assertDontSee('name="file"', false);

    $this->travelBack();
});

test('a future period appears in the list when its start date arrives', function () {
    $this->travelTo('2026-03-15 12:00:00');

    $student = Student::factory()->create();
    $period = ReportPeriod::factory()->create([
        'internship_id' => Internship::factory()->create(['student_id' => $student->id]),
        'starts_on' => '2026-03-16',
        'ends_on' => '2026-03-22',
        'due_at' => '2026-03-22 23:59:59',
    ]);

    $this->actingAs($student->user)
        ->get(route('mahasiswa.laporan.index'))
        ->assertOk()
        ->assertSee('Belum ada periode laporan yang dibuka.')
        ->assertSee('Periode berikutnya akan muncul di daftar ini saat tanggal mulainya tiba.')
        ->assertSeeInOrder(['Total periode', '>0</p>'], false)
        ->assertDontSee('id="period-'.$period->id.'"', false);

    $this->travelTo('2026-03-16 00:00:00');

    $this->get(route('mahasiswa.laporan.index'))
        ->assertOk()
        ->assertSee('id="periods-heading"', false)
        ->assertSee('id="period-'.$period->id.'"', false)
        ->assertSee('href="'.route('mahasiswa.laporan.period', $period).'"', false)
        ->assertSeeInOrder(['Total periode', '>1</p>', 'Belum dikirim', '>1</p>'], false)
        ->assertDontSee('Belum ada periode laporan yang dibuka.');

    $this->travelBack();
});

test('an open period detail shows the initial report upload form inline', function () {
    $this->travelTo('2026-03-15 12:00:00');

    $student = Student::factory()->create();
    $period = ReportPeriod::factory()->create([
        'internship_id' => Internship::factory()->create(['student_id' => $student->id]),
        'starts_on' => '2026-03-15',
        'ends_on' => '2026-03-21',
        'due_at' => '2026-03-21 23:59:59',
    ]);

    $this->actingAs($student->user)
        ->get(route('mahasiswa.laporan.period', $period))
        ->assertOk()
        ->assertViewIs('reports.show')
        ->assertSee('Pekerjaan Anda')
        ->assertSee('Riwayat versi')
        ->assertSee('Belum ada berkas laporan')
        ->assertSee('Kirim laporan')
        ->assertSee('Berkas laporan')
        ->assertSee('Tambahkan catatan tentang laporan ini (opsional)')
        ->assertSee('PDF atau DOCX, maksimal 10 MB.')
        ->assertSee('name="file"', false);

    $this->travelBack();
});

test('a revision period detail uses revision-specific form text beside its version history', function () {
    $this->travelTo('2026-03-15 12:00:00');

    $student = Student::factory()->create();
    $period = ReportPeriod::factory()->create([
        'internship_id' => Internship::factory()->create(['student_id' => $student->id]),
        'starts_on' => '2026-03-01',
        'ends_on' => '2026-03-07',
        'due_at' => '2026-03-07 23:59:59',
    ]);
    $report = WeeklyReport::factory()->create([
        'report_period_id' => $period->id,
        'review_status' => ReviewStatus::RevisionRequested,
    ]);
    ReportVersion::factory()->create([
        'weekly_report_id' => $report->id,
        'original_name' => 'laporan-awal.pdf',
        'uploaded_by' => $student->user_id,
    ]);

    $this->actingAs($student->user)
        ->get(route('mahasiswa.laporan.period', $period))
        ->assertOk()
        ->assertSee('Laporan minggu ke-'.$period->week_number)
        ->assertSee('Perlu Revisi')
        ->assertSee('laporan-awal.pdf')
        ->assertSee('Baca umpan balik di riwayat versi')
        ->assertSee('Berkas revisi')
        ->assertSee('Jelaskan perubahan pada revisi ini (opsional)')
        ->assertSee('Unggah revisi');

    $this->travelBack();
});

test('an upcoming period detail does not show an upload form', function () {
    $this->travelTo('2026-03-15 12:00:00');

    $student = Student::factory()->create();
    $period = ReportPeriod::factory()->create([
        'internship_id' => Internship::factory()->create(['student_id' => $student->id]),
        'starts_on' => '2026-03-16',
        'ends_on' => '2026-03-22',
        'due_at' => '2026-03-22 23:59:59',
    ]);

    $this->actingAs($student->user)
        ->get(route('mahasiswa.laporan.period', $period))
        ->assertOk()
        ->assertSee('Belum Dibuka')
        ->assertSee('Periode dibuka 16 Mar 2026')
        ->assertDontSee('name="file"', false)
        ->assertDontSee('Unggah revisi');

    $this->travelBack();
});

test('a student cannot view another students period detail', function () {
    $student = Student::factory()->create();
    $otherStudent = Student::factory()->create();
    $period = ReportPeriod::factory()->create([
        'internship_id' => Internship::factory()->create(['student_id' => $otherStudent->id]),
    ]);

    $this->actingAs($student->user)
        ->get(route('mahasiswa.laporan.period', $period))
        ->assertForbidden();
});

test('a report uploaded from period details returns to that details page', function () {
    Storage::fake('local');
    $this->travelTo('2026-03-15 12:00:00');

    $student = Student::factory()->create();
    $period = ReportPeriod::factory()->create([
        'internship_id' => Internship::factory()->create(['student_id' => $student->id]),
        'starts_on' => '2026-03-15',
        'ends_on' => '2026-03-21',
        'due_at' => '2026-03-21 23:59:59',
    ]);
    $file = UploadedFile::fake()->createWithContent('laporan-mingguan.pdf', "%PDF-1.4\nisi laporan");

    $this->actingAs($student->user)
        ->from(route('mahasiswa.laporan.period', $period))
        ->post(route('mahasiswa.laporan.store', $period), [
            'file' => $file,
            'student_note' => 'Laporan minggu ini.',
            'period_id' => $period->id,
        ])
        ->assertRedirect(route('mahasiswa.laporan.period', $period))
        ->assertSessionHas('status', 'Laporan berhasil dikirim.');

    $this->assertDatabaseHas('weekly_reports', [
        'report_period_id' => $period->id,
        'review_status' => ReviewStatus::PendingReview->value,
    ]);

    $this->travelBack();
});

test('a report upload validation error stays on the matching period detail', function () {
    $this->travelTo('2026-03-15 12:00:00');

    $student = Student::factory()->create();
    $period = ReportPeriod::factory()->create([
        'internship_id' => Internship::factory()->create(['student_id' => $student->id]),
        'starts_on' => '2026-03-15',
        'ends_on' => '2026-03-21',
        'due_at' => '2026-03-21 23:59:59',
    ]);

    $this->actingAs($student->user)
        ->from(route('mahasiswa.laporan.period', $period))
        ->followingRedirects()
        ->post(route('mahasiswa.laporan.store', $period), ['period_id' => $period->id])
        ->assertOk()
        ->assertViewIs('reports.show')
        ->assertSee('id="file-error-'.$period->id.'"', false)
        ->assertSee('Berkas laporan wajib diunggah.')
        ->assertSee('Kirim laporan');

    $this->travelBack();
});
