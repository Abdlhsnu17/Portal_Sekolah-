@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    @php
        $totalPendaftar = $statusPendaftaran->sum();
        $menunggu = $statistik['menunggu'] ?? 0;
        $tingkatVerifikasi = $statistik['pendaftar'] > 0
            ? round((($statistik['pendaftar'] - $menunggu) / $statistik['pendaftar']) * 100)
            : 0;
    @endphp

    <div class="mb-4 rounded-lg border border-slate-200 bg-white/95 p-4 shadow-card">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="eyebrow text-slate-400">Ringkasan Operasional</p>
                <h2 class="mt-1 text-xl font-bold text-brand-950">Aktivitas sekolah hari ini</h2>
                <p class="mt-0.5 text-sm text-slate-500">Pantau akademik, pendaftaran, dan konten publik dari satu layar.</p>
            </div>
            <div class="grid gap-2 sm:grid-cols-3 lg:min-w-lg">
                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                    <p class="text-xs font-semibold uppercase text-slate-500">Verifikasi</p>
                    <p class="mt-1 metric text-2xl text-brand-950">{{ $tingkatVerifikasi }}%</p>
                </div>
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2">
                    <p class="text-xs font-semibold uppercase text-amber-700">Menunggu</p>
                    <p class="mt-1 metric text-2xl text-amber-900">{{ number_format($menunggu) }}</p>
                </div>
                <a href="{{ route('admin.registrations.index') }}" class="rounded-lg border border-brand-200 bg-brand-50 px-3 py-2 transition hover:border-brand-300 hover:bg-brand-100">
                    <p class="text-xs font-semibold uppercase text-brand-700">Total PSB</p>
                    <p class="mt-1 metric text-2xl text-brand-950">{{ number_format($statistik['pendaftar']) }}</p>
                </a>
            </div>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-3 xl:grid-cols-6">
        @foreach ([
            ['label' => 'Siswa aktif', 'nilai' => $statistik['siswa'], 'ikon' => 'users', 'rute' => route('admin.students.index'), 'tone' => 'bg-sky-50 text-sky-700 ring-sky-100'],
            ['label' => 'Guru aktif', 'nilai' => $statistik['guru'], 'ikon' => 'academic', 'rute' => route('admin.teachers.index'), 'tone' => 'bg-emerald-50 text-emerald-700 ring-emerald-100'],
            ['label' => 'Menunggu', 'nilai' => $statistik['menunggu'], 'ikon' => 'clock', 'rute' => route('admin.registrations.index', ['status' => 'menunggu']), 'tone' => 'bg-amber-50 text-amber-700 ring-amber-100'],
            ['label' => 'Pendaftar', 'nilai' => $statistik['pendaftar'], 'ikon' => 'clipboard', 'rute' => route('admin.registrations.index'), 'tone' => 'bg-brand-50 text-brand-700 ring-brand-100'],
            ['label' => 'Konten', 'nilai' => $statistik['berita'], 'ikon' => 'newspaper', 'rute' => route('admin.announcements.index'), 'tone' => 'bg-violet-50 text-violet-700 ring-violet-100'],
            ['label' => 'Galeri', 'nilai' => $statistik['galeri'], 'ikon' => 'photo', 'rute' => route('admin.galleries.index'), 'tone' => 'bg-rose-50 text-rose-700 ring-rose-100'],
        ] as $kartu)
            <a href="{{ $kartu['rute'] }}"
               class="group rounded-lg border border-slate-200 bg-white p-3.5 shadow-card transition hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-lift">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $kartu['label'] }}</p>
                        <p class="mt-1 metric text-2xl text-brand-950">{{ number_format($kartu['nilai']) }}</p>
                    </div>
                    <span class="grid size-9 shrink-0 place-items-center rounded-lg ring-1 {{ $kartu['tone'] }}">
                        <x-icon :name="$kartu['ikon']" class="size-4" />
                    </span>
                </div>
            </a>
        @endforeach
    </div>

    <section class="mt-4 rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-card" aria-labelledby="aksi-cepat">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="eyebrow text-slate-400">Jalan Pintas</p>
                <h2 id="aksi-cepat" class="mt-1 text-base font-bold text-brand-950">Aksi cepat</h2>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.students.create') }}" class="admin-chip"><x-icon name="plus" class="size-4" /> Tambah siswa</a>
                <a href="{{ route('admin.teachers.create') }}" class="admin-chip"><x-icon name="plus" class="size-4" /> Tambah guru</a>
                <a href="{{ route('admin.announcements.create') }}" class="admin-primary"><x-icon name="pencil" class="size-4" /> Tulis berita</a>
            </div>
        </div>
    </section>

    <div class="mt-4 grid gap-4 lg:grid-cols-5">
        {{-- Sebaran status pendaftaran --}}
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-card lg:col-span-2">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="eyebrow text-slate-400">PSB</p>
                    <h2 class="mt-1 text-base font-bold tracking-tight text-brand-950">Status Pendaftaran</h2>
                </div>
                <span class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600">{{ $tingkatVerifikasi }}% diproses</span>
            </div>

            {{-- Angka utama halaman ini --}}
            <p class="mt-3 metric text-4xl leading-none text-brand-950">{{ number_format($totalPendaftar) }}</p>
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

            <ul class="mt-5 space-y-3">
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
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-card lg:col-span-3">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3.5">
                <div>
                    <p class="eyebrow text-slate-400">Antrian</p>
                    <h2 class="mt-1 text-base font-bold tracking-tight text-brand-950">Pendaftar Terbaru</h2>
                </div>
                <a href="{{ route('admin.registrations.index') }}"
                   class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 transition hover:text-brand-900">
                    Lihat semua <x-icon name="arrow-right" class="size-4" />
                </a>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($pendaftarTerbaru as $item)
                    <li class="flex items-center justify-between gap-4 px-5 py-2.5 transition hover:bg-slate-50">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-brand-50 text-xs font-bold text-brand-700 ring-1 ring-brand-100">
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
    <div class="mt-4 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-card">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3.5">
            <div>
                <p class="eyebrow text-slate-400">Publikasi</p>
                <h2 class="mt-1 text-base font-bold tracking-tight text-brand-950">Konten Terbaru</h2>
            </div>
            <a href="{{ route('admin.announcements.create') }}"
               class="admin-primary">
                <x-icon name="plus" class="size-4" /> Tulis baru
            </a>
        </div>
        <ul class="divide-y divide-slate-100">
            @forelse ($beritaTerbaru as $item)
                <li class="flex items-center justify-between gap-4 px-5 py-2.5 transition hover:bg-slate-50">
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
