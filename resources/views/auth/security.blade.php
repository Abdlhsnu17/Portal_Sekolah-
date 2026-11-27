@extends('layouts.admin')

@section('title', 'Pengaturan')

@section('content')
@php
    $securityStep = ! $user->password_changed_at || $user->password_changed_at->lt(now()->subYears(50))
        ? 1
        : ((! config('security.mfa_enabled') || $user->hasTwoFactorAuthentication()) ? 3 : 2);
@endphp
<x-progress-steps class="mb-5 max-w-6xl" :steps="['Perbarui kata sandi', 'Aktifkan MFA', 'Keamanan siap']" :active="$securityStep" />
<div x-data="{ tab: ['mfa', 'password', 'sessions', 'activity'@if($user->isAdmin()), 'settings'@endif].includes(location.hash.slice(1)) ? location.hash.slice(1) : '{{ $user->isAdmin() ? 'settings' : 'mfa' }}', select(value) { this.tab = value; history.replaceState(null, '', `#${value}`) } }"
     class="max-w-6xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card">
    <div class="border-b border-slate-100 px-6 py-5">
        <h2 class="text-base font-bold text-brand-950">Pengaturan</h2>
        <p class="mt-1 text-sm text-slate-600">Kelola keamanan akun dan pengaturan portal.</p>
    </div>

    <div class="grid lg:grid-cols-[14rem_1fr]">
    <nav class="flex gap-2 overflow-x-auto border-b border-slate-100 bg-slate-50 p-3 lg:flex-col lg:border-b-0 lg:border-r"
         role="tablist" aria-label="Bagian pengaturan">
        @foreach ([
            ...($user->isAdmin() ? [['id' => 'settings', 'label' => 'Portal', 'desc' => 'Tampilan informasi umum']] : []),
            ['id' => 'mfa', 'label' => 'Autentikasi', 'desc' => 'Verifikasi dua faktor'],
            ['id' => 'password', 'label' => 'Kata sandi', 'desc' => 'Ubah akses akun'],
            ['id' => 'sessions', 'label' => 'Sesi aktif', 'desc' => 'Kelola perangkat masuk'],
            ['id' => 'activity', 'label' => 'Aktivitas', 'desc' => 'Riwayat keamanan akun'],
        ] as $tab)
            <button type="button" role="tab" @click="select('{{ $tab['id'] }}')"
                    :aria-selected="tab === '{{ $tab['id'] }}'"
                    :class="tab === '{{ $tab['id'] }}' ? 'border-brand-200 bg-white text-brand-950 shadow-card' : 'border-transparent text-slate-600 hover:bg-white/70 hover:text-brand-900'"
                    class="min-w-40 rounded-lg border px-4 py-3 text-left transition lg:min-w-0">
                <span class="block text-sm font-bold">{{ $tab['label'] }}</span>
                <span class="mt-0.5 block text-xs text-slate-500">{{ $tab['desc'] }}</span>
            </button>
        @endforeach
    </nav>

    <div class="min-w-0 p-6">
        @if (config('security.mfa_enabled'))
        <section x-show="tab === 'mfa'" x-cloak x-transition.opacity role="tabpanel" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
            <h2 class="text-base font-bold text-brand-950">Autentikasi dua faktor</h2>
            <p class="mt-1 text-sm text-slate-600">Gunakan aplikasi autentikator yang mendukung TOTP.</p>
            @if ($user->hasTwoFactorAuthentication())
                <p class="mt-4 rounded-lg bg-emerald-50 p-3 text-sm font-semibold text-emerald-700">MFA aktif sejak {{ $user->two_factor_confirmed_at->format('d M Y H:i') }}.</p>
                @unless($user->isAdmin())
                    <form method="POST" action="{{ route('security.mfa.disable') }}" class="mt-4 space-y-3" data-confirm-delete>@csrf @method('DELETE')
                        <x-form.input name="current_password" type="password" label="Kata sandi saat ini" required />
                        <button class="rounded-lg border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-600">Nonaktifkan MFA</button>
                    </form>
                @endunless
            @elseif ($mfaSecret)
                <div class="mt-4 space-y-3 rounded-lg bg-slate-50 p-4 text-sm">
                    <p>1. Tambahkan akun secara manual di aplikasi autentikator.</p>
                    <p class="break-all rounded bg-white p-2 font-mono font-semibold">{{ $mfaSecret }}</p>
                    <details><summary class="cursor-pointer text-brand-700">Tampilkan URI konfigurasi</summary><p class="mt-2 break-all font-mono text-xs">{{ $mfaUri }}</p></details>
                    <p>2. Masukkan kode yang tampil untuk mengonfirmasi.</p>
                </div>
                <form method="POST" action="{{ route('security.mfa.confirm') }}" class="mt-4 flex items-end gap-3">@csrf
                    <div class="flex-1"><x-form.input name="code" label="Kode 6 digit" required inputmode="numeric" /></div>
                    <button class="rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white">Aktifkan</button>
                </form>
            @else
                <form method="POST" action="{{ route('security.mfa.begin') }}" class="mt-4 space-y-3">@csrf
                    <x-form.input name="current_password" type="password" label="Konfirmasi kata sandi" required />
                    <button class="rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white">Mulai pengaturan MFA</button>
                </form>
            @endif
            @if (session('recovery_codes'))
                <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-4">
                    <p class="text-sm font-bold text-amber-900">Simpan kode pemulihan ini sekarang. Kode hanya ditampilkan sekali.</p>
                    <div class="mt-3 grid grid-cols-2 gap-2 font-mono text-sm">@foreach(session('recovery_codes') as $code)<span>{{ $code }}</span>@endforeach</div>
                </div>
            @endif
        </section>
        @else
        <section x-show="tab === 'mfa'" x-cloak x-transition.opacity role="tabpanel" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
            <h2 class="text-base font-bold text-brand-950">Autentikasi dua faktor</h2>
            <p class="mt-3 rounded-lg bg-amber-50 p-4 text-sm text-amber-800">
                MFA sedang ditangguhkan. Perlindungan password, pembatasan percobaan login, audit, dan keamanan sesi tetap aktif.
            </p>
        </section>
        @endif

        <section x-show="tab === 'password'" x-cloak x-transition.opacity role="tabpanel" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
            <h2 class="text-base font-bold text-brand-950">Ubah kata sandi</h2>
            <p class="mt-1 text-sm text-slate-600">Minimal 12 karakter, huruf besar/kecil, angka, simbol, dan bukan password bocor.</p>
            <form method="POST" action="{{ route('security.password') }}" class="mt-4 space-y-4">@csrf @method('PUT')
                <x-form.input name="current_password" type="password" label="Kata sandi saat ini" required />
                <x-form.input name="password" type="password" label="Kata sandi baru" required />
                <x-form.input name="password_confirmation" type="password" label="Ulangi kata sandi baru" required />
                <button class="rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white">Perbarui kata sandi</button>
            </form>
        </section>

    <section x-show="tab === 'sessions'" x-cloak x-transition.opacity role="tabpanel" class="min-w-0 rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
        <div><h2 class="text-base font-bold text-brand-950">Sesi aktif</h2><p class="mt-1 text-sm text-slate-600">Keluar dari browser atau perangkat lain yang tidak dikenali.</p></div>
            <form method="POST" action="{{ route('security.sessions.destroy') }}" class="mt-4 flex flex-wrap items-end gap-2" data-confirm-delete>@csrf @method('DELETE')
                <div class="min-w-[12rem] flex-1"><x-form.input name="current_password" type="password" label="Kata sandi" required /></div>
                <button class="rounded-lg border border-rose-200 px-4 py-2.5 text-sm font-semibold text-rose-600">Keluarkan sesi lain</button>
            </form>
        <div class="mt-4 max-h-56 divide-y divide-slate-100 overflow-y-auto overscroll-contain pr-2">@forelse($sessions as $session)
            <div class="py-2.5 text-sm"><p class="truncate font-semibold text-slate-800" title="{{ $session->user_agent ?: 'Perangkat tidak diketahui' }}">{{ Str::limit($session->user_agent ?: 'Perangkat tidak diketahui', 82) }}</p><p class="mt-1 text-xs text-slate-500">IP {{ $session->ip_address ?: '—' }} · aktivitas {{ \Carbon\Carbon::createFromTimestamp($session->last_activity)->diffForHumans() }} @if($session->id === request()->session()->getId()) · <span class="font-semibold text-emerald-600">sesi ini</span>@endif</p></div>
        @empty<p class="py-4 text-sm text-slate-500">Data sesi tidak tersedia.</p>@endforelse</div>
    </section>

    <section x-show="tab === 'activity'" x-cloak x-transition.opacity role="tabpanel" class="min-w-0 rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
        <div class="flex items-center justify-between gap-3">
            <div><h2 class="text-base font-bold text-brand-950">Aktivitas keamanan terakhir</h2><p class="mt-1 text-sm text-slate-600">Riwayat akses terbaru akun Anda.</p></div>
            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $audits->count() }} aktivitas</span>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table data-smart-table data-table-name="aktivitas-keamanan" class="w-full min-w-[620px] text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Aktivitas</th>
                        <th class="px-4 py-3">Waktu</th>
                        <th class="px-4 py-3">Alamat IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($audits as $audit)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-700">{{ str_replace('.', ' · ', $audit->event) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ \Carbon\Carbon::parse($audit->created_at)->format('d M Y H:i') }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-500">{{ $audit->ip_address ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-10 text-center text-sm text-slate-500">Belum ada aktivitas keamanan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="smart-table-footer border-t border-slate-100 px-1 pt-4">
            <p class="text-xs font-medium text-slate-500">Menampilkan {{ $audits->count() }} aktivitas terbaru</p>
        </div>
    </section>

    @if ($user->isAdmin())
    <section x-show="tab === 'settings'" x-cloak x-transition.opacity role="tabpanel" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
        <div>
            <h2 class="text-base font-bold text-brand-950">Pengaturan portal</h2>
            <p class="mt-1 text-sm text-slate-600">Atur tampilan informasi umum pada situs publik dan panel admin.</p>
        </div>

        <form method="POST" action="{{ route('admin.settings.update') }}" class="mt-5">
            @csrf
            @method('PUT')

            <div class="admin-form-section">
                <div class="admin-form-heading">
                    <p>Tulisan Berjalan</p>
                    <p>Atur teks informasi yang ditampilkan di bagian atas situs publik dan panel admin.</p>
                </div>

                <label class="flex items-start gap-3 rounded-lg border border-slate-200 px-4 py-3">
                    <input type="checkbox" name="ticker_enabled" value="1" @checked($tickerEnabled)
                           class="mt-0.5 size-4 rounded border-slate-300 text-brand-700 focus:ring-brand-500">
                    <span>
                        <span class="block text-sm font-semibold text-slate-800">Aktifkan tulisan berjalan</span>
                        <span class="mt-0.5 block text-xs text-slate-500">Jika dinonaktifkan, tulisan berjalan tidak ditampilkan.</span>
                    </span>
                </label>

                <div class="mt-5">
                    <x-form.textarea name="ticker_text" label="Isi Tulisan Berjalan" :value="$tickerText"
                                     rows="3" maxlength="500" hint="Maksimal 500 karakter." />
                </div>
            </div>

            <button class="admin-primary mt-6">Simpan Pengaturan</button>
        </form>
    </section>
    @endif
    </div>
</div>
</div>
@endsection
