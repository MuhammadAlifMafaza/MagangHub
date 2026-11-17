<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Ringkasan aktivitas administrasi</x-slot>
        <x-slot name="description">Pantau kondisi utama program magang dan laporan pada periode aktif.</x-slot>

        @php
            $statValueColors = [
                'info' => 'text-sky-700 dark:text-sky-300',
                'success' => 'text-green-700 dark:text-green-300',
                'warning' => 'text-amber-700 dark:text-amber-300',
                'danger' => 'text-red-700 dark:text-red-300',
                'gray' => 'text-gray-700 dark:text-gray-300',
            ];
            $statusBarColors = [
                'warning' => 'bg-amber-500',
                'danger' => 'bg-red-500',
                'success' => 'bg-green-500',
            ];
            $maxReportStatusCount = max(1, ...array_column($reportStatuses, 'count'));
        @endphp

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($primaryStats as $stat)
                <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ $stat['label'] }}</p>
                    <p class="mt-1.5 text-2xl font-semibold tracking-tight {{ $statValueColors[$stat['color']] ?? $statValueColors['gray'] }}">
                        {{ is_int($stat['value']) ? number_format($stat['value']) : $stat['value'] }}
                    </p>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $stat['description'] }}</p>
                </article>
            @endforeach
        </div>

        <div class="mt-3 grid gap-2.5 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($secondaryStats as $stat)
                <div class="flex items-center justify-between gap-3 rounded-xl border border-gray-200 px-3 py-2.5 dark:border-gray-700">
                    <span class="text-sm text-gray-600 dark:text-gray-300">{{ $stat['label'] }}</span>
                    <span class="font-semibold tabular-nums {{ $statValueColors[$stat['color']] ?? $statValueColors['gray'] }}">
                        {{ number_format($stat['value']) }}
                    </span>
                </div>
            @endforeach
        </div>

        <section aria-labelledby="admin-report-status-heading" class="mt-4 rounded-xl border border-gray-200 p-3 dark:border-gray-700 sm:p-4">
            <div class="flex flex-wrap items-end justify-between gap-2">
                <div>
                    <h3 id="admin-report-status-heading" class="font-semibold text-gray-950 dark:text-white">Status pemeriksaan laporan</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Jumlah laporan per status pada periode aktif.</p>
                </div>
                <a href="{{ $recapUrl }}" class="text-sm font-medium text-primary-600 hover:underline dark:text-primary-400">Buka rekap administratif</a>
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-3">
                @foreach ($reportStatuses as $status)
                    <div>
                        <div class="mb-2 flex items-center justify-between gap-3 text-sm">
                            <span class="text-gray-700 dark:text-gray-200">{{ $status['label'] }}</span>
                            <span class="font-semibold tabular-nums text-gray-950 dark:text-white">{{ number_format($status['count']) }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800" role="img" aria-label="{{ $status['label'] }}: {{ $status['count'] }} laporan">
                            <div class="h-full rounded-full {{ $statusBarColors[$status['color']] ?? 'bg-primary-500' }}" style="width: {{ ($status['count'] / $maxReportStatusCount) * 100 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </x-filament::section>
</x-filament-widgets::widget>
