<x-filament-panels::page.simple heading="" subheading="">
    <div class="grid w-full overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-900 lg:grid-cols-2">
        <aside class="staff-login-brand relative hidden flex-col justify-between overflow-hidden p-10 text-white lg:flex xl:p-12">
            <div aria-hidden="true" class="absolute -end-20 -top-20 size-72 rounded-full border-[36px] border-white/10"></div>
            <div class="relative">
                <img src="{{ asset('images/brand/MagangHub-Logo-tag.svg') }}" alt="MagangHub" class="h-12 w-auto rounded-lg bg-white px-3 py-2">
                <p class="mt-16 text-sm font-semibold uppercase tracking-[0.18em] text-white/70">Institut Widya Pratama Pekalongan</p>
                <h1 class="mt-4 text-3xl font-semibold leading-tight xl:text-4xl">Ruang kerja staff magang yang terhubung.</h1>
                <p class="mt-5 max-w-md text-sm leading-7 text-white/75">Kelola laporan, jadwal pembimbingan, dan kegiatan magang melalui panel sesuai peran Anda.</p>
            </div>
            <p class="relative text-xs text-white/60">Akses hanya untuk akun administrator dan dosen yang aktif.</p>
        </aside>

        <section class="p-6 sm:p-10 xl:p-12">
            <div class="mb-8 lg:hidden">
                <img src="{{ asset('images/brand/MagangHub-Logo-tag.svg') }}" alt="MagangHub" class="h-10 w-auto">
            </div>
            <p class="text-sm font-semibold text-primary-700">{{ filament()->getCurrentPanel()->getId() === 'dosen' ? 'Panel dosen' : 'Panel administrator' }}</p>
            <h2 class="mt-2 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $this->getHeading() }}</h2>
            <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $this->getSubheading() }}</p>

            <div class="mt-8">
                {{ $this->content }}
            </div>

            <div class="mt-6 flex flex-col items-center justify-between gap-3 text-sm sm:flex-row">
                <a href="{{ route('password.request') }}" class="font-medium text-primary-700 underline underline-offset-4">Lupa kata sandi?</a>
                <a href="{{ route('login') }}" class="text-gray-600 underline underline-offset-4 dark:text-gray-300">Masuk melalui portal mahasiswa</a>
            </div>
        </section>
    </div>
</x-filament-panels::page.simple>
