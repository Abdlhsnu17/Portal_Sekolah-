@extends('layouts.admin')

@section('title', 'Catatan Siswa')

@section('content')
    <div class="grid gap-5 xl:grid-cols-3">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
            <h2 class="text-lg font-bold text-brand-950">{{ $editingNote ? 'Ubah catatan' : 'Buat catatan siswa' }}</h2>
            @if($editingNote)
                <p class="mt-2 text-sm text-slate-500">Siswa: {{ $editingNote->student->nama }} ({{ $editingNote->student->nis }})</p>
            @endif
            <form method="POST" action="{{ $editingNote ? route('teaching.notes.update', $editingNote) : route('teaching.notes.store') }}" class="mt-4 space-y-4" @unless($editingNote) x-data="{ students: @js($students->map(fn ($student) => ['id' => (string) $student->id, 'label' => $student->nama.' — '.$student->nis])->values()), selectedId: @js((string) old('student_id', '')), search: '', open: false, get filteredStudents() { const query = this.search.trim().toLocaleLowerCase('id'); return query ? this.students.filter((student) => student.label.toLocaleLowerCase('id').includes(query)) : this.students; }, init() { this.search = this.students.find((student) => student.id === this.selectedId)?.label ?? ''; }, selectStudent(student) { this.selectedId = student.id; this.search = student.label; this.open = false; $refs.studentSearch.setCustomValidity(''); }, validateSelection() { if (!this.selectedId) { $refs.studentSearch.setCustomValidity('Pilih siswa dari daftar yang tersedia.'); $refs.studentSearch.reportValidity(); return false; } $refs.studentSearch.setCustomValidity(''); return true; } }" @click.outside="open = false" @submit="if (!validateSelection()) $event.preventDefault()" @endunless>
                @csrf
                @if($editingNote) @method('PUT') @endif
                @unless($editingNote)
                    <div>
                        <div class="mb-2 flex items-baseline justify-between gap-3">
                            <label for="note-student-search" class="block text-sm font-semibold text-slate-800">Siswa <span class="text-rose-500">*</span></label>
                            <span class="text-xs text-slate-400">Cari berdasarkan nama atau NIS</span>
                        </div>
                        <div class="relative">
                            <input type="hidden" name="student_id" :value="selectedId">
                            <input id="note-student-search" x-ref="studentSearch" type="search" autocomplete="off" required x-model="search" @focus="open = true" @input="selectedId = ''; open = true; $el.setCustomValidity('')" @keydown.escape="open = false" @keydown.enter.prevent="if (filteredStudents.length === 1) selectStudent(filteredStudents[0]); else open = true" :aria-expanded="open.toString()" aria-controls="note-student-options" aria-autocomplete="list" role="combobox" placeholder="Ketik nama atau NIS siswa..." class="w-full rounded-lg border-slate-200 py-2.5 pl-3 pr-10 text-sm focus:border-brand-500 focus:ring-brand-500">
                            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400"><x-icon name="chevron-right" class="size-4 rotate-90" /></span>
                            <div id="note-student-options" x-show="open" x-cloak x-transition role="listbox" aria-label="Daftar siswa" class="absolute z-20 mt-2 max-h-60 w-full overflow-y-auto overscroll-contain rounded-xl border border-slate-200 bg-white py-1 shadow-lift">
                                <template x-for="student in filteredStudents" :key="student.id">
                                    <button type="button" role="option" :aria-selected="selectedId === student.id" @click="selectStudent(student)" class="block w-full px-3 py-2.5 text-left text-sm text-slate-700 hover:bg-brand-50 hover:text-brand-800" :class="selectedId === student.id ? 'bg-brand-50 font-semibold text-brand-800' : ''" x-text="student.label"></button>
                                </template>
                                <p x-show="filteredStudents.length === 0" class="px-3 py-3 text-sm text-slate-500">Siswa tidak ditemukan.</p>
                            </div>
                        </div>
                        @error('student_id')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>
                @endunless
                <x-form.input name="title" label="Judul catatan" required maxlength="150" :value="old('title', $editingNote?->title)" />
                <label for="content" class="block text-sm font-medium text-slate-700">Isi catatan <span class="text-rose-500">*</span></label>
                <textarea id="content" name="content" rows="6" maxlength="5000" required class="w-full rounded-lg border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500">{{ old('content', $editingNote?->content) }}</textarea>
                @error('content')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                <label class="flex items-start gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="publish" value="1" @checked(old('publish', $editingNote?->published_at !== null)) class="mt-0.5 rounded border-slate-300 text-brand-700 focus:ring-brand-500">
                    <span>Terbitkan agar siswa terkait dan wali yang tertaut dapat membacanya. Draf hanya terlihat oleh Anda.</span>
                </label>
                <div class="flex flex-wrap gap-2">
                    <button class="rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">{{ $editingNote ? 'Simpan perubahan' : 'Simpan catatan' }}</button>
                    @if($editingNote)<a href="{{ route('teaching.notes.index') }}" class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600">Batal</a>@endif
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card xl:col-span-2">
            <div class="border-b border-slate-100 p-5">
                <h2 class="font-bold text-brand-950">Catatan yang saya buat</h2>
                <p class="mt-1 text-sm text-slate-500">Catatan nilai dan absensi tetap berada di modul masing-masing.</p>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($notes as $note)
                    <article class="p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h3 class="font-semibold text-brand-950">{{ $note->title }}</h3>
                                <p class="mt-1 text-xs text-slate-500">{{ $note->student->nama }} · {{ $note->student->nis }} · {{ $note->updated_at->format('d M Y') }}</p>
                            </div>
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $note->published_at ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $note->published_at ? 'Terbit' : 'Draf privat' }}</span>
                        </div>
                        <p class="mt-3 whitespace-pre-line text-sm text-slate-600">{{ \Illuminate\Support\Str::limit($note->content, 280) }}</p>
                        <div class="mt-3 flex gap-3">
                            <a href="{{ route('teaching.notes.index', ['edit' => $note->id]) }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">Ubah</a>
                            <form method="POST" action="{{ route('teaching.notes.destroy', $note) }}" data-confirm-delete data-delete-title="Hapus catatan siswa?" data-delete-description="Catatan ini tidak lagi terlihat oleh siswa atau wali.">
                                @csrf
                                @method('DELETE')
                                <button class="text-sm font-semibold text-rose-600 hover:text-rose-800">Hapus</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="p-5"><x-empty-state icon="clipboard" title="Belum ada catatan" description="Catatan siswa yang Anda buat akan tampil di sini." /></div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
