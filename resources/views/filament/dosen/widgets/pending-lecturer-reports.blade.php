<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Laporan Menunggu Pemeriksaan</x-slot>

        @if ($reports->isEmpty())
            <p class="text-sm text-gray-600 dark:text-gray-300">Tidak ada laporan yang menunggu pemeriksaan.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-start text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-gray-700 dark:border-gray-700 dark:text-gray-300">
                            <th scope="col" class="px-3 py-2">Mahasiswa</th>
                            <th scope="col" class="px-3 py-2">Minggu</th>
                            <th scope="col" class="px-3 py-2">Dikirim</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reports as $report)
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <td class="px-3 py-2">{{ $report->reportPeriod->internship->student->user->name }}</td>
                                <td class="px-3 py-2">Minggu ke-{{ $report->reportPeriod->week_number }}</td>
                                <td class="px-3 py-2">{{ $report->latestVersion?->submitted_at?->locale('id')->translatedFormat('d M Y, H:i') ?? 'Belum dikirim' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
