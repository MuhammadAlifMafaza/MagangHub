<?php

use App\Actions\PublishAssessment;
use App\Actions\ReviewReport;
use App\Actions\SaveAssessment;
use App\Actions\SubmitReportVersion;
use App\Enums\AssessmentStatus;
use App\Enums\ReportCollectionStatus;
use App\Enums\ReportReviewDecision;
use App\Enums\ReviewStatus;
use App\Enums\UserRole;
use App\Filament\Pages\ReportDetails as ReportDetailsPage;
use App\Models\Assessment;
use App\Models\Internship;
use App\Models\Lecturer;
use App\Models\ReportPeriod;
use App\Models\ReportVersion;
use App\Models\Student;
use App\Models\SupervisorAssignment;
use App\Models\User;
use App\Models\WeeklyReport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('a student submission creates an immutable private version and late submissions are marked', function () {
    Storage::fake('local');
    $student = Student::factory()->create();
    $internship = Internship::factory()->create(['student_id' => $student->id]);
    $period = ReportPeriod::factory()->create(['internship_id' => $internship->id, 'starts_on' => now()->subDay(), 'due_at' => now()->subMinute()]);
    $file = UploadedFile::fake()->createWithContent('weekly.pdf', "%PDF-1.4\ncontent");

    $result = app(SubmitReportVersion::class)->handle($student, $period, $file);

    expect($result['report']->review_status)->toBe(ReviewStatus::PendingReview)
        ->and($result['version']->version_number)->toBe(1)
        ->and($result['version']->is_late)->toBeTrue()
        ->and($result['version']->stored_path)->toStartWith('reports/'.$student->id.'/'.$period->id.'/');
    Storage::disk('local')->assertExists($result['version']->stored_path);
});

test('a submitted PDF is persisted and its original contents can be previewed and downloaded', function () {
    Storage::fake('local');
    $student = Student::factory()->create();
    $period = ReportPeriod::factory()->create([
        'internship_id' => Internship::factory()->create(['student_id' => $student->id]),
        'starts_on' => now()->subDay()->toDateString(),
        'due_at' => now()->addDay(),
    ]);
    $pdfContents = "%PDF-1.4\nUploaded report contents\n%%EOF\n";
    $file = UploadedFile::fake()->createWithContent('weekly.pdf', $pdfContents);

    $this->actingAs($student->user)
        ->from(route('mahasiswa.laporan.index'))
        ->post(route('mahasiswa.laporan.store', $period), ['file' => $file])
        ->assertRedirect(route('mahasiswa.laporan.index'))
        ->assertSessionHas('status', 'Laporan berhasil dikirim.');

    $version = ReportVersion::query()->firstOrFail();
    Storage::disk('local')->assertExists($version->stored_path);
    expect(Storage::disk('local')->get($version->stored_path))->toBe($pdfContents)
        ->and($version->size_bytes)->toBe(strlen($pdfContents))
        ->and($version->checksum_sha256)->toBe(hash('sha256', $pdfContents));

    $this->actingAs($student->user)
        ->get(route('laporan.versi.preview', $version))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertStreamedContent($pdfContents);

    $this->actingAs($student->user)
        ->get(route('laporan.versi.viewer', $version))
        ->assertOk()
        ->assertSee('Pratinjau PDF')
        ->assertSee(route('laporan.versi.download', $version), false)
        ->assertSee(route('laporan.versi.viewer', $version), false)
        ->assertSee('fetch(')
        ->assertSee('URL.createObjectURL');

    $this->actingAs($student->user)
        ->get(route('laporan.versi.download', $version))
        ->assertOk()
        ->assertDownload('weekly.pdf')
        ->assertStreamedContent($pdfContents);
});

test('report collection status is derived from the opening date submission and deadline', function () {
    $this->travelTo('2026-03-15 12:00:00');
    $internship = Internship::factory()->create([
        'start_date' => '2026-03-01',
        'end_date' => '2026-03-31',
    ]);
    $notOpened = ReportPeriod::factory()->create([
        'internship_id' => $internship->id,
        'week_number' => 1,
        'starts_on' => '2026-03-16',
        'ends_on' => '2026-03-22',
        'due_at' => '2026-03-22 23:59:59',
    ]);
    $notSubmitted = ReportPeriod::factory()->create([
        'internship_id' => $internship->id,
        'week_number' => 2,
        'starts_on' => '2026-03-15',
        'ends_on' => '2026-03-21',
        'due_at' => '2026-03-21 23:59:59',
    ]);
    $notSubmittedLate = ReportPeriod::factory()->create([
        'internship_id' => $internship->id,
        'week_number' => 3,
        'starts_on' => '2026-03-08',
        'ends_on' => '2026-03-14',
        'due_at' => '2026-03-14 23:59:59',
    ]);
    $submittedPeriod = ReportPeriod::factory()->create([
        'internship_id' => $internship->id,
        'week_number' => 4,
        'starts_on' => '2026-03-01',
        'ends_on' => '2026-03-07',
        'due_at' => '2026-03-07 23:59:59',
    ]);
    $submittedReport = WeeklyReport::factory()->create(['report_period_id' => $submittedPeriod->id]);
    ReportVersion::factory()->create([
        'weekly_report_id' => $submittedReport->id,
        'is_late' => false,
        'uploaded_by' => $internship->student->user_id,
    ]);
    $submittedLatePeriod = ReportPeriod::factory()->create([
        'internship_id' => $internship->id,
        'week_number' => 5,
        'starts_on' => '2026-02-22',
        'ends_on' => '2026-02-28',
        'due_at' => '2026-02-28 23:59:59',
    ]);
    $submittedLateReport = WeeklyReport::factory()->create(['report_period_id' => $submittedLatePeriod->id]);
    ReportVersion::factory()->create([
        'weekly_report_id' => $submittedLateReport->id,
        'is_late' => true,
        'uploaded_by' => $internship->student->user_id,
    ]);
    ReportVersion::factory()->create([
        'weekly_report_id' => $submittedLateReport->id,
        'version_number' => 2,
        'is_late' => false,
        'uploaded_by' => $internship->student->user_id,
    ]);

    expect([
        $notOpened->collectionStatus(),
        $notSubmitted->collectionStatus(),
        $notSubmittedLate->collectionStatus(),
        $submittedPeriod->collectionStatus(),
        $submittedLatePeriod->collectionStatus(),
    ])->toBe([
        ReportCollectionStatus::NotOpened,
        ReportCollectionStatus::NotSubmitted,
        ReportCollectionStatus::NotSubmittedLate,
        ReportCollectionStatus::Submitted,
        ReportCollectionStatus::SubmittedLate,
    ]);

    $this->travelBack();
});

test('report submission rejects invalid extensions and another student period', function () {
    Storage::fake('local');
    $student = Student::factory()->create();
    $other = Student::factory()->create();
    $period = ReportPeriod::factory()->create(['starts_on' => now()->subDay()]);

    expect(fn () => app(SubmitReportVersion::class)->handle($student, $period, UploadedFile::fake()->create('bad.exe', 1)))
        ->toThrow(HttpException::class);
    $ownedPeriod = ReportPeriod::factory()->create(['internship_id' => Internship::factory()->create(['student_id' => $other->id]), 'starts_on' => now()->subDay()]);
    expect(fn () => app(SubmitReportVersion::class)->handle($student, $ownedPeriod, UploadedFile::fake()->createWithContent('weekly.pdf', "%PDF-1.4\ncontent")))
        ->toThrow(HttpException::class);
});

test('review requires feedback for revisions and approval locks the report to the reviewed version', function () {
    $student = Student::factory()->create();
    $lecturer = Lecturer::factory()->create();
    SupervisorAssignment::factory()->create(['student_id' => $student->id, 'lecturer_id' => $lecturer->id]);
    $period = ReportPeriod::factory()->create(['internship_id' => Internship::factory()->create(['student_id' => $student->id])]);
    $report = WeeklyReport::factory()->create(['report_period_id' => $period->id]);
    $version = ReportVersion::factory()->create(['weekly_report_id' => $report->id, 'uploaded_by' => $student->user_id]);

    expect(fn () => app(ReviewReport::class)->handle($lecturer, $report, ReportReviewDecision::RevisionRequested, null))
        ->toThrow(ValidationException::class);
    app(ReviewReport::class)->handle($lecturer, $report, ReportReviewDecision::Approved, null);
    expect($report->fresh()->review_status)->toBe(ReviewStatus::Approved)
        ->and($version->fresh()->review)->not->toBeNull();
    expect(fn () => app(ReviewReport::class)->handle($lecturer, $report, ReportReviewDecision::Approved, null))
        ->toThrow(HttpException::class);
});

test('assessment can only be saved for approved reports and students queries expose published assessments only', function () {
    $student = Student::factory()->create();
    $lecturer = Lecturer::factory()->create();
    SupervisorAssignment::factory()->create(['student_id' => $student->id, 'lecturer_id' => $lecturer->id]);
    $period = ReportPeriod::factory()->create(['internship_id' => Internship::factory()->create(['student_id' => $student->id])]);
    $report = WeeklyReport::factory()->create(['report_period_id' => $period->id]);
    expect(fn () => app(SaveAssessment::class)->handle($lecturer, $report, 80))
        ->toThrow(HttpException::class);

    $report->update(['review_status' => ReviewStatus::Approved]);
    $draft = app(SaveAssessment::class)->handle($lecturer, $report, 80, 'Catatan');
    expect($draft->status)->toBe(AssessmentStatus::Draft);
    expect(fn () => app(SaveAssessment::class)->handle($lecturer, $report, 101))->toThrow(ValidationException::class);

    $published = app(PublishAssessment::class)->handle($draft);
    expect($published->status)->toBe(AssessmentStatus::Published)
        ->and($published->published_at)->not->toBeNull()
        ->and(Assessment::query()->published()->count())->toBe(1);
});

test('former lecturer retains review and grading access only for periods covered by their assignment', function () {
    $student = Student::factory()->create();
    $lecturer = Lecturer::factory()->create();
    $internship = Internship::factory()->create(['student_id' => $student->id]);
    SupervisorAssignment::factory()->create([
        'student_id' => $student->id,
        'lecturer_id' => $lecturer->id,
        'status' => 'ended',
        'assigned_at' => '2026-01-01 00:00:00',
        'ended_at' => '2026-01-15 23:59:59',
    ]);
    $historicalPeriod = ReportPeriod::factory()->create([
        'internship_id' => $internship->id,
        'starts_on' => '2026-01-10',
        'due_at' => '2026-01-16 23:59:59',
    ]);
    $historicalReport = WeeklyReport::factory()->create(['report_period_id' => $historicalPeriod->id]);
    ReportVersion::factory()->create(['weekly_report_id' => $historicalReport->id, 'uploaded_by' => $student->user_id]);
    $laterPeriod = ReportPeriod::factory()->create([
        'internship_id' => $internship->id,
        'week_number' => 2,
        'starts_on' => '2026-01-16',
        'due_at' => '2026-01-22 23:59:59',
    ]);
    $laterReport = WeeklyReport::factory()->create(['report_period_id' => $laterPeriod->id]);

    expect(Gate::forUser($lecturer->user)->allows('review', $historicalReport))->toBeTrue()
        ->and(Gate::forUser($lecturer->user)->allows('review', $laterReport))->toBeFalse();

    app(ReviewReport::class)->handle($lecturer, $historicalReport, ReportReviewDecision::Approved, null);
    expect($historicalReport->fresh()->review_status)->toBe(ReviewStatus::Approved);
    $assessment = app(SaveAssessment::class)->handle($lecturer, $historicalReport, 90);
    expect($assessment->lecturer_id)->toBe($lecturer->id)
        ->and(fn () => app(SaveAssessment::class)->handle($lecturer, $laterReport, 90))->toThrow(HttpException::class);
});

test('private report downloads are authorized and unpublished assessments stay hidden from students', function () {
    Storage::fake('local');
    $student = Student::factory()->create();
    $otherStudent = Student::factory()->create();
    $lecturer = Lecturer::factory()->create();
    SupervisorAssignment::factory()->create(['student_id' => $student->id, 'lecturer_id' => $lecturer->id]);
    $report = WeeklyReport::factory()->create([
        'report_period_id' => ReportPeriod::factory()->create(['internship_id' => Internship::factory()->create(['student_id' => $student->id])])->id,
        'review_status' => ReviewStatus::Approved,
    ]);
    $version = ReportVersion::factory()->create([
        'weekly_report_id' => $report->id,
        'uploaded_by' => $student->user_id,
        'size_bytes' => strlen('%PDF-1.4 private report'),
        'checksum_sha256' => hash('sha256', '%PDF-1.4 private report'),
    ]);
    Storage::disk('local')->put($version->stored_path, '%PDF-1.4 private report');
    Assessment::factory()->create([
        'weekly_report_id' => $report->id,
        'lecturer_id' => $lecturer->id,
        'status' => AssessmentStatus::Draft,
        'score' => 91,
    ]);

    $this->actingAs($otherStudent->user)
        ->get(route('laporan.versi.download', $version))
        ->assertForbidden();

    $this->actingAs($student->user)
        ->get(route('laporan.show', $report))
        ->assertRedirect(route('mahasiswa.laporan.show', $report));

    $this->actingAs($student->user)
        ->get(route('mahasiswa.laporan.show', $report))
        ->assertOk()
        ->assertSee('<html lang="id">', false)
        ->assertDontSee('91.00');

    $this->actingAs($student->user)
        ->get(route('laporan.versi.download', $version))
        ->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename=report.pdf');
});

test('administrators see report details in the admin panel with student and assessment context', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $student = Student::factory()->create([
        'nim' => '25.11.1234',
        'study_program' => 'Informatika',
    ]);
    $internship = Internship::factory()->create([
        'student_id' => $student->id,
        'company_name' => 'Acme Labs',
    ]);
    $period = ReportPeriod::factory()->create([
        'internship_id' => $internship->id,
        'week_number' => 3,
    ]);
    $report = WeeklyReport::factory()->create(['report_period_id' => $period->id]);

    $detailUrl = ReportDetailsPage::getUrl(['report' => $report], panel: 'admin');

    $this->actingAs($admin)
        ->get(route('laporan.show', $report))
        ->assertRedirect($detailUrl);

    $this->actingAs($admin)
        ->get($detailUrl)
        ->assertOk()
        ->assertSee('fi-sidebar')
        ->assertSee('Informasi mahasiswa')
        ->assertSee('25.11.1234')
        ->assertSee('Informatika')
        ->assertSee('Acme Labs')
        ->assertSee('Belum ada penilaian untuk laporan ini.');

    $this->actingAs($admin)
        ->get(ReportDetailsPage::getUrl(['report' => $report], panel: 'dosen'))
        ->assertForbidden();
});

test('assigned lecturers can preview PDF reports but not access another lecturers report', function () {
    Storage::fake('local');
    $student = Student::factory()->create();
    $lecturer = Lecturer::factory()->create();
    $otherLecturer = Lecturer::factory()->create();
    SupervisorAssignment::factory()->create(['student_id' => $student->id, 'lecturer_id' => $lecturer->id]);
    $report = WeeklyReport::factory()->create([
        'report_period_id' => ReportPeriod::factory()->create(['internship_id' => Internship::factory()->create(['student_id' => $student->id])])->id,
    ]);
    $version = ReportVersion::factory()->create([
        'weekly_report_id' => $report->id,
        'uploaded_by' => $student->user_id,
        'size_bytes' => strlen('%PDF-1.4 private report'),
        'checksum_sha256' => hash('sha256', '%PDF-1.4 private report'),
    ]);
    Storage::disk('local')->put($version->stored_path, '%PDF-1.4 private report');

    $detailUrl = ReportDetailsPage::getUrl(['report' => $report], panel: 'dosen');

    $this->actingAs($lecturer->user)
        ->get(route('laporan.show', $report))
        ->assertRedirect($detailUrl);

    $this->actingAs($lecturer->user)
        ->get($detailUrl)
        ->assertOk()
        ->assertSee('Baca PDF')
        ->assertSee(route('laporan.versi.viewer', $version), false)
        ->assertSee(route('laporan.versi.download', $version), false);

    $this->actingAs($lecturer->user)
        ->get(route('laporan.versi.preview', $version))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertHeader('content-disposition', 'inline; filename=report.pdf')
        ->assertHeader('x-content-type-options', 'nosniff')
        ->assertHeader('accept-ranges', 'bytes');

    $this->actingAs($lecturer->user)
        ->withHeaders(['Range' => 'bytes=0-8'])
        ->get(route('laporan.versi.preview', $version))
        ->assertStatus(206)
        ->assertHeader('content-range', 'bytes 0-8/'.strlen('%PDF-1.4 private report'))
        ->assertStreamedContent(substr('%PDF-1.4 private report', 0, 9));

    $this->actingAs($lecturer->user)
        ->withHeaders(['Range' => 'bytes='.strlen('%PDF-1.4 private report').'-'])
        ->get(route('laporan.versi.preview', $version))
        ->assertStatus(416)
        ->assertHeader('content-range', 'bytes */'.strlen('%PDF-1.4 private report'));

    $this->actingAs($otherLecturer->user)
        ->get($detailUrl)
        ->assertForbidden();

    $this->actingAs($otherLecturer->user)
        ->get(route('laporan.versi.preview', $version))
        ->assertForbidden();

    $this->actingAs($lecturer->user)
        ->get(ReportDetailsPage::getUrl(['report' => $report], panel: 'admin'))
        ->assertForbidden();

    Storage::disk('local')->delete($version->stored_path);

    $this->actingAs($lecturer->user)
        ->get(route('laporan.versi.preview', $version))
        ->assertNotFound();

    $this->actingAs($lecturer->user)
        ->get(route('laporan.versi.download', $version))
        ->assertNotFound();

    Storage::disk('local')->put($version->stored_path, '%PDF-1.4 short');

    $this->actingAs($lecturer->user)
        ->get(route('laporan.versi.preview', $version))
        ->assertNotFound();

    $this->actingAs($lecturer->user)
        ->get(route('laporan.versi.download', $version))
        ->assertNotFound();
});

test('lecturer report details show a recovery message when the physical upload is missing', function () {
    Storage::fake('local');
    $student = Student::factory()->create();
    $lecturer = Lecturer::factory()->create();
    SupervisorAssignment::factory()->create(['student_id' => $student->id, 'lecturer_id' => $lecturer->id]);
    $report = WeeklyReport::factory()->create([
        'report_period_id' => ReportPeriod::factory()->create([
            'internship_id' => Internship::factory()->create(['student_id' => $student->id]),
        ])->id,
    ]);
    $version = ReportVersion::factory()->create([
        'weekly_report_id' => $report->id,
        'uploaded_by' => $student->user_id,
    ]);

    $this->actingAs($lecturer->user)
        ->get(ReportDetailsPage::getUrl(['report' => $report], panel: 'dosen'))
        ->assertOk()
        ->assertSee('Berkas belum tersedia di penyimpanan. Hubungi administrator untuk pemulihan.')
        ->assertDontSee(route('laporan.versi.preview', $version), false)
        ->assertDontSee(route('laporan.versi.download', $version), false);
});

test('non-PDF reports cannot be opened in the inline preview', function () {
    Storage::fake('local');
    $student = Student::factory()->create();
    $lecturer = Lecturer::factory()->create();
    SupervisorAssignment::factory()->create(['student_id' => $student->id, 'lecturer_id' => $lecturer->id]);
    $report = WeeklyReport::factory()->create([
        'report_period_id' => ReportPeriod::factory()->create(['internship_id' => Internship::factory()->create(['student_id' => $student->id])])->id,
    ]);
    $version = ReportVersion::factory()->create([
        'weekly_report_id' => $report->id,
        'original_name' => 'laporan.docx',
        'stored_path' => 'reports/laporan.docx',
        'extension' => 'docx',
        'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'uploaded_by' => $student->user_id,
    ]);
    Storage::disk('local')->put($version->stored_path, 'private docx report');

    $this->actingAs($lecturer->user)
        ->get(route('laporan.versi.preview', $version))
        ->assertStatus(415);
});

test('example', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
