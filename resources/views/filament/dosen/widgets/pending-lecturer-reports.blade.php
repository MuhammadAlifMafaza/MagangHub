<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Laporan Menunggu Pemeriksaan</x-slot>

        @if ($reports->isEmpty())
            <p class="rounded-xl border border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">Tidak ada laporan yang menunggu pemeriksaan.</p>
        @else
            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                <table class="min-w-[42rem] w-full text-start text-sm">
                    <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:bg-gray-800/70 dark:text-gray-300">
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th scope="col" class="whitespace-nowrap px-4 py-3">Mahasiswa</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3">Minggu</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3">Dikirim</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-end">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reports as $report)
                            <tr class="border-b border-gray-100 transition-colors last:border-0 hover:bg-gray-50/80 dark:border-gray-700 dark:hover:bg-white/5">
                                <td class="px-4 py-3 font-medium">{{ $report->reportPeriod->internship->student->user->name }}</td>
                                <td class="whitespace-nowrap px-4 py-3">Minggu ke-{{ $report->reportPeriod->week_number }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $report->latestVersion?->submitted_at?->locale('id')->translatedFormat('d M Y, H:i') ?? 'Belum dikirim' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-end">
                                    <a href="{{ route('laporan.show', $report) }}" class="font-medium text-primary-600 hover:underline dark:text-primary-400">Buka laporan</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
