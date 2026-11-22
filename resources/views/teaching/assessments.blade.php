@extends('layouts.admin')

@section('title', 'Penilaian Siswa')

@section('content')
    <div class="mb-5">
        <h1 class="text-2xl font-bold text-brand-950">Penilaian Siswa</h1>
        <p class="mt-1 text-sm text-slate-500">Pilih komponen penilaian, lalu masukkan nilai untuk seluruh siswa di rombel Anda.</p>
    </div>

    <div x-data="{ tab: 'harian' }">
        <div class="mb-5 flex w-full gap-2 overflow-x-auto rounded-xl border border-slate-200 bg-white p-2 shadow-card" role="tablist" aria-label="Kelompok penilaian">
            @foreach($assessmentGroups as $groupKey => $group)
                <button type="button" @click="tab = '{{ $groupKey }}'" :class="tab === '{{ $groupKey }}' ? 'bg-brand-700 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'" class="shrink-0 rounded-lg px-4 py-2.5 text-sm font-semibold transition" role="tab" :aria-selected="tab === '{{ $groupKey }}'">
                    {{ $group['title'] }}
                </button>
            @endforeach
        </div>

        @foreach($assessmentGroups as $groupKey => $group)
            <section x-show="tab === '{{ $groupKey }}'" x-cloak x-transition.opacity class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card" role="tabpanel">
                <div class="border-b border-slate-100 p-5">
                    <h2 class="font-bold text-brand-950">{{ $group['title'] }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $group['description'] }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table data-smart-table data-smart-table-include-last-column data-table-name="penilaian-{{ $groupKey }}" class="w-full min-w-[640px] text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr><th class="px-5 py-3">Penilaian</th><th class="px-5 py-3">Kelas / Mapel</th><th class="px-5 py-3">Tanggal</th><th class="px-5 py-3">Terinput</th><th class="px-5 py-3 text-right">Aksi</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($group['assessments'] as $assessment)
                                <tr>
                                    <td class="px-5 py-3 font-semibold text-brand-950">{{ $assessment->title }}</td>
                                    <td class="px-5 py-3">{{ $assessment->schoolClass->name }}<span class="block text-xs text-slate-500">{{ $assessment->subject->nama }}</span></td>
                                    <td class="px-5 py-3 text-slate-600">{{ $assessment->assessment_date->translatedFormat('d M Y') }}</td>
                                    <td class="px-5 py-3"><span class="font-semibold text-brand-700">{{ $assessment->scores_count }}</span> siswa</td>
                                    <td class="px-5 py-3 text-right"><a href="{{ route('teaching.assessments.scores', $assessment) }}" class="font-semibold text-brand-700 hover:text-brand-900">Input nilai</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-6 text-center text-sm text-slate-500">Belum ada komponen penilaian pada kelompok ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach
    </div>
@endsection
