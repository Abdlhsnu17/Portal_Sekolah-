<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Petugas — {{ $profil->nama }}</title>
    <meta name="theme-color" content="#163a5f">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=inter:400,500,600,700|plus-jakarta-sans:600,700,800&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-brand-50 font-sans text-slate-700 antialiased">

<main class="grid min-h-screen lg:grid-cols-[1.05fr_0.95fr]">
    {{-- Panel merek --}}
    <section class="relative hidden min-h-screen overflow-hidden bg-brand-950 lg:flex lg:flex-col" aria-label="Tentang portal sekolah">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute -left-40 -top-40 size-136 rounded-full bg-brand-500/30 blur-3xl"></div>
            <div class="absolute -bottom-48 right-0 size-120 rounded-full bg-accent-500/15 blur-3xl"></div>
            <div class="absolute inset-0 opacity-[0.06]" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 28px 28px;"></div>
        </div>

        <div class="relative flex min-h-screen flex-col justify-between p-10 xl:p-14">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <span class="grid size-12 place-items-center rounded-2xl bg-white/10 ring-1 ring-white/15 backdrop-blur">
                    <x-logo tone="light" class="size-9" />
                </span>
                <span>
                    <span class="block text-sm font-bold tracking-tight text-white">{{ $profil->nama }}</span>
                    <span class="mt-0.5 block text-xs text-brand-200/60">Portal administrasi resmi</span>
                </span>
            </a>

            <div class="max-w-xl py-12">
                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3.5 py-1.5 text-xs font-semibold text-brand-100 ring-1 ring-white/15">
                    <span class="size-2 rounded-full bg-accent-400"></span>
                    Ruang kerja tenaga kependidikan
                </span>
                <h2 class="mt-7 text-4xl font-bold leading-[1.15] tracking-tight text-white xl:text-5xl">
                    Kelola sekolah dengan<br><span class="text-accent-400">lebih mudah.</span>
                </h2>
                <p class="mt-5 max-w-lg text-base leading-relaxed text-brand-100/70">
                    Satu tempat yang aman untuk mengelola pendaftaran, data akademik, informasi sekolah, dan layanan administrasi.
                </p>

                <div class="mt-9 grid max-w-lg grid-cols-3 gap-3">
                    @foreach ([['shield-check', 'Aman', 'Akses terlindungi'], ['users', 'Terpadu', 'Data terpusat'], ['sparkles', 'Praktis', 'Mudah digunakan']] as [$ikon, $judul, $teks])
                        <div class="rounded-2xl bg-white/[0.07] p-4 ring-1 ring-white/10 backdrop-blur-sm">
                            <x-icon :name="$ikon" class="size-5 text-accent-400" />
                            <p class="mt-3 text-sm font-bold text-white">{{ $judul }}</p>
                            <p class="mt-1 text-xs leading-relaxed text-brand-100/50">{{ $teks }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center justify-between text-xs text-brand-100/40">
                <p>&copy; {{ date('Y') }} {{ $profil->nama }}</p>
                <p>Portal Sekolah</p>
            </div>
        </div>
    </section>

    {{-- Formulir --}}
    <section class="relative flex min-h-screen items-center overflow-hidden bg-white px-5 py-10 sm:px-10 lg:px-12">
        <div class="pointer-events-none absolute -right-32 -top-32 size-80 rounded-full bg-brand-100/70 blur-3xl lg:hidden"></div>
        <div class="relative mx-auto w-full max-w-md">
            <div class="mb-10 flex items-center justify-between lg:hidden">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <x-logo class="size-11" />
                    <span class="max-w-48 text-sm font-bold leading-tight text-brand-950">{{ $profil->nama }}</span>
                </a>
            </div>

            <div class="mb-8">
                <p class="eyebrow text-brand-500">Portal Petugas</p>
                <h1 class="mt-3 text-3xl font-bold tracking-tight text-brand-950">Selamat datang kembali</h1>
                <p class="mt-2 text-sm leading-relaxed text-slate-500">Masukkan akun resmi Anda untuk melanjutkan ke panel administrasi.</p>
            </div>

            <x-alert />

            <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5" x-data="{ tampilkanSandi: false, memuat: false }" @submit="memuat = true">
                @csrf
                <x-form.input name="email" label="Email" type="email" required autofocus placeholder="nama@sekolah.test" />

                <div>
                    <label for="password" class="block text-sm font-semibold text-slate-700">
                        Kata Sandi <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative mt-1.5">
                        <input :type="tampilkanSandi ? 'text' : 'password'" name="password" id="password" required autocomplete="current-password"
                               class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 pr-20 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-brand-400 focus:ring-4 focus:ring-brand-100/70">
                        <button type="button" @click="tampilkanSandi = !tampilkanSandi" class="absolute inset-y-0 right-0 px-4 text-xs font-semibold text-brand-600 transition hover:text-brand-800" x-text="tampilkanSandi ? 'Sembunyikan' : 'Lihat'"></button>
                    </div>
                </div>

                @if ($captchaRequired)
                    <div>
                        <label for="captcha" class="block text-sm font-medium text-slate-700">
                            Verifikasi: berapa {{ $captchaQuestion }}? <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" name="captcha" id="captcha" required inputmode="numeric"
                               class="mt-1.5 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100/70">
                        @error('captcha') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                <label class="flex cursor-pointer items-center gap-2.5 text-sm text-slate-600">
                    <input type="checkbox" name="remember" value="1"
                           class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    Ingat saya di perangkat ini
                </label>

                <button type="submit"
                        :disabled="memuat"
                        class="group flex w-full items-center justify-center gap-2 rounded-xl bg-brand-500 px-4 py-3.5 text-sm font-semibold text-white shadow-lift transition hover:bg-brand-600 disabled:cursor-wait disabled:opacity-70">
                    <span x-text="memuat ? 'Memproses...' : 'Masuk ke Panel'"></span>
                    <x-icon name="login" class="size-4" />
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="{{ route('home') }}"
                   class="inline-flex rounded-lg px-3 py-2 text-sm font-semibold text-brand-700 transition hover:bg-brand-50 hover:text-brand-900">
                    Kembali ke Beranda
                </a>
            </div>

            <div class="mt-8 flex items-start gap-3 rounded-xl border border-brand-100 bg-brand-50/70 p-4 text-xs leading-relaxed text-slate-500">
                <x-icon name="shield-check" class="mt-0.5 size-5 shrink-0 text-brand-500" />
                <p>Halaman ini hanya untuk petugas berwenang. Aktivitas masuk dicatat untuk menjaga keamanan data sekolah.</p>
            </div>

            <p class="mt-8 text-center text-xs text-slate-400 lg:text-left">
                Mengalami kendala akses? Hubungi administrator sekolah.
            </p>
        </div>
    </section>
</main>

</body>
</html>
