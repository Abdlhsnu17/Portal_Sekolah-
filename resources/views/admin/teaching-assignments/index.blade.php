@extends('layouts.admin')

@section('title', 'Penugasan Mengajar')

@section('content')
    <div x-data="{ tabs: ['penugasan', 'mapel', 'kelas'], tab: ['penugasan', 'mapel', 'kelas'].includes(location.hash.slice(1)) ? location.hash.slice(1) : 'penugasan', select(value) { this.tab = value; history.replaceState(null, '', `#${value}`); } }" @hashchange.window="tab = tabs.includes(location.hash.slice(1)) ? location.hash.slice(1) : tab">
        <div class="mb-5 flex gap-2 overflow-x-auto border-b border-slate-200" role="tablist" aria-label="Pengelolaan akademik">
            @foreach (['penugasan' => 'Penugasan Guru', 'mapel' => 'Mata Pelajaran', 'kelas' => 'Kelas'] as $id => $label)
                <button type="button" @click="select('{{ $id }}')" :aria-selected="(tab === '{{ $id }}').toString()" role="tab"
                    :class="tab === '{{ $id }}' ? 'border-brand-700 text-brand-800' : 'border-transparent text-slate-500 hover:text-slate-700'"
                    class="shrink-0 border-b-2 px-4 py-3 text-sm font-semibold transition">{{ $label }}</button>
            @endforeach
        </div>

        <section x-show="tab === 'penugasan'" x-cloak x-transition.opacity role="tabpanel" class="space-y-5">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
                <h2 class="text-lg font-bold text-brand-950">Tetapkan guru mapel</h2>
                <p class="mt-1 text-sm text-slate-500">Pilih guru, mata pelajaran, dan kelas untuk tahun ajaran yang bersangkutan.</p>
                <form method="POST" action="{{ route('admin.teaching-assignments.store') }}" class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @csrf
                    <x-form.select name="teacher_id" label="Guru" required :options="$teachers->pluck('nama', 'id')->all()" placeholder="Pilih guru" />
                    <x-form.select name="subject_id" label="Mata pelajaran" required :options="$subjects->pluck('nama', 'id')->all()" placeholder="Pilih mata pelajaran" />
                    <div><x-form.input name="kelas" label="Kelas" required list="daftar-kelas" placeholder="Contoh: X-A" /><datalist id="daftar-kelas">@foreach($classes as $class)<option value="{{ $class }}">@endforeach</datalist></div>
                    <x-form.input name="tahun_ajaran" label="Tahun ajaran" required placeholder="2026/2027" />
                    <div class="xl:col-span-4 flex justify-end"><button class="rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">Simpan penugasan</button></div>
                </form>
            </div>
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card">
                <div class="border-b border-slate-100 p-4"><h2 class="font-bold text-brand-950">Daftar penugasan</h2></div>
                <div class="overflow-x-auto"><table data-smart-table data-table-name="penugasan-mengajar" class="w-full min-w-[620px] text-left text-sm"><thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Guru</th><th class="px-4 py-3">Mata pelajaran</th><th class="px-4 py-3">Kelas</th><th class="px-4 py-3">Tahun ajaran</th><th class="px-4 py-3 text-right">Aksi</th></tr></thead><tbody class="divide-y divide-slate-100">
                    @forelse($assignments as $assignment)
                        <tr><td class="px-4 py-3 font-semibold text-brand-950">{{ $assignment->teacher->nama }}</td><td class="px-4 py-3">{{ $assignment->subject->nama }}</td><td class="px-4 py-3">{{ $assignment->kelas }}</td><td class="px-4 py-3">{{ $assignment->tahun_ajaran }}</td><td class="px-4 py-3 text-right"><form method="POST" action="{{ route('admin.teaching-assignments.destroy', $assignment) }}" data-confirm-delete>@csrf @method('DELETE')<button aria-label="Hapus penugasan {{ $assignment->teacher->nama }}" title="Hapus" class="inline-flex size-9 items-center justify-center rounded-lg text-rose-600 transition hover:bg-rose-50 hover:text-rose-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500"><x-icon name="trash" class="size-5" /></button></form></td></tr>
                    @empty <tr><td colspan="5"><x-empty-state icon="academic" title="Belum ada penugasan" description="Tetapkan guru ke mata pelajaran dan kelas di atas." /></td></tr> @endforelse
                </tbody></table></div>
                <div class="border-t border-slate-100 p-4">{{ $assignments->links() }}</div>
            </div>
        </section>

        <section x-show="tab === 'mapel'" x-cloak x-transition.opacity role="tabpanel" class="admin-workspace-lg grid gap-5 lg:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card"><h2 class="text-lg font-bold text-brand-950">Tambah mata pelajaran</h2><p class="mt-1 text-sm text-slate-500">Mapel dibuat satu kali dan bisa dipakai untuk semua kelas.</p><form method="POST" action="{{ route('admin.subjects.store') }}" class="mt-4 space-y-4">@csrf <x-form.input name="kode" label="Kode" required placeholder="Contoh: MTK" /><x-form.input name="nama" label="Nama" required placeholder="Contoh: Matematika" /><button class="w-full rounded-lg border border-brand-200 bg-brand-50 px-4 py-2.5 text-sm font-semibold text-brand-800 hover:bg-brand-100">Tambah mapel</button></form></div>
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card lg:col-span-2"><div class="border-b border-slate-100 p-4"><h2 class="font-bold text-brand-950">Master mata pelajaran</h2></div><div class="divide-y divide-slate-100">@forelse($subjects as $subject)<div class="flex items-center justify-between px-4 py-3"><span class="font-semibold text-brand-950">{{ $subject->nama }}</span><span class="rounded-full bg-slate-100 px-2.5 py-1 font-mono text-xs text-slate-600">{{ $subject->kode }}</span></div>@empty <x-empty-state icon="academic" title="Belum ada mapel" description="Tambahkan mata pelajaran pertama." />@endforelse</div></div>
        </section>

        <section x-show="tab === 'kelas'" x-cloak x-transition.opacity role="tabpanel" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card"><div class="border-b border-slate-100 p-5"><h2 class="text-lg font-bold text-brand-950">Daftar kelas</h2><p class="mt-1 text-sm text-slate-500">Kelas diambil dari data siswa. Kelola nama kelas melalui menu Data Siswa.</p></div><div class="grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">@forelse($classSummary as $class)<div class="rounded-xl border border-slate-200 p-4"><p class="text-lg font-bold text-brand-950">{{ $class->kelas }}</p><p class="mt-1 text-sm text-slate-500">{{ $class->student_count }} siswa terdaftar</p></div>@empty <div class="sm:col-span-2 lg:col-span-3 xl:col-span-4"><x-empty-state icon="users" title="Belum ada kelas" description="Tambahkan kelas saat mengisi data siswa." /></div>@endforelse</div></section>
    </div>
@endsection
