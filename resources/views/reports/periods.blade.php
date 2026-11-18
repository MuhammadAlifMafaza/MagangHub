<x-layouts::app.sidebar title="Laporan Mingguan">
    @php
        $today = today(config('magang.timezone'));
        $currentPeriods = $periods->filter(fn ($period) => $period->starts_on->lessThanOrEqualTo($today) && $period->ends_on->greaterThanOrEqualTo($today))
            ->sortByDesc('starts_on');
        $pastPeriods = $periods->filter(fn ($period) => $period->starts_on->lessThanOrEqualTo($today) && $period->ends_on->lessThan($today))
            ->sortByDesc('starts_on');
        $periodsForDisplay = $currentPeriods->concat($pastPeriods)->values();
        $notSubmittedCount = $periodsForDisplay->filter(fn ($period) => in_array($period->collectionStatus()->value, ['not_submitted', 'not_submitted_late'], true))->count();
        $pendingReviewCount = $periodsForDisplay->filter(fn ($period) => $period->weeklyReport?->review_status->value === 'pending_review')->count();
        $revisionCount = $periodsForDisplay->filter(fn ($period) => $period->weeklyReport?->review_status->value === 'revision_requested')->count();
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
                    <p class="mt-2 text-2xl font-semibold tabular-nums text-ink">{{ $periodsForDisplay->count() }}</p>
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

            @if ($periodsForDisplay->isEmpty())
                <div class="flex flex-col items-center rounded-2xl border border-dashed border-line-input bg-surface px-6 py-10 text-center">
                    <flux:icon.document variant="outline" class="size-9 text-ink-muted" />
                    <h2 class="mt-3 text-base font-semibold text-ink">Belum ada periode laporan yang dibuka.</h2>
                    <p class="mt-2 max-w-md text-sm leading-6 text-ink-muted">Periode berikutnya akan muncul di daftar ini saat tanggal mulainya tiba.</p>
                </div>
            @else
                <section aria-labelledby="periods-heading" class="flex flex-col gap-3">
                    <h2 id="periods-heading" class="text-lg font-semibold text-ink">Daftar periode</h2>
                    <ul class="flex flex-col gap-3">
                        @foreach ($periodsForDisplay as $period)
                            <li>
                                @include('reports.partials.period-card', [
                                    'period' => $period,
                                    'currentPeriodId' => $currentPeriod?->id,
                                ])
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </main>
</x-layouts::app.sidebar>
