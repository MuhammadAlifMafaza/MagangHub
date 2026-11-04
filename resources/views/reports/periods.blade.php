<x-layouts::app.sidebar title="Laporan Mingguan">
    <flux:main>
        <main class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex items-start gap-4">
                    <div><p class="text-sm font-semibold text-primary-700">Progres magang</p><h1 class="mt-1 text-2xl font-semibold tracking-tight text-ink sm:text-3xl">Laporan mingguan</h1><p class="mt-2 max-w-2xl text-sm leading-6 text-ink-muted">Kirim laporan sesuai periode, pantau status pemeriksaan, dan lanjutkan revisi bila diperlukan.</p></div>
                </div>
                <p class="text-sm text-ink-muted">PDF atau DOCX · Maksimum 10 MB</p>
            </header>

            @if (session('status'))
                <p role="status" class="flex items-start gap-3 rounded-xl border border-success/20 bg-success-bg p-4 text-sm text-success"><flux:icon.check-circle variant="outline" class="size-5 shrink-0" />{{ session('status') }}</p>
            @endif

            <section aria-label="Daftar periode laporan" class="flex flex-col gap-4">
                @forelse ($periods as $period)
                    @php
                        $report = $period->weeklyReport;
                        $latest = $report?->latestVersion;
                        $collectionStatus = $period->collectionStatus();
                        $canUpload = $report
                            ? ($report->review_status->value === 'revision_requested' || ($report->review_status->value === 'pending_review' && ! $latest?->review))
                            : $period->starts_on->isPast();
                    @endphp
                    <article class="overflow-hidden rounded-2xl border border-line bg-surface shadow-sm shadow-primary-800/[0.03]">
                        <div class="flex flex-col gap-4 border-b border-line bg-linear-to-r from-primary-50/80 to-surface p-4 sm:flex-row sm:items-start sm:justify-between sm:p-5">
                            <div class="flex items-start gap-4">
                                <div><h2 class="text-base font-semibold text-ink">Minggu ke-{{ $period->week_number }}</h2><p class="mt-1 text-sm text-ink-muted">{{ $period->starts_on->locale('id')->translatedFormat('d M Y') }}–{{ $period->ends_on->locale('id')->translatedFormat('d M Y') }}</p><p class="mt-1 text-sm text-ink-muted">Batas pengumpulan {{ $period->due_at->locale('id')->translatedFormat('d M Y, H:i') }} WIB</p></div>
                            </div>
                            <x-ui.status-badge :label="$collectionStatus->label()" :tone="$collectionStatus->color()" />
                        </div>

                        <div class="flex flex-col gap-5 p-4 sm:p-5">
                            @if ($report)
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-2 text-sm">
                                    <span class="font-medium text-ink-muted">Pemeriksaan</span>
                                    <span class="font-semibold text-ink">{{ $report->review_status->label() }}</span>
                                    <span class="hidden text-line sm:inline" aria-hidden="true">|</span>
                                    <a class="inline-flex min-h-10 items-center gap-2 font-semibold text-primary-700 underline decoration-primary-700/40 underline-offset-4 hover:text-primary-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700" href="{{ route('laporan.show', $report) }}"><flux:icon.clock variant="outline" class="size-4" />Riwayat, umpan balik, dan berkas</a>
                                </div>

                                @if ($report->assessment?->status?->value === 'published')
                                    <section aria-label="Hasil penilaian" class="flex flex-col gap-3 rounded-xl border border-success/20 bg-success-bg p-4 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="flex items-center gap-3"><span aria-hidden="true" class="flex size-10 items-center justify-center rounded-lg bg-white/70 text-success"><flux:icon.trophy variant="outline" class="size-5" /></span><div><h3 class="font-semibold text-success">Nilai telah dipublikasikan</h3><p class="text-sm text-success/90">Minggu ke-{{ $period->week_number }}</p></div></div>
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
                                    <div class="flex items-start gap-3"><span aria-hidden="true" class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-700"><flux:icon.cloud-arrow-up variant="outline" class="size-5" /></span><div><h3 class="font-semibold text-ink">{{ $report ? ($report->review_status->value === 'revision_requested' ? 'Unggah revisi laporan' : 'Perbarui berkas laporan') : 'Kirim laporan minggu ini' }}</h3><p class="mt-1 text-sm leading-6 text-ink-muted">Pilih dokumen PDF atau DOCX. Ukuran berkas maksimal 10 MB.</p></div></div>
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div class="flex flex-col gap-2">
                                            <label for="file-{{ $period->id }}" class="text-sm font-medium text-ink">Berkas laporan <span class="text-danger" aria-hidden="true">*</span></label>
                                            <input id="file-{{ $period->id }}" name="file" type="file" accept=".pdf,.docx" required aria-describedby="file-help-{{ $period->id }} @error('file') file-error-{{ $period->id }} @enderror" class="block min-h-11 w-full rounded-lg border border-line-input bg-surface text-sm text-ink file:me-3 file:min-h-11 file:border-0 file:bg-primary-50 file:px-3 file:font-semibold file:text-primary-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700">
                                            <p id="file-help-{{ $period->id }}" class="text-xs text-ink-muted">Format yang diterima: PDF, DOCX.</p>
                                            @error('file')<p id="file-error-{{ $period->id }}" role="alert" class="flex items-center gap-1.5 text-sm text-danger"><flux:icon.exclamation-circle variant="outline" class="size-4 shrink-0" />{{ $message }}</p>@enderror
                                        </div>
                                        <div class="flex flex-col gap-2">
                                            <label for="note-{{ $period->id }}" class="text-sm font-medium text-ink">Catatan untuk dosen <span class="font-normal text-ink-muted">(opsional)</span></label>
                                            <textarea id="note-{{ $period->id }}" name="student_note" rows="3" placeholder="Tambahkan konteks singkat tentang laporan atau revisi ini" class="w-full rounded-lg border border-line-input bg-surface px-3 py-2.5 text-sm text-ink placeholder:text-ink-muted/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700">{{ old('student_note') }}</textarea>
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
                    </article>
                @empty
                    <div class="flex flex-col items-center rounded-lg border border-dashed border-line-input bg-surface px-6 py-10 text-center">
                        <h2 class="text-base font-semibold text-ink">Belum ada periode laporan</h2>
                        <p class="mt-2 max-w-md text-sm leading-6 text-ink-muted">Periode pelaporan akan muncul di sini setelah data magang Anda tersedia.</p>
                    </div>
                @endforelse
            </section>
        </main>
    </flux:main>
</x-layouts::app.sidebar>
