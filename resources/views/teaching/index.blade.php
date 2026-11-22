@extends('layouts.admin')

@section('title', 'Pengajaran Saya')

@section('content')
    <div class="mb-5 flex flex-wrap gap-3">
        <a href="{{ route('teaching.attendance') }}" class="rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">
            Catat absensi siswa
        </a>
    </div>
    @if($homeroomClasses->isNotEmpty())
        <section class="mb-5 rounded-2xl border border-brand-200 bg-brand-50 p-5 shadow-card"><h2 class="font-bold text-brand-950">Wali kelas</h2><div class="mt-3 flex flex-wrap gap-3">@foreach($homeroomClasses as $class)<div class="rounded-lg bg-white px-4 py-3 text-sm shadow-sm"><b>{{ $class->name }}</b><span class="ml-2 text-slate-500">{{ $class->academicYear->name }} · {{ $class->enrollments_count }} siswa</span></div>@endforeach</div></section>
    @endif
    <section class="rounded-2xl border border-slate-200 bg-white shadow-card">
        <div class="border-b border-slate-100 p-5">
            <h2 class="text-lg font-bold text-brand-950">Penugasan {{ $teacher->nama }}</h2>
            <p class="mt-1 text-sm text-slate-500">Berikut kelas dan mata pelajaran yang ditugaskan kepada Anda.</p>
        </div>
        <div class="grid gap-4 p-5 sm:grid-cols-2 xl:grid-cols-3">
            @forelse($assignments as $assignment)
                <article class="rounded-xl border border-brand-100 bg-brand-50/40 p-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-brand-600">{{ $assignment->tahun_ajaran }}</p>
                    <h3 class="mt-2 text-lg font-bold text-brand-950">{{ $assignment->subject->nama }}</h3>
                    <p class="mt-1 text-sm text-slate-600">Kelas {{ $assignment->kelas }}</p>
                </article>
            @empty
                <div class="sm:col-span-2 xl:col-span-3"><x-empty-state icon="academic" title="Belum ada penugasan" description="Hubungi administrator sekolah untuk menetapkan kelas dan mata pelajaran Anda." /></div>
            @endforelse
        </div>
    </section>

    <section class="mt-5 grid gap-5 xl:grid-cols-2">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card"><div class="border-b border-slate-100 p-5"><h2 class="font-bold text-brand-950">Jadwal mengajar</h2></div><div class="divide-y divide-slate-100">@forelse($schedules as $schedule)<div class="flex items-center justify-between gap-4 px-5 py-3"><div><p class="font-semibold text-brand-950">{{ $schedule->subject->nama }} · {{ $schedule->schoolClass->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $schedule->schoolClass->academicYear->name }} · {{ $schedule->room ?: 'Ruang belum diisi' }}</p></div><span class="text-right text-sm font-semibold text-brand-700">{{ $schedule->day_label }}<span class="block text-xs font-normal text-slate-500">{{ substr($schedule->starts_at,0,5) }}–{{ substr($schedule->ends_at,0,5) }}</span></span></div>@empty <x-empty-state icon="clock" title="Belum ada jadwal" description="Jadwal mengajar akan muncul setelah dibuat admin."/> @endforelse</div></div>
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card"><div class="border-b border-slate-100 p-5"><h2 class="font-bold text-brand-950">Penilaian terbaru</h2></div><div class="divide-y divide-slate-100">@forelse($recentAssessments as $assessment)<div class="flex items-center justify-between gap-4 px-5 py-3"><div><p class="font-semibold text-brand-950">{{ $assessment->title }}</p><p class="mt-1 text-xs text-slate-500">{{ $assessment->subject->nama }} · {{ $assessment->schoolClass->name }}</p></div><a href="{{ route('teaching.assessments.scores', $assessment) }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">Input nilai · {{ $assessment->scores_count }}</a></div>@empty <x-empty-state icon="academic" title="Belum ada penilaian" description="Penilaian yang ditugaskan kepada Anda akan tampil di sini."/> @endforelse</div></div>
    </section>
@endsection
