<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">Laporan minggu ke-{{ $report->reportPeriod->week_number }}</x-slot>
            <x-slot name="description">
                {{ $report->reportPeriod->internship->student->user->name }}
                <span aria-hidden="true">·</span>
                {{ $report->reportPeriod->starts_on->locale('id')->translatedFormat('d M Y') }}–{{ $report->reportPeriod->ends_on->locale('id')->translatedFormat('d M Y') }}
            </x-slot>
            <x-slot name="afterHeader">
                <x-filament::badge :color="$report->review_status->color()">
                    {{ $report->review_status->label() }}
                </x-filament::badge>
            </x-slot>

            @if (auth()->user()->role->value === 'lecturer' && $report->review_status->value === 'revision_requested')
                <div class="flex items-start gap-3 rounded-lg border border-warning-300 bg-warning-50 p-4 text-sm text-warning-800 dark:border-warning-700 dark:bg-warning-950 dark:text-warning-200">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="mt-0.5 h-5 w-5 shrink-0" />
                    <p>Menunggu mahasiswa mengirim versi revisi. Umpan balik sebelumnya tersedia di riwayat versi.</p>
                </div>
            @endif
        </x-filament::section>

        <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
            <x-filament::section heading="Riwayat berkas dan pemeriksaan" description="Versi laporan, ketersediaan berkas, dan umpan balik dosen.">
                @forelse ($report->versions as $version)
                    <article @class([
                        'py-5 first:pt-0 last:pb-0',
                        'border-t border-gray-200 dark:border-gray-700' => ! $loop->first,
                    ])>
                        <div class="flex min-w-0 flex-col gap-4 md:flex-row md:items-start md:justify-between">
                            <div class="flex min-w-0 items-start gap-3">
                                <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300" aria-hidden="true">
                                    <x-filament::icon icon="heroicon-o-document-text" class="h-5 w-5" />
                                </span>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="font-semibold text-gray-950 dark:text-white">Versi {{ $version->version_number }}</h3>
                                        @if ($loop->last)
                                            <x-filament::badge color="info" size="sm">Terbaru</x-filament::badge>
                                        @endif
                                        @if ($version->is_late)
                                            <x-filament::badge color="danger" size="sm">Terlambat</x-filament::badge>
                                        @endif
                                    </div>
                                    <p class="mt-1 break-all text-sm font-medium text-gray-800 dark:text-gray-200">{{ $version->original_name }}</p>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $version->submitted_at->locale('id')->translatedFormat('d M Y, H:i') }} WIB
                                        <span aria-hidden="true">·</span>
                                        @if ($version->size_bytes < 1024)
                                            {{ number_format($version->size_bytes) }} byte
                                        @else
                                            {{ number_format($version->size_bytes / 1024, $version->size_bytes < 10240 ? 1 : 0, ',', '.') }} KB
                                        @endif
                                    </p>

                                    @if ($version->student_note)
                                        <div class="mt-3 rounded-lg bg-gray-50 p-3 text-sm leading-6 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                            <span class="font-medium">Catatan mahasiswa:</span> {{ $version->student_note }}
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="flex shrink-0 flex-wrap items-center gap-2 md:justify-end">
                                @if ($fileAvailability->get($version->getKey(), false))
                                    @if ($version->mime_type === 'application/pdf')
                                        <x-filament::button
                                            :href="route('laporan.versi.viewer', $version)"
                                            tag="a"
                                            target="_blank"
                                            rel="noopener"
                                            color="gray"
                                            outlined
                                            size="sm"
                                            icon="heroicon-o-eye"
                                        >
                                            Baca PDF
                                        </x-filament::button>
                                    @endif

                                    <x-filament::button
                                        :href="route('laporan.versi.download', $version)"
                                        tag="a"
                                        color="gray"
                                        outlined
                                        size="sm"
                                        icon="heroicon-o-arrow-down-tray"
                                    >
                                        Unduh berkas
                                    </x-filament::button>
                                @else
                                    <x-filament::badge color="danger">Berkas tidak tersedia</x-filament::badge>
                                    <p role="status" class="basis-full text-sm text-danger-600 md:text-right dark:text-danger-400">Berkas belum tersedia di penyimpanan. Hubungi administrator untuk pemulihan.</p>
                                @endif
                            </div>
                        </div>

                        @if ($version->review)
                            <div class="mt-4 rounded-lg border-s-2 border-primary-500 bg-gray-50 p-4 dark:bg-gray-800">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <x-filament::badge :color="$version->review->decision->color()" :icon="$version->review->decision->value === 'approved' ? 'heroicon-o-check-circle' : 'heroicon-o-arrow-path'">
                                        {{ $version->review->decision->label() }}
                                    </x-filament::badge>
                                    <time class="text-xs text-gray-500 dark:text-gray-400" datetime="{{ $version->review->reviewed_at->toIso8601String() }}">
                                        {{ $version->review->reviewed_at->locale('id')->translatedFormat('d M Y, H:i') }} WIB
                                    </time>
                                </div>
                                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-gray-700 dark:text-gray-200">{{ $version->review->feedback ?: 'Tidak ada catatan tambahan.' }}</p>
                            </div>
                        @endif
                    </article>
                @empty
                    <div class="rounded-lg border border-dashed border-gray-300 px-4 py-8 text-center dark:border-gray-700">
                        <x-filament::icon icon="heroicon-o-document-text" class="mx-auto h-8 w-8 text-gray-400" />
                        <p class="mt-2 font-medium text-gray-900 dark:text-white">Belum ada versi laporan</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Belum ada berkas yang dikumpulkan untuk periode ini.</p>
                    </div>
                @endforelse
            </x-filament::section>

            <aside class="space-y-6">
                <x-filament::section heading="Informasi mahasiswa">
                    <dl class="grid gap-4 text-sm">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Nama</dt>
                            <dd class="mt-1 font-medium text-gray-950 dark:text-white">{{ $report->reportPeriod->internship->student->user->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">NIM</dt>
                            <dd class="mt-1 font-medium text-gray-950 dark:text-white">{{ $report->reportPeriod->internship->student->nim }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Program studi</dt>
                            <dd class="mt-1 font-medium text-gray-950 dark:text-white">{{ $report->reportPeriod->internship->student->study_program ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Tempat magang</dt>
                            <dd class="mt-1 font-medium text-gray-950 dark:text-white">{{ $report->reportPeriod->internship->company_name }}</dd>
                        </div>
                    </dl>
                </x-filament::section>

                @if (auth()->user()->role->value === 'lecturer' && $report->review_status->value === 'pending_review')
                    <x-filament::section heading="Keputusan pemeriksaan" description="Pilih keputusan dan sampaikan umpan balik kepada mahasiswa.">
                        <form method="POST" action="{{ route('dosen.laporan.review', $report) }}" class="space-y-4">
                            @csrf
                            <fieldset class="space-y-2">
                                <legend class="sr-only">Keputusan pemeriksaan</legend>
                                <label class="flex min-h-10 items-center gap-2 rounded-lg border border-gray-300 px-3 text-sm dark:border-gray-600">
                                    <input type="radio" name="decision" value="approved" required class="h-4 w-4 accent-primary-600">
                                    Setujui laporan
                                </label>
                                <label class="flex min-h-10 items-center gap-2 rounded-lg border border-gray-300 px-3 text-sm dark:border-gray-600">
                                    <input type="radio" name="decision" value="revision_requested" class="h-4 w-4 accent-primary-600">
                                    Minta revisi
                                </label>
                            </fieldset>
                            <div class="space-y-2">
                                <label for="review-feedback" class="text-sm font-medium text-gray-700 dark:text-gray-200">Umpan balik</label>
                                <textarea id="review-feedback" name="feedback" rows="4" class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white">{{ old('feedback') }}</textarea>
                                @error('feedback')
                                    <p role="alert" class="text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <x-filament::button type="submit" class="w-full" icon="heroicon-o-check">
                                Simpan pemeriksaan
                            </x-filament::button>
                        </form>
                    </x-filament::section>
                @elseif (auth()->user()->role->value === 'lecturer' && $report->review_status->value === 'approved')
                    <x-filament::section heading="Penilaian" description="Draf nilai belum terlihat oleh mahasiswa sampai dipublikasikan.">
                        <form method="POST" action="{{ route('dosen.laporan.penilaian.update', $report) }}" class="space-y-4">
                            @csrf
                            @method('PUT')
                            <div class="space-y-2">
                                <label for="assessment-score" class="text-sm font-medium text-gray-700 dark:text-gray-200">Nilai (0–100)</label>
                                <input id="assessment-score" type="number" min="0" max="100" step="0.01" name="score" value="{{ old('score', $report->assessment?->score) }}" required class="block min-h-10 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                                @error('score')
                                    <p role="alert" class="text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="space-y-2">
                                <label for="assessment-notes" class="text-sm font-medium text-gray-700 dark:text-gray-200">Catatan <span class="font-normal text-gray-500 dark:text-gray-400">(opsional)</span></label>
                                <textarea id="assessment-notes" name="notes" rows="3" class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white">{{ old('notes', $report->assessment?->notes) }}</textarea>
                            </div>
                            <x-filament::button type="submit" color="gray" outlined class="w-full" icon="heroicon-o-pencil-square">
                                Simpan draf
                            </x-filament::button>
                        </form>

                        @if ($report->assessment?->status->value === 'draft')
                            <form method="POST" action="{{ route('dosen.penilaian.publikasi', $report->assessment) }}" class="mt-3">
                                @csrf
                                <x-filament::button type="submit" color="success" class="w-full" icon="heroicon-o-paper-airplane">
                                    Publikasikan nilai
                                </x-filament::button>
                            </form>
                        @endif
                    </x-filament::section>
                @elseif (auth()->user()->role->value === 'lecturer')
                    <x-filament::section heading="Menunggu revisi mahasiswa">
                        <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">Umpan balik pemeriksaan tersedia pada riwayat versi laporan.</p>
                    </x-filament::section>
                @else
                    <x-filament::section heading="Penilaian" description="Status dan catatan penilaian laporan ini.">
                        @if ($report->assessment)
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <x-filament::badge :color="$report->assessment->status->color()">
                                    {{ $report->assessment->status->label() }}
                                </x-filament::badge>
                                <p class="text-2xl font-semibold tabular-nums text-gray-950 dark:text-white">
                                    {{ number_format((float) $report->assessment->score, 2, ',', '.') }}
                                    <span class="text-sm font-normal text-gray-500 dark:text-gray-400">/ 100</span>
                                </p>
                            </div>
                            @if ($report->assessment->published_at)
                                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Dipublikasikan {{ $report->assessment->published_at->locale('id')->translatedFormat('d M Y') }}</p>
                            @endif
                            @if ($report->assessment->notes)
                                <p class="mt-4 border-t border-gray-200 pt-4 text-sm leading-6 text-gray-700 dark:border-gray-700 dark:text-gray-200">{{ $report->assessment->notes }}</p>
                            @endif
                        @else
                            <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">Belum ada penilaian untuk laporan ini.</p>
                        @endif
                    </x-filament::section>
                @endif
            </aside>
        </div>
    </div>
</x-filament-panels::page>
