<x-filament-panels::page>
    @php
        $reportShortcuts = [
            [
                'status' => \App\Enums\ReviewStatus::PendingReview,
                'label' => 'Menunggu pemeriksaan',
                'count' => $pendingReviewCount,
                'color' => 'warning',
                'icon' => 'heroicon-o-clock',
            ],
            [
                'status' => \App\Enums\ReviewStatus::RevisionRequested,
                'label' => 'Perlu revisi',
                'count' => $revisionRequestedCount,
                'color' => 'danger',
                'icon' => 'heroicon-o-arrow-path',
            ],
            [
                'status' => \App\Enums\ReviewStatus::Approved,
                'label' => 'Disetujui',
                'count' => $approvedReviewCount,
                'color' => 'success',
                'icon' => 'heroicon-o-check-circle',
            ],
        ];

        $shortcutValueColors = [
            'warning' => 'text-warning-700 dark:text-warning-300',
            'danger' => 'text-danger-700 dark:text-danger-300',
            'success' => 'text-success-700 dark:text-success-300',
            'primary' => 'text-primary-700 dark:text-primary-300',
        ];

        $hasActiveFilters = $studentFilter !== ''
            || $lecturerFilter !== ''
            || $monthFilter !== ''
            || $yearFilter !== ''
            || $weekFilter !== ''
            || $reviewStatusFilter !== ''
            || $assessmentStatusFilter !== ''
            || $reportScope !== 'attention'
            || filled($tableSearch)
            || collect($tableColumnSearches)->filter()->isNotEmpty();

        $activeAssessmentStatus = \App\Enums\AssessmentStatus::tryFrom($assessmentStatusFilter);
    @endphp

    <div class="space-y-6">
        <header class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-primary-600 dark:text-primary-400">Pemantauan magang</p>
                <p class="mt-1 max-w-3xl text-sm leading-6 text-gray-600 dark:text-gray-300">
                    Pantau laporan yang perlu ditindaklanjuti, lalu telusuri seluruh periode dan rekap pembimbingan.
                </p>
            </div>
        </header>

        <x-filament::section heading="Ringkasan laporan">
            <x-slot name="description">Pilih status untuk langsung menyaring daftar laporan. Ringkasan mengikuti filter rekap di bawah ini.</x-slot>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($reportShortcuts as $shortcut)
                    <button
                        type="button"
                        wire:click="setReportStatusShortcut('{{ $shortcut['status']->value }}')"
                        aria-pressed="{{ $reviewStatusFilter === $shortcut['status']->value ? 'true' : 'false' }}"
                        @class([
                            'group flex min-h-28 flex-col items-start justify-between rounded-xl border bg-white p-4 text-start shadow-sm transition hover:border-primary-300 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 dark:bg-gray-900 dark:hover:border-primary-500',
                            'border-primary-500 ring-2 ring-primary-500/20 dark:border-primary-400' => $reviewStatusFilter === $shortcut['status']->value,
                            'border-gray-200 dark:border-gray-700' => $reviewStatusFilter !== $shortcut['status']->value,
                        ])
                    >
                        <span class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                            <x-filament::icon :icon="$shortcut['icon']" class="h-5 w-5 shrink-0 {{ $shortcutValueColors[$shortcut['color']] }}" aria-hidden="true" />
                            {{ $shortcut['label'] }}
                        </span>
                        <span class="flex w-full items-end justify-between gap-3">
                            <span class="text-2xl font-semibold tabular-nums {{ $shortcutValueColors[$shortcut['color']] }}">{{ number_format($shortcut['count']) }}</span>
                            <span class="text-xs font-medium text-primary-600 group-hover:underline dark:text-primary-400">Lihat laporan</span>
                        </span>
                    </button>
                @endforeach

                <button
                    type="button"
                    wire:click="setAssessmentStatusShortcut('published')"
                    aria-pressed="{{ $assessmentStatusFilter === 'published' ? 'true' : 'false' }}"
                    @class([
                        'group flex min-h-28 flex-col items-start justify-between rounded-xl border bg-white p-4 text-start shadow-sm transition hover:border-primary-300 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 dark:bg-gray-900 dark:hover:border-primary-500',
                        'border-primary-500 ring-2 ring-primary-500/20 dark:border-primary-400' => $assessmentStatusFilter === 'published',
                        'border-gray-200 dark:border-gray-700' => $assessmentStatusFilter !== 'published',
                    ])
                >
                    <span class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                        <x-filament::icon icon="heroicon-o-academic-cap" class="h-5 w-5 shrink-0 {{ $shortcutValueColors['primary'] }}" aria-hidden="true" />
                        Nilai dipublikasikan
                    </span>
                    <span class="flex w-full items-end justify-between gap-3">
                        <span class="text-2xl font-semibold tabular-nums {{ $shortcutValueColors['primary'] }}">{{ number_format($publishedAssessmentCount) }}</span>
                        <span class="text-xs font-medium text-primary-600 group-hover:underline dark:text-primary-400">Lihat laporan disetujui</span>
                    </span>
                </button>
            </div>
        </x-filament::section>

        <x-filament::section heading="Filter rekap">
            <x-slot name="description">Filter ini berlaku untuk kartu ringkasan, daftar laporan, dan ringkasan per dosen. Bulan dan tahun ditentukan dari tanggal mulai periode.</x-slot>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <div class="space-y-1.5">
                    <label for="recap-student-filter" class="text-sm font-medium text-gray-700 dark:text-gray-200">Mahasiswa</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select id="recap-student-filter" wire:model.live="studentFilter">
                            <option value="">Semua mahasiswa</option>
                            @foreach ($students as $student)
                                <option wire:key="student-option-{{ $student->id }}" value="{{ $student->id }}">{{ $student->user->name }} ({{ $student->nim }})</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

                <div class="space-y-1.5">
                    <label for="recap-lecturer-filter" class="text-sm font-medium text-gray-700 dark:text-gray-200">Dosen pembimbing aktif</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select id="recap-lecturer-filter" wire:model.live="lecturerFilter">
                            <option value="">Semua dosen</option>
                            @foreach ($lecturers as $lecturer)
                                <option wire:key="lecturer-option-{{ $lecturer->id }}" value="{{ $lecturer->id }}">{{ $lecturer->user->name }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

                <div class="space-y-1.5">
                    <label for="recap-month-filter" class="text-sm font-medium text-gray-700 dark:text-gray-200">Bulan mulai periode</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select id="recap-month-filter" wire:model.live="monthFilter">
                            <option value="">Semua bulan</option>
                            @foreach ($months as $month => $label)
                                <option wire:key="month-option-{{ $month }}" value="{{ $month }}">{{ $label }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

                <div class="space-y-1.5">
                    <label for="recap-year-filter" class="text-sm font-medium text-gray-700 dark:text-gray-200">Tahun mulai periode</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select id="recap-year-filter" wire:model.live="yearFilter">
                            <option value="">Semua tahun</option>
                            @foreach ($years as $year)
                                <option wire:key="year-option-{{ $year }}" value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

                <div class="space-y-1.5">
                    <label for="recap-week-filter" class="text-sm font-medium text-gray-700 dark:text-gray-200">Minggu</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select id="recap-week-filter" wire:model.live="weekFilter">
                            <option value="">Semua minggu</option>
                            @foreach ($weeks as $week)
                                <option wire:key="week-option-{{ $week }}" value="{{ $week }}">Minggu ke-{{ $week }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
            </div>
        </x-filament::section>

        <section aria-label="Filter daftar laporan" class="flex flex-col gap-3">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex flex-wrap items-center gap-2" role="tablist" aria-label="Kategori laporan">
                    <x-filament::button
                        wire:click="setReportScope('attention')"
                        icon="heroicon-o-exclamation-triangle"
                        role="tab"
                        :aria-selected="$reportScope === 'attention' ? 'true' : 'false'"
                        :color="$reportScope === 'attention' ? 'primary' : 'gray'"
                        :outlined="$reportScope !== 'attention'"
                        size="sm"
                    >
                        Perlu dipantau
                    </x-filament::button>
                    <x-filament::button
                        wire:click="setReportScope('all')"
                        icon="heroicon-o-list-bullet"
                        role="tab"
                        :aria-selected="$reportScope === 'all' ? 'true' : 'false'"
                        :color="$reportScope === 'all' ? 'primary' : 'gray'"
                        :outlined="$reportScope !== 'all'"
                        size="sm"
                    >
                        Semua periode
                    </x-filament::button>
                    <x-filament::button
                        wire:click="setReportScope('upcoming')"
                        icon="heroicon-o-calendar-days"
                        role="tab"
                        :aria-selected="$reportScope === 'upcoming' ? 'true' : 'false'"
                        :color="$reportScope === 'upcoming' ? 'primary' : 'gray'"
                        :outlined="$reportScope !== 'upcoming'"
                        size="sm"
                    >
                        Mendatang
                    </x-filament::button>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                    <div class="flex items-center gap-2">
                        <label for="recap-review-filter" class="shrink-0 text-sm font-medium text-gray-700 dark:text-gray-200">Status pemeriksaan</label>
                        <x-filament::input.wrapper class="min-w-44">
                            <x-filament::input.select id="recap-review-filter" wire:model.live="reviewStatusFilter">
                                <option value="">Semua status</option>
                                <option value="pending_review">Menunggu Diperiksa</option>
                                <option value="revision_requested">Perlu Revisi</option>
                                <option value="approved">Disetujui</option>
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>

                    @if ($activeAssessmentStatus !== null)
                        <x-filament::badge color="success">Filter aktif: Nilai {{ $activeAssessmentStatus->label() }}</x-filament::badge>
                    @endif

                    @if ($hasActiveFilters)
                        <x-filament::button wire:click="resetFilters" color="gray" outlined size="sm" icon="heroicon-o-arrow-path">
                            Atur ulang
                        </x-filament::button>
                    @endif
                </div>
            </div>
        </section>

        {{ $this->table }}

        <x-filament::section heading="Pembimbingan per dosen">
            <x-slot name="description">Slot dihitung pada rentang tanggal periode terpilih untuk dosen pembimbing aktif; pemesanan mengikuti mahasiswa yang dipilih.</x-slot>

            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                <table class="w-full text-start text-sm">
                    <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:bg-gray-800/70 dark:text-gray-300">
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-start">Dosen</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-end">Slot dosen pada periode</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-end">Terisi</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-end">Selesai</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-end">Tidak hadir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($guidanceCounts as $row)
                            <tr wire:key="guidance-lecturer-{{ $row['lecturer']->getKey() }}" class="border-b border-gray-100 transition-colors last:border-0 hover:bg-gray-50/80 dark:border-gray-700 dark:hover:bg-white/5">
                                <td class="px-4 py-3 font-medium text-gray-950 dark:text-white">{{ $row['lecturer']->user->name }}</td>
                                <td class="px-4 py-3 text-end tabular-nums">{{ number_format($row['slots']) }}</td>
                                <td class="px-4 py-3 text-end tabular-nums">{{ number_format($row['booked']) }}</td>
                                <td class="px-4 py-3 text-end tabular-nums">{{ number_format($row['completed']) }}</td>
                                <td class="px-4 py-3 text-end tabular-nums">{{ number_format($row['no_show']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-600 dark:text-gray-300">Belum ada data pembimbingan untuk filter ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section heading="Status laporan dan penilaian per dosen">
            <x-slot name="description">Status laporan dikelompokkan menurut pembimbing aktif saat ini; penilaian dikelompokkan menurut dosen penilai.</x-slot>

            <div class="grid items-stretch gap-6 xl:grid-cols-2">
                <section aria-labelledby="recap-supervisor-status-heading" class="flex h-full min-w-0 flex-col rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                    <h3 id="recap-supervisor-status-heading" class="mb-4 min-h-6 font-semibold text-gray-950 dark:text-white">Status laporan per pembimbing aktif</h3>
                    <div class="flex-1 overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="w-full text-start text-sm">
                            <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:bg-gray-800/70 dark:text-gray-300">
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th scope="col" class="whitespace-nowrap px-4 py-3 text-start">Dosen</th>
                                    <th scope="col" class="whitespace-nowrap px-4 py-3 text-start">Status laporan</th>
                                    <th scope="col" class="whitespace-nowrap px-4 py-3 text-end">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($supervisorStatusCounts as $row)
                                    <tr wire:key="supervisor-status-{{ $row->lecturer_id }}-{{ $row->review_status->value }}" class="border-b border-gray-100 last:border-0 hover:bg-gray-50/80 dark:border-gray-700 dark:hover:bg-white/5">
                                        <td class="px-4 py-3 font-medium text-gray-950 dark:text-white">{{ $row->lecturer_name }}</td>
                                        <td class="px-4 py-3"><x-filament::badge :color="$row->review_status->color()">{{ $row->review_status->label() }}</x-filament::badge></td>
                                        <td class="px-4 py-3 text-end tabular-nums">{{ number_format($row->total) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-8 text-center text-gray-600 dark:text-gray-300">Belum ada status laporan pada filter ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section aria-labelledby="recap-assessment-heading" class="flex h-full min-w-0 flex-col rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                    <h3 id="recap-assessment-heading" class="mb-4 min-h-6 font-semibold text-gray-950 dark:text-white">Penilaian per dosen penilai</h3>
                    <div class="flex-1 overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="w-full text-start text-sm">
                            <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:bg-gray-800/70 dark:text-gray-300">
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th scope="col" class="whitespace-nowrap px-4 py-3 text-start">Dosen</th>
                                    <th scope="col" class="whitespace-nowrap px-4 py-3 text-start">Status nilai</th>
                                    <th scope="col" class="whitespace-nowrap px-4 py-3 text-end">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($assessmentCounts as $row)
                                    <tr wire:key="assessment-count-{{ $row->lecturer_id }}-{{ $row->status->value }}" class="border-b border-gray-100 last:border-0 hover:bg-gray-50/80 dark:border-gray-700 dark:hover:bg-white/5">
                                        <td class="px-4 py-3 font-medium text-gray-950 dark:text-white">{{ $row->lecturer->user->name }}</td>
                                        <td class="px-4 py-3"><x-filament::badge :color="$row->status->color()">{{ $row->status->label() }}</x-filament::badge></td>
                                        <td class="px-4 py-3 text-end tabular-nums">{{ number_format($row->total) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-8 text-center text-gray-600 dark:text-gray-300">Belum ada penilaian pada filter ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
