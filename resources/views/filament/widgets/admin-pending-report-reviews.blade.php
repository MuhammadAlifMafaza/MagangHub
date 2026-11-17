<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Laporan Menunggu Pemeriksaan</x-slot>
        <x-slot name="description">Laporan pada periode aktif yang perlu ditindaklanjuti oleh dosen pembimbing.</x-slot>
        <x-slot name="afterHeader">
            <a href="{{ $recapUrl }}" class="text-sm font-medium text-primary-600 hover:underline dark:text-primary-400">Lihat rekap</a>
        </x-slot>

        @if ($reports->isEmpty())
            <p class="rounded-xl border border-dashed border-gray-300 px-3 py-6 text-center text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
                Tidak ada laporan yang menunggu pemeriksaan pada periode aktif.
            </p>
        @else
            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                <table class="min-w-[36rem] w-full text-start text-sm">
                    <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:bg-gray-800/70 dark:text-gray-300">
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th scope="col" class="whitespace-nowrap px-3 py-2">Mahasiswa</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-2">Minggu</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-2">Dikirim</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-2 text-end">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reports as $report)
                            <tr wire:key="pending-report-{{ $report->getKey() }}" class="border-b border-gray-100 transition-colors last:border-0 hover:bg-gray-50/80 dark:border-gray-700 dark:hover:bg-white/5">
                                <td class="px-3 py-2 font-medium text-gray-950 dark:text-white">{{ $report->reportPeriod->internship->student->user->name }}</td>
                                <td class="whitespace-nowrap px-3 py-2">Minggu ke-{{ $report->reportPeriod->week_number }}</td>
                                <td class="whitespace-nowrap px-3 py-2">{{ $report->latestVersion?->submitted_at?->locale('id')->translatedFormat('d M Y, H:i') ?? 'Belum dikirim' }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-end">
                                    <a href="{{ \App\Filament\Pages\ReportDetails::getUrl(['report' => $report], panel: 'admin') }}" class="font-medium text-primary-600 hover:underline dark:text-primary-400">Buka laporan</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
