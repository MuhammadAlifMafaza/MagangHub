@if (request()->user()->role->value === 'student')
<x-layouts::app.sidebar title="Detail Laporan">
    <main class="mx-auto flex w-full max-w-6xl flex-col gap-6 px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
        <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-sm text-ink-muted"><a class="rounded underline decoration-line-input underline-offset-4 hover:text-primary-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700" href="{{ route('mahasiswa.laporan.index') }}">Laporan mingguan</a><flux:icon.chevron-right variant="mini" class="size-4" /><span aria-current="page" class="font-medium text-ink">Minggu ke-{{ $report->reportPeriod->week_number }}</span></nav>

        <header class="flex flex-col gap-4 rounded-2xl border border-primary-100 bg-linear-to-r from-primary-50/80 to-surface p-5 sm:flex-row sm:items-end sm:justify-between sm:p-6">
            <div><p class="text-sm text-ink-muted">Riwayat pengumpulan</p><h1 class="mt-1 text-2xl font-semibold tracking-tight text-ink">Laporan minggu ke-{{ $report->reportPeriod->week_number }}</h1><p class="mt-1 text-sm text-ink-muted">{{ $report->reportPeriod->starts_on->locale('id')->translatedFormat('d M Y') }}–{{ $report->reportPeriod->ends_on->locale('id')->translatedFormat('d M Y') }}</p></div>
            <div class="flex items-center gap-2"><span class="text-sm text-ink-muted">Status pemeriksaan</span><x-ui.status-badge :label="$report->review_status->label()" :tone="$report->review_status->color()" /></div>
        </header>

        @if ($report->review_status->value === 'revision_requested')
            <p role="status" class="flex items-start gap-3 rounded-xl border border-warning/20 bg-warning-bg p-4 text-sm leading-6 text-warning"><flux:icon.exclamation-triangle variant="outline" class="mt-0.5 size-5 shrink-0" /><span><strong class="font-semibold">Revisi diperlukan.</strong> Baca umpan balik dosen pada versi terakhir, lalu unggah versi baru melalui daftar laporan.</span><a class="ml-auto inline-flex min-h-10 shrink-0 items-center gap-1 font-semibold underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-warning" href="{{ route('mahasiswa.laporan.index') }}">Ke laporan <flux:icon.arrow-right variant="outline" class="size-4" /></a></p>
        @endif

        <section aria-labelledby="history-heading" class="overflow-hidden rounded-2xl border border-line bg-surface shadow-sm shadow-primary-800/[0.03]">
            <div class="border-b border-line px-4 py-4 sm:px-5"><h2 id="history-heading" class="font-semibold text-ink">Riwayat versi</h2><p class="mt-1 text-sm text-ink-muted">Dokumen dan umpan balik untuk laporan ini</p></div>
            <ol class="divide-y divide-line px-5 sm:px-6">
                @foreach ($report->versions as $version)
                    <li class="relative py-5 first:pt-5">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex min-w-0 items-start gap-3">
                                <span aria-hidden="true" class="flex size-9 shrink-0 items-center justify-center rounded-lg {{ $loop->last ? 'bg-primary-50 text-primary-700' : 'bg-neutral-bg text-neutral' }}"><flux:icon.document variant="outline" class="size-5" /></span>
                                <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><h3 class="font-semibold text-ink">Versi {{ $version->version_number }}</h3>@if ($loop->last)<span class="rounded-full bg-primary-50 px-2 py-0.5 text-xs font-semibold text-primary-700">Terbaru</span>@endif @if ($version->is_late)<x-ui.status-badge label="Terlambat" tone="danger" />@endif</div><p class="mt-1 break-all text-sm font-medium text-ink">{{ $version->original_name }}</p><p class="mt-1 text-xs text-ink-muted">{{ $version->submitted_at->locale('id')->translatedFormat('d M Y, H:i') }} WIB <span aria-hidden="true">·</span> @if ($version->size_bytes < 1024) {{ number_format($version->size_bytes) }} byte @else {{ number_format($version->size_bytes / 1024, $version->size_bytes < 10240 ? 1 : 0, ',', '.') }} KB @endif</p>@if ($version->student_note)<p class="mt-3 whitespace-pre-line text-sm leading-6 text-ink-muted"><span class="font-medium text-ink">Catatan Anda:</span> {{ $version->student_note }}</p>@endif</div>
                            </div>
                            <a href="{{ route('laporan.versi.download', $version) }}" class="inline-flex min-h-10 shrink-0 items-center justify-center gap-2 self-start rounded-lg border border-line-input px-3 py-2 text-sm font-semibold text-primary-700 transition hover:bg-primary-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700"><flux:icon.arrow-down-tray variant="outline" class="size-4" />Unduh berkas</a>
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
        </section>

        <section aria-labelledby="grade-heading" class="rounded-2xl border border-line bg-linear-to-r from-surface to-primary-50/70 p-4 shadow-sm shadow-primary-800/[0.03] sm:p-5">
            <div class="flex items-start gap-4"><span aria-hidden="true" class="flex size-11 shrink-0 items-center justify-center rounded-xl {{ $report->assessment ? 'bg-success-bg text-success' : 'bg-neutral-bg text-neutral' }}"><flux:icon.trophy variant="outline" class="size-5" /></span><div class="min-w-0 flex-1"><h2 id="grade-heading" class="font-semibold text-ink">Hasil penilaian</h2>
                @if ($report->assessment)
                    <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"><p class="text-3xl font-semibold tabular-nums tracking-tight text-ink">{{ number_format((float) $report->assessment->score, 2, ',', '.') }}<span class="ml-1 text-base font-medium text-ink-muted">/ 100</span></p><p class="text-sm text-ink-muted">Dipublikasikan {{ $report->assessment->published_at?->locale('id')->translatedFormat('d M Y') }}</p></div>
                    @if ($report->assessment->notes)<p class="mt-3 whitespace-pre-line border-t border-line pt-3 text-sm leading-6 text-ink-muted">{{ $report->assessment->notes }}</p>@endif
                @else
                    <p class="mt-2 text-sm leading-6 text-ink-muted">Belum ada nilai yang dipublikasikan untuk laporan ini. Nilai akan terlihat di sini setelah dosen menyelesaikan penilaian.</p>
                @endif
            </div></div>
        </section>
    </main>
</x-layouts::app.sidebar>
@else
@if (request()->user()->role->value === 'lecturer')
<main class="mx-auto w-full max-w-6xl space-y-6 px-4 py-6 text-ink sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="Breadcrumb" class="text-sm text-ink-muted"><a class="underline underline-offset-4 hover:text-primary-700" href="{{ route('dosen.laporan.antrean') }}">Antrean laporan</a><span aria-hidden="true"> / </span><span aria-current="page">Detail laporan</span></nav>
    <header class="flex flex-col gap-4 rounded-2xl border border-primary-100 bg-linear-to-r from-primary-50/80 to-surface p-5 sm:flex-row sm:items-end sm:justify-between sm:p-6"><div><p class="text-sm text-ink-muted">Pemeriksaan laporan mahasiswa</p><h1 class="mt-1 text-2xl font-semibold tracking-tight">Laporan minggu ke-{{ $report->reportPeriod->week_number }}</h1><p class="mt-2 font-medium">{{ $report->reportPeriod->internship->student->user->name }}</p><p class="mt-1 text-sm text-ink-muted">{{ $report->reportPeriod->starts_on->locale('id')->translatedFormat('d M Y') }}–{{ $report->reportPeriod->ends_on->locale('id')->translatedFormat('d M Y') }}</p></div><x-ui.status-badge :label="$report->review_status->label()" :tone="$report->review_status->color()" /></header>

    <section aria-labelledby="lecturer-versions-heading" class="overflow-hidden rounded-2xl border border-line bg-surface shadow-sm shadow-primary-800/[0.03]">
        <div class="border-b border-line px-4 py-4 sm:px-5"><h2 id="lecturer-versions-heading" class="font-semibold">Riwayat berkas dan umpan balik</h2><p class="mt-1 text-sm text-ink-muted">Baca PDF langsung atau unduh dokumen laporan.</p></div>
        <ol class="divide-y divide-line px-4 sm:px-5">
            @foreach ($report->versions as $version)
                <li class="flex flex-col gap-3 py-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0"><h3 class="font-semibold">Versi {{ $version->version_number }}@if ($loop->last) <span class="text-sm font-normal text-ink-muted">(terbaru)</span>@endif</h3><p class="mt-1 break-all text-sm text-ink">{{ $version->original_name }}</p><p class="mt-1 text-sm text-ink-muted">{{ $version->submitted_at->locale('id')->translatedFormat('d M Y, H:i') }} WIB · {{ number_format($version->size_bytes / 1024, 0) }} KB</p>@if ($version->review)<div class="mt-3 rounded-xl bg-bg p-3"><p class="text-sm font-medium">{{ $version->review->decision->label() }}</p><p class="mt-1 whitespace-pre-line text-sm leading-6 text-ink-muted">{{ $version->review->feedback ?: 'Tidak ada catatan tambahan.' }}</p></div>@endif</div>
                    <div class="flex shrink-0 flex-wrap gap-2">
                        @if ($version->mime_type === 'application/pdf')
                            <a class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-line-input px-3 py-2 text-sm font-semibold text-primary-700 hover:bg-primary-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700" href="{{ route('laporan.versi.preview', $version) }}" target="_blank" rel="noopener">Baca PDF</a>
                        @endif
                        <a class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-line-input px-3 py-2 text-sm font-semibold text-primary-700 hover:bg-primary-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700" href="{{ route('laporan.versi.download', $version) }}">Unduh berkas</a>
                    </div>
                </li>
            @endforeach
        </ol>
    </section>

    @if ($report->review_status->value === 'pending_review')
        <form method="POST" action="{{ route('dosen.laporan.review', $report) }}" class="space-y-5 rounded-2xl border border-line bg-surface p-4 shadow-sm shadow-primary-800/[0.03] sm:p-5">
            @csrf
            <fieldset class="space-y-3"><legend class="font-semibold">Keputusan pemeriksaan</legend><div class="flex flex-col gap-3 sm:flex-row sm:gap-6"><label class="inline-flex items-center gap-2 text-sm"><input type="radio" name="decision" value="approved" required class="size-4 accent-primary-700"> Setujui</label><label class="inline-flex items-center gap-2 text-sm"><input type="radio" name="decision" value="revision_requested" class="size-4 accent-primary-700"> Minta revisi</label></div></fieldset>
            <div class="space-y-2"><label for="review-feedback" class="text-sm font-medium">Umpan balik</label><textarea id="review-feedback" class="block w-full rounded-md border border-line-input bg-surface p-3 text-sm text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700" name="feedback" rows="4">{{ old('feedback') }}</textarea>@error('feedback')<p role="alert" class="text-sm text-danger">{{ $message }}</p>@enderror</div>
            <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-md bg-primary-700 px-4 py-2 text-sm font-medium text-white hover:bg-primary-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700">Simpan pemeriksaan</button>
        </form>
    @endif

    @if ($report->review_status->value === 'approved')
        <section aria-labelledby="assessment-heading" class="space-y-4 rounded-2xl border border-line bg-surface p-4 shadow-sm shadow-primary-800/[0.03] sm:p-5">
            <div><h2 id="assessment-heading" class="font-semibold">Penilaian</h2><p class="mt-1 text-sm text-ink-muted">Nilai yang disimpan sebagai draf belum terlihat oleh mahasiswa.</p></div>
            <form method="POST" action="{{ route('dosen.laporan.penilaian.update', $report) }}" class="space-y-4">
                @csrf @method('PUT')
                <div class="space-y-2"><label for="assessment-score" class="text-sm font-medium">Nilai (0–100)</label><input id="assessment-score" type="number" min="0" max="100" step="0.01" name="score" value="{{ old('score', $report->assessment?->score) }}" required class="block min-h-10 w-full rounded-md border border-line-input bg-surface px-3 py-2 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 sm:max-w-xs">@error('score')<p role="alert" class="text-sm text-danger">{{ $message }}</p>@enderror</div>
                <div class="space-y-2"><label for="assessment-notes" class="text-sm font-medium">Catatan <span class="font-normal text-ink-muted">(opsional)</span></label><textarea id="assessment-notes" name="notes" rows="3" class="block w-full rounded-md border border-line-input bg-surface p-3 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700">{{ old('notes', $report->assessment?->notes) }}</textarea></div>
                <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-md border border-line-input px-4 py-2 text-sm font-medium text-ink hover:bg-bg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700">Simpan draf</button>
            </form>
            @if ($report->assessment?->status->value === 'draft')
                <form method="POST" action="{{ route('dosen.penilaian.publikasi', $report->assessment) }}">@csrf<button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-md bg-primary-700 px-4 py-2 text-sm font-medium text-white hover:bg-primary-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700">Publikasikan nilai</button></form>
            @endif
        </section>
    @endif
</main>
@else
<main class="mx-auto max-w-5xl space-y-6 p-6 text-ink"><h1 class="text-2xl font-semibold">Laporan · Minggu ke-{{ $report->reportPeriod->week_number }}</h1><p>Status pemeriksaan: {{ $report->review_status->label() }}</p>
    @foreach($report->versions as $version)<article class="space-y-2 rounded-lg border border-line bg-surface p-4"><h2 class="font-semibold">Versi {{ $version->version_number }} · {{ $version->original_name }}</h2><p class="text-sm text-ink-muted">{{ $version->submitted_at->translatedFormat('d M Y, H:i') }} WIB · {{ $version->size_bytes }} byte</p><a class="text-primary-700 underline" href="{{ route('laporan.versi.download', $version) }}">Unduh berkas</a></article>@endforeach
</main>
@endif
@endif
