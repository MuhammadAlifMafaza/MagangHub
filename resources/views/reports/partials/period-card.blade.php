@php
    $report = $period->weeklyReport;
    $collectionStatus = $period->collectionStatus();
    $reviewStatus = $report?->review_status;
    $hasRevisionRequest = $reviewStatus?->value === 'revision_requested';
    $isCurrentPeriod = $currentPeriodId === $period->id;
@endphp

<article id="period-{{ $period->id }}">
    <a
        href="{{ route('mahasiswa.laporan.period', $period) }}"
        aria-labelledby="period-title-{{ $period->id }}"
        @class([
            'group flex flex-col gap-4 rounded-2xl border bg-surface p-4 shadow-sm shadow-primary-800/[0.03] transition hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 sm:flex-row sm:items-center sm:justify-between sm:gap-6 sm:p-5',
            'border-danger/30' => $hasRevisionRequest || $collectionStatus->value === 'not_submitted_late',
            'border-warning/30' => ! $hasRevisionRequest && $collectionStatus->value === 'not_submitted',
            'border-line' => ! $hasRevisionRequest && ! in_array($collectionStatus->value, ['not_submitted', 'not_submitted_late'], true),
        ])
    >
        <div class="flex min-w-0 items-start gap-3 sm:items-center">
            <span aria-hidden="true" class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-700 transition group-hover:bg-primary-100">
                <flux:icon.document variant="outline" class="size-5" />
            </span>
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <h3 id="period-title-{{ $period->id }}" class="font-semibold text-ink">Minggu ke-{{ $period->week_number }}</h3>
                    @if ($isCurrentPeriod)
                        <span class="rounded-full bg-primary-50 px-2.5 py-1 text-xs font-semibold text-primary-700">Berjalan</span>
                    @endif
                    <p class="text-sm text-ink-muted">
                        {{ $period->starts_on->locale('id')->translatedFormat('d M Y') }}–{{ $period->ends_on->locale('id')->translatedFormat('d M Y') }}
                    </p>
                </div>
                <p class="mt-1 flex items-center gap-2 text-sm text-ink-muted">
                    <flux:icon.clock variant="outline" class="size-4 shrink-0" />
                    Tenggat {{ $period->due_at->locale('id')->translatedFormat('d M Y, H:i') }} WIB
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 sm:justify-end">
            <x-ui.status-badge :label="$collectionStatus->label()" :tone="$collectionStatus->color()" />
            @if ($reviewStatus)
                <x-ui.status-badge :label="$reviewStatus->label()" :tone="$reviewStatus->color()" />
            @endif
            <span class="flex items-center gap-1.5 text-sm font-semibold text-primary-700 transition group-hover:gap-2.5 sm:ml-2">
                Buka detail
                <flux:icon.arrow-right variant="outline" class="size-4" />
            </span>
        </div>
    </a>
</article>
