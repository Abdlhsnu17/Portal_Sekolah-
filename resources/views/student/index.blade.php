@extends('layouts.admin')

@section('title', 'Belajar Saya')

@section('content')
    <div class="mb-5 rounded-2xl border border-brand-100 bg-white p-5 shadow-card">
        <p class="text-sm text-slate-500">Siswa</p>
        <h2 class="mt-1 text-xl font-bold text-brand-950">{{ $student->nama }}</h2>
        <p class="mt-1 text-sm text-slate-600">NIS {{ $student->nis }}</p>
    </div>

    <div class="grid gap-5 xl:grid-cols-2">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-100 p-5"><h2 class="font-bold text-brand-950">Kelas saya</h2></div>
            <div class="divide-y divide-slate-100">
                @forelse($enrollments as $enrollment)
                    <div class="px-5 py-4"><p class="font-semibold text-brand-950">{{ $enrollment->schoolClass->name }}</p><p class="mt-1 text-sm text-slate-500">{{ $enrollment->schoolClass->academicYear->name }}</p></div>
                @empty
                    <div class="p-5"><x-empty-state icon="academic" title="Belum ditempatkan di kelas" description="Hubungi pihak sekolah untuk informasi lebih lanjut." /></div>
                @endforelse
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-100 p-5"><h2 class="font-bold text-brand-950">Jadwal pelajaran</h2></div>
            <div class="divide-y divide-slate-100">
                @forelse($schedules as $schedule)
                    <div class="flex items-center justify-between gap-4 px-5 py-3">
                        <div><p class="font-semibold text-brand-950">{{ $schedule->subject->nama }} · {{ $schedule->schoolClass->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $schedule->teacher->nama }} · {{ $schedule->room ?: 'Ruang belum diisi' }}</p></div>
                        <span class="text-right text-sm font-semibold text-brand-700">{{ $schedule->day_label }}<span class="block text-xs font-normal text-slate-500">{{ substr($schedule->starts_at, 0, 5) }}–{{ substr($schedule->ends_at, 0, 5) }}</span></span>
                    </div>
                @empty
                    <div class="p-5"><x-empty-state icon="clock" title="Belum ada jadwal" description="Jadwal akan muncul setelah diterbitkan sekolah." /></div>
                @endforelse
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-100 p-5"><h2 class="font-bold text-brand-950">Nilai yang sudah diterbitkan</h2></div>
            <div class="divide-y divide-slate-100">
                @forelse($scores as $item)
                    <div class="flex items-center justify-between gap-4 px-5 py-3">
                        <div><p class="font-semibold text-brand-950">{{ $item->assessment->title }}</p><p class="mt-1 text-xs text-slate-500">{{ $item->assessment->subject->nama }} · {{ $item->assessment->schoolClass->name }}</p></div>
                        <span class="font-bold tabular text-brand-800">{{ $item->score }}<span class="text-xs font-normal text-slate-500"> / {{ $item->assessment->maximum_score }}</span></span>
                    </div>
                @empty
                    <div class="p-5"><x-empty-state icon="academic" title="Belum ada nilai" description="Nilai akan ditampilkan setelah guru mengisinya." /></div>
                @endforelse
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-100 p-5"><h2 class="font-bold text-brand-950">Agenda belajar</h2></div>
            <div class="divide-y divide-slate-100">
                @forelse($assessments as $assessment)
                    @php($myScore = $assessment->scores->first())
                    <div class="flex items-center justify-between gap-4 px-5 py-3">
                        <div><p class="font-semibold text-brand-950">{{ $assessment->title }}</p><p class="mt-1 text-xs text-slate-500">{{ $assessment->subject->nama }} · {{ $assessment->schoolClass->name }} · {{ $assessment->assessment_date->format('d M Y') }}</p></div>
                        <span class="text-right text-sm font-semibold text-brand-700">{{ $myScore?->score !== null ? $myScore->score.' / '.$assessment->maximum_score : 'Belum dinilai' }}</span>
                    </div>
                @empty
                    <div class="p-5"><x-empty-state icon="academic" title="Belum ada agenda belajar" description="Penugasan dan penilaian kelas akan tampil di sini." /></div>
                @endforelse
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-100 p-5"><h2 class="font-bold text-brand-950">Riwayat absensi</h2></div>
            <div class="divide-y divide-slate-100">
                @forelse($attendances as $attendance)
                    <div class="flex items-center justify-between gap-4 px-5 py-3">
                        <div><p class="font-semibold text-brand-950">{{ $attendance->schoolClass->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $attendance->attendance_date->format('d M Y') }}</p></div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold capitalize text-slate-700">{{ $attendance->status }}</span>
                    </div>
                @empty
                    <div class="p-5"><x-empty-state icon="clock" title="Belum ada riwayat absensi" description="Absensi kelas akan tampil di sini." /></div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
