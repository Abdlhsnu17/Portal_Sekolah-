@extends('layouts.admin')

@section('title', 'Jadwal Pelajaran')

@section('content')
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card">
        <div class="border-b border-slate-100 p-5">
            <h2 class="font-bold text-brand-950">Jadwal pelajaran {{ $student->nama }}</h2>
            <p class="mt-1 text-sm text-slate-500">Jadwal untuk kelas aktif Anda.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr><th class="px-4 py-3">Hari/jam</th><th class="px-4 py-3">Mata pelajaran</th><th class="px-4 py-3">Guru</th><th class="px-4 py-3">Kelas</th><th class="px-4 py-3">Ruang</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($schedules as $schedule)
                        <tr>
                            <td class="px-4 py-3 font-semibold text-brand-950">{{ $schedule->day_label }}<span class="block text-xs font-normal text-slate-500">{{ substr($schedule->starts_at, 0, 5) }}–{{ substr($schedule->ends_at, 0, 5) }}</span></td>
                            <td class="px-4 py-3">{{ $schedule->subject->nama }}</td>
                            <td class="px-4 py-3">{{ $schedule->teacher->nama }}</td>
                            <td class="px-4 py-3">{{ $schedule->schoolClass->name }}</td>
                            <td class="px-4 py-3">{{ $schedule->room ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty-state icon="clock" title="Belum ada jadwal" description="Jadwal akan muncul setelah diterbitkan sekolah." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
