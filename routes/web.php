<?php

use App\Http\Controllers\StudentDashboardController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'student.account'])->prefix('mahasiswa')->group(function () {
    Route::get('/', StudentDashboardController::class)->name('dashboard');
});

Route::redirect('/dashboard', '/mahasiswa')->name('dashboard.legacy');

require __DIR__ . '/settings.php';
require __DIR__ . '/guidance.php';
require __DIR__ . '/reports.php';
