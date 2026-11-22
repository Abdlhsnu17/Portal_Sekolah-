@extends('layouts.admin')

@section('title', 'Absensi Siswa')

@section('content')
    <form method="GET" action="{{ route('teaching.attendance') }}" class="mb-5 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-card">
        <label class="min-w-52 flex-1 text-sm font-medium text-slate-700">
            Kelas
            <select name="school_class_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" @selected($classId === $class->id)>{{ $class->name }} · {{ $class->academicYear->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-sm font-medium text-slate-700">
            Tanggal
            <input type="date" name="date" value="{{ $date }}" max="{{ now()->toDateString() }}" class="mt-1 block rounded-lg border-slate-300 text-sm">
        </label>
        <button class="rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white">Tampilkan</button>
    </form>

    @if($schoolClass)
        <form method="POST" action="{{ route('teaching.attendance.store') }}" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-card">
            @csrf
            <input type="hidden" name="school_class_id" value="{{ $schoolClass->id }}">
            <input type="hidden" name="attendance_date" value="{{ $date }}">
            <div class="flex items-center justify-between gap-4 border-b border-slate-100 p-4">
                <div><h2 class="font-bold text-brand-950">{{ $schoolClass->name }}</h2><p class="text-sm text-slate-500">{{ $schoolClass->academicYear->name }} · {{ $date }}</p></div>
                <button class="rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white">Simpan absensi</button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Siswa</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Catatan</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($schoolClass->enrollments as $enrollment)
                            @php($saved = $attendances->get($enrollment->student_id))
                            <tr>
                                <td class="px-4 py-3"><p class="font-semibold text-brand-950">{{ $enrollment->student->nama }}</p><p class="text-xs text-slate-500">{{ $enrollment->student->nis }}</p></td>
                                <td class="px-4 py-3">
                                    <select name="attendance[{{ $enrollment->student_id }}][status]" required class="rounded-lg border-slate-300 text-sm">
                                        @foreach(\App\Models\StudentAttendance::STATUSES as $value => $label)
                                            <option value="{{ $value }}" @selected(old("attendance.{$enrollment->student_id}.status", $saved?->status ?? 'hadir') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-3"><input name="attendance[{{ $enrollment->student_id }}][note]" value="{{ old("attendance.{$enrollment->student_id}.note", $saved?->note) }}" maxlength="500" class="w-full rounded-lg border-slate-300 text-sm"></td>
                            </tr>
                        @empty
                            <tr><td colspan="3"><x-empty-state icon="users" title="Belum ada siswa aktif" description="Kelas ini belum memiliki siswa aktif." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>
    @else
        <x-empty-state icon="academic" title="Belum ada kelas penugasan" description="Hubungi administrator untuk menetapkan kelas dan mata pelajaran Anda." />
    @endif
@endsection
