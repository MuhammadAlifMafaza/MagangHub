<?php

use App\Http\Controllers\Auth\PasswordResetOtpController;
use App\Http\Controllers\StudentDashboardController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('guest')->group(function () {
    Route::post('/password/otp/{audience}', [PasswordResetOtpController::class, 'store'])
        ->whereIn('audience', ['student', 'staff'])
        ->middleware('throttle:5,1')
        ->name('password.otp.send');
    Route::get('/password/otp/{audience}/verify', [PasswordResetOtpController::class, 'create'])
        ->whereIn('audience', ['student', 'staff'])
        ->name('password.otp.verify');
    Route::post('/password/otp/{audience}/reset', [PasswordResetOtpController::class, 'reset'])
        ->whereIn('audience', ['student', 'staff'])
        ->middleware('throttle:10,1')
        ->name('password.otp.reset');
});

Route::middleware(['auth', 'student.account'])->prefix('mahasiswa')->group(function () {
    Route::get('/', StudentDashboardController::class)->name('dashboard');
});

Route::redirect('/dashboard', '/mahasiswa')->name('dashboard.legacy');

require __DIR__.'/settings.php';
require __DIR__.'/guidance.php';
require __DIR__.'/reports.php';
