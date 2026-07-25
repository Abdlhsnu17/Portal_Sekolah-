<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Petugas — {{ $profil->nama }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-700 antialiased">

<div class="grid min-h-screen lg:grid-cols-2">
    {{-- Panel merek --}}
    <div class="relative hidden overflow-hidden bg-brand-950 lg:block">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute -left-32 -top-32 size-120 rounded-full bg-brand-600/25 blur-3xl"></div>
            <div class="absolute -bottom-40 -right-20 size-96 rounded-full bg-accent-500/10 blur-3xl"></div>
            <div class="absolute inset-0 opacity-[0.07]"
                 style="background-image: linear-gradient(white 1px, transparent 1px), linear-gradient(90deg, white 1px, transparent 1px); background-size: 64px 64px;"></div>
        </div>

        <div class="relative flex h-full flex-col justify-between p-12">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <span class="grid size-11 place-items-center rounded-xl bg-white/10 text-white ring-1 ring-white/15">
                    <x-icon name="academic" class="size-6" />
                </span>
                <span class="text-[15px] font-bold tracking-tight text-white">{{ $profil->nama }}</span>
            </a>

            <div>
                <h2 class="text-4xl font-bold leading-tight tracking-tight text-white">
                    Panel administrasi<br>portal sekolah
                </h2>
                <p class="mt-5 max-w-md text-base leading-relaxed text-brand-100/70">
                    Kelola pendaftaran siswa baru, data siswa dan guru, konten berita, serta galeri
                    sekolah dari satu tempat.
                </p>

                <ul class="mt-8 space-y-3 text-sm text-brand-100/70">
                    @foreach (['Verifikasi berkas pendaftar', 'Kelola data siswa & tenaga pendidik', 'Publikasikan berita dan pengumuman'] as $poin)
                        <li class="flex items-center gap-3">
                            <x-icon name="check-circle" class="size-5 shrink-0 text-accent-400" />{{ $poin }}
                        </li>
                    @endforeach
                </ul>
            </div>

            <p class="text-xs text-brand-100/40">
                &copy; {{ date('Y') }} {{ $profil->nama }}
            </p>
        </div>
    </div>

    {{-- Formulir --}}
    <div class="flex flex-col justify-center px-6 py-12 sm:px-12">
        <div class="mx-auto w-full max-w-sm">
            <div class="lg:hidden">
                <span class="grid size-12 place-items-center rounded-xl bg-brand-700 text-white">
                    <x-icon name="academic" class="size-6" />
                </span>
            </div>

            <h1 class="mt-6 text-2xl font-bold tracking-tight text-brand-950 lg:mt-0">Login Petugas</h1>
            <p class="mt-2 text-sm text-slate-600">Masuk untuk mengakses panel administrasi.</p>

            <x-alert />

            <form method="POST" action="{{ route('login') }}" class="mt-7 space-y-5">
                @csrf
                <x-form.input name="email" label="Email" type="email" required autofocus placeholder="nama@sekolah.test" />

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700">
                        Kata Sandi <span class="text-rose-500">*</span>
                    </label>
                    <input type="password" name="password" id="password" required
                           class="mt-1.5 w-full rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm outline-none transition focus:border-brand-400 focus:ring-2 focus:ring-brand-100">
                </div>

                <label class="flex items-center gap-2.5 text-sm text-slate-600">
                    <input type="checkbox" name="remember" value="1"
                           class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    Ingat saya di perangkat ini
                </label>

                <button type="submit"
                        class="w-full rounded-xl bg-brand-700 px-4 py-3 text-sm font-semibold text-white shadow-card transition hover:bg-brand-800 hover:shadow-lift">
                    Masuk
                </button>
            </form>

            <a href="{{ route('home') }}"
               class="mt-8 inline-flex items-center gap-2 text-sm text-slate-500 transition hover:text-brand-700">
                <x-icon name="arrow-left" class="size-4" /> Kembali ke situs
            </a>
        </div>
    </div>
</div>

</body>
</html>
