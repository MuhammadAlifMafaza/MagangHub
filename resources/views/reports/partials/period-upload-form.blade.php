@php
    $hasRevisionRequest = $report?->review_status->value === 'revision_requested';
    $isUpdatingReport = $report !== null && ! $hasRevisionRequest;
    $showPeriodErrors = (string) old('period_id') === (string) $period->id;
    $uploadTitle = $hasRevisionRequest ? 'Unggah revisi' : ($isUpdatingReport ? 'Perbarui laporan' : 'Kirim laporan');
    $fileLabel = $hasRevisionRequest ? 'Berkas revisi' : ($isUpdatingReport ? 'Berkas laporan terbaru' : 'Berkas laporan');
    $notePlaceholder = $hasRevisionRequest
        ? 'Jelaskan perubahan pada revisi ini (opsional)'
        : ($isUpdatingReport ? 'Jelaskan pembaruan laporan ini (opsional)' : 'Tambahkan catatan tentang laporan ini (opsional)');
    $confirmationHint = $hasRevisionRequest
        ? 'Pastikan revisi ini sudah menanggapi umpan balik dosen.'
        : ($isUpdatingReport ? 'Berkas ini akan menjadi versi terbaru laporan.' : 'Pastikan laporan untuk periode ini sudah siap dikirim.');
@endphp

<form method="POST" enctype="multipart/form-data" action="{{ route('mahasiswa.laporan.store', $period) }}" class="mt-4 flex flex-col gap-4 border-t border-line pt-4">
    @csrf
    <input type="hidden" name="period_id" value="{{ $period->id }}">

    <div class="flex flex-col gap-2">
        <label for="file-{{ $period->id }}" class="text-sm font-medium text-ink">{{ $fileLabel }} <span class="text-danger" aria-hidden="true">*</span></label>
        <input
            id="file-{{ $period->id }}"
            name="file"
            type="file"
            accept=".pdf,.docx"
            required
            aria-describedby="file-help-{{ $period->id }} @if ($showPeriodErrors && $errors->has('file')) file-error-{{ $period->id }} @endif"
            @if ($showPeriodErrors && $errors->has('file')) aria-invalid="true" @endif
            class="block min-h-11 w-full rounded-lg border border-line-input bg-surface text-sm text-ink file:me-3 file:min-h-11 file:border-0 file:bg-primary-50 file:px-3 file:font-semibold file:text-primary-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700"
        >
        <p id="file-help-{{ $period->id }}" class="text-xs leading-5 text-ink-muted">PDF atau DOCX, maksimal 10 MB.</p>
        @if ($showPeriodErrors)
            @error('file')
                <p id="file-error-{{ $period->id }}" role="alert" class="flex items-center gap-1.5 text-sm text-danger"><flux:icon.exclamation-circle variant="outline" class="size-4 shrink-0" />{{ $message }}</p>
            @enderror
        @endif
    </div>

    <div class="flex flex-col gap-2">
        <label for="note-{{ $period->id }}" class="text-sm font-medium text-ink">Catatan untuk dosen <span class="font-normal text-ink-muted">(opsional)</span></label>
        <textarea
            id="note-{{ $period->id }}"
            name="student_note"
            rows="3"
            placeholder="{{ $notePlaceholder }}"
            aria-describedby="note-help-{{ $period->id }} @if ($showPeriodErrors && $errors->has('student_note')) note-error-{{ $period->id }} @endif"
            @if ($showPeriodErrors && $errors->has('student_note')) aria-invalid="true" @endif
            class="w-full rounded-lg border border-line-input bg-surface px-3 py-2.5 text-sm text-ink placeholder:text-ink-muted/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700"
        >{{ $showPeriodErrors ? old('student_note') : '' }}</textarea>
        <p id="note-help-{{ $period->id }}" class="text-xs leading-5 text-ink-muted">Maksimal 5.000 karakter.</p>
        @if ($showPeriodErrors)
            @error('student_note')
                <p id="note-error-{{ $period->id }}" role="alert" class="text-sm text-danger">{{ $message }}</p>
            @enderror
        @endif
    </div>

    <div class="flex flex-col gap-3 border-t border-line pt-4">
        <p class="text-xs leading-5 text-ink-muted">{{ $confirmationHint }}</p>
        <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-lg bg-primary-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700">
            <flux:icon.cloud-arrow-up variant="outline" class="size-4" />
            {{ $uploadTitle }}
        </button>
    </div>
</form>
