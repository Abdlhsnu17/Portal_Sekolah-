@extends('layouts.admin')

@section('title', 'Input Nilai')

@section('content')
    <form method="POST" action="{{ route('teaching.assessments.scores.store', $assessment) }}" class="overflow-hidden rounded-2xl border border-brand-100 bg-white shadow-card">
        @csrf
        @method('PUT')
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-brand-100 bg-brand-50/70 p-5">
            <div>
                <a href="{{ route('teaching.assessments.index') }}" class="text-sm font-semibold text-brand-700">← Kembali ke penilaian siswa</a>
                <h2 class="mt-2 text-lg font-bold text-brand-950">{{ $assessment->title }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $assessment->schoolClass->name }} · {{ $assessment->subject->nama }} · Nilai maksimum {{ $assessment->maximum_score }}</p>
            </div>
            <button class="rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white">Simpan nilai</button>
        </div>
        <div class="overflow-x-auto">
            <table data-smart-table data-smart-table-include-last-column data-table-name="input-nilai-{{ $assessment->id }}" class="w-full min-w-[760px] text-left text-sm">
                <thead class="bg-brand-100/80 text-xs font-bold uppercase tracking-wide text-brand-900">
                    <tr><th class="px-5 py-3.5">Siswa</th><th class="px-5 py-3.5">Nilai</th><th class="px-5 py-3.5">Catatan</th></tr>
                </thead>
                <tbody class="divide-y divide-brand-100">
                    @forelse($students as $enrollment)
                        @php($saved = $scores->get($enrollment->student_id))
                        <tr class="odd:bg-white even:bg-slate-50/70 hover:!bg-brand-50/80">
                            <td class="px-5 py-4"><p class="font-semibold text-brand-950">{{ $enrollment->student->nama }}</p><p class="mt-0.5 text-xs text-slate-500">NIS {{ $enrollment->student->nis }}</p></td>
                            <td class="px-5 py-4"><input type="number" name="scores[{{ $enrollment->student_id }}][score]" value="{{ old("scores.{$enrollment->student_id}.score", $saved?->score) }}" min="0" max="{{ $assessment->maximum_score }}" step="0.01" placeholder="0–{{ $assessment->maximum_score }}" class="w-32 rounded-lg border border-brand-200 bg-white px-3 py-2 text-sm font-semibold text-brand-950 shadow-sm placeholder:font-normal placeholder:text-slate-400 focus:border-brand-600 focus:ring-brand-600"></td>
                            <td class="px-5 py-4"><input name="scores[{{ $enrollment->student_id }}][note]" value="{{ old("scores.{$enrollment->student_id}.note", $saved?->note) }}" placeholder="Tambahkan catatan (opsional)" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm placeholder:text-slate-400 focus:border-brand-600 focus:ring-brand-600" maxlength="500"></td>
                        </tr>
                    @empty
                        <tr><td colspan="3"><x-empty-state icon="users" title="Belum ada siswa aktif" description="Tempatkan siswa ke rombel dahulu." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>
@endsection
