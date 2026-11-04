<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="MagangHub membantu mahasiswa dan dosen mengelola laporan mingguan, umpan balik, dan jadwal pembimbingan magang.">
        <title>MagangHub — Portal Pengelolaan Magang</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-white font-sans text-ink antialiased">
        <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-white focus:px-4 focus:py-3 focus:text-primary-700 focus:shadow-lg">Lewati ke konten utama</a>

        <header class="border-b border-line">
            <nav aria-label="Navigasi utama" class="mx-auto flex min-h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" aria-label="MagangHub, halaman utama" class="inline-flex items-center rounded focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary-700">
                    <img src="{{ asset('images/brand/MagangHub-Logo-tag.svg') }}" alt="MagangHub" class="h-10 w-auto">
                </a>
                <a href="{{ route('login') }}" class="inline-flex min-h-10 items-center justify-center rounded-md bg-primary-700 px-5 py-2 text-sm font-medium text-white transition hover:bg-primary-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700">Masuk</a>
            </nav>
        </header>

        <main id="main-content" tabindex="-1">
            <section class="mx-auto grid max-w-6xl items-center gap-10 px-4 py-16 sm:px-6 sm:py-20 lg:grid-cols-2 lg:gap-16 lg:px-8 lg:py-24">
                <div class="max-w-xl">
                    <p class="text-sm font-semibold text-secondary-700">Portal pengelolaan kegiatan magang</p>
                    <h1 class="mt-4 text-4xl font-semibold leading-tight tracking-tight text-ink sm:text-5xl">Progres magang, tersusun dalam satu tempat.</h1>
                    <p class="mt-5 text-base leading-7 text-ink-muted">MagangHub membantu mahasiswa dan dosen mengelola laporan mingguan, umpan balik, dan jadwal pembimbingan dengan lebih teratur.</p>
                    <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center justify-center rounded-md bg-primary-700 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-primary-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700">Masuk ke portal</a>
                        <a href="#fitur" class="inline-flex min-h-11 items-center justify-center rounded-md border border-line-input bg-white px-5 py-2.5 text-sm font-medium text-ink transition hover:bg-bg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700">Lihat fitur</a>
                    </div>
                    <p class="mt-4 text-sm text-ink-muted">Akun diberikan oleh administrator. Pendaftaran mandiri tidak tersedia.</p>
                </div>

                <div class="flex min-h-64 items-center justify-center rounded-xl border border-line bg-bg p-8 sm:min-h-80">
                    <div class="max-w-sm text-center">
                        <img src="{{ asset('images/brand/Logo.svg') }}" alt="" class="mx-auto size-24 sm:size-28">
                        <p class="mt-5 text-lg font-semibold text-primary-700">MagangHub</p>
                        <p class="mt-1 text-sm leading-6 text-ink-muted">Laporan, bimbingan, dan progres magang yang mudah diikuti.</p>
                    </div>
                </div>
            </section>

            <section id="fitur" class="scroll-mt-8 border-t border-line bg-bg py-14 sm:py-16">
                <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                    <header class="max-w-2xl">
                        <p class="text-sm font-semibold text-secondary-700">Fitur utama</p>
                        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-ink sm:text-3xl">Informasi penting, tanpa alur yang berbelit.</h2>
                        <p class="mt-3 text-sm leading-6 text-ink-muted">Gunakan portal untuk mengikuti tahapan pelaporan dan pembimbingan selama kegiatan magang.</p>
                    </header>

                    <div class="mt-8 grid gap-4 md:grid-cols-3">
                        <article class="rounded-lg border border-line bg-white p-5">
                            <span aria-hidden="true" class="flex size-10 items-center justify-center rounded-md bg-primary-50 text-primary-700"><flux:icon.document-text variant="outline" class="size-5" /></span>
                            <h3 class="mt-4 font-semibold text-ink">Laporan mingguan</h3>
                            <p class="mt-2 text-sm leading-6 text-ink-muted">Kirim laporan sesuai periode dan lihat riwayat berkas yang telah dikumpulkan.</p>
                        </article>
                        <article class="rounded-lg border border-line bg-white p-5">
                            <span aria-hidden="true" class="flex size-10 items-center justify-center rounded-md bg-primary-50 text-primary-700"><flux:icon.calendar-days variant="outline" class="size-5" /></span>
                            <h3 class="mt-4 font-semibold text-ink">Jadwal pembimbingan</h3>
                            <p class="mt-2 text-sm leading-6 text-ink-muted">Lihat slot dari dosen pembimbing aktif dan pantau pemesanan Anda.</p>
                        </article>
                        <article class="rounded-lg border border-line bg-white p-5">
                            <span aria-hidden="true" class="flex size-10 items-center justify-center rounded-md bg-primary-50 text-primary-700"><flux:icon.chat-bubble-left-right variant="outline" class="size-5" /></span>
                            <h3 class="mt-4 font-semibold text-ink">Umpan balik dan nilai</h3>
                            <p class="mt-2 text-sm leading-6 text-ink-muted">Tinjau masukan dosen dan lihat nilai setelah dipublikasikan.</p>
                        </article>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-line bg-white">
            <div class="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-5 text-sm text-ink-muted sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                <span class="font-medium text-ink">MagangHub</span>
                <span>Portal pengelolaan laporan dan pembimbingan magang.</span>
            </div>
        </footer>
    </body>
</html>
