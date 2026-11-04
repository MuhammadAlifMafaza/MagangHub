<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section heading="Filter Rekap">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <label class="space-y-1 text-sm font-medium">
                    <span>Mahasiswa</span>
                    <select wire:model.live="studentFilter" class="fi-select-input w-full rounded-lg">
                        <option value="">Semua mahasiswa</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}">{{ $student->user->name }} ({{ $student->nim }})</option>
                        @endforeach
                    </select>
                </label>
                <label class="space-y-1 text-sm font-medium">
                    <span>Dosen pembimbing</span>
                    <select wire:model.live="lecturerFilter" class="fi-select-input w-full rounded-lg">
                        <option value="">Semua dosen</option>
                        @foreach ($lecturers as $lecturer)
                            <option value="{{ $lecturer->id }}">{{ $lecturer->user->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="space-y-1 text-sm font-medium">
                    <span>Minggu</span>
                    <select wire:model.live="weekFilter" class="fi-select-input w-full rounded-lg">
                        <option value="">Semua minggu</option>
                        @foreach ($weeks as $week)
                            <option value="{{ $week }}">Minggu ke-{{ $week }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="space-y-1 text-sm font-medium">
                    <span>Status pemeriksaan</span>
                    <select wire:model.live="reviewStatusFilter" class="fi-select-input w-full rounded-lg">
                        <option value="">Semua status</option>
                        <option value="pending_review">Menunggu Diperiksa</option>
                        <option value="revision_requested">Perlu Revisi</option>
                        <option value="approved">Disetujui</option>
                    </select>
                </label>
            </div>
        </x-filament::section>

        <x-filament::section heading="Pengumpulan Laporan per Mahasiswa dan Minggu">
            <div class="overflow-x-auto">
                <table class="w-full text-start text-sm">
                    <thead><tr class="border-b text-gray-700"><th class="px-3 py-2">Mahasiswa</th><th class="px-3 py-2">Dosen Pembimbing</th><th class="px-3 py-2">Minggu</th><th class="px-3 py-2">Pengumpulan</th><th class="px-3 py-2">Pemeriksaan</th><th class="px-3 py-2">Penilaian</th><th class="px-3 py-2">Laporan</th></tr></thead>
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
                            <tr class="border-b">
                                <td class="px-3 py-2">{{ $period->internship->student->user->name }}</td>
                                <td class="px-3 py-2">{{ $assignment?->lecturer?->user?->name ?? '—' }}</td>
                                <td class="px-3 py-2">Minggu ke-{{ $period->week_number }}</td>
                                <td class="px-3 py-2"><span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusBadgeClasses[$collectionStatus->color()] }}">{{ $collectionStatus->label() }}</span></td>
                                <td class="px-3 py-2">{{ $report?->review_status?->label() ?? '—' }}</td>
                                <td class="px-3 py-2">{{ $report?->assessment?->status?->label() ?? 'Belum dinilai' }}</td>
                                <td class="px-3 py-2">@if ($report)<a class="font-medium text-primary-700 underline focus-visible:outline-2 focus-visible:outline-offset-2" href="{{ route('laporan.show', $report) }}">Detail</a>@if ($report->latestVersion)<span aria-hidden="true"> · </span><a class="font-medium text-primary-700 underline focus-visible:outline-2 focus-visible:outline-offset-2" href="{{ route('laporan.versi.download', $report->latestVersion) }}">Unduh terbaru</a>@endif @else — @endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-3 py-6 text-center text-gray-600">Tidak ada data laporan untuk filter ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $periods->links() }}</div>
        </x-filament::section>

        <x-filament::section heading="Pembimbingan per Dosen">
            <div class="overflow-x-auto">
                <table class="w-full text-start text-sm">
                    <thead><tr class="border-b text-gray-700"><th class="px-3 py-2">Dosen</th><th class="px-3 py-2">Jumlah Slot</th><th class="px-3 py-2">Terisi</th><th class="px-3 py-2">Selesai</th><th class="px-3 py-2">Tidak Hadir</th></tr></thead>
                    <tbody>
                        @foreach ($guidanceCounts as $row)
                            <tr class="border-b"><td class="px-3 py-2">{{ $row['lecturer']->user->name }}</td><td class="px-3 py-2 tabular-nums">{{ $row['slots'] }}</td><td class="px-3 py-2 tabular-nums">{{ $row['booked'] }}</td><td class="px-3 py-2 tabular-nums">{{ $row['completed'] }}</td><td class="px-3 py-2 tabular-nums">{{ $row['no_show'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section heading="Pemeriksaan dan Penilaian per Dosen">
            <div class="grid gap-6 xl:grid-cols-2">
                <div class="overflow-x-auto">
                    <h3 class="mb-2 font-semibold">Pemeriksaan</h3>
                    <table class="w-full text-start text-sm">
                        <thead><tr class="border-b text-gray-700"><th class="px-3 py-2">Dosen</th><th class="px-3 py-2">Keputusan</th><th class="px-3 py-2">Jumlah</th></tr></thead>
                        <tbody>
                            @forelse ($reviewCounts as $row)
                                <tr class="border-b"><td class="px-3 py-2">{{ $row->lecturer_name }}</td><td class="px-3 py-2">{{ $row->review_status->label() }}</td><td class="px-3 py-2 tabular-nums">{{ $row->total }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="px-3 py-4 text-gray-600">Belum ada pemeriksaan pada filter ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="overflow-x-auto">
                    <h3 class="mb-2 font-semibold">Penilaian</h3>
                    <table class="w-full text-start text-sm">
                        <thead><tr class="border-b text-gray-700"><th class="px-3 py-2">Dosen</th><th class="px-3 py-2">Status</th><th class="px-3 py-2">Jumlah</th></tr></thead>
                        <tbody>
                            @forelse ($assessmentCounts as $row)
                                <tr class="border-b"><td class="px-3 py-2">{{ $row->lecturer->user->name }}</td><td class="px-3 py-2">{{ $row->status->label() }}</td><td class="px-3 py-2 tabular-nums">{{ $row->total }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="px-3 py-4 text-gray-600">Belum ada penilaian pada filter ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
