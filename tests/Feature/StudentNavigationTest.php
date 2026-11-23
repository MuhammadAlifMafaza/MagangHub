<?php

use App\Models\Student;

test('student sidebar orders work areas first and keeps notifications and account actions at the bottom', function () {
    $student = Student::factory()->create();

    $this->actingAs($student->user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeInOrder([
            'Dashboard',
            'Jadwal Bimbingan',
            'Laporan Mingguan',
            'Notifikasi',
            'Profil',
            'Pengaturan',
            'Keluar',
        ]);
});
