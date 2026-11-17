<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section heading="Filter Rekap">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <label class="space-y-1 text-sm font-medium">
                    <span>Mahasiswa</span>
                    <select wire:model.live="studentFilter" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        <option value="">Semua mahasiswa</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}">{{ $student->user->name }} ({{ $student->nim }})</option>
                        @endforeach
                    </select>
                </label>
                <label class="space-y-1 text-sm font-medium">
                    <span>Dosen pembimbing</span>
                    <select wire:model.live="lecturerFilter" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        <option value="">Semua dosen</option>
                        @foreach ($lecturers as $lecturer)
                            <option value="{{ $lecturer->id }}">{{ $lecturer->user->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="space-y-1 text-sm font-medium">
                    <span>Minggu</span>
                    <select wire:model.live="weekFilter" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        <option value="">Semua minggu</option>
                        @foreach ($weeks as $week)
                            <option value="{{ $week }}">Minggu ke-{{ $week }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="space-y-1 text-sm font-medium">
                    <span>Status pemeriksaan</span>
                    <select wire:model.live="reviewStatusFilter" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        <option value="">Semua status</option>
                        <option value="pending_review">Menunggu Diperiksa</option>
                        <option value="revision_requested">Perlu Revisi</option>
                        <option value="approved">Disetujui</option>
                    </select>
                </label>
            </div>
        </x-filament::section>

        <x-filament::section heading="Pengumpulan Laporan per Mahasiswa dan Minggu">
            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                <table class="min-w-[68rem] w-full text-start text-sm">
                    <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:bg-gray-800/70 dark:text-gray-300"><tr class="border-b border-gray-200 dark:border-gray-700"><th scope="col" class="whitespace-nowrap px-4 py-3">Mahasiswa</th><th scope="col" class="whitespace-nowrap px-4 py-3">Dosen Pembimbing</th><th scope="col" class="whitespace-nowrap px-4 py-3">Minggu</th><th scope="col" class="whitespace-nowrap px-4 py-3">Pengumpulan</th><th scope="col" class="whitespace-nowrap px-4 py-3">Pemeriksaan</th><th scope="col" class="whitespace-nowrap px-4 py-3">Penilaian</th><th scope="col" class="whitespace-nowrap px-4 py-3">Laporan</th></tr></thead>
                    <tbody>
                        @forelse ($periods as $period)
                            @php
                                $report = $period->weeklyReport;
                                $assignments = $period->internship->student->supervisorAssignments;
                                $assignment = $assignments->first(fn ($assignment) => $assignment->status->value === 'active') ?? $assignments->first();
                                $collectionStatus = $period->collectionStatus();
                                $statusBadgeClasses = [
                                    'success' => 'bg-success-bg text-success',
                                    'warning' => 'bg-warning-bg text-warning',
                                    'danger' => 'bg-danger-bg text-danger',
                                    'info' => 'bg-info-bg text-info',
                                    'gray' => 'bg-neutral-bg text-neutral',
                                ];
                            @endphp
                            <tr class="border-b border-gray-100 transition-colors hover:bg-gray-50/80 dark:border-gray-700 dark:hover:bg-white/5">
                                <td class="whitespace-nowrap px-4 py-3 font-medium">{{ $period->internship->student->user->name }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $assignment?->lecturer?->user?->name ?? '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3">Minggu ke-{{ $period->week_number }}</td>
                                <td class="whitespace-nowrap px-4 py-3"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $statusBadgeClasses[$collectionStatus->color()] }}">{{ $collectionStatus->label() }}</span></td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $report?->review_status?->label() ?? '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $report?->assessment?->status?->label() ?? 'Belum dinilai' }}</td>
                                <td class="whitespace-nowrap px-4 py-3">@if ($report)<a class="font-medium text-primary-700 underline focus-visible:outline-2 focus-visible:outline-offset-2" href="{{ route('laporan.show', $report) }}">Detail</a>@if ($report->latestVersion)<span aria-hidden="true"> · </span><a class="font-medium text-primary-700 underline focus-visible:outline-2 focus-visible:outline-offset-2" href="{{ route('laporan.versi.download', $report->latestVersion) }}">Unduh terbaru</a>@endif @else — @endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-8 text-center text-gray-600 dark:text-gray-300">Tidak ada data laporan untuk filter ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $periods->links() }}</div>
        </x-filament::section>

        <x-filament::section heading="Pembimbingan per Dosen">
            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                <table class="min-w-[40rem] w-full text-start text-sm">
                    <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:bg-gray-800/70 dark:text-gray-300"><tr class="border-b border-gray-200 dark:border-gray-700"><th scope="col" class="px-4 py-3">Dosen</th><th scope="col" class="px-4 py-3">Jumlah Slot</th><th scope="col" class="px-4 py-3">Terisi</th><th scope="col" class="px-4 py-3">Selesai</th><th scope="col" class="px-4 py-3">Tidak Hadir</th></tr></thead>
                    <tbody>
                        @foreach ($guidanceCounts as $row)
                            <tr class="border-b border-gray-100 transition-colors hover:bg-gray-50/80 dark:border-gray-700 dark:hover:bg-white/5"><td class="px-4 py-3 font-medium">{{ $row['lecturer']->user->name }}</td><td class="px-4 py-3 tabular-nums">{{ $row['slots'] }}</td><td class="px-4 py-3 tabular-nums">{{ $row['booked'] }}</td><td class="px-4 py-3 tabular-nums">{{ $row['completed'] }}</td><td class="px-4 py-3 tabular-nums">{{ $row['no_show'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section heading="Pemeriksaan dan Penilaian per Dosen">
            <div class="grid gap-6 xl:grid-cols-2">
                <div>
                    <h3 class="mb-2 font-semibold">Pemeriksaan</h3>
                    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                        <table class="min-w-[32rem] w-full text-start text-sm">
                            <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:bg-gray-800/70 dark:text-gray-300"><tr class="border-b border-gray-200 dark:border-gray-700"><th scope="col" class="px-4 py-3">Dosen</th><th scope="col" class="px-4 py-3">Keputusan</th><th scope="col" class="px-4 py-3">Jumlah</th></tr></thead>
                            <tbody>
                                @forelse ($reviewCounts as $row)
                                    <tr class="border-b border-gray-100 transition-colors hover:bg-gray-50/80 dark:border-gray-700 dark:hover:bg-white/5"><td class="px-4 py-3 font-medium">{{ $row->lecturer_name }}</td><td class="px-4 py-3">{{ $row->review_status->label() }}</td><td class="px-4 py-3 tabular-nums">{{ $row->total }}</td></tr>
                                @empty
                                    <tr><td colspan="3" class="px-4 py-6 text-gray-600 dark:text-gray-300">Belum ada pemeriksaan pada filter ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div>
                    <h3 class="mb-2 font-semibold">Penilaian</h3>
                    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                        <table class="min-w-[32rem] w-full text-start text-sm">
                            <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:bg-gray-800/70 dark:text-gray-300"><tr class="border-b border-gray-200 dark:border-gray-700"><th scope="col" class="px-4 py-3">Dosen</th><th scope="col" class="px-4 py-3">Status</th><th scope="col" class="px-4 py-3">Jumlah</th></tr></thead>
                            <tbody>
                                @forelse ($assessmentCounts as $row)
                                    <tr class="border-b border-gray-100 transition-colors hover:bg-gray-50/80 dark:border-gray-700 dark:hover:bg-white/5"><td class="px-4 py-3 font-medium">{{ $row->lecturer->user->name }}</td><td class="px-4 py-3">{{ $row->status->label() }}</td><td class="px-4 py-3 tabular-nums">{{ $row->total }}</td></tr>
                                @empty
                                    <tr><td colspan="3" class="px-4 py-6 text-gray-600 dark:text-gray-300">Belum ada penilaian pada filter ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
