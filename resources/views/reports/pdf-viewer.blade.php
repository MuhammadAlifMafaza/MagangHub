<!DOCTYPE html>
<html lang="id">
    <head>
        @include('partials.head', ['title' => 'Baca laporan'])
    </head>
    <body class="min-h-dvh bg-bg text-ink antialiased">
        @php
            $report = $version->weeklyReport;
            $backUrl = auth()->user()->role === \App\Enums\UserRole::Student
                ? route('mahasiswa.laporan.show', $report)
                : route('laporan.show', $report);
        @endphp

        <header class="flex flex-wrap items-center gap-4 border-b border-line bg-surface px-4 py-3 sm:px-6">
            <a href="{{ $backUrl }}" class="inline-flex min-h-10 items-center gap-2 rounded-lg px-3 text-sm font-semibold text-primary-700 hover:bg-primary-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700">
                <flux:icon.arrow-left variant="outline" class="size-4" />
                Kembali ke laporan
            </a>
            <div class="min-w-0 flex-1">
                <h1 class="truncate text-sm font-semibold sm:text-base">Laporan minggu ke-{{ $report->reportPeriod->week_number }}</h1>
                <p class="truncate text-xs text-ink-muted">{{ $version->original_name }} · Versi {{ $version->version_number }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('laporan.versi.viewer', $version) }}" target="_blank" rel="noopener" class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-line-input px-3 text-sm font-semibold hover:bg-bg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700">
                    <flux:icon.arrow-top-right-on-square variant="outline" class="size-4" />
                    Buka tab baru
                </a>
                <a href="{{ route('laporan.versi.download', $version) }}" class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-primary-700 px-3 text-sm font-semibold text-white hover:bg-primary-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700">
                    <flux:icon.arrow-down-tray variant="outline" class="size-4" />
                    Unduh
                </a>
            </div>
        </header>

        <main class="mx-auto h-[calc(100dvh-4.5rem)] max-w-[1600px] p-2 sm:p-4">
            <div id="pdf-loading" role="status" aria-live="polite" class="flex h-full items-center justify-center rounded-xl border border-line bg-surface p-6 text-center text-sm text-ink-muted">
                Memuat dokumen PDF…
            </div>
            <iframe
                id="pdf-frame"
                title="Pratinjau PDF {{ $version->original_name }}"
                class="hidden h-full w-full rounded-xl border border-line bg-white"
            ></iframe>
            <div id="pdf-error" class="hidden h-full items-center justify-center rounded-xl border border-line bg-surface p-6 text-center">
                <div>
                    <p class="font-semibold">Pratinjau PDF tidak dapat dimuat.</p>
                    <p class="mt-2 text-sm text-ink-muted">Coba muat ulang halaman atau unduh dokumen untuk membukanya di aplikasi PDF.</p>
                    <a href="{{ route('laporan.versi.download', $version) }}" class="mt-4 inline-flex min-h-10 items-center justify-center rounded-lg bg-primary-700 px-4 text-sm font-semibold text-white">Unduh PDF</a>
                </div>
            </div>
        </main>

        <script>
            const pdfFrame = document.getElementById('pdf-frame');
            const pdfLoading = document.getElementById('pdf-loading');
            const pdfError = document.getElementById('pdf-error');

            fetch(@json(route('laporan.versi.preview', $version)), { credentials: 'same-origin' })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('PDF request failed.');
                    }

                    return response.arrayBuffer();
                })
                .then((bytes) => {
                    const pdfUrl = URL.createObjectURL(new Blob([bytes], { type: 'application/pdf' }));
                    pdfFrame.src = pdfUrl;
                    pdfFrame.classList.remove('hidden');
                    pdfLoading.classList.add('hidden');
                })
                .catch(() => {
                    pdfLoading.classList.add('hidden');
                    pdfError.classList.remove('hidden');
                    pdfError.classList.add('flex');
                });
        </script>
    </body>
</html>
