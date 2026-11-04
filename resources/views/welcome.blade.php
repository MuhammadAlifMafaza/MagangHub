<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="MagangHub membantu mahasiswa dan dosen Institut Widya Pratama Pekalongan mengelola laporan mingguan, umpan balik, dan jadwal pembimbingan magang.">
        <meta name="theme-color" content="#1c3155">
        <title>MagangHub — Portal Pengelolaan Magang</title>
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-white font-sans text-ink antialiased">
        <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-white focus:px-4 focus:py-3 focus:text-primary-700 focus:shadow-lg">Lewati ke konten utama</a>

        <header class="sticky top-0 z-40 border-b border-line bg-surface/95 shadow-sm backdrop-blur">
            <nav aria-label="Navigasi utama" class="mx-auto flex min-h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" aria-label="MagangHub, halaman utama" class="inline-flex items-center rounded focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary-700">
                    <img src="{{ asset('images/brand/MagangHub-Logo-tag.svg') }}" alt="MagangHub" class="h-10 w-auto">
                </a>

                <div class="hidden items-center gap-6 md:flex">
                    <a href="#fitur" class="text-sm font-medium text-ink-muted transition hover:text-primary-700">Fitur</a>
                    <a href="#alur" class="text-sm font-medium text-ink-muted transition hover:text-primary-700">Alur penggunaan</a>
                    <a href="#institusi" class="text-sm font-medium text-ink-muted transition hover:text-primary-700">Tentang</a>
                </div>

                @auth
                    @php
                        $user = auth()->user();
                        $panelUrl = match ($user->role) {
                            \App\Enums\UserRole::Student => route('dashboard'),
                            \App\Enums\UserRole::Lecturer => route('filament.dosen.pages.dashboard'),
                            \App\Enums\UserRole::Admin => route('filament.admin.pages.dashboard'),
                        };
                    @endphp

                    <details class="group relative">
                        <summary aria-label="Menu akun {{ $user->name }}" class="flex min-h-11 cursor-pointer list-none items-center gap-2 rounded-xl border border-line bg-surface px-2 py-1.5 text-sm text-ink shadow-sm transition hover:bg-bg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 [&::-webkit-details-marker]:hidden">
                            <span aria-hidden="true" class="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary-50 text-xs font-semibold text-primary-700">{{ $user->initials() }}</span>
                            <span class="hidden max-w-36 truncate font-semibold sm:inline">{{ $user->name }}</span>
                            <span aria-hidden="true" class="text-ink-muted transition-transform group-open:rotate-180">⌄</span>
                        </summary>

                        <div class="absolute end-0 top-full z-50 mt-2 w-64 rounded-2xl border border-line bg-surface p-2 text-ink shadow-xl">
                            <div class="flex items-center gap-3 px-3 py-3">
                                <span aria-hidden="true" class="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary-50 font-semibold text-primary-700">{{ $user->initials() }}</span>
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold">{{ $user->name }}</span>
                                    <span class="block truncate text-xs text-ink-muted">{{ $user->email }}</span>
                                </span>
                            </div>
                            <div class="my-1 border-t border-line"></div>
                            <a href="{{ $panelUrl }}" class="flex min-h-10 items-center rounded-lg px-3 text-sm font-medium text-ink transition hover:bg-primary-50 hover:text-primary-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700">Buka panel</a>
                            @if ($user->role === \App\Enums\UserRole::Student)
                                <a href="{{ route('profile.edit') }}" class="flex min-h-10 items-center rounded-lg px-3 text-sm font-medium text-ink transition hover:bg-primary-50 hover:text-primary-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700">Profil dan pengaturan</a>
                            @endif
                            <div class="my-1 border-t border-line"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="flex min-h-10 w-full items-center rounded-lg px-3 text-start text-sm font-medium text-danger transition hover:bg-danger-bg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger">Keluar</button>
                            </form>
                        </div>
                    </details>
                @else
                    <a href="{{ route('login') }}" class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-primary-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700">Masuk</a>
                @endauth
            </nav>
        </header>

        <main id="main-content" tabindex="-1">
            <section class="relative isolate overflow-hidden bg-linear-to-br from-primary-800 via-primary-700 to-primary-800 text-white">
                <div aria-hidden="true" class="absolute -end-24 -top-24 -z-10 size-96 rounded-full border-[48px] border-white/5"></div>
                <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 py-16 sm:px-6 sm:py-20 lg:grid-cols-[1.1fr_0.9fr] lg:px-8 lg:py-24">
                    <div class="max-w-2xl">
                        <p class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-sm font-medium text-white/90">
                            <span aria-hidden="true" class="size-2 rounded-full bg-brand-teal"></span>
                            Portal kegiatan magang · Institut Widya Pratama Pekalongan
                        </p>
                        <h1 class="mt-6 text-4xl font-semibold leading-tight tracking-tight sm:text-5xl lg:text-6xl">Perjalanan magang lebih terarah, dari laporan hingga bimbingan.</h1>
                        <p class="mt-5 max-w-xl text-base leading-7 text-white/80 sm:text-lg">MagangHub membantu mahasiswa dan dosen mengelola laporan mingguan, jadwal pembimbingan, umpan balik, dan progres dalam satu portal.</p>
                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            @auth
                                <a href="{{ $panelUrl }}" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-white px-6 py-3 text-sm font-semibold text-primary-800 shadow-sm transition hover:bg-primary-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Buka panel</a>
                            @else
                                <a href="{{ route('login') }}" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-white px-6 py-3 text-sm font-semibold text-primary-800 shadow-sm transition hover:bg-primary-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Masuk ke portal</a>
                            @endauth
                            <a href="#alur" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-white/30 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Lihat alur penggunaan</a>
                        </div>
                        <p class="mt-4 text-sm text-white/70">Akun diberikan oleh administrator. Pendaftaran mandiri tidak tersedia.</p>
                    </div>

                    <div aria-label="Ringkasan fitur MagangHub" class="relative mx-auto w-full max-w-lg rounded-3xl border border-white/15 bg-white/10 p-4 shadow-2xl shadow-black/10 backdrop-blur sm:p-6">
                        <div class="flex items-center justify-between gap-3 border-b border-white/15 pb-4">
                            <div class="flex items-center gap-3">
                                <img src="{{ asset('images/brand/Logo.svg') }}" alt="" class="size-10 rounded-xl bg-white p-1.5">
                                <div><p class="text-sm font-semibold">Ruang kerja magang</p><p class="mt-0.5 text-xs text-white/70">Laporan · Bimbingan · Progres</p></div>
                            </div>
                            <span class="rounded-full bg-brand-teal/20 px-3 py-1 text-xs font-semibold text-white">MagangHub</span>
                        </div>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <article class="rounded-2xl border border-white/10 bg-primary-900/30 p-4">
                                <span aria-hidden="true" class="flex size-9 items-center justify-center rounded-xl bg-white/10"><flux:icon.document-text variant="outline" class="size-5" /></span>
                                <h2 class="mt-3 text-sm font-semibold">Laporan mingguan</h2>
                                <p class="mt-1 text-xs leading-5 text-white/70">Kirim laporan dan pantau proses pemeriksaannya.</p>
                            </article>
                            <article class="rounded-2xl border border-white/10 bg-primary-900/30 p-4">
                                <span aria-hidden="true" class="flex size-9 items-center justify-center rounded-xl bg-white/10"><flux:icon.calendar-days variant="outline" class="size-5" /></span>
                                <h2 class="mt-3 text-sm font-semibold">Jadwal bimbingan</h2>
                                <p class="mt-1 text-xs leading-5 text-white/70">Lihat jadwal dan kelola pemesanan dengan dosen.</p>
                            </article>
                            <div class="flex items-center gap-3 rounded-2xl border border-white/10 bg-primary-900/30 p-4 sm:col-span-2">
                                <span aria-hidden="true" class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-teal/20"><flux:icon.chat-bubble-left-right variant="outline" class="size-5" /></span>
                                <p class="text-sm leading-6 text-white/80">Umpan balik dan nilai tersedia bersama riwayat laporan, sesuai akses akun Anda.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="fitur" class="scroll-mt-24 bg-bg py-16 sm:py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <header class="mx-auto max-w-2xl text-center">
                        <p class="text-sm font-semibold text-secondary-700">Fitur utama</p>
                        <h2 class="mt-2 text-3xl font-semibold tracking-tight text-ink sm:text-4xl">Semua kebutuhan magang, lebih mudah dipantau.</h2>
                        <p class="mt-4 text-base leading-7 text-ink-muted">Informasi tersusun berdasarkan aktivitas yang dijalani mahasiswa dan dosen selama program magang.</p>
                    </header>

                    <div class="mt-10 grid gap-5 md:grid-cols-3">
                        <article class="rounded-2xl border border-line bg-surface p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md">
                            <span aria-hidden="true" class="flex size-12 items-center justify-center rounded-xl bg-primary-50 text-primary-700"><flux:icon.document-text variant="outline" class="size-6" /></span>
                            <h3 class="mt-5 text-lg font-semibold text-ink">Laporan mingguan</h3>
                            <p class="mt-2 text-sm leading-6 text-ink-muted">Kirim laporan sesuai periode, baca umpan balik, dan lihat riwayat versi berkas.</p>
                        </article>
                        <article class="rounded-2xl border border-line bg-surface p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md">
                            <span aria-hidden="true" class="flex size-12 items-center justify-center rounded-xl bg-primary-50 text-primary-700"><flux:icon.calendar-days variant="outline" class="size-6" /></span>
                            <h3 class="mt-5 text-lg font-semibold text-ink">Bimbingan terjadwal</h3>
                            <p class="mt-2 text-sm leading-6 text-ink-muted">Temukan jadwal pembimbing aktif dan pantau status pemesanan bimbingan.</p>
                        </article>
                        <article class="rounded-2xl border border-line bg-surface p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md">
                            <span aria-hidden="true" class="flex size-12 items-center justify-center rounded-xl bg-primary-50 text-primary-700"><flux:icon.chat-bubble-left-right variant="outline" class="size-6" /></span>
                            <h3 class="mt-5 text-lg font-semibold text-ink">Umpan balik dan nilai</h3>
                            <p class="mt-2 text-sm leading-6 text-ink-muted">Tinjau masukan dosen dan lihat hasil penilaian setelah dipublikasikan.</p>
                        </article>
                    </div>
                </div>
            </section>

            <section id="alur" class="scroll-mt-24 border-y border-line bg-surface py-16 sm:py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <header class="mx-auto max-w-2xl text-center">
                        <p class="text-sm font-semibold text-secondary-700">Alur penggunaan</p>
                        <h2 class="mt-2 text-3xl font-semibold tracking-tight text-ink sm:text-4xl">Mulai dari akun, lanjutkan dengan progres.</h2>
                        <p class="mt-4 text-base leading-7 text-ink-muted">Akun portal disiapkan administrator. Setelah masuk, setiap peran melihat informasi yang sesuai.</p>
                    </header>

                    <ol class="mt-10 grid gap-5 md:grid-cols-4">
                        <li class="rounded-2xl border border-line bg-bg p-5">
                            <span class="flex size-9 items-center justify-center rounded-full bg-primary-700 text-sm font-bold text-white">1</span>
                            <h3 class="mt-4 font-semibold text-ink">Terima akun</h3>
                            <p class="mt-2 text-sm leading-6 text-ink-muted">Dapatkan informasi akun dari administrator.</p>
                        </li>
                        <li class="rounded-2xl border border-line bg-bg p-5">
                            <span class="flex size-9 items-center justify-center rounded-full bg-primary-700 text-sm font-bold text-white">2</span>
                            <h3 class="mt-4 font-semibold text-ink">Masuk ke portal</h3>
                            <p class="mt-2 text-sm leading-6 text-ink-muted">Gunakan email atau NIM/NIDN dan kata sandi.</p>
                        </li>
                        <li class="rounded-2xl border border-line bg-bg p-5">
                            <span class="flex size-9 items-center justify-center rounded-full bg-primary-700 text-sm font-bold text-white">3</span>
                            <h3 class="mt-4 font-semibold text-ink">Kelola kegiatan</h3>
                            <p class="mt-2 text-sm leading-6 text-ink-muted">Kirim laporan atau atur bimbingan sesuai peran.</p>
                        </li>
                        <li class="rounded-2xl border border-line bg-bg p-5">
                            <span class="flex size-9 items-center justify-center rounded-full bg-primary-700 text-sm font-bold text-white">4</span>
                            <h3 class="mt-4 font-semibold text-ink">Pantau hasil</h3>
                            <p class="mt-2 text-sm leading-6 text-ink-muted">Lihat status, umpan balik, dan nilai yang tersedia.</p>
                        </li>
                    </ol>
                </div>
            </section>

            <section id="institusi" class="scroll-mt-24 bg-bg py-16 sm:py-20">
                <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[0.8fr_1.2fr] lg:items-center lg:px-8">
                    <div class="flex items-center gap-4 rounded-2xl border border-line bg-surface p-5 shadow-sm sm:p-7">
                        <img src="{{ asset('images/brand/Logo.svg') }}" alt="" class="size-14 rounded-xl bg-primary-50 p-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-secondary-700">Portal kegiatan magang</p>
                            <p class="mt-1 text-lg font-semibold text-ink">MagangHub</p>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-secondary-700">Tentang portal</p>
                        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-ink sm:text-3xl">Dukungan digital untuk kegiatan magang Institut Widya Pratama Pekalongan.</h2>
                        <p class="mt-4 text-sm leading-7 text-ink-muted">MagangHub menyatukan informasi laporan dan bimbingan dalam satu tempat. Mahasiswa, dosen, dan administrator mendapatkan tampilan sesuai peran serta hak akses masing-masing.</p>
                    </div>
                </div>
            </section>

            <section id="faq" class="scroll-mt-24 border-t border-line bg-surface py-16 sm:py-20">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <header class="text-center">
                        <p class="text-sm font-semibold text-secondary-700">Pertanyaan umum</p>
                        <h2 class="mt-2 text-3xl font-semibold tracking-tight text-ink">Yang perlu diketahui sebelum masuk</h2>
                    </header>
                    <div class="mt-8 divide-y divide-line rounded-2xl border border-line bg-bg px-5 sm:px-7">
                        <details class="group py-5">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-ink focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary-700 [&::-webkit-details-marker]:hidden">Siapa yang dapat menggunakan MagangHub?<span aria-hidden="true" class="text-primary-700 transition-transform group-open:rotate-180">⌄</span></summary>
                            <p class="mt-3 max-w-2xl text-sm leading-6 text-ink-muted">Mahasiswa, dosen, dan administrator yang memiliki akun aktif. Informasi yang ditampilkan mengikuti peran dan hak akses akun.</p>
                        </details>
                        <details class="group py-5">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-ink focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary-700 [&::-webkit-details-marker]:hidden">Bagaimana cara mendapatkan akun?<span aria-hidden="true" class="text-primary-700 transition-transform group-open:rotate-180">⌄</span></summary>
                            <p class="mt-3 max-w-2xl text-sm leading-6 text-ink-muted">Akun diberikan oleh administrator. Jika belum menerima informasi akun, hubungi administrator atau unit terkait di Institut Widya Pratama Pekalongan.</p>
                        </details>
                        <details class="group py-5">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-ink focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary-700 [&::-webkit-details-marker]:hidden">Apakah saya dapat masuk menggunakan NIM atau NIDN?<span aria-hidden="true" class="text-primary-700 transition-transform group-open:rotate-180">⌄</span></summary>
                            <p class="mt-3 max-w-2xl text-sm leading-6 text-ink-muted">Mahasiswa dapat menggunakan NIM, dosen dapat menggunakan NIDN, dan email tetap dapat digunakan sebagai pilihan masuk.</p>
                        </details>
                        <details class="group py-5">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-ink focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary-700 [&::-webkit-details-marker]:hidden">Bagaimana jika saya lupa kata sandi?<span aria-hidden="true" class="text-primary-700 transition-transform group-open:rotate-180">⌄</span></summary>
                            <p class="mt-3 max-w-2xl text-sm leading-6 text-ink-muted">Pilih “Lupa kata sandi?” di halaman masuk. Tautan pengaturan ulang dikirim ke alamat email yang terdaftar.</p>
                        </details>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-primary-700 bg-primary-800 text-white">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-2 lg:grid-cols-[1.2fr_0.8fr_1fr] lg:px-8">
                <div>
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-3 rounded focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white">
                        <img src="{{ asset('images/brand/Logo.svg') }}" alt="" class="size-10 rounded-lg bg-white p-1.5">
                        <span class="text-lg font-semibold">MagangHub</span>
                    </a>
                    <p class="mt-4 max-w-sm text-sm leading-6 text-white/70">Portal untuk membantu kegiatan laporan dan pembimbingan magang Institut Widya Pratama Pekalongan.</p>
                </div>
                <div>
                    <h2 class="text-sm font-semibold">Jelajahi</h2>
                    <ul class="mt-4 grid gap-3 text-sm text-white/75">
                        <li><a href="#fitur" class="rounded hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Fitur portal</a></li>
                        <li><a href="#alur" class="rounded hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Alur penggunaan</a></li>
                        <li><a href="#faq" class="rounded hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Pertanyaan umum</a></li>
                        <li><a href="{{ route('login') }}" class="rounded hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Masuk ke portal</a></li>
                    </ul>
                </div>
                <div>
                    <h2 class="text-sm font-semibold">Institut Widya Pratama Pekalongan</h2>
                    <p class="mt-4 text-sm leading-6 text-white/70">Untuk informasi akun dan bantuan penggunaan portal, silakan menghubungi administrator atau unit terkait di institut.</p>
                </div>
            </div>
            <div class="border-t border-white/15">
                <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-5 text-xs text-white/60 sm:px-6 md:flex-row md:items-center md:justify-between lg:px-8">
                    <span>© {{ now()->year }} MagangHub · Institut Widya Pratama Pekalongan</span>
                    <span>Informasi portal ditampilkan sesuai peran dan hak akses akun.</span>
                </div>
            </div>
        </footer>
    </body>
</html>
