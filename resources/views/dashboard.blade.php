<x-layouts::app :title="__('Dashboard')">
    @php
        $collectionStatus = $currentPeriod?->collectionStatus();
    @endphp

    <main class="mx-auto w-full max-w-6xl space-y-6 px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
        <header class="space-y-1">
            <p class="text-sm font-medium text-secondary-700">Portal mahasiswa</p>
            <h1 class="text-2xl font-semibold tracking-tight text-ink">Dashboard Mahasiswa</h1>
            <p class="text-sm text-ink-muted">Selamat datang, {{ auth()->user()->name }}. Berikut ringkasan progres magang Anda.</p>
        </header>

        <section aria-label="Ringkasan magang" class="grid gap-4 md:grid-cols-2">
            <article class="rounded-xl border border-line bg-surface p-5 shadow-sm sm:p-6">
                <div class="flex items-center gap-3">
                    <span aria-hidden="true" class="flex size-10 items-center justify-center rounded-lg bg-primary-50 text-primary-700"><flux:icon.user-group variant="outline" class="size-5" /></span>
                    <h2 class="font-semibold text-ink">Dosen Pembimbing Aktif</h2>
                </div>
                @if ($activeAssignment)
                    <p class="mt-4 text-lg font-semibold text-ink">{{ $activeAssignment->lecturer->user->name }}</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ $activeAssignment->lecturer->department ?: 'Departemen belum diisi' }}</p>
                @else
                    <p class="mt-4 text-sm leading-6 text-ink-muted">Belum ada dosen pembimbing aktif. Hubungi administrator untuk informasi lebih lanjut.</p>
                @endif
            </article>

            <article class="rounded-xl border border-line bg-surface p-5 shadow-sm sm:p-6">
                <div class="flex items-center gap-3">
                    <span aria-hidden="true" class="flex size-10 items-center justify-center rounded-lg bg-primary-50 text-primary-700"><flux:icon.calendar-days variant="outline" class="size-5" /></span>
                    <h2 class="font-semibold text-ink">Periode Pelaporan Berjalan</h2>
                </div>
                @if ($currentPeriod)
                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <p class="text-lg font-semibold text-ink">Minggu ke-{{ $currentPeriod->week_number }}</p>
                        <x-ui.status-badge :label="$collectionStatus->label()" :tone="$collectionStatus->color()" />
                    </div>
                    <p class="mt-1 text-sm text-ink-muted">{{ $currentPeriod->starts_on->locale('id')->translatedFormat('d M Y') }}–{{ $currentPeriod->ends_on->locale('id')->translatedFormat('d M Y') }}</p>
                    <p class="mt-1 text-sm text-ink-muted">Batas pengumpulan {{ $currentPeriod->due_at->locale('id')->translatedFormat('d M Y, H:i') }} WIB</p>
                    <a href="{{ $currentPeriod->weeklyReport ? route('laporan.show', $currentPeriod->weeklyReport) : route('mahasiswa.laporan.index') }}" class="mt-4 inline-flex min-h-10 items-center gap-2 text-sm font-semibold text-primary-700 underline underline-offset-4 hover:text-primary-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700">
                        {{ $currentPeriod->weeklyReport ? 'Lihat laporan periode ini' : 'Buka daftar periode' }}
                        <flux:icon.arrow-right variant="outline" class="size-4" />
                    </a>
                @else
                    <p class="mt-4 text-sm leading-6 text-ink-muted">{{ $activeAssignment ? 'Belum ada periode pelaporan yang sedang berjalan.' : 'Periode akan tampil setelah data magang tersedia.' }}</p>
                @endif
            </article>
        </section>

        <section class="grid gap-4 lg:grid-cols-2">
            <article class="rounded-xl border border-line bg-surface shadow-sm">
                <header class="flex items-center justify-between gap-3 border-b border-line px-5 py-4 sm:px-6">
                    <div>
                        <h2 class="font-semibold text-ink">Laporan Perlu Direvisi</h2>
                        <p class="mt-1 text-sm text-ink-muted">Laporan yang menunggu tindak lanjut Anda.</p>
                    </div>
                    <span class="rounded-full bg-neutral-bg px-2.5 py-1 text-xs font-semibold text-neutral">{{ $reportsNeedingRevision->count() }}</span>
                </header>
                <div class="divide-y divide-line px-5 sm:px-6">
                    @forelse ($reportsNeedingRevision as $report)
                        <div class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-medium text-ink">Laporan · Minggu ke-{{ $report->reportPeriod->week_number }}</p>
                                <p class="mt-1 text-sm text-ink-muted">Baca umpan balik dosen dan unggah versi revisi.</p>
                            </div>
                            <a href="{{ route('laporan.show', $report) }}" class="inline-flex min-h-10 items-center gap-2 self-start text-sm font-semibold text-primary-700 underline underline-offset-4 hover:text-primary-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 sm:self-auto">
                                Tinjau umpan balik <flux:icon.arrow-right variant="outline" class="size-4" />
                            </a>
                        </div>
                    @empty
                        <p class="py-5 text-sm leading-6 text-ink-muted">Tidak ada laporan yang menunggu revisi.</p>
                    @endforelse
                </div>
            </article>

            <article class="rounded-xl border border-line bg-surface shadow-sm">
                <header class="flex items-center justify-between gap-3 border-b border-line px-5 py-4 sm:px-6">
                    <div>
                        <h2 class="font-semibold text-ink">Pembimbingan Mendatang</h2>
                        <p class="mt-1 text-sm text-ink-muted">Jadwal bimbingan yang sudah Anda pesan.</p>
                    </div>
                    <a href="{{ route('guidance.student') }}" aria-label="Lihat jadwal pembimbingan" class="rounded-lg p-2 text-primary-700 hover:bg-primary-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700"><flux:icon.arrow-up-right variant="outline" class="size-5" /></a>
                </header>
                <div class="divide-y divide-line px-5 sm:px-6">
                    @forelse ($upcomingGuidanceBookings as $booking)
                        <div class="flex items-start gap-3 py-4">
                            <span aria-hidden="true" class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-700"><flux:icon.calendar variant="outline" class="size-4" /></span>
                            <div class="min-w-0">
                                <p class="font-medium text-ink">{{ $booking->slot->starts_at->locale('id')->translatedFormat('d M Y') }}</p>
                                <p class="mt-1 text-sm text-ink-muted">{{ $booking->slot->starts_at->format('H:i') }} WIB · {{ $booking->slot->lecturer->user->name }}</p>
                                @if ($booking->slot->location)<p class="mt-1 text-sm text-ink-muted">{{ $booking->slot->location }}</p>@endif
                            </div>
                        </div>
                    @empty
                        <p class="py-5 text-sm leading-6 text-ink-muted">Belum ada pembimbingan mendatang.</p>
                    @endforelse
                </div>
                <footer class="border-t border-line px-5 py-3 sm:px-6">
                    <a href="{{ route('guidance.student') }}" class="inline-flex min-h-10 items-center gap-2 text-sm font-semibold text-primary-700 underline underline-offset-4 hover:text-primary-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700">Lihat Jadwal Pembimbingan <flux:icon.arrow-right variant="outline" class="size-4" /></a>
                </footer>
            </article>
        </section>

        <section aria-labelledby="assessment-heading" class="rounded-xl border border-line bg-surface p-5 shadow-sm sm:p-6">
            <div class="flex items-start gap-3">
                <span aria-hidden="true" class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-success-bg text-success"><flux:icon.trophy variant="outline" class="size-5" /></span>
                <div class="min-w-0 flex-1">
                    <h2 id="assessment-heading" class="font-semibold text-ink">Hasil Penilaian Terbaru</h2>
                    @if ($latestPublishedAssessment)
                        <p class="mt-3 text-3xl font-semibold tabular-nums text-ink">{{ number_format((float) $latestPublishedAssessment->score, 2, ',', '.') }}<span class="ml-1 text-base font-medium text-ink-muted">/ 100</span></p>
                        <p class="mt-1 text-sm text-ink-muted">Minggu ke-{{ $latestPublishedAssessment->weeklyReport->reportPeriod->week_number }} · Dipublikasikan {{ $latestPublishedAssessment->published_at->locale('id')->translatedFormat('d M Y, H:i') }} WIB</p>
                        @if ($latestPublishedAssessment->notes)<p class="mt-4 border-t border-line pt-4 text-sm leading-6 text-ink-muted">{{ $latestPublishedAssessment->notes }}</p>@endif
                    @else
                        <p class="mt-2 text-sm leading-6 text-ink-muted">Belum ada penilaian yang dipublikasikan.</p>
                    @endif
                </div>
            </div>
        </section>
    </main>
</x-layouts::app>
