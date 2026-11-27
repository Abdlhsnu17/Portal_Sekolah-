@extends('layouts.admin')

@section('title', 'Penugasan Mengajar')

@section('content')
    <div x-data="{ tabs: ['penugasan', 'mapel', 'kelas'], tab: ['penugasan', 'mapel', 'kelas'].includes(location.hash.slice(1)) ? location.hash.slice(1) : 'penugasan', select(value) { this.tab = value; history.replaceState(null, '', `#${value}`); } }" @hashchange.window="tab = tabs.includes(location.hash.slice(1)) ? location.hash.slice(1) : tab">
        <x-progress-steps class="mb-5" :steps="['Siapkan mata pelajaran', 'Pastikan kelas tersedia', 'Tetapkan guru']" :active="$subjects->isEmpty() ? 1 : (empty($classes) ? 2 : 3)" />
        <div class="mb-5 flex gap-2 overflow-x-auto border-b border-slate-200" role="tablist" aria-label="Pengelolaan akademik">
            @foreach (['penugasan' => 'Penugasan Guru', 'mapel' => 'Mata Pelajaran', 'kelas' => 'Kelas'] as $id => $label)
                <button type="button" @click="select('{{ $id }}')" :aria-selected="(tab === '{{ $id }}').toString()" role="tab"
                    :class="tab === '{{ $id }}' ? 'border-brand-700 text-brand-800' : 'border-transparent text-slate-500 hover:text-slate-700'"
                    class="shrink-0 border-b-2 px-4 py-3 text-sm font-semibold transition">{{ $label }}</button>
            @endforeach
        </div>

        <section x-show="tab === 'penugasan'" x-cloak x-transition.opacity role="tabpanel" x-data="{ assignmentTab: 'list' }" class="space-y-5">
            <div class="flex gap-1 overflow-x-auto rounded-2xl border border-slate-200 bg-white px-3 shadow-card" role="tablist" aria-label="Penugasan guru">
                <button type="button" @click="assignmentTab = 'form'" :class="assignmentTab === 'form' ? 'border-brand-700 text-brand-800' : 'border-transparent text-slate-500'" class="shrink-0 border-b-2 px-4 py-3 text-sm font-semibold">Tetapkan guru mapel</button>
                <button type="button" @click="assignmentTab = 'list'" :class="assignmentTab === 'list' ? 'border-brand-700 text-brand-800' : 'border-transparent text-slate-500'" class="shrink-0 border-b-2 px-4 py-3 text-sm font-semibold">Daftar penugasan <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs">{{ $assignments->total() }}</span></button>
            </div>
            <div x-show="assignmentTab === 'form'" x-cloak class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
                <h2 class="text-lg font-bold text-brand-950">Tetapkan guru mapel</h2>
                <p class="mt-1 text-sm text-slate-500">Pilih guru, mata pelajaran, dan kelas untuk tahun ajaran yang bersangkutan.</p>
                <form method="POST" action="{{ route('admin.teaching-assignments.store') }}" class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4" x-data="{ teachers: @js($teachers->map(fn ($teacher) => ['id' => (string) $teacher->id, 'label' => $teacher->nama.' — '.$teacher->nip])->values()), subjects: @js($subjects->map(fn ($subject) => ['id' => (string) $subject->id, 'label' => $subject->nama])->values()), classes: @js(collect($classes)->map(fn ($class) => ['value' => (string) $class, 'label' => (string) $class])->values()), selectedId: @js((string) old('teacher_id', '')), selectedSubjectId: @js((string) old('subject_id', '')), selectedClass: @js((string) old('kelas', '')), search: '', subjectSearch: '', classSearch: '', open: false, subjectOpen: false, classOpen: false, get filteredTeachers() { const query = this.search.trim().toLocaleLowerCase('id'); return query ? this.teachers.filter((teacher) => teacher.label.toLocaleLowerCase('id').includes(query)) : this.teachers; }, get filteredSubjects() { const query = this.subjectSearch.trim().toLocaleLowerCase('id'); return query ? this.subjects.filter((subject) => subject.label.toLocaleLowerCase('id').includes(query)) : this.subjects; }, get filteredClasses() { const query = this.classSearch.trim().toLocaleLowerCase('id'); return query ? this.classes.filter((schoolClass) => schoolClass.label.toLocaleLowerCase('id').includes(query)) : this.classes; }, init() { this.search = this.teachers.find((teacher) => teacher.id === this.selectedId)?.label ?? ''; this.subjectSearch = this.subjects.find((subject) => subject.id === this.selectedSubjectId)?.label ?? ''; this.classSearch = this.classes.find((schoolClass) => schoolClass.value === this.selectedClass)?.label ?? ''; }, selectTeacher(teacher) { this.selectedId = teacher.id; this.search = teacher.label; this.open = false; $refs.teacherSearch.setCustomValidity(''); }, selectSubject(subject) { this.selectedSubjectId = subject.id; this.subjectSearch = subject.label; this.subjectOpen = false; $refs.subjectSearch.setCustomValidity(''); }, selectClass(schoolClass) { this.selectedClass = schoolClass.value; this.classSearch = schoolClass.label; this.classOpen = false; $refs.classSearch.setCustomValidity(''); }, validateSelection(ref, value, message) { if (!value) { $refs[ref].setCustomValidity(message); $refs[ref].reportValidity(); return false; } $refs[ref].setCustomValidity(''); return true; }, validateSelections() { return this.validateSelection('teacherSearch', this.selectedId, 'Pilih guru dari daftar yang tersedia.') && this.validateSelection('subjectSearch', this.selectedSubjectId, 'Pilih mata pelajaran dari daftar yang tersedia.') && this.validateSelection('classSearch', this.selectedClass, 'Pilih kelas dari daftar yang tersedia.'); } }" @click.outside="open = false; subjectOpen = false; classOpen = false" @submit="if (!validateSelections()) $event.preventDefault()">
                    @csrf
                    <div>
                        <div class="mb-2 flex items-baseline justify-between gap-3">
                            <label for="assignment-teacher-search" class="block text-sm font-semibold text-slate-800">Guru <span class="text-rose-500">*</span></label>
                        </div>
                        <div class="relative">
                            <input type="hidden" name="teacher_id" :value="selectedId">
                            <input id="assignment-teacher-search" x-ref="teacherSearch" type="search" autocomplete="off" required x-model="search" @focus="open = true" @input="selectedId = ''; open = true; $el.setCustomValidity('')" @keydown.escape="open = false" @keydown.enter.prevent="if (filteredTeachers.length === 1) selectTeacher(filteredTeachers[0]); else open = true" :aria-expanded="open.toString()" aria-controls="assignment-teacher-options" aria-autocomplete="list" role="combobox" placeholder="Ketik nama atau NIP guru..." class="w-full rounded-lg border-slate-200 py-2.5 pl-3 pr-10 text-sm focus:border-brand-500 focus:ring-brand-500">
                            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400"><x-icon name="chevron-right" class="size-4 rotate-90" /></span>
                            <div id="assignment-teacher-options" x-show="open" x-cloak x-transition role="listbox" aria-label="Daftar guru" class="absolute z-20 mt-2 max-h-60 w-full overflow-y-auto overscroll-contain rounded-xl border border-slate-200 bg-white py-1 shadow-lift">
                                <template x-for="teacher in filteredTeachers" :key="teacher.id">
                                    <button type="button" role="option" :aria-selected="selectedId === teacher.id" @click="selectTeacher(teacher)" class="block w-full px-3 py-2.5 text-left text-sm text-slate-700 hover:bg-brand-50 hover:text-brand-800" :class="selectedId === teacher.id ? 'bg-brand-50 font-semibold text-brand-800' : ''" x-text="teacher.label"></button>
                                </template>
                                <p x-show="filteredTeachers.length === 0" class="px-3 py-3 text-sm text-slate-500">Guru tidak ditemukan.</p>
                            </div>
                        </div>
                        @error('teacher_id')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <div class="mb-2 flex items-baseline justify-between gap-3">
                            <label for="assignment-subject-search" class="block text-sm font-semibold text-slate-800">Mata pelajaran <span class="text-rose-500">*</span></label>
                        </div>
                        <div class="relative">
                            <input type="hidden" name="subject_id" :value="selectedSubjectId">
                            <input id="assignment-subject-search" x-ref="subjectSearch" type="search" autocomplete="off" required x-model="subjectSearch" @focus="subjectOpen = true" @input="selectedSubjectId = ''; subjectOpen = true; $el.setCustomValidity('')" @keydown.escape="subjectOpen = false" @keydown.enter.prevent="if (filteredSubjects.length === 1) selectSubject(filteredSubjects[0]); else subjectOpen = true" :aria-expanded="subjectOpen.toString()" aria-controls="assignment-subject-options" aria-autocomplete="list" role="combobox" placeholder="Ketik mata pelajaran..." class="w-full rounded-lg border-slate-200 py-2.5 pl-3 pr-10 text-sm focus:border-brand-500 focus:ring-brand-500">
                            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400"><x-icon name="chevron-right" class="size-4 rotate-90" /></span>
                            <div id="assignment-subject-options" x-show="subjectOpen" x-cloak x-transition role="listbox" aria-label="Daftar mata pelajaran" class="absolute z-20 mt-2 max-h-60 w-full overflow-y-auto overscroll-contain rounded-xl border border-slate-200 bg-white py-1 shadow-lift">
                                <template x-for="subject in filteredSubjects" :key="subject.id"><button type="button" role="option" :aria-selected="selectedSubjectId === subject.id" @click="selectSubject(subject)" class="block w-full px-3 py-2.5 text-left text-sm text-slate-700 hover:bg-brand-50 hover:text-brand-800" :class="selectedSubjectId === subject.id ? 'bg-brand-50 font-semibold text-brand-800' : ''" x-text="subject.label"></button></template>
                                <p x-show="filteredSubjects.length === 0" class="px-3 py-3 text-sm text-slate-500">Mata pelajaran tidak ditemukan.</p>
                            </div>
                        </div>
                        @error('subject_id')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <div class="mb-2 flex items-baseline justify-between gap-3">
                            <label for="assignment-class-search" class="block text-sm font-semibold text-slate-800">Kelas <span class="text-rose-500">*</span></label>
                        </div>
                        <div class="relative">
                            <input type="hidden" name="kelas" :value="selectedClass">
                            <input id="assignment-class-search" x-ref="classSearch" type="search" autocomplete="off" required x-model="classSearch" @focus="classOpen = true" @input="selectedClass = ''; classOpen = true; $el.setCustomValidity('')" @keydown.escape="classOpen = false" @keydown.enter.prevent="if (filteredClasses.length === 1) selectClass(filteredClasses[0]); else classOpen = true" :aria-expanded="classOpen.toString()" aria-controls="assignment-class-options" aria-autocomplete="list" role="combobox" placeholder="Ketik nama kelas..." class="w-full rounded-lg border-slate-200 py-2.5 pl-3 pr-10 text-sm focus:border-brand-500 focus:ring-brand-500">
                            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400"><x-icon name="chevron-right" class="size-4 rotate-90" /></span>
                            <div id="assignment-class-options" x-show="classOpen" x-cloak x-transition role="listbox" aria-label="Daftar kelas" class="absolute z-20 mt-2 max-h-60 w-full overflow-y-auto overscroll-contain rounded-xl border border-slate-200 bg-white py-1 shadow-lift">
                                <template x-for="schoolClass in filteredClasses" :key="schoolClass.value"><button type="button" role="option" :aria-selected="selectedClass === schoolClass.value" @click="selectClass(schoolClass)" class="block w-full px-3 py-2.5 text-left text-sm text-slate-700 hover:bg-brand-50 hover:text-brand-800" :class="selectedClass === schoolClass.value ? 'bg-brand-50 font-semibold text-brand-800' : ''" x-text="schoolClass.label"></button></template>
                                <p x-show="filteredClasses.length === 0" class="px-3 py-3 text-sm text-slate-500">Kelas tidak ditemukan.</p>
                            </div>
                        </div>
                        @error('kelas')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <x-form.input name="tahun_ajaran" label="Tahun ajaran" required placeholder="2026/2027" />
                    <div class="xl:col-span-4 flex justify-end"><button class="rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">Simpan penugasan</button></div>
                </form>
            </div>
            <div x-show="assignmentTab === 'list'" x-cloak class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card">
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
