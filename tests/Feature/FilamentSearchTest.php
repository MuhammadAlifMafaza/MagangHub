<?php

use App\Enums\UserRole;
use App\Filament\Pages\AdministrativeRecap;
use App\Filament\Resources\Internships\Schemas\InternshipForm;
use App\Filament\Resources\SupervisorAssignments\Schemas\SupervisorAssignmentForm;
use App\Models\Internship;
use App\Models\Lecturer;
use App\Models\ReportPeriod;
use App\Models\Student;
use App\Models\User;
use App\Models\WeeklyReport;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Livewire\Livewire;

function filamentSearchRecapTableHtml(string $html): string
{
    $labelPosition = strpos($html, 'aria-label="Daftar laporan"');

    if ($labelPosition === false) {
        return '';
    }

    $tableStart = strrpos(substr($html, 0, $labelPosition), '<table');
    $tableEnd = strpos($html, '</table>', $labelPosition);

    if ($tableStart === false || $tableEnd === false) {
        return '';
    }

    return substr($html, $tableStart, $tableEnd + strlen('</table>') - $tableStart);
}

function searchSelect(Schema $schema, string $name): Select
{
    $component = $schema->getComponent($name);

    expect($component)->toBeInstanceOf(Select::class);

    return $component;
}

function makeSearchableRecapPeriod(Student $student): void
{
    $internship = Internship::factory()->create(['student_id' => $student->id]);
    $period = ReportPeriod::factory()->create(['internship_id' => $internship->id]);

    WeeklyReport::factory()->create(['report_period_id' => $period->id]);
}

test('administrative recap searches student name and NIM and safely returns no rows for generated labels', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $student = Student::factory()->create([
        'user_id' => User::factory()->create(['name' => 'Searchable Recap Student'])->id,
        'nim' => 'NIM-SEARCH-7319',
    ]);
    $otherStudent = Student::factory()->create([
        'user_id' => User::factory()->create(['name' => 'Different Recap Student'])->id,
        'nim' => 'NIM-OTHER-8426',
    ]);

    makeSearchableRecapPeriod($student);
    makeSearchableRecapPeriod($otherStudent);

    $this->actingAs($admin);

    $byName = filamentSearchRecapTableHtml(Livewire::test(AdministrativeRecap::class)
        ->call('setReportScope', 'all')
        ->set('tableSearch', 'Searchable Recap Student')
        ->html());
    $byNim = filamentSearchRecapTableHtml(Livewire::test(AdministrativeRecap::class)
        ->call('setReportScope', 'all')
        ->set('tableSearch', 'NIM-SEARCH-7319')
        ->html());
    $generatedLabelComponent = Livewire::test(AdministrativeRecap::class)
        ->call('setReportScope', 'all')
        ->set('tableSearch', 'Minggu ke-1');
    $byGeneratedLabel = filamentSearchRecapTableHtml($generatedLabelComponent->html());
    $unknownTextComponent = Livewire::test(AdministrativeRecap::class)
        ->call('setReportScope', 'all')
        ->set('tableSearch', "' OR 1=1 --");
    $byUnknownText = filamentSearchRecapTableHtml($unknownTextComponent->html());

    $generatedLabelComponent->assertSee('Tidak ada laporan pada pilihan ini');
    $unknownTextComponent->assertSee('Tidak ada laporan pada pilihan ini');

    expect($byName)->toContain('Searchable Recap Student')
        ->and($byName)->not->toContain('Different Recap Student')
        ->and($byNim)->toContain('Searchable Recap Student')
        ->and($byNim)->not->toContain('Different Recap Student')
        ->and($byGeneratedLabel)->not->toContain('Searchable Recap Student')
        ->and($byUnknownText)->not->toContain('Searchable Recap Student');

    expect($byGeneratedLabel)->not->toContain('Different Recap Student')
        ->and($byUnknownText)->not->toContain('Different Recap Student');
});

test('administrative recap search remains constrained by selected recap filters', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $filteredStudent = Student::factory()->create([
        'user_id' => User::factory()->create(['name' => 'Filtered Recap Student'])->id,
    ]);
    $otherStudent = Student::factory()->create([
        'user_id' => User::factory()->create(['name' => 'Outside Filter Student'])->id,
    ]);

    makeSearchableRecapPeriod($filteredStudent);
    makeSearchableRecapPeriod($otherStudent);

    $this->actingAs($admin);

    $component = Livewire::test(AdministrativeRecap::class)
        ->call('setReportScope', 'all')
        ->set('studentFilter', (string) $filteredStudent->id)
        ->set('tableSearch', 'Outside Filter Student');
    $table = filamentSearchRecapTableHtml($component->html());

    $component->assertSee('Tidak ada laporan pada pilihan ini');

    expect($table)->not->toContain('Filtered Recap Student')
        ->and($table)->not->toContain('Outside Filter Student');
});

test('internship and supervisor assignment student searches only return matching active students', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

    $activeStudent = Student::factory()->create([
        'user_id' => User::factory()->create(['name' => 'Active Search Student'])->id,
        'nim' => 'NIM-ACTIVE-9101',
    ]);
    $inactiveStudent = Student::factory()->create([
        'user_id' => User::factory()->create(['name' => 'Inactive Search Student', 'is_active' => false])->id,
    ]);
    $wrongRoleStudent = Student::factory()->create([
        'user_id' => User::factory()->create(['name' => 'Wrong Role Search Student', 'role' => UserRole::Lecturer])->id,
    ]);

    $livewire = Livewire::test(AdministrativeRecap::class)->instance();
    $schema = Schema::make($livewire);
    $internshipSelect = searchSelect(InternshipForm::configure(Schema::make($livewire)), 'student_id');
    $assignmentSelect = searchSelect(SupervisorAssignmentForm::configure($schema), 'student_id');
    $internshipMatches = $internshipSelect->getSearchResults('Search Student');
    $assignmentMatches = $assignmentSelect->getSearchResults('Search Student');

    expect($internshipMatches)->toHaveKey($activeStudent->id)
        ->and($internshipMatches)->not->toHaveKey($inactiveStudent->id)
        ->and($internshipMatches)->not->toHaveKey($wrongRoleStudent->id)
        ->and($assignmentMatches)->toHaveKey($activeStudent->id)
        ->and($internshipSelect->getSearchResults('NIM-ACTIVE-9101'))->toHaveKey($activeStudent->id)
        ->and($internshipSelect->getSearchResults('Belum dinilai'))->toBe([])
        ->and($assignmentSelect->getSearchResults('unknown student keyword'))->toBe([])
        ->and($internshipSelect->getSearchResults(''))->toBe([]);

    foreach (range(1, 51) as $index) {
        Student::factory()->create([
            'user_id' => User::factory()->create(['name' => "Capacity Candidate {$index}"])->id,
        ]);
    }

    expect($internshipSelect->getSearchResults('Capacity Candidate'))->toHaveCount(50);
});

test('supervisor assignment lecturer search only returns matching active lecturer accounts', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

    $activeLecturer = Lecturer::factory()->create([
        'user_id' => User::factory()->create(['name' => 'Available Search Lecturer', 'role' => UserRole::Lecturer])->id,
    ]);
    Lecturer::factory()->create([
        'user_id' => User::factory()->create([
            'name' => 'Inactive Search Lecturer',
            'role' => UserRole::Lecturer,
            'is_active' => false,
        ])->id,
    ]);
    Lecturer::factory()->create([
        'user_id' => User::factory()->create(['name' => 'Wrong Role Search Lecturer', 'role' => UserRole::Student])->id,
    ]);

    $livewire = Livewire::test(AdministrativeRecap::class)->instance();
    $lecturerSelect = searchSelect(SupervisorAssignmentForm::configure(Schema::make($livewire)), 'lecturer_id');
    $matches = $lecturerSelect->getSearchResults('Search Lecturer');

    expect($matches)->toHaveKey($activeLecturer->id)
        ->and($matches)->toHaveCount(1)
        ->and($lecturerSelect->getSearchResults('Minggu ke-2'))->toBe([]);
});
