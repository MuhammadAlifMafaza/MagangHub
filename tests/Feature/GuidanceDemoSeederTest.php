<?php

use App\Models\Internship;
use App\Models\ReportPeriod;
use App\Models\ReportVersion;
use App\Models\Student;
use App\Models\WeeklyReport;
use Database\Seeders\GuidanceDemoSeeder;
use Illuminate\Support\Facades\Storage;

test('demo reports use valid PDFs and only legacy demo placeholders are repaired', function () {
    Storage::fake('local');
    $seeder = app(GuidanceDemoSeeder::class);
    $seeder->run();

    $demoStudent = Student::query()->whereHas('user', fn ($query) => $query->where('email', 'mahasiswa.demo@maganghub.test'))->firstOrFail();
    $demoVersions = ReportVersion::query()
        ->where('uploaded_by', $demoStudent->user_id)
        ->where('student_note', 'Data fiktif untuk demonstrasi lokal.')
        ->get();

    expect($demoVersions)->not->toBeEmpty();

    foreach ($demoVersions as $demoVersion) {
        $contents = Storage::disk('local')->get($demoVersion->stored_path);
        preg_match('/startxref\n(\d+)\n%%EOF/', $contents, $matches);

        expect($contents)->toStartWith("%PDF-1.4\n")
            ->and($matches)->toHaveKey(1)
            ->and(substr($contents, (int) $matches[1], 5))->toBe("xref\n")
            ->and($demoVersion->size_bytes)->toBe(strlen($contents))
            ->and($demoVersion->checksum_sha256)->toBe(hash('sha256', $contents));
    }

    $legacyVersion = $demoVersions->first();
    $legacyContents = "%PDF-1.4\nMagangHub demo report\n%%EOF\n";
    Storage::disk('local')->put($legacyVersion->stored_path, $legacyContents);
    $legacyVersion->update([
        'size_bytes' => strlen($legacyContents),
        'checksum_sha256' => hash('sha256', $legacyContents),
    ]);

    $internship = Internship::query()->where('student_id', $demoStudent->id)->firstOrFail();
    $inactivePeriod = ReportPeriod::query()
        ->where('internship_id', $internship->id)
        ->where('week_number', 10)
        ->firstOrFail();
    $inactiveReport = WeeklyReport::factory()->create(['report_period_id' => $inactivePeriod->id]);
    $inactiveLegacyPath = 'reports/'.$demoStudent->id.'/'.$inactivePeriod->id.'/legacy-demo-report.pdf';
    $inactiveLegacyVersion = ReportVersion::factory()->create([
        'weekly_report_id' => $inactiveReport->id,
        'stored_path' => $inactiveLegacyPath,
        'size_bytes' => strlen($legacyContents),
        'checksum_sha256' => hash('sha256', $legacyContents),
        'student_note' => 'Data fiktif untuk demonstrasi lokal.',
        'uploaded_by' => $demoStudent->user_id,
    ]);

    $userUploadContents = "%PDF-1.4\nKeep this student document unchanged.\n%%EOF\n";
    $userUploadPath = 'reports/'.$demoStudent->id.'/'.$legacyVersion->weeklyReport->report_period_id.'/student-upload.pdf';
    Storage::disk('local')->put($userUploadPath, $userUploadContents);
    $userVersion = ReportVersion::factory()->create([
        'weekly_report_id' => $legacyVersion->weekly_report_id,
        'version_number' => 2,
        'stored_path' => $userUploadPath,
        'size_bytes' => strlen($userUploadContents),
        'checksum_sha256' => hash('sha256', $userUploadContents),
        'student_note' => 'Catatan unggahan mahasiswa.',
        'uploaded_by' => $demoStudent->user_id,
    ]);

    $seeder->run();

    $repairedContents = Storage::disk('local')->get($legacyVersion->stored_path);
    preg_match('/startxref\n(\d+)\n%%EOF/', $repairedContents, $matches);
    $inactiveRepairedContents = Storage::disk('local')->get($inactiveLegacyPath);
    preg_match('/startxref\n(\d+)\n%%EOF/', $inactiveRepairedContents, $inactiveMatches);

    expect($repairedContents)->toStartWith("%PDF-1.4\n")
        ->and($matches)->toHaveKey(1)
        ->and(substr($repairedContents, (int) $matches[1], 5))->toBe("xref\n")
        ->and($legacyVersion->fresh()->size_bytes)->toBe(strlen($repairedContents))
        ->and($legacyVersion->fresh()->checksum_sha256)->toBe(hash('sha256', $repairedContents))
        ->and($inactiveRepairedContents)->toStartWith("%PDF-1.4\n")
        ->and($inactiveMatches)->toHaveKey(1)
        ->and(substr($inactiveRepairedContents, (int) $inactiveMatches[1], 5))->toBe("xref\n")
        ->and($inactiveLegacyVersion->fresh()->size_bytes)->toBe(strlen($inactiveRepairedContents))
        ->and($inactiveLegacyVersion->fresh()->checksum_sha256)->toBe(hash('sha256', $inactiveRepairedContents))
        ->and(Storage::disk('local')->get($userVersion->stored_path))->toBe($userUploadContents);
});
