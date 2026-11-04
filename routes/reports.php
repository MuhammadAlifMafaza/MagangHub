<?php

use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/mahasiswa/laporan', [ReportController::class, 'periods'])->name('mahasiswa.laporan.index');
    Route::post('/mahasiswa/laporan/periode/{period}', [ReportController::class, 'submit'])->name('mahasiswa.laporan.store');
    Route::get('/laporan/{report}', [ReportController::class, 'show'])->name('laporan.show');
    Route::get('/laporan/versi/{version}/unduh', [ReportController::class, 'download'])->name('laporan.versi.download');
    Route::get('/dosen/laporan/antrean', [ReportController::class, 'lecturerQueue'])->name('dosen.laporan.antrean');
    Route::post('/dosen/laporan/{report}/pemeriksaan', [ReportController::class, 'review'])->name('dosen.laporan.review');
    Route::put('/dosen/laporan/{report}/penilaian', [ReportController::class, 'saveAssessment'])->name('dosen.laporan.penilaian.update');
    Route::post('/dosen/penilaian/{assessment}/publikasi', [ReportController::class, 'publishAssessment'])->name('dosen.penilaian.publikasi');
});
