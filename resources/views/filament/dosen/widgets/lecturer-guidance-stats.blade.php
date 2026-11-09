<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Ringkasan aktivitas bimbingan</x-slot>
        <x-slot name="description">Statistik untuk mahasiswa dan aktivitas yang berada dalam bimbingan Anda.</x-slot>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm text-gray-600 dark:text-gray-300">Mahasiswa bimbingan aktif</p>
                <p class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ number_format($stats['active_students']) }}</p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm text-gray-600 dark:text-gray-300">Slot mendatang</p>
                <p class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ number_format($stats['upcoming_slots']) }}</p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm text-gray-600 dark:text-gray-300">Pemesanan mendatang</p>
                <p class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ number_format($stats['upcoming_bookings']) }}</p>
            </article>
            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm text-gray-600 dark:text-gray-300">Penilaian draf</p>
                <p class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ number_format($stats['draft_assessments']) }}</p>
            </article>
        </div>

        @php
            $maxReportCount = max(1, ...array_column($reportStatuses, 'count'));
            $statusColors = [
                'warning' => 'bg-amber-500',
                'danger' => 'bg-red-500',
                'success' => 'bg-green-500',
            ];
        @endphp

        <section aria-labelledby="report-status-heading" class="mt-6 rounded-xl border border-gray-200 p-4 dark:border-gray-700 sm:p-5">
            <div class="flex flex-wrap items-end justify-between gap-2">
                <div>
                    <h3 id="report-status-heading" class="font-semibold text-gray-950 dark:text-white">Status laporan bimbingan</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Jumlah laporan per status pemeriksaan.</p>
                </div>
                <a href="{{ \App\Filament\Dosen\Resources\ReportReviews\ReportReviewResource::getUrl('index', panel: 'dosen') }}" class="text-sm font-medium text-primary-600 hover:underline dark:text-primary-400">Buka pemeriksaan laporan</a>
            </div>

            <div class="mt-5 grid gap-4 md:grid-cols-3">
                @foreach ($reportStatuses as $status)
                    <div>
                        <div class="mb-2 flex items-center justify-between gap-3 text-sm">
                            <span class="text-gray-700 dark:text-gray-200">{{ $status['label'] }}</span>
                            <span class="font-semibold tabular-nums text-gray-950 dark:text-white">{{ number_format($status['count']) }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800" role="img" aria-label="{{ $status['label'] }}: {{ $status['count'] }} laporan">
                            <div class="h-full rounded-full {{ $statusColors[$status['color']] ?? 'bg-primary-500' }}" style="width: {{ ($status['count'] / $maxReportCount) * 100 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </x-filament::section>
</x-filament-widgets::widget>
