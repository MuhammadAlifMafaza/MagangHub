<x-layouts::app.sidebar title="Antrean Laporan">
    <main class="mx-auto w-full max-w-6xl space-y-6 px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
        <header>
            <p class="text-sm font-semibold text-secondary-700">Panel dosen</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-ink sm:text-3xl">Laporan Perlu Diperiksa</h1>
            <p class="mt-2 text-sm leading-6 text-ink-muted">Tinjau laporan terbaru yang menunggu umpan balik Anda.</p>
        </header>

        <section aria-label="Daftar laporan menunggu pemeriksaan" class="space-y-3">
            @forelse ($reports as $report)
                <article class="flex flex-col gap-4 rounded-2xl border border-line bg-surface p-4 shadow-sm transition-shadow hover:shadow-md sm:flex-row sm:items-center sm:justify-between sm:p-5">
                    <div class="flex min-w-0 items-start gap-3">
                        <span aria-hidden="true" class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-700">
                            <flux:icon.document-text variant="outline" class="size-5" />
                        </span>
                        <div class="min-w-0">
                            <h2 class="font-semibold text-ink">{{ $report->reportPeriod->internship->student->user->name }}</h2>
                            <p class="mt-1 text-sm text-ink-muted">Minggu ke-{{ $report->reportPeriod->week_number }} <span aria-hidden="true">·</span> Menunggu sejak {{ $report->latestVersion->submitted_at->locale('id')->translatedFormat('d M Y, H:i') }} WIB</p>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-3 sm:justify-end">
                        <x-ui.status-badge label="Menunggu pemeriksaan" tone="warning" />
                        <a class="inline-flex min-h-10 shrink-0 items-center justify-center gap-2 rounded-lg bg-primary-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700" href="{{ route('laporan.show', $report) }}">
                            Periksa
                            <flux:icon.arrow-right variant="outline" class="size-4" />
                        </a>
                    </div>
                </article>
            @empty
                <div class="flex flex-col items-center rounded-2xl border border-dashed border-line-input bg-surface px-6 py-10 text-center">
                    <span aria-hidden="true" class="flex size-12 items-center justify-center rounded-xl bg-primary-50 text-primary-700">
                        <flux:icon.check-circle variant="outline" class="size-6" />
                    </span>
                    <h2 class="mt-4 font-semibold text-ink">Antrean sudah kosong</h2>
                    <p class="mt-1 max-w-md text-sm leading-6 text-ink-muted">Tidak ada laporan yang menunggu pemeriksaan saat ini.</p>
                </div>
            @endforelse
        </section>

        <div>{{ $reports->links() }}</div>
    </main>
</x-layouts::app.sidebar>
