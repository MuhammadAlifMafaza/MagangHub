@if (request()->user()->role->value === 'student')
    @php
        $report = $report ?? null;
        $collectionStatus = $period->collectionStatus();
        $reviewStatus = $report?->review_status;
        $hasRevisionRequest = $reviewStatus?->value === 'revision_requested';
    @endphp

    <div class="mx-auto flex w-full max-w-7xl flex-col gap-5 px-4 py-5 sm:px-6 sm:py-7 lg:px-8">
        <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-sm text-ink-muted"><a class="rounded underline decoration-line-input underline-offset-4 hover:text-primary-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700" href="{{ route('mahasiswa.laporan.index') }}">Laporan mingguan</a><flux:icon.chevron-right variant="mini" class="size-4" /><span aria-current="page" class="font-medium text-ink">Minggu ke-{{ $period->week_number }}</span></nav>

        @if (session('status'))
            <p role="status" class="flex items-start gap-3 rounded-xl border border-success/20 bg-success-bg p-4 text-sm text-success"><flux:icon.check-circle variant="outline" class="size-5 shrink-0" />{{ session('status') }}</p>
        @endif

        <header class="flex flex-col gap-4 rounded-2xl border border-primary-100 bg-linear-to-r from-primary-50/80 to-surface p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-5">
            <div>
                <p class="text-sm text-ink-muted">Detail periode laporan</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-ink">Laporan minggu ke-{{ $period->week_number }}</h1>
                <p class="mt-1 text-sm text-ink-muted">{{ $period->starts_on->locale('id')->translatedFormat('d M Y') }}–{{ $period->ends_on->locale('id')->translatedFormat('d M Y') }}</p>
            </div>
            <div class="flex flex-col gap-2 sm:items-end">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-sm text-ink-muted">Status pengumpulan</span>
                    <x-ui.status-badge :label="$collectionStatus->label()" :tone="$collectionStatus->color()" />
                </div>
                @if ($reviewStatus)
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-sm text-ink-muted">Status pemeriksaan</span>
                        <x-ui.status-badge :label="$reviewStatus->label()" :tone="$reviewStatus->color()" />
                    </div>
                @endif
            </div>
        </header>

        @if ($hasRevisionRequest)
            <p role="status" class="flex flex-col gap-3 rounded-xl border border-warning/20 bg-warning-bg p-4 text-sm leading-6 text-warning sm:flex-row sm:items-center"><span class="flex items-start gap-3"><flux:icon.exclamation-triangle variant="outline" class="mt-0.5 size-5 shrink-0" /><span><strong class="font-semibold">Revisi diperlukan.</strong> Baca umpan balik dosen pada versi terakhir sebelum mengirim revisi.</span></span><a class="inline-flex min-h-10 shrink-0 items-center gap-1 font-semibold underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-warning sm:ml-auto" href="#submission-form">Ke formulir revisi <flux:icon.arrow-down variant="outline" class="size-4" /></a></p>
        @endif

        <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <section aria-labelledby="history-heading" class="overflow-hidden rounded-2xl border border-line bg-surface shadow-sm shadow-primary-800/[0.03]">
                <div class="border-b border-line px-4 py-4 sm:px-5">
                    <h2 id="history-heading" class="font-semibold text-ink">Riwayat versi</h2>
                    <p class="mt-1 text-sm text-ink-muted">Dokumen dan umpan balik untuk periode ini</p>
                </div>

                @if ($report && $report->versions->isNotEmpty())
                    <ol class="divide-y divide-line px-5 sm:px-6">
                        @foreach ($report->versions as $version)
                            <li class="relative py-5 first:pt-5">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="flex min-w-0 items-start gap-3">
                                        <span aria-hidden="true" class="flex size-9 shrink-0 items-center justify-center rounded-lg {{ $loop->last ? 'bg-primary-50 text-primary-700' : 'bg-neutral-bg text-neutral' }}"><flux:icon.document variant="outline" class="size-5" /></span>
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h3 class="font-semibold text-ink">Versi {{ $version->version_number }}</h3>
                                                @if ($loop->last)<span class="rounded-full bg-primary-50 px-2 py-0.5 text-xs font-semibold text-primary-700">Terbaru</span>@endif
                                                @if ($version->is_late)<x-ui.status-badge label="Terlambat" tone="danger" />@endif
                                            </div>
                                            <p class="mt-1 break-all text-sm font-medium text-ink">{{ $version->original_name }}</p>
                                            <p class="mt-1 text-xs text-ink-muted">{{ $version->submitted_at->locale('id')->translatedFormat('d M Y, H:i') }} WIB <span aria-hidden="true">·</span> @if ($version->size_bytes < 1024) {{ number_format($version->size_bytes) }} byte @else {{ number_format($version->size_bytes / 1024, $version->size_bytes < 10240 ? 1 : 0, ',', '.') }} KB @endif</p>
                                            @if ($version->student_note)<p class="mt-3 whitespace-pre-line text-sm leading-6 text-ink-muted"><span class="font-medium text-ink">Catatan Anda:</span> {{ $version->student_note }}</p>@endif
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 flex-wrap gap-2">
                                        @if ($fileAvailability->get($version->getKey(), false))
                                            @if ($version->mime_type === 'application/pdf')
                                                <a href="{{ route('laporan.versi.preview', $version) }}" target="_blank" rel="noopener" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-line-input px-3 py-2 text-sm font-semibold text-primary-700 transition hover:bg-primary-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700"><flux:icon.document-magnifying-glass variant="outline" class="size-4" />Baca PDF</a>
                                            @endif
                                            <a href="{{ route('laporan.versi.download', $version) }}" class="inline-flex min-h-10 items-center justify-center gap-2 self-start rounded-lg border border-line-input px-3 py-2 text-sm font-semibold text-primary-700 transition hover:bg-primary-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700"><flux:icon.arrow-down-tray variant="outline" class="size-4" />Unduh berkas</a>
                                        @else
                                            <p role="status" class="max-w-sm rounded-lg border border-warning/20 bg-warning-bg px-3 py-2 text-sm leading-5 text-warning">Berkas belum tersedia di penyimpanan. Hubungi administrator untuk pemulihan.</p>
                                        @endif
                                    </div>
                                </div>
                                @if ($version->review)
                                    <div class="ml-12 mt-4 rounded-xl border border-info/20 bg-info-bg/50 p-4 sm:ml-12">
                                        <div class="flex flex-wrap items-center justify-between gap-2"><h4 class="flex items-center gap-2 text-sm font-semibold text-info"><flux:icon.chat-bubble-left-right variant="outline" class="size-4" />{{ $version->review->decision->label() }}</h4><time class="text-xs text-ink-muted" datetime="{{ $version->review->reviewed_at->toIso8601String() }}">{{ $version->review->reviewed_at->locale('id')->translatedFormat('d M Y, H:i') }} WIB</time></div>
                                        @if ($version->review->feedback)<p class="mt-2 whitespace-pre-line text-sm leading-6 text-ink">{{ $version->review->feedback }}</p>@else<p class="mt-2 text-sm text-ink-muted">Tidak ada catatan tambahan.</p>@endif
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @else
                    <div class="flex flex-col items-center px-6 py-10 text-center">
                        <span aria-hidden="true" class="flex size-11 items-center justify-center rounded-xl bg-primary-50 text-primary-700"><flux:icon.document variant="outline" class="size-5" /></span>
                        <h3 class="mt-3 font-semibold text-ink">Belum ada berkas laporan</h3>
                        <p class="mt-1 max-w-md text-sm leading-6 text-ink-muted">
                            @if ($report)
                                Riwayat versi akan tampil di sini setelah berkas laporan tersedia.
                            @else
                                Setelah laporan dikirim, versi berkas dan umpan balik dosen akan tampil di sini.
                            @endif
                        </p>
                    </div>
                @endif
            </section>

            <aside class="order-first flex flex-col gap-4 lg:order-last">
                <section id="submission-form" aria-labelledby="submission-heading" class="rounded-2xl border border-line bg-surface p-4 shadow-sm shadow-primary-800/[0.03] sm:p-5">
                    <div class="flex items-start gap-3">
                        <span aria-hidden="true" class="flex size-10 shrink-0 items-center justify-center rounded-xl {{ $canUpload ? 'bg-primary-50 text-primary-700' : 'bg-neutral-bg text-neutral' }}"><flux:icon.cloud-arrow-up variant="outline" class="size-5" /></span>
                        <div class="min-w-0">
                            <h2 id="submission-heading" class="font-semibold text-ink">Pekerjaan Anda</h2>
                            @if ($canUpload)
                                @if ($hasRevisionRequest)
                                    <p class="mt-1 text-sm leading-6 text-ink-muted">Baca umpan balik di riwayat versi, lalu unggah revisi laporan.</p>
                                @elseif ($collectionStatus->value === 'not_submitted_late')
                                    <p class="mt-1 text-sm leading-6 text-danger">Tenggat terlewati. Laporan masih dapat dikirim dan akan ditandai terlambat.</p>
                                @elseif ($report)
                                    <p class="mt-1 text-sm leading-6 text-ink-muted">Laporan belum diperiksa. Anda masih dapat memperbarui berkasnya.</p>
                                @else
                                    <p class="mt-1 text-sm leading-6 text-ink-muted">Kirim laporan untuk periode ini sebelum tenggat.</p>
                                @endif
                            @elseif ($collectionStatus->value === 'not_opened')
                                <p class="mt-1 text-sm leading-6 text-ink-muted">Periode dibuka {{ $period->starts_on->locale('id')->translatedFormat('d M Y') }}. Formulir tersedia setelah periode dimulai.</p>
                            @elseif ($reviewStatus?->value === 'pending_review')
                                <p class="mt-1 text-sm leading-6 text-ink-muted">Laporan sudah dikirim dan sedang menunggu pemeriksaan dosen.</p>
                            @elseif ($reviewStatus?->value === 'approved')
                                <p class="mt-1 text-sm leading-6 text-success">Laporan telah disetujui dosen.</p>
                            @else
                                <p class="mt-1 text-sm leading-6 text-ink-muted">Belum ada tindakan yang perlu dilakukan untuk periode ini.</p>
                            @endif
                        </div>
                    </div>

                    @if ($canUpload)
                        @include('reports.partials.period-upload-form', ['period' => $period, 'report' => $report])
                    @endif
                </section>

                <section aria-labelledby="grade-heading" class="rounded-2xl border border-line bg-linear-to-r from-surface to-primary-50/70 p-4 shadow-sm shadow-primary-800/[0.03] sm:p-5">
                    <div class="flex items-start gap-4">
                        <span aria-hidden="true" class="flex size-11 shrink-0 items-center justify-center rounded-xl {{ $report?->assessment ? 'bg-success-bg text-success' : 'bg-neutral-bg text-neutral' }}"><flux:icon.trophy variant="outline" class="size-5" /></span>
                        <div class="min-w-0 flex-1">
                            <h2 id="grade-heading" class="font-semibold text-ink">Hasil penilaian</h2>
                            @if ($report?->assessment)
                                <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"><p class="text-3xl font-semibold tabular-nums tracking-tight text-ink">{{ number_format((float) $report->assessment->score, 2, ',', '.') }}<span class="ml-1 text-base font-medium text-ink-muted">/ 100</span></p><p class="text-sm text-ink-muted">Dipublikasikan {{ $report->assessment->published_at?->locale('id')->translatedFormat('d M Y') }}</p></div>
                                @if ($report->assessment->notes)<p class="mt-3 whitespace-pre-line border-t border-line pt-3 text-sm leading-6 text-ink-muted">{{ $report->assessment->notes }}</p>@endif
                            @elseif ($report)
                                <p class="mt-2 text-sm leading-6 text-ink-muted">Belum ada nilai yang dipublikasikan. Nilai akan tampil setelah dosen menyelesaikan penilaian.</p>
                            @else
                                <p class="mt-2 text-sm leading-6 text-ink-muted">Nilai akan tampil di sini setelah laporan diperiksa dan dosen memublikasikan penilaian.</p>
                            @endif
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </div>
@elseif (request()->user()->role->value === 'lecturer')
<div class="mx-auto w-full max-w-7xl space-y-5 px-4 py-5 text-ink sm:px-6 sm:py-7 lg:px-8">
    <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-sm text-ink-muted">
        <a class="rounded underline underline-offset-4 hover:text-primary-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700" href="{{ \App\Filament\Dosen\Resources\ReportReviews\ReportReviewResource::getUrl('index', panel: 'dosen') }}">Pemeriksaan laporan</a>
        <flux:icon.chevron-right variant="mini" class="size-4" />
        <span aria-current="page">Detail laporan</span>
    </nav>

    <header class="flex flex-col gap-4 rounded-2xl border border-primary-100 bg-linear-to-r from-primary-50/80 to-surface p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-5">
        <div>
            <p class="text-sm font-semibold text-secondary-700">Pemeriksaan laporan mahasiswa</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight">Laporan minggu ke-{{ $report->reportPeriod->week_number }}</h1>
            <p class="mt-1 font-medium">{{ $report->reportPeriod->internship->student->user->name }}</p>
            <p class="mt-1 text-sm text-ink-muted">{{ $report->reportPeriod->starts_on->locale('id')->translatedFormat('d M Y') }}–{{ $report->reportPeriod->ends_on->locale('id')->translatedFormat('d M Y') }}</p>
        </div>
        <x-ui.status-badge :label="$report->review_status->label()" :tone="$report->review_status->color()" />
    </header>

    <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <section aria-labelledby="lecturer-versions-heading" class="overflow-hidden rounded-2xl border border-line bg-surface shadow-sm shadow-primary-800/[0.03]">
            <div class="border-b border-line px-4 py-3.5 sm:px-5">
                <h2 id="lecturer-versions-heading" class="font-semibold">Riwayat berkas dan umpan balik</h2>
                <p class="mt-1 text-sm text-ink-muted">Baca PDF langsung atau unduh dokumen laporan.</p>
            </div>
            <ol class="divide-y divide-line px-4 sm:px-5">
                @forelse ($report->versions as $version)
                    <li class="flex flex-col gap-3 py-4">
                        <div class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-semibold">Versi {{ $version->version_number }}</h3>
                                    @if ($loop->last)<x-ui.status-badge label="Terbaru" tone="info" />@endif
                                    @if ($version->is_late)<x-ui.status-badge label="Terlambat" tone="danger" />@endif
                                </div>
                                <p class="mt-1 break-all text-sm font-medium">{{ $version->original_name }}</p>
                                <p class="mt-1 text-xs text-ink-muted">{{ $version->submitted_at->locale('id')->translatedFormat('d M Y, H:i') }} WIB <span aria-hidden="true">·</span> {{ number_format($version->size_bytes / 1024, 0) }} KB</p>
                            </div>
                            <div class="flex shrink-0 flex-wrap gap-2 sm:justify-end">
                                @if ($fileAvailability->get($version->getKey(), false))
                                    @if ($version->mime_type === 'application/pdf')
                                        <a class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-line-input px-3 py-2 text-sm font-semibold text-primary-700 transition hover:bg-primary-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700" href="{{ route('laporan.versi.preview', $version) }}" target="_blank" rel="noopener"><flux:icon.document-magnifying-glass variant="outline" class="size-4" />Baca PDF</a>
                                    @endif
                                    <a class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-line-input px-3 py-2 text-sm font-semibold text-primary-700 transition hover:bg-primary-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700" href="{{ route('laporan.versi.download', $version) }}"><flux:icon.arrow-down-tray variant="outline" class="size-4" />Unduh berkas</a>
                                @else
                                    <p role="status" class="max-w-sm rounded-lg border border-warning/20 bg-warning-bg px-3 py-2 text-sm leading-5 text-warning">Berkas belum tersedia di penyimpanan. Hubungi administrator untuk pemulihan.</p>
                                @endif
                            </div>
                        </div>
                        @if ($version->review)
                            <div class="rounded-xl border border-info/20 bg-info-bg/50 p-3 sm:ml-12 sm:p-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <h4 class="flex items-center gap-2 text-sm font-semibold text-info"><flux:icon.chat-bubble-left-right variant="outline" class="size-4" />{{ $version->review->decision->label() }}</h4>
                                    <time class="text-xs text-ink-muted" datetime="{{ $version->review->reviewed_at->toIso8601String() }}">{{ $version->review->reviewed_at->locale('id')->translatedFormat('d M Y, H:i') }} WIB</time>
                                </div>
                                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-ink">{{ $version->review->feedback ?: 'Tidak ada catatan tambahan.' }}</p>
                            </div>
                        @endif
                    </li>
                @empty
                    <li class="px-2 py-8 text-center">
                        <p class="font-medium">Belum ada berkas laporan</p>
                        <p class="mt-1 text-sm text-ink-muted">Belum ada versi yang dikumpulkan untuk periode ini.</p>
                    </li>
                @endforelse
            </ol>
        </section>

        <aside class="space-y-4">
            <section aria-label="Informasi mahasiswa" class="rounded-2xl border border-line bg-surface p-4 shadow-sm shadow-primary-800/[0.03]">
                <h2 class="font-semibold">Informasi mahasiswa</h2>
                <dl class="mt-3 space-y-3 text-sm">
                    <div><dt class="text-xs font-semibold uppercase tracking-wide text-ink-muted">Nama</dt><dd class="mt-0.5 font-medium">{{ $report->reportPeriod->internship->student->user->name }}</dd></div>
                    <div><dt class="text-xs font-semibold uppercase tracking-wide text-ink-muted">NIM</dt><dd class="mt-0.5 font-medium">{{ $report->reportPeriod->internship->student->nim }}</dd></div>
                    <div><dt class="text-xs font-semibold uppercase tracking-wide text-ink-muted">Program studi</dt><dd class="mt-0.5 font-medium">{{ $report->reportPeriod->internship->student->study_program ?: '—' }}</dd></div>
                </dl>
            </section>

            @if ($report->review_status->value === 'pending_review')
                <form method="POST" action="{{ route('dosen.laporan.review', $report) }}" class="space-y-4 rounded-2xl border border-line bg-surface p-4 shadow-sm shadow-primary-800/[0.03]">
                    @csrf
                    <div>
                        <h2 class="font-semibold">Keputusan pemeriksaan</h2>
                        <p class="mt-1 text-sm text-ink-muted">Pilih hasil pemeriksaan dan sampaikan umpan balik.</p>
                    </div>
                    <fieldset class="space-y-2">
                        <legend class="sr-only">Keputusan pemeriksaan</legend>
                        <label class="flex min-h-10 items-center gap-2 rounded-lg border border-line px-3 text-sm"><input type="radio" name="decision" value="approved" required class="size-4 accent-primary-700"> Setujui laporan</label>
                        <label class="flex min-h-10 items-center gap-2 rounded-lg border border-line px-3 text-sm"><input type="radio" name="decision" value="revision_requested" class="size-4 accent-primary-700"> Minta revisi</label>
                    </fieldset>
                    <div class="space-y-2">
                        <label for="review-feedback" class="text-sm font-medium">Umpan balik</label>
                        <textarea id="review-feedback" class="block w-full rounded-lg border border-line-input bg-surface p-3 text-sm text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700" name="feedback" rows="4">{{ old('feedback') }}</textarea>
                        @error('feedback')<p role="alert" class="text-sm text-danger">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-lg bg-primary-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700"><flux:icon.check-circle variant="outline" class="size-4" />Simpan pemeriksaan</button>
                </form>
            @elseif ($report->review_status->value === 'approved')
                <section aria-labelledby="assessment-heading" class="space-y-4 rounded-2xl border border-line bg-surface p-4 shadow-sm shadow-primary-800/[0.03]">
                    <div>
                        <h2 id="assessment-heading" class="font-semibold">Penilaian</h2>
                        <p class="mt-1 text-sm text-ink-muted">Nilai yang disimpan sebagai draf belum terlihat oleh mahasiswa.</p>
                    </div>
                    <form method="POST" action="{{ route('dosen.laporan.penilaian.update', $report) }}" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <div class="space-y-2">
                            <label for="assessment-score" class="text-sm font-medium">Nilai (0–100)</label>
                            <input id="assessment-score" type="number" min="0" max="100" step="0.01" name="score" value="{{ old('score', $report->assessment?->score) }}" required class="block min-h-10 w-full rounded-lg border border-line-input bg-surface px-3 py-2 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700">
                            @error('score')<p role="alert" class="text-sm text-danger">{{ $message }}</p>@enderror
                        </div>
                        <div class="space-y-2">
                            <label for="assessment-notes" class="text-sm font-medium">Catatan <span class="font-normal text-ink-muted">(opsional)</span></label>
                            <textarea id="assessment-notes" name="notes" rows="3" class="block w-full rounded-lg border border-line-input bg-surface p-3 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700">{{ old('notes', $report->assessment?->notes) }}</textarea>
                        </div>
                        <button type="submit" class="inline-flex min-h-10 w-full items-center justify-center rounded-lg border border-line-input px-4 py-2 text-sm font-semibold text-ink transition hover:bg-bg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700">Simpan draf</button>
                    </form>
                    @if ($report->assessment?->status->value === 'draft')
                        <form method="POST" action="{{ route('dosen.penilaian.publikasi', $report->assessment) }}">
                            @csrf
                            <button type="submit" class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-lg bg-primary-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700"><flux:icon.paper-airplane variant="outline" class="size-4" />Publikasikan nilai</button>
                        </form>
                    @endif
                </section>
            @else
                <section class="rounded-2xl border border-info/20 bg-info-bg/50 p-4">
                    <h2 class="font-semibold text-info">Menunggu revisi mahasiswa</h2>
                    <p class="mt-1 text-sm leading-6 text-ink-muted">Umpan balik pemeriksaan tersedia pada riwayat versi laporan.</p>
                </section>
            @endif
        </aside>
    </div>
</div>
@else
<div class="mx-auto w-full max-w-7xl space-y-5 px-4 py-5 text-ink sm:px-6 sm:py-7 lg:px-8">
    <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-sm text-ink-muted">
        <a class="rounded underline underline-offset-4 hover:text-primary-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700" href="{{ route('filament.admin.pages.administrative-recap') }}">Rekap administratif</a>
        <flux:icon.chevron-right variant="mini" class="size-4" />
        <span aria-current="page">Detail laporan</span>
    </nav>

    <header class="flex flex-col gap-4 rounded-2xl border border-primary-100 bg-linear-to-r from-primary-50/80 to-surface p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-5">
        <div>
            <p class="text-sm font-semibold text-secondary-700">Ruang administrasi</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight">Laporan minggu ke-{{ $report->reportPeriod->week_number }}</h1>
            <p class="mt-1 font-medium">{{ $report->reportPeriod->internship->student->user->name }}</p>
            <p class="mt-1 text-sm text-ink-muted">{{ $report->reportPeriod->starts_on->locale('id')->translatedFormat('d M Y') }}–{{ $report->reportPeriod->ends_on->locale('id')->translatedFormat('d M Y') }}</p>
        </div>
        <x-ui.status-badge :label="$report->review_status->label()" :tone="$report->review_status->color()" />
    </header>

    <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_18rem]">
    <section aria-labelledby="admin-versions-heading" class="overflow-hidden rounded-2xl border border-line bg-surface shadow-sm shadow-primary-800/[0.03]">
        <div class="border-b border-line px-4 py-3.5 sm:px-5">
            <h2 id="admin-versions-heading" class="font-semibold">Riwayat berkas dan pemeriksaan</h2>
            <p class="mt-1 text-sm text-ink-muted">Periksa dokumen terbaru, ketersediaan penyimpanan, dan umpan balik dosen.</p>
        </div>
        <ol class="divide-y divide-line px-4 sm:px-5">
            @forelse ($report->versions as $version)
                <li class="py-4">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex min-w-0 items-start gap-3">
                            <span aria-hidden="true" class="flex size-10 shrink-0 items-center justify-center rounded-xl {{ $loop->last ? 'bg-primary-50 text-primary-700' : 'bg-neutral-bg text-neutral' }}">
                                <flux:icon.document-text variant="outline" class="size-5" />
                            </span>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-semibold">Versi {{ $version->version_number }}</h3>
                                    @if ($loop->last)<x-ui.status-badge label="Terbaru" tone="info" />@endif
                                    @if ($version->is_late)<x-ui.status-badge label="Terlambat" tone="danger" />@endif
                                </div>
                                <p class="mt-1 break-all text-sm font-medium">{{ $version->original_name }}</p>
                                <p class="mt-1 text-xs text-ink-muted">{{ $version->submitted_at->locale('id')->translatedFormat('d M Y, H:i') }} WIB <span aria-hidden="true">·</span> @if ($version->size_bytes < 1024) {{ number_format($version->size_bytes) }} byte @else {{ number_format($version->size_bytes / 1024, $version->size_bytes < 10240 ? 1 : 0, ',', '.') }} KB @endif</p>
                                @if ($version->student_note)
                                    <p class="mt-3 whitespace-pre-line text-sm leading-6 text-ink-muted"><span class="font-medium text-ink">Catatan mahasiswa:</span> {{ $version->student_note }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex shrink-0 flex-wrap items-center gap-2 sm:justify-end">
                            @if ($fileAvailability->get($version->getKey(), false))
                                <x-ui.status-badge label="Berkas tersedia" tone="success" />
                                @if ($version->mime_type === 'application/pdf')
                                    <a class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-line-input px-3 py-2 text-sm font-semibold text-primary-700 transition hover:bg-primary-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700" href="{{ route('laporan.versi.preview', $version) }}" target="_blank" rel="noopener">
                                        <flux:icon.document-magnifying-glass variant="outline" class="size-4" />Baca PDF
                                    </a>
                                @endif
                                <a class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-line-input px-3 py-2 text-sm font-semibold text-primary-700 transition hover:bg-primary-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700" href="{{ route('laporan.versi.download', $version) }}">
                                    <flux:icon.arrow-down-tray variant="outline" class="size-4" />Unduh
                                </a>
                            @else
                                <x-ui.status-badge label="Berkas tidak tersedia" tone="danger" />
                                <p role="status" class="basis-full text-sm leading-5 text-warning sm:text-right">Berkas tidak ditemukan di penyimpanan. Hubungi administrator untuk pemulihan.</p>
                            @endif
                        </div>
                    </div>

                    @if ($version->review)
                        <div class="ml-0 mt-4 rounded-xl border border-info/20 bg-info-bg/50 p-4 sm:ml-12">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <h4 class="flex items-center gap-2 text-sm font-semibold text-info"><flux:icon.chat-bubble-left-right variant="outline" class="size-4" />{{ $version->review->decision->label() }}</h4>
                                <time class="text-xs text-ink-muted" datetime="{{ $version->review->reviewed_at->toIso8601String() }}">{{ $version->review->reviewed_at->locale('id')->translatedFormat('d M Y, H:i') }} WIB</time>
                            </div>
                            <p class="mt-2 whitespace-pre-line text-sm leading-6 text-ink">{{ $version->review->feedback ?: 'Tidak ada catatan tambahan.' }}</p>
                        </div>
                    @endif
                </li>
            @empty
                <li class="px-2 py-10 text-center">
                    <p class="font-medium">Belum ada versi laporan</p>
                    <p class="mt-1 text-sm text-ink-muted">Belum ada berkas yang dikumpulkan untuk periode ini.</p>
                </li>
            @endforelse
        </ol>
    </section>

    <aside class="space-y-4">
    <section aria-label="Informasi mahasiswa" class="rounded-2xl border border-line bg-surface p-4 shadow-sm shadow-primary-800/[0.03]">
        <h2 class="font-semibold">Informasi mahasiswa</h2>
        <dl class="mt-3 space-y-3 text-sm">
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-ink-muted">NIM</dt><dd class="mt-0.5 font-medium">{{ $report->reportPeriod->internship->student->nim }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-ink-muted">Program studi</dt><dd class="mt-0.5 font-medium">{{ $report->reportPeriod->internship->student->study_program ?: '—' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-ink-muted">Tempat magang</dt><dd class="mt-0.5 font-medium">{{ $report->reportPeriod->internship->company_name }}</dd></div>
        </dl>
    </section>

    <section aria-labelledby="admin-assessment-heading" class="rounded-2xl border border-line bg-surface p-4 shadow-sm shadow-primary-800/[0.03]">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 id="admin-assessment-heading" class="font-semibold">Penilaian</h2>
                <p class="mt-1 text-sm text-ink-muted">Status dan catatan penilaian laporan ini.</p>
            </div>
            @if ($report->assessment)
                <x-ui.status-badge :label="$report->assessment->status->label()" :tone="$report->assessment->status->color()" />
            @endif
        </div>
        @if ($report->assessment)
            <div class="mt-4 flex flex-col gap-3 border-t border-line pt-4 sm:flex-row sm:items-end sm:justify-between">
                <p class="text-3xl font-semibold tabular-nums tracking-tight">{{ number_format((float) $report->assessment->score, 2, ',', '.') }}<span class="ml-1 text-base font-medium text-ink-muted">/ 100</span></p>
                @if ($report->assessment->published_at)
                    <p class="text-sm text-ink-muted">Dipublikasikan {{ $report->assessment->published_at->locale('id')->translatedFormat('d M Y') }}</p>
                @endif
            </div>
            @if ($report->assessment->notes)
                <p class="mt-4 whitespace-pre-line border-t border-line pt-4 text-sm leading-6 text-ink-muted">{{ $report->assessment->notes }}</p>
            @endif
        @else
            <p class="mt-3 text-sm leading-6 text-ink-muted">Belum ada penilaian untuk laporan ini.</p>
        @endif
    </section>
    </aside>
    </div>
</div>
@endif
