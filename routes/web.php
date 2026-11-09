<?php

use App\Http\Controllers\Auth\PasswordResetOtpController;
use App\Http\Controllers\StudentDashboardController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('guest')->prefix('password/otp')->name('password.otp.')->group(function () {
    Route::post('/', [PasswordResetOtpController::class, 'store'])->middleware('throttle:5,1')->name('send');
    Route::get('/verify', [PasswordResetOtpController::class, 'create'])->name('verify');
    Route::post('/reset', [PasswordResetOtpController::class, 'reset'])->middleware('throttle:10,1')->name('reset');
});

Route::middleware(['auth', 'student.account'])->prefix('mahasiswa')->group(function () {
    Route::get('/', StudentDashboardController::class)->name('dashboard');
});

Route::redirect('/dashboard', '/mahasiswa')->name('dashboard.legacy');

require __DIR__.'/settings.php';
require __DIR__.'/guidance.php';
require __DIR__.'/reports.php';
