<?php

use App\Livewire\StudentGuidance;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'student.account'])->group(function (): void {
    Route::livewire('/bimbingan', StudentGuidance::class)->name('guidance.student');
});
