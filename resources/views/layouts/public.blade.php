<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Beranda') — {{ $profil->nama ?? 'Portal Sekolah' }}</title>
    <meta name="description" content="@yield('description', 'Portal informasi resmi ' . ($profil->nama ?? 'sekolah') . '.')">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white font-sans text-slate-700 antialiased">

@php
    $navigasi = [
        ['rute' => 'home', 'label' => 'Beranda', 'aktif' => 'home'],
        ['rute' => 'profil', 'label' => 'Profil', 'aktif' => 'profil'],
        ['rute' => 'berita', 'label' => 'Berita', 'aktif' => 'berita*'],
        ['rute' => 'galeri', 'label' => 'Galeri', 'aktif' => 'galeri'],
        ['rute' => 'pendaftaran.status', 'label' => 'Cek Status', 'aktif' => 'pendaftaran.status'],
    ];
@endphp

{{-- Bar utilitas: kontak ringkas, memberi kesan situs resmi --}}
<div class="hidden border-b border-brand-800/40 bg-brand-950 text-brand-100 md:block">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-6 px-6 py-2 text-xs">
        <div class="flex items-center gap-5">
            @if ($profil?->telepon)
                <span class="flex items-center gap-1.5"><x-icon name="phone" class="size-3.5" />{{ $profil->telepon }}</span>
            @endif
            @if ($profil?->email)
                <span class="flex items-center gap-1.5"><x-icon name="mail" class="size-3.5" />{{ $profil->email }}</span>
            @endif
        </div>
        <div class="flex items-center gap-5">
            @if ($profil?->akreditasi)
                <span class="flex items-center gap-1.5"><x-icon name="shield-check" class="size-3.5" />Terakreditasi {{ $profil->akreditasi }}</span>
            @endif
            <a href="{{ route('login') }}" class="flex items-center gap-1.5 transition hover:text-white">
                <x-icon name="logout" class="size-3.5" />Login Petugas
            </a>
        </div>
    </div>
</div>

<header x-data="{ open: false }" class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/85 backdrop-blur-md">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-6 px-6 py-3.5">
        <a href="{{ route('home') }}" class="flex items-center gap-3">
            <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-brand-700 text-white shadow-card">
                <x-icon name="academic" class="size-6" />
            </span>
            <span class="leading-tight">
                <span class="block text-[15px] font-bold tracking-tight text-brand-950">{{ $profil->nama ?? 'Portal Sekolah' }}</span>
                <span class="block text-xs text-slate-500">{{ $profil->jenjang ? 'Jenjang '.$profil->jenjang : 'Portal Informasi Sekolah' }}</span>
            </span>
        </a>

        <nav class="hidden items-center gap-1 lg:flex">
            @foreach ($navigasi as $item)
                <a href="{{ route($item['rute']) }}"
                   class="relative rounded-lg px-3.5 py-2 text-sm font-medium transition {{ request()->routeIs($item['aktif']) ? 'text-brand-700' : 'text-slate-600 hover:bg-slate-50 hover:text-brand-800' }}">
                    {{ $item['label'] }}
                    @if (request()->routeIs($item['aktif']))
                        <span class="absolute inset-x-3.5 -bottom-0.5 h-0.5 rounded-full bg-brand-600"></span>
                    @endif
                </a>
            @endforeach
            <a href="{{ route('pendaftaran.form') }}"
               class="ml-3 inline-flex items-center gap-2 rounded-xl bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white shadow-card transition hover:bg-brand-800 hover:shadow-lift">
                Daftar Sekarang
                <x-icon name="arrow-right" class="size-4" />
            </a>
        </nav>

        <button type="button" @click="open = !open" aria-label="Buka menu"
                class="grid size-10 place-items-center rounded-lg border border-slate-200 text-slate-600 transition hover:bg-slate-50 lg:hidden">
            <x-icon x-show="!open" name="menu" />
            <x-icon x-show="open" x-cloak name="close" />
        </button>
    </div>

    <nav x-show="open" x-cloak x-transition.opacity class="border-t border-slate-200 bg-white px-6 py-4 lg:hidden">
        <div class="flex flex-col gap-1">
            @foreach ($navigasi as $item)
                <a href="{{ route($item['rute']) }}"
                   class="rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs($item['aktif']) ? 'bg-brand-50 text-brand-700' : 'text-slate-700 hover:bg-slate-50' }}">
                    {{ $item['label'] }}
                </a>
            @endforeach
            <a href="{{ route('pendaftaran.form') }}"
               class="mt-2 rounded-xl bg-brand-700 px-4 py-2.5 text-center text-sm font-semibold text-white">
                Daftar Sekarang
            </a>
        </div>
    </nav>
</header>

<main>
    <div class="mx-auto max-w-6xl px-6">
        <x-alert />
    </div>
    @yield('content')
</main>

<footer class="mt-20 border-t border-slate-200 bg-brand-950 text-slate-300">
    <div class="mx-auto grid max-w-6xl gap-10 px-6 py-14 sm:grid-cols-2 lg:grid-cols-12">
        <div class="lg:col-span-5">
            <div class="flex items-center gap-3">
                <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-white/10 text-white ring-1 ring-white/15">
                    <x-icon name="academic" class="size-6" />
                </span>
                <p class="text-base font-bold text-white">{{ $profil->nama ?? 'Portal Sekolah' }}</p>
            </div>
            <p class="mt-4 max-w-sm text-sm leading-relaxed text-slate-400">
                {{ $profil->alamat ?: 'Alamat sekolah belum dilengkapi pada profil.' }}
            </p>
            @if ($profil?->npsn)
                <p class="mt-4 inline-flex items-center gap-2 rounded-lg bg-white/5 px-3 py-1.5 text-xs text-slate-300 ring-1 ring-white/10">
                    NPSN <span class="tabular font-semibold text-white">{{ $profil->npsn }}</span>
                </p>
            @endif
        </div>

        <div class="lg:col-span-3">
            <p class="eyebrow text-slate-500">Navigasi</p>
            <ul class="mt-4 space-y-2.5 text-sm">
                @foreach ([['profil', 'Profil Sekolah'], ['berita', 'Berita & Pengumuman'], ['galeri', 'Galeri'], ['pendaftaran.form', 'Pendaftaran']] as [$rute, $label])
                    <li>
                        <a href="{{ route($rute) }}" class="text-slate-400 transition hover:text-white">{{ $label }}</a>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="lg:col-span-4">
            <p class="eyebrow text-slate-500">Hubungi Kami</p>
            <ul class="mt-4 space-y-3 text-sm text-slate-400">
                @if ($profil?->telepon)
                    <li class="flex items-start gap-2.5"><x-icon name="phone" class="mt-0.5 size-4 shrink-0 text-slate-500" />{{ $profil->telepon }}</li>
                @endif
                @if ($profil?->email)
                    <li class="flex items-start gap-2.5"><x-icon name="mail" class="mt-0.5 size-4 shrink-0 text-slate-500" />{{ $profil->email }}</li>
                @endif
                @if ($profil?->kepala_sekolah)
                    <li class="flex items-start gap-2.5"><x-icon name="users" class="mt-0.5 size-4 shrink-0 text-slate-500" />Kepala Sekolah: {{ $profil->kepala_sekolah }}</li>
                @endif
            </ul>
            <a href="{{ route('login') }}"
               class="mt-5 inline-flex items-center gap-2 rounded-lg bg-white/5 px-4 py-2 text-sm font-medium text-white ring-1 ring-white/10 transition hover:bg-white/10">
                <x-icon name="logout" class="size-4" />Login Petugas
            </a>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="mx-auto max-w-6xl px-6 py-5 text-center text-xs text-slate-500">
            &copy; {{ date('Y') }} {{ $profil->nama ?? 'Portal Sekolah' }}. Seluruh hak cipta dilindungi.
        </div>
    </div>
</footer>

</body>
</html>
