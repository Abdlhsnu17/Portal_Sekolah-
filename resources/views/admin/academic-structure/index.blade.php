@extends('layouts.admin')

@section('title', 'Tahun Ajaran & Kelas')

@section('content')
@php($editingYear = $years->firstWhere('id', request()->integer('edit_year')))
@php($editingClass = $classes->firstWhere('id', request()->integer('edit_class')))
@php($editingEnrollment = $enrollments->firstWhere('id', request()->integer('edit_enrollment')))
<div x-data="{ tabs: ['tahun', 'kelas', 'penempatan'], tab: ['tahun', 'kelas', 'penempatan'].includes(location.hash.slice(1)) ? location.hash.slice(1) : @js(request('tab', request()->filled('edit_class') ? 'kelas' : 'tahun')), select(value) { this.tab = value; history.replaceState(null, '', `#${value}`); } }" @hashchange.window="tab = tabs.includes(location.hash.slice(1)) ? location.hash.slice(1) : tab" class="space-y-5">
    <div class="flex gap-2 overflow-x-auto border-b border-slate-200" role="tablist">
        @foreach (['tahun' => 'Tahun Ajaran', 'kelas' => 'Rombel', 'penempatan' => 'Penempatan Siswa'] as $id => $label)
            <button type="button" @click="select('{{ $id }}')" role="tab" :aria-selected="(tab === '{{ $id }}').toString()"
                :class="tab === '{{ $id }}' ? 'border-brand-700 text-brand-800' : 'border-transparent text-slate-500'"
                class="shrink-0 border-b-2 px-4 py-3 text-sm font-semibold">{{ $label }}</button>
        @endforeach
    </div>

    <section x-show="tab === 'tahun'" x-cloak class="admin-workspace-lg grid gap-5 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
            <div class="flex items-center justify-between gap-3"><h2 class="text-lg font-bold text-brand-950">{{ $editingYear ? 'Ubah tahun ajaran' : 'Tambah tahun ajaran' }}</h2>@if($editingYear)<a href="{{ route('admin.academic-structure.index') }}" class="text-sm font-semibold text-slate-500">Batal</a>@endif</div>
            <form method="POST" action="{{ $editingYear ? route('admin.academic-years.update', $editingYear) : route('admin.academic-years.store') }}" class="mt-4 space-y-4">@csrf @if($editingYear) @method('PUT') @endif
                <x-form.input name="name" label="Tahun ajaran" required placeholder="2026/2027" :value="old('name', $editingYear?->name)" />
                <x-form.input name="starts_on" type="date" label="Tanggal mulai" required :value="old('starts_on', $editingYear?->starts_on?->format('Y-m-d'))" />
                <x-form.input name="ends_on" type="date" label="Tanggal selesai" required :value="old('ends_on', $editingYear?->ends_on?->format('Y-m-d'))" />
                <label class="flex items-center gap-2 text-sm font-medium text-slate-700"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $editingYear?->is_active)) class="rounded border-slate-300 text-brand-700"> Jadikan aktif</label>
                <button class="w-full rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">{{ $editingYear ? 'Simpan perubahan' : 'Simpan' }}</button>
            </form>
        </div>
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card lg:col-span-2">
            <div class="border-b border-slate-100 p-4"><h2 class="font-bold text-brand-950">Daftar tahun ajaran</h2></div>
            <div class="divide-y divide-slate-100">@forelse($years as $year)
                <div class="flex items-center justify-between gap-4 px-5 py-4"><div><p class="font-bold text-brand-950">{{ $year->name }} @if($year->is_active)<span class="ml-2 rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700">Aktif</span>@endif</p><p class="mt-1 text-sm text-slate-500">{{ $year->starts_on->translatedFormat('d M Y') }} — {{ $year->ends_on->translatedFormat('d M Y') }} · {{ $year->school_classes_count }} rombel</p></div><div class="flex items-center gap-3">@unless($year->is_active)<form method="POST" action="{{ route('admin.academic-years.activate', $year) }}">@csrf @method('PUT')<button class="text-sm font-semibold text-brand-700">Aktifkan</button></form>@endunless<a href="{{ route('admin.academic-structure.index', ['edit_year' => $year->id]) }}" aria-label="Ubah tahun ajaran {{ $year->name }}" title="Ubah" class="inline-flex size-9 items-center justify-center rounded-lg text-brand-700 transition hover:bg-brand-50 hover:text-brand-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"><x-icon name="pencil" class="size-5" /></a><form method="POST" action="{{ route('admin.academic-years.destroy', $year) }}" data-confirm-delete data-delete-title="Hapus tahun ajaran?" data-delete-description="Rombel dan penempatan siswa di tahun ini juga akan dihapus.">@csrf @method('DELETE')<button aria-label="Hapus tahun ajaran {{ $year->name }}" title="Hapus" class="inline-flex size-9 items-center justify-center rounded-lg text-rose-600 transition hover:bg-rose-50 hover:text-rose-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500"><x-icon name="trash" class="size-5" /></button></form></div></div>
            @empty <x-empty-state icon="academic" title="Belum ada tahun ajaran" description="Buat tahun ajaran sebelum membuat rombel." /> @endforelse</div>
        </div>
    </section>

    <section x-show="tab === 'kelas'" x-cloak class="admin-workspace-lg grid gap-5 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card"><div class="flex items-center justify-between gap-3"><h2 class="text-lg font-bold text-brand-950">{{ $editingClass ? 'Ubah rombel' : 'Tambah rombel' }}</h2>@if($editingClass)<a href="{{ route('admin.academic-structure.index', ['tab' => 'kelas']) }}" class="text-sm font-semibold text-slate-500">Batal</a>@endif</div><form method="POST" action="{{ $editingClass ? route('admin.school-classes.update', $editingClass) : route('admin.school-classes.store') }}" class="mt-4 space-y-4">@csrf @if($editingClass) @method('PUT') @endif
            <x-form.search-select name="academic_year_id" label="Tahun ajaran" required :options="$years->pluck('name', 'id')->all()" placeholder="Cari tahun ajaran..." :value="old('academic_year_id', $editingClass?->academic_year_id)" />
            <x-form.input name="name" label="Nama rombel" required placeholder="Contoh: X-A" :value="old('name', $editingClass?->name)" />
            <x-form.input name="grade_level" label="Tingkat" placeholder="Contoh: X" :value="old('grade_level', $editingClass?->grade_level)" />
            <x-form.input name="major" label="Jurusan" placeholder="Opsional" :value="old('major', $editingClass?->major)" />
            <x-form.search-select name="homeroom_teacher_id" label="Wali kelas" :options="$teachers->mapWithKeys(fn($teacher) => [$teacher->id => $teacher->nama.' — '.$teacher->nip])->all()" placeholder="Cari wali kelas..." :value="old('homeroom_teacher_id', $editingClass?->homeroom_teacher_id)" />
            <x-form.input name="capacity" type="number" label="Kapasitas" min="1" placeholder="Opsional" :value="old('capacity', $editingClass?->capacity)" />
            <button class="w-full rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white">{{ $editingClass ? 'Simpan perubahan' : 'Simpan rombel' }}</button></form></div>
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card lg:col-span-2"><div class="border-b border-slate-100 p-4"><h2 class="font-bold text-brand-950">Rombel terdaftar</h2></div><div class="overflow-x-auto"><table data-smart-table data-table-name="rombel" data-smart-table-include-last-column class="w-full min-w-[640px] text-sm"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Rombel</th><th class="px-4 py-3">Tahun</th><th class="px-4 py-3">Wali kelas</th><th class="px-4 py-3">Siswa</th><th class="px-4 py-3 text-right">Aksi</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($classes as $class)<tr><td class="px-4 py-3 font-semibold text-brand-950">{{ $class->name }}<span class="block text-xs font-normal text-slate-500">{{ collect([$class->grade_level, $class->major])->filter()->join(' · ') }}</span></td><td class="px-4 py-3">{{ $class->academicYear->name }}</td><td class="px-4 py-3">{{ $class->homeroomTeacher?->nama ?? '—' }}</td><td class="px-4 py-3">{{ $class->enrollments_count }}{{ $class->capacity ? ' / '.$class->capacity : '' }}</td><td class="px-4 py-3"><div class="flex justify-end gap-3"><a href="{{ route('admin.academic-structure.index', ['tab' => 'kelas', 'edit_class' => $class->id]) }}" aria-label="Ubah rombel {{ $class->name }}" title="Ubah" class="inline-flex size-9 items-center justify-center rounded-lg text-brand-700 transition hover:bg-brand-50 hover:text-brand-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"><x-icon name="pencil" class="size-5" /></a><form method="POST" action="{{ route('admin.school-classes.destroy', $class) }}" data-confirm-delete data-delete-title="Hapus rombel?" data-delete-description="Semua penempatan siswa dalam rombel ini juga akan dihapus.">@csrf @method('DELETE')<button aria-label="Hapus rombel {{ $class->name }}" title="Hapus" class="inline-flex size-9 items-center justify-center rounded-lg text-rose-600 transition hover:bg-rose-50 hover:text-rose-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500"><x-icon name="trash" class="size-5" /></button></form></div></td></tr>@empty<tr><td colspan="5"><x-empty-state icon="academic" title="Belum ada rombel" description="Tambahkan rombel pertama." /></td></tr>@endforelse</tbody></table></div></div>
    </section>

    <section x-show="tab === 'penempatan'" x-cloak class="admin-workspace-lg grid gap-5 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
            <div class="flex items-center justify-between gap-3"><h2 class="text-lg font-bold text-brand-950">{{ $editingEnrollment ? 'Ubah penempatan siswa' : 'Tempatkan siswa' }}</h2>@if($editingEnrollment)<a href="{{ route('admin.academic-structure.index', ['tab' => 'penempatan']) }}" class="text-sm font-semibold text-slate-500">Batal</a>@endif</div>
            <p class="mt-1 text-sm text-slate-500">Riwayat penempatan disimpan per rombel dan tahun ajaran.</p>

            <form method="POST" action="{{ $editingEnrollment ? route('admin.student-enrollments.update', $editingEnrollment) : route('admin.student-enrollments.store') }}" class="mt-4 space-y-4"
                  x-data="{
                      students: @js($students->map(fn ($student) => ['id' => (string) $student->id, 'label' => $student->nis.' — '.$student->nama])->values()),
                      selectedId: @js((string) old('student_id', $editingEnrollment?->student_id ?? '')),
                      search: '',
                      open: false,
                      get filteredStudents() {
                          const query = this.search.trim().toLocaleLowerCase('id');
                          return query
                              ? this.students.filter((student) => student.label.toLocaleLowerCase('id').includes(query))
                              : this.students;
                      },
                      init() {
                          this.search = this.students.find((student) => student.id === this.selectedId)?.label ?? '';
                      },
                      selectStudent(student) {
                          this.selectedId = student.id;
                          this.search = student.label;
                          this.open = false;
                      },
                  }"
                  @submit="if (!selectedId) { $refs.studentSearch.setCustomValidity('Pilih siswa dari daftar yang tersedia.'); $refs.studentSearch.reportValidity(); $event.preventDefault(); }">
                @csrf @if($editingEnrollment) @method('PUT') @endif

                <div @click.outside="open = false">
                    <label for="enrollment-student-search" class="mb-1.5 block text-sm font-semibold text-slate-700">Siswa <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <input type="hidden" name="student_id" :value="selectedId">
                        <input id="enrollment-student-search" x-ref="studentSearch" type="search" autocomplete="off" required
                               x-model="search" @focus="open = true"
                               @input="selectedId = ''; $refs.studentSearch.setCustomValidity(''); open = true"
                               @keydown.escape="open = false"
                               @keydown.enter.prevent="if (filteredStudents.length === 1) selectStudent(filteredStudents[0]); else open = true"
                               :aria-expanded="open.toString()" aria-controls="enrollment-student-options" aria-autocomplete="list"
                               role="combobox" placeholder="Ketik nama atau NIS siswa..."
                               class="w-full rounded-lg border-slate-200 py-2.5 pl-3 pr-10 text-sm focus:border-brand-500 focus:ring-brand-500">
                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">
                            <x-icon name="chevron-right" class="size-4 rotate-90" />
                        </span>

                        <div id="enrollment-student-options" x-show="open" x-cloak x-transition role="listbox" aria-label="Daftar siswa"
                             class="absolute z-20 mt-2 max-h-60 w-full overflow-y-auto overscroll-contain rounded-xl border border-slate-200 bg-white py-1 shadow-lift">
                            <template x-for="student in filteredStudents" :key="student.id">
                                <button type="button" role="option" :aria-selected="selectedId === student.id"
                                        @click="selectStudent(student)"
                                        class="block w-full px-3 py-2.5 text-left text-sm text-slate-700 hover:bg-brand-50 hover:text-brand-800"
                                        :class="selectedId === student.id ? 'bg-brand-50 font-semibold text-brand-800' : ''"
                                        x-text="student.label"></button>
                            </template>
                            <p x-show="filteredStudents.length === 0" class="px-3 py-3 text-sm text-slate-500">Siswa tidak ditemukan.</p>
                        </div>
                    </div>
                </div>

                <x-form.search-select name="school_class_id" label="Rombel" required :options="$classes->mapWithKeys(fn($class) => [$class->id => $class->academicYear->name.' — '.$class->name])->all()" placeholder="Cari rombel..." :value="old('school_class_id', $editingEnrollment?->school_class_id)" />
                <x-form.input name="enrolled_on" type="date" label="Tanggal penempatan" :value="old('enrolled_on', $editingEnrollment?->enrolled_on?->format('Y-m-d'))" />
                <button class="w-full rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white">{{ $editingEnrollment ? 'Simpan perubahan' : 'Simpan penempatan' }}</button>
            </form>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card lg:col-span-2"><div class="border-b border-slate-100 p-4"><h2 class="font-bold text-brand-950">Penempatan siswa</h2><p class="mt-1 text-sm text-slate-500">Riwayat penempatan dipakai untuk kelas aktif, absensi, jadwal, dan nilai.</p></div><div class="overflow-x-auto"><table data-smart-table data-table-name="penempatan-siswa" data-smart-table-include-last-column class="w-full min-w-[620px] text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Siswa</th><th class="px-4 py-3">Rombel</th><th class="px-4 py-3">Tanggal</th><th class="px-4 py-3 text-right">Aksi</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($enrollments as $enrollment)<tr><td class="px-4 py-3 font-semibold text-brand-950">{{ $enrollment->student->nama }}<span class="block text-xs font-normal text-slate-500">{{ $enrollment->student->nis }}</span></td><td class="px-4 py-3">{{ $enrollment->schoolClass->name }}<span class="block text-xs text-slate-500">{{ $enrollment->schoolClass->academicYear->name }}</span></td><td class="px-4 py-3">{{ $enrollment->enrolled_on?->translatedFormat('d M Y') ?? '—' }}</td><td class="px-4 py-3"><div class="flex justify-end gap-3"><a href="{{ route('admin.academic-structure.index', ['tab' => 'penempatan', 'edit_enrollment' => $enrollment->id]) }}" aria-label="Ubah penempatan {{ $enrollment->student->nama }}" title="Ubah" class="inline-flex size-9 items-center justify-center rounded-lg text-brand-700 transition hover:bg-brand-50 hover:text-brand-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"><x-icon name="pencil" class="size-5" /></a><form method="POST" action="{{ route('admin.student-enrollments.destroy', $enrollment) }}" data-confirm-delete data-delete-title="Hapus penempatan?" data-delete-description="Siswa tidak lagi tercatat di rombel ini.">@csrf @method('DELETE')<button aria-label="Hapus penempatan {{ $enrollment->student->nama }}" title="Hapus" class="inline-flex size-9 items-center justify-center rounded-lg text-rose-600 transition hover:bg-rose-50 hover:text-rose-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500"><x-icon name="trash" class="size-5" /></button></form></div></td></tr>@empty<tr><td colspan="4"><x-empty-state icon="academic" title="Belum ada penempatan" description="Tempatkan siswa ke rombel untuk memulai." /></td></tr>@endforelse</tbody></table></div></div>
    </section>
</div>
@endsection
