<x-layouts::app.sidebar title="Laporan Mingguan">
    @php
        $today = today(config('magang.timezone'));
        $currentPeriods = $periods->filter(fn ($period) => $period->starts_on->lessThanOrEqualTo($today) && $period->ends_on->greaterThanOrEqualTo($today));
        $pastPeriods = $periods->reject(fn ($period) => $currentPeriods->contains('id', $period->id))
            ->filter(fn ($period) => $period->ends_on->lessThan($today))
            ->sortByDesc('starts_on');
        $futurePeriods = $periods->filter(fn ($period) => $period->starts_on->greaterThan($today))
            ->sortBy('starts_on');
        $periodsForDisplay = $currentPeriods->concat($pastPeriods)->concat($futurePeriods);
        $notSubmittedCount = $periods->filter(fn ($period) => in_array($period->collectionStatus()->value, ['not_submitted', 'not_submitted_late'], true))->count();
        $pendingReviewCount = $periods->filter(fn ($period) => $period->weeklyReport?->review_status->value === 'pending_review')->count();
        $revisionCount = $periods->filter(fn ($period) => $period->weeklyReport?->review_status->value === 'revision_requested')->count();
        $currentPeriod = $currentPeriods->first();
    @endphp

        <main class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-semibold text-primary-700">Progres magang</p>
                    <h1 class="mt-1 text-2xl font-semibold tracking-tight text-ink sm:text-3xl">Laporan mingguan</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-muted">Kirim laporan sesuai periode, pantau status pemeriksaan, dan lanjutkan revisi bila diperlukan.</p>
                </div>
                <p class="text-sm text-ink-muted">PDF atau DOCX · Maksimum 10 MB</p>
            </header>

            @if (session('status'))
                <p role="status" class="flex items-start gap-3 rounded-xl border border-success/20 bg-success-bg p-4 text-sm text-success"><flux:icon.check-circle variant="outline" class="size-5 shrink-0" />{{ session('status') }}</p>
            @endif

            <section aria-label="Ringkasan progres laporan" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-2xl border border-line bg-surface p-4 shadow-sm">
                    <p class="text-sm text-ink-muted">Total periode</p>
                    <p class="mt-2 text-2xl font-semibold tabular-nums text-ink">{{ $periods->count() }}</p>
                </article>
                <article class="rounded-2xl border border-line bg-surface p-4 shadow-sm">
                    <p class="text-sm text-ink-muted">Belum dikirim</p>
                    <p class="mt-2 text-2xl font-semibold tabular-nums text-warning">{{ $notSubmittedCount }}</p>
                </article>
                <article class="rounded-2xl border border-line bg-surface p-4 shadow-sm">
                    <p class="text-sm text-ink-muted">Menunggu pemeriksaan</p>
                    <p class="mt-2 text-2xl font-semibold tabular-nums text-info">{{ $pendingReviewCount }}</p>
                </article>
                <article class="rounded-2xl border border-line bg-surface p-4 shadow-sm">
                    <p class="text-sm text-ink-muted">Perlu revisi</p>
                    <p class="mt-2 text-2xl font-semibold tabular-nums text-danger">{{ $revisionCount }}</p>
                </article>
            </section>

            @if ($currentPeriod)
                <a href="#period-{{ $currentPeriod->id }}" class="flex min-h-12 items-center justify-between gap-3 rounded-xl border border-primary-100 bg-primary-50/70 px-4 py-3 text-sm text-primary-800 transition hover:bg-primary-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 dark:text-ink">
                    <span><span class="font-semibold">Periode berjalan: minggu ke-{{ $currentPeriod->week_number }}</span><span class="hidden sm:inline"> · {{ $currentPeriod->starts_on->locale('id')->translatedFormat('d M') }}–{{ $currentPeriod->ends_on->locale('id')->translatedFormat('d M Y') }}</span></span>
                    <span class="inline-flex shrink-0 items-center gap-2 font-semibold">Buka periode <flux:icon.arrow-down variant="outline" class="size-4" /></span>
                </a>
            @endif

            <section aria-label="Daftar periode laporan" class="flex flex-col gap-4">
                @forelse ($periodsForDisplay as $period)
                    @php
                        $report = $period->weeklyReport;
                        $latest = $report?->latestVersion;
                        $collectionStatus = $period->collectionStatus();
                        $isCurrentPeriod = $currentPeriod?->id === $period->id;
                        $showPeriodErrors = (string) old('period_id') === (string) $period->id;
                        $hasRevisionRequest = $report?->review_status->value === 'revision_requested';
                        $canUpload = $report
                            ? ($report->review_status->value === 'revision_requested' || ($report->review_status->value === 'pending_review' && ! $latest?->review))
                            : $period->starts_on->isPast();
                    @endphp
                    <details id="period-{{ $period->id }}" @if ($isCurrentPeriod || $hasRevisionRequest || $showPeriodErrors) open @endif class="group overflow-hidden rounded-2xl border border-line bg-surface shadow-sm shadow-primary-800/[0.03]">
                        <summary class="flex cursor-pointer list-none flex-col gap-3 bg-linear-to-r from-primary-50/80 to-surface p-4 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 sm:flex-row sm:items-center sm:justify-between sm:p-5 [&::-webkit-details-marker]:hidden">
                            <div class="flex min-w-0 items-start gap-3">
                                <div class="min-w-0">
                                    <h2 class="text-base font-semibold text-ink">Minggu ke-{{ $period->week_number }} @if ($isCurrentPeriod)<span class="ml-1 rounded-full bg-primary-100 px-2 py-0.5 align-middle text-xs font-semibold text-primary-800 dark:text-ink">Berjalan</span>@endif</h2>
                                    <p class="mt-1 text-sm text-ink-muted">{{ $period->starts_on->locale('id')->translatedFormat('d M Y') }}–{{ $period->ends_on->locale('id')->translatedFormat('d M Y') }} <span class="hidden sm:inline">· Batas {{ $period->due_at->locale('id')->translatedFormat('d M Y, H:i') }} WIB</span></p>
                                </div>
                            </div>
                            <span class="flex shrink-0 items-center justify-between gap-3 sm:justify-end">
                                <x-ui.status-badge :label="$collectionStatus->label()" :tone="$collectionStatus->color()" />
                                <flux:icon.chevron-down variant="outline" class="size-4 text-ink-muted transition-transform group-open:rotate-180" />
                            </span>
                        </summary>

                        <div class="flex flex-col gap-5 border-t border-line p-4 sm:p-5">
                            @if ($report)
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-2 text-sm">
                                    <span class="font-medium text-ink-muted">Pemeriksaan</span>
                                    <span class="font-semibold text-ink">{{ $report->review_status->label() }}</span>
                                    <span class="hidden text-line sm:inline" aria-hidden="true">|</span>
                                    <a class="inline-flex min-h-10 items-center gap-2 font-semibold text-primary-700 underline decoration-primary-700/40 underline-offset-4 hover:text-primary-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700" href="{{ route('laporan.show', $report) }}"><flux:icon.clock variant="outline" class="size-4" />Riwayat, umpan balik, dan berkas</a>
                                </div>

                                @if ($report->assessment?->status?->value === 'published')
                                    <section aria-label="Hasil penilaian" class="flex flex-col gap-3 rounded-xl border border-success/20 bg-success-bg p-4 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="flex items-center gap-3"><span aria-hidden="true" class="flex size-10 items-center justify-center rounded-lg bg-surface/80 text-success"><flux:icon.trophy variant="outline" class="size-5" /></span><div><h3 class="font-semibold text-success">Nilai telah dipublikasikan</h3><p class="text-sm text-success/90">Minggu ke-{{ $period->week_number }}</p></div></div>
                                        <p class="text-2xl font-semibold tabular-nums text-success">{{ number_format((float) $report->assessment->score, 2, ',', '.') }}<span class="ml-1 text-sm font-medium">/ 100</span></p>
                                        @if ($report->assessment->notes)<p class="basis-full border-t border-success/20 pt-3 text-sm leading-6 text-success">{{ $report->assessment->notes }}</p>@endif
                                    </section>
                                @elseif ($report->assessment)
                                    <p class="flex items-center gap-2 rounded-lg bg-neutral-bg px-4 py-3 text-sm text-neutral"><flux:icon.clock variant="outline" class="size-4" />Penilaian sedang disiapkan dan akan tampil setelah dipublikasikan.</p>
                                @endif
                            @endif

                            @if ($canUpload)
                                <form method="POST" enctype="multipart/form-data" action="{{ route('mahasiswa.laporan.store', $period) }}" class="flex flex-col gap-4 rounded-2xl border border-primary-100 bg-primary-50/50 p-4 sm:p-5">
                                    @csrf
                                    <input type="hidden" name="period_id" value="{{ $period->id }}">
                                    <div class="flex items-start gap-3"><span aria-hidden="true" class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-700"><flux:icon.cloud-arrow-up variant="outline" class="size-5" /></span><div><h3 class="font-semibold text-ink">{{ $report ? ($report->review_status->value === 'revision_requested' ? 'Unggah revisi laporan' : 'Perbarui berkas laporan') : 'Kirim laporan minggu ini' }}</h3><p class="mt-1 text-sm leading-6 text-ink-muted">Pilih dokumen PDF atau DOCX. Ukuran berkas maksimal 10 MB.</p></div></div>
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div class="flex flex-col gap-2">
                                            <label for="file-{{ $period->id }}" class="text-sm font-medium text-ink">Berkas laporan <span class="text-danger" aria-hidden="true">*</span></label>
                                            <input id="file-{{ $period->id }}" name="file" type="file" accept=".pdf,.docx" required aria-describedby="file-help-{{ $period->id }} @if ($showPeriodErrors && $errors->has('file')) file-error-{{ $period->id }} @endif" @if ($showPeriodErrors && $errors->has('file')) aria-invalid="true" @endif class="block min-h-11 w-full rounded-lg border border-line-input bg-surface text-sm text-ink file:me-3 file:min-h-11 file:border-0 file:bg-primary-50 file:px-3 file:font-semibold file:text-primary-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700">
                                            <p id="file-help-{{ $period->id }}" class="text-xs text-ink-muted">Format yang diterima: PDF, DOCX.</p>
                                            @if ($showPeriodErrors)
                                                @error('file')<p id="file-error-{{ $period->id }}" role="alert" class="flex items-center gap-1.5 text-sm text-danger"><flux:icon.exclamation-circle variant="outline" class="size-4 shrink-0" />{{ $message }}</p>@enderror
                                            @endif
                                        </div>
                                        <div class="flex flex-col gap-2">
                                            <label for="note-{{ $period->id }}" class="text-sm font-medium text-ink">Catatan untuk dosen <span class="font-normal text-ink-muted">(opsional)</span></label>
                                            <textarea id="note-{{ $period->id }}" name="student_note" rows="3" placeholder="Tambahkan konteks singkat tentang laporan atau revisi ini" aria-describedby="note-help-{{ $period->id }} @if ($showPeriodErrors && $errors->has('student_note')) note-error-{{ $period->id }} @endif" @if ($showPeriodErrors && $errors->has('student_note')) aria-invalid="true" @endif class="w-full rounded-lg border border-line-input bg-surface px-3 py-2.5 text-sm text-ink placeholder:text-ink-muted/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700">{{ $showPeriodErrors ? old('student_note') : '' }}</textarea>
                                            <p id="note-help-{{ $period->id }}" class="text-xs text-ink-muted">Maksimal 5.000 karakter.</p>
                                            @if ($showPeriodErrors)
                                                @error('student_note')<p id="note-error-{{ $period->id }}" role="alert" class="text-sm text-danger">{{ $message }}</p>@enderror
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex flex-col-reverse gap-3 border-t border-line pt-4 sm:flex-row sm:items-center sm:justify-between">
                                        <p class="text-xs leading-5 text-ink-muted">Pastikan dokumen yang dipilih adalah versi yang benar sebelum dikirim.</p>
                                        <button type="submit" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-primary-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700"><flux:icon.paper-airplane variant="outline" class="size-4" />{{ $report ? 'Unggah versi baru' : 'Kirim laporan' }}</button>
                                    </div>
                                </form>
                            @elseif (! $report)
                                <p class="flex items-start gap-3 rounded-lg bg-neutral-bg p-4 text-sm leading-6 text-neutral"><flux:icon.lock-closed variant="outline" class="mt-0.5 size-4 shrink-0" />Periode ini belum dibuka. Form pengumpulan akan tersedia saat periode dimulai.</p>
                            @endif
                        </div>
                    </details>
                @empty
                    <div class="flex flex-col items-center rounded-lg border border-dashed border-line-input bg-surface px-6 py-10 text-center">
                        <h2 class="text-base font-semibold text-ink">Belum ada periode laporan</h2>
                        <p class="mt-2 max-w-md text-sm leading-6 text-ink-muted">Periode pelaporan akan muncul di sini setelah data magang Anda tersedia.</p>
                    </div>
                @endforelse
            </section>
        </main>
</x-layouts::app.sidebar>
