@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    {{-- Ringkasan angka: label + nilai, tanpa hiasan warna --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ([
            ['label' => 'Siswa aktif', 'nilai' => $statistik['siswa'], 'ikon' => 'users', 'rute' => route('admin.students.index')],
            ['label' => 'Guru aktif', 'nilai' => $statistik['guru'], 'ikon' => 'academic', 'rute' => route('admin.teachers.index')],
            ['label' => 'Menunggu verifikasi', 'nilai' => $statistik['menunggu'], 'ikon' => 'clock', 'rute' => route('admin.registrations.index', ['status' => 'menunggu'])],
            ['label' => 'Total pendaftar', 'nilai' => $statistik['pendaftar'], 'ikon' => 'clipboard', 'rute' => route('admin.registrations.index')],
            ['label' => 'Berita & pengumuman', 'nilai' => $statistik['berita'], 'ikon' => 'newspaper', 'rute' => route('admin.announcements.index')],
            ['label' => 'Foto galeri', 'nilai' => $statistik['galeri'], 'ikon' => 'photo', 'rute' => route('admin.galleries.index')],
        ] as $kartu)
            <a href="{{ $kartu['rute'] }}"
               class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-card transition hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-lift">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-slate-600">{{ $kartu['label'] }}</p>
                    <x-icon :name="$kartu['ikon']" class="size-5 text-slate-400 transition group-hover:text-brand-600" />
                </div>
                <p class="mt-3 text-3xl font-semibold text-brand-950">{{ number_format($kartu['nilai']) }}</p>
            </a>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-5">
        {{-- Sebaran status pendaftaran --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card lg:col-span-2">
            <h2 class="text-base font-bold tracking-tight text-brand-950">Status Pendaftaran</h2>

            @php $totalPendaftar = $statusPendaftaran->sum(); @endphp

            {{-- Angka utama halaman ini --}}
            <p class="mt-4 text-5xl font-semibold leading-none text-brand-950">{{ number_format($totalPendaftar) }}</p>
            <p class="mt-2 text-sm text-slate-500">pendaftar sejak awal periode</p>

            @php
                $skala = max($statusPendaftaran->max() ?: 1, 1);
                $warna = [
                    'menunggu' => 'bg-state-warning',
                    'diverifikasi' => 'bg-brand-600',
                    'diterima' => 'bg-state-good',
                    'ditolak' => 'bg-state-critical',
                ];
            @endphp

            <ul class="mt-7 space-y-4">
                @foreach ($statusPendaftaran as $status => $jumlah)
                    {{-- Batang terpendek tetap 3% agar status bernilai kecil masih terlihat --}}
                    @php $lebar = $jumlah > 0 ? max(round($jumlah / $skala * 100), 3) : 0; @endphp

                    <li>
                        {{-- Setiap batang diberi label langsung; warna tidak pernah berdiri sendiri --}}
                        <div class="flex items-baseline justify-between gap-3 text-sm">
                            <span class="font-medium capitalize text-slate-700">{{ $status }}</span>
                            <span class="tabular font-semibold text-brand-950">{{ $jumlah }}</span>
                        </div>
                        <div class="mt-2 h-2 overflow-hidden rounded-sm bg-slate-100">
                            <div class="h-full rounded-sm {{ $warna[$status] ?? 'bg-slate-400' }}"
                                 @style(["width: {$lebar}%"])></div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Pendaftar terbaru --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card lg:col-span-3">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h2 class="text-base font-bold tracking-tight text-brand-950">Pendaftar Terbaru</h2>
                <a href="{{ route('admin.registrations.index') }}"
                   class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 transition hover:text-brand-900">
                    Lihat semua <x-icon name="arrow-right" class="size-4" />
                </a>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($pendaftarTerbaru as $item)
                    <li class="flex items-center justify-between gap-4 px-6 py-3.5 transition hover:bg-slate-50">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="grid size-9 shrink-0 place-items-center rounded-full bg-brand-50 text-xs font-bold text-brand-700">
                                {{ strtoupper(substr($item->nama_lengkap, 0, 1)) }}
                            </span>
                            <div class="min-w-0">
                                <a href="{{ route('admin.registrations.show', $item) }}"
                                   class="block truncate text-sm font-semibold text-brand-950 transition hover:text-brand-700">
                                    {{ $item->nama_lengkap }}
                                </a>
                                <p class="mt-0.5 truncate text-xs text-slate-500">
                                    <span class="tabular">{{ $item->nomor_pendaftaran }}</span> &middot; {{ $item->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                        <x-status-badge :status="$item->status" class="shrink-0" />
                    </li>
                @empty
                    <li class="px-6 py-14 text-center text-sm text-slate-500">Belum ada pendaftar.</li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- Konten terbaru --}}
    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <h2 class="text-base font-bold tracking-tight text-brand-950">Konten Terbaru</h2>
            <a href="{{ route('admin.announcements.create') }}"
               class="inline-flex items-center gap-1.5 rounded-lg bg-brand-700 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-brand-800">
                <x-icon name="plus" class="size-4" /> Tulis baru
            </a>
        </div>
        <ul class="divide-y divide-slate-100">
            @forelse ($beritaTerbaru as $item)
                <li class="flex items-center justify-between gap-4 px-6 py-3.5 transition hover:bg-slate-50">
                    <div class="min-w-0">
                        <a href="{{ route('admin.announcements.edit', $item) }}"
                           class="block truncate text-sm font-semibold text-brand-950 transition hover:text-brand-700">
                            {{ $item->judul }}
                        </a>
                        <p class="mt-0.5 text-xs text-slate-500">
                            <span class="capitalize">{{ $item->kategori }}</span> &middot; {{ $item->created_at->diffForHumans() }}
                        </p>
                    </div>
                    <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $item->is_published ? 'bg-green-50 text-green-800 ring-green-200' : 'bg-slate-50 text-slate-600 ring-slate-200' }}">
                        <x-icon :name="$item->is_published ? 'check-circle' : 'pencil'" class="size-3.5" />
                        {{ $item->is_published ? 'Terbit' : 'Draf' }}
                    </span>
                </li>
            @empty
                <li class="px-6 py-14 text-center text-sm text-slate-500">Belum ada konten.</li>
            @endforelse
        </ul>
    </div>
@endsection
