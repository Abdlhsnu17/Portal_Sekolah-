@extends('layouts.admin')

@section('title', 'Dashboard Staf')

@section('content')
    <div class="mb-5 rounded-2xl border border-brand-100 bg-white p-5 shadow-card">
        <p class="text-sm font-semibold text-brand-700">Ruang kerja staf</p>
        <h2 class="mt-1 text-xl font-bold text-brand-950">Selamat datang, {{ auth()->user()->name }}</h2>
        <p class="mt-1 text-sm text-slate-500">Ringkasan pendaftaran dan data sekolah yang tersedia untuk peran staf.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Pendaftar menunggu', 'value' => $statistics['pendingRegistrations'], 'route' => route('admin.registrations.index', ['status' => 'menunggu']), 'tone' => 'bg-amber-50 text-amber-700'],
            ['label' => 'Siswa aktif', 'value' => $statistics['activeStudents'], 'route' => route('admin.students.index'), 'tone' => 'bg-sky-50 text-sky-700'],
            ['label' => 'Guru aktif', 'value' => $statistics['activeTeachers'], 'route' => route('admin.teachers.index'), 'tone' => 'bg-emerald-50 text-emerald-700'],
            ['label' => 'Konten sekolah', 'value' => $statistics['announcements'], 'route' => route('admin.announcements.index'), 'tone' => 'bg-violet-50 text-violet-700'],
        ] as $card)
            <a href="{{ $card['route'] }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-card transition hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-lift">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-2 text-3xl font-bold text-brand-950">{{ number_format($card['value']) }}</p>
                <span class="mt-3 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $card['tone'] }}">Buka modul</span>
            </a>
        @endforeach
    </div>

    <section class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Penerimaan siswa</p>
                <h2 class="mt-1 font-bold text-brand-950">Pendaftar terbaru</h2>
            </div>
            <a href="{{ route('admin.registrations.index') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">Lihat semua</a>
        </div>
        <ul class="divide-y divide-slate-100">
            @forelse ($recentRegistrations as $registration)
                <li class="flex items-center justify-between gap-4 px-5 py-3">
                    <div class="min-w-0">
                        <a href="{{ route('admin.registrations.show', $registration) }}" class="block truncate text-sm font-semibold text-brand-950 hover:text-brand-700">{{ $registration->nama_lengkap }}</a>
                        <p class="mt-1 text-xs text-slate-500">{{ $registration->nomor_pendaftaran }} · {{ $registration->created_at->diffForHumans() }}</p>
                    </div>
                    <x-status-badge :status="$registration->status" class="shrink-0" />
                </li>
            @empty
                <li class="px-5 py-10 text-center text-sm text-slate-500">Belum ada pendaftar.</li>
            @endforelse
        </ul>
    </section>
@endsection
