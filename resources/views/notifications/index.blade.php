<x-layouts::app.sidebar title="Notifikasi">
    <div class="mx-auto w-full max-w-4xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <flux:heading size="xl">Notifikasi</flux:heading>
                <flux:text class="mt-2">Pembaruan penting tentang laporan mingguan dan jadwal bimbingan Anda.</flux:text>
            </div>

            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('mahasiswa.notifikasi.read-all') }}">
                    @csrf
                    <flux:button type="submit" variant="ghost" icon="check">Tandai semua dibaca</flux:button>
                </form>
            @endif
        </header>

        @if (session('status'))
            <flux:callout variant="success" icon="check-circle">{{ session('status') }}</flux:callout>
        @endif

        <section aria-label="Daftar notifikasi" class="overflow-hidden rounded-2xl border border-line bg-surface shadow-sm">
            @forelse ($notifications as $notification)
                @php($data = $notification->data)
                <article @class(['border-b border-line p-5 last:border-b-0 sm:p-6', 'bg-primary-50/50 dark:bg-slate-900/40' => $notification->unread()])>
                    <div class="flex gap-4">
                        <span @class(['mt-1 flex size-10 shrink-0 items-center justify-center rounded-full', 'bg-primary-100 text-primary-700 dark:bg-slate-800 dark:text-sky-300' => $notification->unread(), 'bg-bg text-ink-muted' => $notification->read()]) aria-hidden="true">
                            <flux:icon.bell variant="outline" class="size-5" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-semibold text-ink">{{ $data['title'] ?? 'Pembaruan MagangHub' }}</h2>
                                @if ($notification->unread())
                                    <span class="rounded-full bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-700 dark:bg-slate-800 dark:text-sky-300">Belum dibaca</span>
                                @endif
                            </div>
                            <p class="mt-2 text-sm leading-6 text-ink-muted">{{ $data['message'] ?? '' }}</p>
                            <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                                <time class="text-xs text-ink-muted" datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->timezone(config('app.timezone'))->translatedFormat('d M Y, H:i') }}</time>
                                @if (filled($data['action_url'] ?? null))
                                    <form method="POST" action="{{ route('mahasiswa.notifikasi.open', $notification->id) }}">
                                        @csrf
                                        <flux:button type="submit" variant="ghost" size="sm" icon-trailing="arrow-right">{{ $data['action_label'] ?? 'Buka' }}</flux:button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="px-6 py-16 text-center">
                    <span class="mx-auto flex size-12 items-center justify-center rounded-full bg-bg text-ink-muted" aria-hidden="true">
                        <flux:icon.bell-slash variant="outline" class="size-6" />
                    </span>
                    <h2 class="mt-4 font-semibold text-ink">Belum ada notifikasi</h2>
                    <p class="mt-2 text-sm text-ink-muted">Pembaruan laporan dan bimbingan akan muncul di sini.</p>
                </div>
            @endforelse
        </section>

        @if ($notifications->hasPages())
            <nav aria-label="Navigasi halaman notifikasi">{{ $notifications->links() }}</nav>
        @endif
    </div>
</x-layouts::app.sidebar>
