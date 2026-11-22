@extends('layouts.admin')

@section('title', 'Jadwal Pelajaran')

@section('content')
@php($editingSchedule = $schedules->firstWhere('id', request()->integer('edit')))
<div class="grid gap-5 xl:grid-cols-3">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
        <div class="flex items-center justify-between gap-3"><h2 class="text-lg font-bold text-brand-950">{{ $editingSchedule ? 'Ubah jadwal' : 'Tambah jadwal' }}</h2>@if($editingSchedule)<a href="{{ route('admin.class-schedules.index', ['school_class_id' => $selectedClassId]) }}" class="text-sm font-semibold text-slate-500">Batal</a>@endif</div>
        <p class="mt-1 text-sm text-slate-500">Sistem menolak bentrok rombel, guru, dan ruang.</p>
        <form method="POST" action="{{ $editingSchedule ? route('admin.class-schedules.update', $editingSchedule) : route('admin.class-schedules.store') }}" class="mt-5 space-y-4">@csrf @if($editingSchedule) @method('PUT') @endif
            <x-form.select name="school_class_id" label="Rombel" required :options="$classes->mapWithKeys(fn($class) => [$class->id => $class->academicYear->name.' — '.$class->name])->all()" placeholder="Pilih rombel" :value="old('school_class_id', $editingSchedule?->school_class_id)" />
            <x-form.select name="subject_id" label="Mata pelajaran" required :options="$subjects->pluck('nama', 'id')->all()" placeholder="Pilih mata pelajaran" :value="old('subject_id', $editingSchedule?->subject_id)" />
            <x-form.select name="teacher_id" label="Guru" required :options="$teachers->pluck('nama', 'id')->all()" placeholder="Pilih guru" :value="old('teacher_id', $editingSchedule?->teacher_id)" />
            <x-form.select name="day_of_week" label="Hari" required :options="\App\Models\ClassSchedule::DAYS" placeholder="Pilih hari" :value="old('day_of_week', $editingSchedule?->day_of_week)" />
            <div class="grid grid-cols-2 gap-3"><x-form.input name="starts_at" type="time" label="Mulai" required :value="old('starts_at', $editingSchedule ? substr($editingSchedule->starts_at, 0, 5) : '')" /><x-form.input name="ends_at" type="time" label="Selesai" required :value="old('ends_at', $editingSchedule ? substr($editingSchedule->ends_at, 0, 5) : '')" /></div>
            <x-form.input name="room" label="Ruang" placeholder="Contoh: Lab 1" :value="old('room', $editingSchedule?->room)" />
            <button class="w-full rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">{{ $editingSchedule ? 'Simpan perubahan' : 'Simpan jadwal' }}</button>
        </form>
    </div>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card xl:col-span-2">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-4"><div><h2 class="font-bold text-brand-950">Jadwal terdaftar</h2><p class="mt-1 text-sm text-slate-500">Filter berdasarkan rombel untuk melihat jadwal mingguan.</p></div><form method="GET"><select name="school_class_id" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-sm"><option value="">Semua rombel</option>@foreach($classes as $class)<option value="{{ $class->id }}" @selected($selectedClassId === $class->id)>{{ $class->academicYear->name }} — {{ $class->name }}</option>@endforeach</select></form></div>
        <div class="overflow-x-auto"><table data-smart-table data-table-name="jadwal-pelajaran" class="w-full min-w-[720px] text-left text-sm"><thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Hari/Jam</th><th class="px-4 py-3">Rombel</th><th class="px-4 py-3">Mata pelajaran</th><th class="px-4 py-3">Guru</th><th class="px-4 py-3">Ruang</th><th class="px-4 py-3"></th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($schedules as $schedule)<tr><td class="px-4 py-3 font-semibold text-brand-950">{{ $schedule->day_label }}<span class="block text-xs font-normal text-slate-500">{{ substr($schedule->starts_at, 0, 5) }}–{{ substr($schedule->ends_at, 0, 5) }}</span></td><td class="px-4 py-3">{{ $schedule->schoolClass->name }}<span class="block text-xs text-slate-500">{{ $schedule->schoolClass->academicYear->name }}</span></td><td class="px-4 py-3">{{ $schedule->subject->nama }}</td><td class="px-4 py-3">{{ $schedule->teacher->nama }}</td><td class="px-4 py-3">{{ $schedule->room ?: '—' }}</td><td class="px-4 py-3"><div class="flex justify-end gap-3"><a href="{{ route('admin.class-schedules.index', ['school_class_id' => $schedule->school_class_id, 'edit' => $schedule->id]) }}" class="font-semibold text-brand-700">Ubah</a><form method="POST" action="{{ route('admin.class-schedules.destroy', $schedule) }}" data-confirm-delete>@csrf @method('DELETE')<button class="font-semibold text-rose-600">Hapus</button></form></div></td></tr>@empty<tr><td colspan="6"><x-empty-state icon="academic" title="Belum ada jadwal" description="Tambahkan jadwal pelajaran pertama." /></td></tr>@endforelse</tbody></table></div>
    </div>
    </div>
@endsection
