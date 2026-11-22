@extends('layouts.admin')

@section('title', 'Dashboard Guru')

@section('content')
    <section class="mb-5" aria-labelledby="ringkasan-mengajar">
        <div class="mb-3">
            <p class="eyebrow text-slate-400">Ringkasan Mengajar</p>
            <h2 id="ringkasan-mengajar" class="mt-1 text-xl font-bold text-brand-950">Dashboard {{ $teacher->nama }}</h2>
            <p class="mt-1 text-sm text-slate-500">Pantau beban mengajar dan kelas yang Anda ampu.</p>
            @if($homeroomClasses->isNotEmpty())
                <div class="mt-3 flex flex-wrap gap-2" aria-label="Tugas wali kelas">
                    @foreach($homeroomClasses as $class)
                        <span class="inline-flex items-center gap-2 rounded-full border border-brand-200 bg-brand-50 px-3 py-1.5 text-sm text-brand-900">
                            <span class="font-semibold">Wali kelas {{ $class->name }}</span>
                            <span class="text-brand-700">{{ $class->enrollments_count }} siswa</span>
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => 'Kelas diampu', 'value' => $statistics['classes'], 'description' => 'Kelas pada penugasan aktif', 'icon' => 'academic', 'tone' => 'bg-sky-50 text-sky-700 ring-sky-100'],
                ['label' => 'Mata pelajaran', 'value' => $statistics['subjects'], 'description' => 'Mapel yang ditugaskan', 'icon' => 'clipboard', 'tone' => 'bg-violet-50 text-violet-700 ring-violet-100'],
                ['label' => 'Siswa diampu', 'value' => $statistics['students'], 'description' => 'Siswa aktif di kelas Anda', 'icon' => 'users', 'tone' => 'bg-emerald-50 text-emerald-700 ring-emerald-100'],
                ['label' => 'Jadwal mengajar', 'value' => $statistics['schedules'], 'description' => 'Sesi terjadwal per minggu', 'icon' => 'clock', 'tone' => 'bg-amber-50 text-amber-700 ring-amber-100'],
            ] as $card)
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-card">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p>
                            <p class="mt-1 metric text-3xl text-brand-950">{{ number_format($card['value']) }}</p>
                        </div>
                        <span class="grid size-10 shrink-0 place-items-center rounded-lg ring-1 {{ $card['tone'] }}"><x-icon :name="$card['icon']" class="size-5" /></span>
                    </div>
                    <p class="mt-2 text-xs text-slate-500">{{ $card['description'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <div class="mb-5 flex flex-wrap gap-3">
        <a href="{{ route('teaching.attendance') }}" class="rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">
            Catat absensi siswa
        </a>
        <a href="{{ route('teaching.schedule') }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-brand-700 hover:bg-brand-50">
            Jadwal mengajar lengkap
        </a>
        <a href="{{ route('teaching.notes.index') }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-brand-700 hover:bg-brand-50">
            Kelola catatan siswa
        </a>
    </div>
    <div class="grid gap-5 lg:grid-cols-2">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-100 p-5">
                <h2 class="text-lg font-bold text-brand-950">Penugasan mengajar</h2>
                <p class="mt-1 text-sm text-slate-500">Berikut kelas dan mata pelajaran yang ditugaskan kepada Anda.</p>
            </div>
            <div class="grid gap-4 p-5 sm:grid-cols-2">
                @forelse($assignments as $assignment)
                    <article class="rounded-xl border border-brand-100 bg-brand-50/40 p-4">
                        <p class="text-xs font-bold uppercase tracking-wider text-brand-600">{{ $assignment->tahun_ajaran }}</p>
                        <h3 class="mt-2 text-lg font-bold text-brand-950">{{ $assignment->subject->nama }}</h3>
                        <p class="mt-1 text-sm text-slate-600">Kelas {{ $assignment->kelas }}</p>
                    </article>
                @empty
                    <div class="sm:col-span-2"><x-empty-state icon="academic" title="Belum ada penugasan" description="Hubungi administrator sekolah untuk menetapkan kelas dan mata pelajaran Anda." /></div>
                @endforelse
            </div>
        </section>

        <section>
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card">
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 p-5">
                <h2 class="font-bold text-brand-950">Cuplikan jadwal mengajar</h2>
                <a href="{{ route('teaching.schedule') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">Lihat semua</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($schedules as $schedule)
                    <div class="flex items-center justify-between gap-4 px-5 py-3">
                        <div><p class="font-semibold text-brand-950">{{ $schedule->subject->nama }} · {{ $schedule->schoolClass->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $schedule->schoolClass->academicYear->name }} · {{ $schedule->room ?: 'Ruang belum diisi' }}</p></div>
                        <span class="text-right text-sm font-semibold text-brand-700">{{ $schedule->day_label }}<span class="block text-xs font-normal text-slate-500">{{ substr($schedule->starts_at, 0, 5) }}–{{ substr($schedule->ends_at, 0, 5) }}</span></span>
                    </div>
                @empty
                    <x-empty-state icon="clock" title="Belum ada jadwal" description="Jadwal mengajar akan muncul setelah dibuat admin." />
                @endforelse
            </div>
        </div>
        </section>
    </div>
@endsection
