<main class="mx-auto w-full max-w-7xl space-y-7 px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
    <header class="relative isolate overflow-hidden rounded-3xl border border-primary-100 bg-linear-to-br from-primary-50 via-surface to-surface p-5 sm:p-7">
        <div aria-hidden="true" class="absolute -right-8 -top-10 -z-10 size-40 rounded-full bg-brand-teal/15 blur-2xl"></div>
        <p class="text-sm font-semibold text-primary-700">Bimbingan akademik</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-ink sm:text-3xl">Jadwal Pembimbingan</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-muted">Pilih waktu yang sesuai, lihat jadwal yang akan datang, dan kelola pemesanan bersama dosen pembimbing Anda.</p>
        @if ($lecturer)
            <p class="mt-4 inline-flex max-w-full flex-wrap items-center gap-x-2 gap-y-1 rounded-xl border border-primary-100 bg-white/80 px-3 py-2 text-sm text-ink-muted"><flux:icon.user-circle variant="outline" class="size-5 text-primary-700" />Pembimbing aktif: <span class="font-semibold text-ink">{{ $lecturer->user->name }}</span>@if ($lecturer->department) <span aria-hidden="true">·</span>{{ $lecturer->department }}@endif</p>
        @endif
    </header>

    @if (session('guidance-success'))
        <p role="status" class="rounded-lg border border-success/20 bg-success-bg p-3 text-sm text-success">{{ session('guidance-success') }}</p>
    @endif
    @if (session('guidance-warning'))
        <p role="alert" class="rounded-lg border border-warning/20 bg-warning-bg p-3 text-sm text-warning">{{ session('guidance-warning') }}</p>
    @endif
    @error('slot')<p role="alert" class="rounded-lg border border-danger/20 bg-danger-bg p-3 text-sm text-danger">{{ $message }}</p>@enderror
    @error('booking')<p role="alert" class="rounded-lg border border-danger/20 bg-danger-bg p-3 text-sm text-danger">{{ $message }}</p>@enderror

    <section aria-labelledby="available-slots-heading" class="space-y-4">
        <div>
            <h2 id="available-slots-heading" class="text-lg font-semibold text-ink">Jadwal tersedia</h2>
            <p class="mt-1 text-sm text-ink-muted">Pemesanan ditutup {{ config('magang.guidance.booking_cutoff_hours') }} jam sebelum jadwal.</p>
            @if ($hasUpcomingBooking)
                <p class="mt-3 rounded-lg bg-warning-bg p-3 text-sm text-warning">Anda memiliki pemesanan mendatang. Selesaikan atau batalkan pemesanan tersebut sebelum memilih jadwal lain.</p>
            @endif
        </div>

        <div class="overflow-hidden rounded-2xl border border-line bg-surface shadow-sm shadow-primary-800/[0.03]">
            @if ($availableSlots->isEmpty())
                <p class="p-5 text-sm text-ink-muted">Belum ada jadwal terbuka dari pembimbing aktif Anda.</p>
            @else
            @foreach ($availableSlots as $slot)
                @php
                    $isFull = $slot->booked_count >= $slot->capacity;
                    $bookingClosed = $slot->starts_at->lessThanOrEqualTo($bookingCutoff);
                @endphp
                <article wire:key="slot-{{ $slot->id }}" class="flex flex-col gap-4 border-b border-line p-4 transition-colors last:border-b-0 hover:bg-primary-50/40 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                    <div class="min-w-0">
                        <h3 class="font-medium text-ink">{{ $slot->starts_at->locale('id')->translatedFormat('l, d F Y') }}</h3>
                        <p class="mt-1 text-sm text-ink-muted">{{ $slot->starts_at->format('H:i') }}–{{ $slot->ends_at->format('H:i') }} WIB · {{ $slot->location ?: 'Lokasi belum ditentukan' }}</p>
                        <p class="mt-1 text-xs text-ink-muted">{{ $slot->booked_count }} dari {{ $slot->capacity }} tempat terisi</p>
                    </div>
                    <div class="flex shrink-0 flex-row flex-wrap items-center justify-between gap-3 sm:flex-col sm:items-end">
                        @if ($isFull)
                            <x-ui.status-badge label="Penuh" tone="neutral" />
                        @elseif ($bookingClosed)
                            <x-ui.status-badge label="Pemesanan ditutup" tone="neutral" />
                        @else
                            <x-ui.status-badge label="Tersedia" tone="success" />
                        @endif

                        @if ($isFull || $bookingClosed || $hasUpcomingBooking)
                            <button type="button" disabled aria-disabled="true" class="inline-flex min-h-10 cursor-not-allowed items-center justify-center rounded-md bg-primary-700 px-4 py-2 text-sm font-medium text-white opacity-50">Pesan slot</button>
                        @else
                            <button type="button" wire:click="confirmBooking({{ $slot->id }})" wire:loading.attr="disabled" wire:target="confirmBooking" class="inline-flex min-h-10 items-center justify-center rounded-md bg-primary-700 px-4 py-2 text-sm font-medium text-white hover:bg-primary-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 disabled:cursor-wait disabled:opacity-60">
                                Pesan slot
                                <span class="sr-only"> {{ $slot->starts_at->locale('id')->translatedFormat('d F H:i') }}</span>
                            </button>
                        @endif
                    </div>
                </article>
            @endforeach
            @endif
        </div>
    </section>

    @if ($bookingToConfirm)
        @php
            $confirmationSlot = $availableSlots->firstWhere('id', $bookingToConfirm);
        @endphp
        @if ($confirmationSlot)
            <section role="alertdialog" aria-modal="true" aria-labelledby="booking-confirmation-title" aria-describedby="booking-confirmation-description" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <div class="w-full max-w-md rounded-lg border border-line bg-surface p-5 shadow-lg sm:p-6">
                    <h2 id="booking-confirmation-title" class="text-lg font-semibold text-ink">Konfirmasi pemesanan</h2>
                    <p class="mt-3 font-medium text-ink">{{ $confirmationSlot->lecturer->user->name }}</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ $confirmationSlot->starts_at->locale('id')->translatedFormat('l, d F Y') }} · {{ $confirmationSlot->starts_at->format('H:i') }}–{{ $confirmationSlot->ends_at->format('H:i') }} WIB</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ $confirmationSlot->location ?: 'Lokasi belum ditentukan' }}</p>
                    <p id="booking-confirmation-description" class="mt-4 text-sm leading-6 text-ink-muted">Pemesanan dapat dibatalkan paling lambat {{ config('magang.guidance.cancellation_cutoff_hours') }} jam sebelum jadwal.</p>
                    <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button type="button" wire:click="$set('bookingToConfirm', null)" class="inline-flex min-h-10 items-center justify-center rounded-md border border-line-input bg-surface px-4 py-2 text-sm font-medium text-ink hover:bg-bg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700">Kembali</button>
                        <button type="button" wire:click="book" wire:loading.attr="disabled" wire:target="book" class="inline-flex min-h-10 items-center justify-center rounded-md bg-primary-700 px-4 py-2 text-sm font-medium text-white hover:bg-primary-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 disabled:cursor-wait disabled:opacity-60">
                            <span wire:loading.remove wire:target="book">Konfirmasi pemesanan</span>
                            <span wire:loading wire:target="book">Memproses…</span>
                        </button>
                    </div>
                </div>
            </section>
        @endif
    @endif

    <section aria-labelledby="booking-history-heading" class="space-y-4">
        <div>
            <h2 id="booking-history-heading" class="text-lg font-semibold text-ink">Riwayat pemesanan</h2>
            <p class="mt-1 text-sm text-ink-muted">Jadwal dan status pemesanan sebelumnya.</p>
        </div>
        <div class="overflow-hidden rounded-2xl border border-line bg-surface shadow-sm shadow-primary-800/[0.03]">
            @if ($bookings->isEmpty())
                <p class="p-5 text-sm text-ink-muted">Belum ada riwayat pemesanan bimbingan.</p>
            @else
            @foreach ($bookings as $booking)
                @php
                    $canCancel = $booking->status === \App\Enums\GuidanceBookingStatus::Booked && $booking->slot->starts_at->greaterThan($cancellationCutoff);
                    $tone = $booking->status->color();
                    $badgeClasses = [
                        'success' => 'bg-success-bg text-success',
                        'warning' => 'bg-warning-bg text-warning',
                        'danger' => 'bg-danger-bg text-danger',
                        'info' => 'bg-info-bg text-info',
                        'neutral' => 'bg-neutral-bg text-neutral',
                    ];
                @endphp
                <article wire:key="booking-{{ $booking->id }}" class="flex flex-col gap-4 border-b border-line p-4 transition-colors last:border-b-0 hover:bg-primary-50/40 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                    <div class="min-w-0">
                        <h3 class="font-medium text-ink">{{ $booking->slot->starts_at->locale('id')->translatedFormat('l, d F Y') }}</h3>
                        <p class="mt-1 text-sm text-ink-muted">{{ $booking->slot->starts_at->format('H:i') }}–{{ $booking->slot->ends_at->format('H:i') }} WIB · {{ $booking->slot->lecturer->user->name }}</p>
                        <p class="mt-1 text-sm text-ink-muted">{{ $booking->slot->location ?: 'Lokasi belum ditentukan' }}</p>
                        <span class="mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $badgeClasses[$tone] ?? $badgeClasses['neutral'] }}">{{ $booking->status->label() }}</span>
                        @if ($booking->lecturer_notes)
                            <p class="mt-3 text-sm text-ink-muted">Catatan dosen: {{ $booking->lecturer_notes }}</p>
                        @endif
                        @if ($booking->cancel_reason)
                            <p class="mt-1 text-sm text-ink-muted">Alasan pembatalan: {{ $booking->cancel_reason }}</p>
                        @endif
                    </div>
                    @if ($booking->status === \App\Enums\GuidanceBookingStatus::Booked)
                        <div class="flex w-full shrink-0 flex-col gap-2 sm:w-64">
                            @if ($canCancel)
                                @if ($bookingToCancel === $booking->id)
                                    <label for="cancel-reason-{{ $booking->id }}" class="text-sm font-medium text-ink">Alasan pembatalan <span class="font-normal text-ink-muted">(opsional)</span></label>
                                    <textarea id="cancel-reason-{{ $booking->id }}" wire:model="cancelReason" rows="2" class="w-full rounded-md border border-line-input bg-surface px-3 py-2 text-sm text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700"></textarea>
                                    @if ($errors->has('cancelReason'))
                                        <span role="alert" class="text-sm text-danger">{{ $errors->first('cancelReason') }}</span>
                                    @endif
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" wire:click="cancel({{ $booking->id }})" wire:loading.attr="disabled" class="inline-flex min-h-10 items-center justify-center rounded-md bg-danger px-3 py-2 text-sm font-medium text-white disabled:opacity-60">Konfirmasi pembatalan</button>
                                        <button type="button" wire:click="$set('bookingToCancel', null)" class="inline-flex min-h-10 items-center justify-center rounded-md border border-line-input px-3 py-2 text-sm font-medium text-ink">Kembali</button>
                                    </div>
                                @else
                                    <button type="button" wire:click="$set('bookingToCancel', {{ $booking->id }})" class="inline-flex min-h-10 items-center justify-center self-start rounded-md border border-line-input px-3 py-2 text-sm font-medium text-ink hover:bg-bg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700">Batalkan pemesanan</button>
                                @endif
                            @else
                                <p class="text-sm leading-6 text-ink-muted">Batas pembatalan telah lewat. Hubungi dosen pembimbing jika perlu mengubah jadwal.</p>
                            @endif
                        </div>
                    @endif
                </article>
            @endforeach
            @endif
        </div>
    </section>
</main>
