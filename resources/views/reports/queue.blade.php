<x-layouts::app.sidebar title="Antrean Laporan">
<flux:main><main class="mx-auto max-w-6xl space-y-6 px-4 py-8" id="main-content"><h1 class="text-2xl font-semibold text-ink">Laporan Perlu Diperiksa</h1>
    @forelse($reports as $report)<article class="flex flex-wrap items-center justify-between gap-4 rounded-lg border border-line bg-surface p-4 shadow-sm"><div><h2 class="font-semibold text-ink">{{ $report->reportPeriod->internship->student->user->name }} · Minggu ke-{{ $report->reportPeriod->week_number }}</h2><p class="text-sm text-ink-muted">Menunggu sejak {{ $report->latestVersion->submitted_at->translatedFormat('d M Y, H:i') }} WIB</p></div><a class="min-h-11 rounded-lg bg-primary-700 px-4 py-3 text-white hover:bg-primary-800" href="{{ route('laporan.show', $report) }}">Periksa laporan</a></article>
    @empty <p class="rounded-lg border border-line bg-surface p-6 text-ink-muted">Tidak ada laporan yang menunggu pemeriksaan.</p>@endforelse
    {{ $reports->links() }}
</main></flux:main></x-layouts::app.sidebar>
