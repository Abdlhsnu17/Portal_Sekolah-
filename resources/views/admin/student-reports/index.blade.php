@extends('layouts.admin')

@section('title', 'Rekap Nilai & Rapor')

@section('content')
    <div class="space-y-5">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
            <form method="GET"
                  class="flex flex-col items-start gap-4 sm:flex-row sm:items-end"
                  x-data="{
                      students: @js($students->map(fn ($s) => ['id' => (string) $s->id, 'label' => $s->nis.' — '.$s->nama])->values()),
                      selectedId: @js((string) old('student_id', $student?->id ?? '')),
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
                <div class="w-full sm:max-w-md" @click.outside="open = false">
                    <label for="student-search" class="mb-1.5 block text-sm font-semibold text-slate-700">Pilih siswa</label>
                    <div class="relative">
                        <input type="hidden" name="student_id" :value="selectedId">
                        <input id="student-search" x-ref="studentSearch" type="search" autocomplete="off" required
                               x-model="search" @focus="open = true"
                               @input="selectedId = ''; $refs.studentSearch.setCustomValidity(''); open = true"
                               @keydown.escape="open = false"
                               @keydown.enter.prevent="if (filteredStudents.length === 1) selectStudent(filteredStudents[0]); else open = true"
                               :aria-expanded="open.toString()" aria-controls="student-options" aria-autocomplete="list"
                               role="combobox" placeholder="Ketik nama atau NIS siswa..."
                               class="w-full rounded-lg border-slate-200 py-2.5 pl-3 pr-10 text-sm focus:border-brand-500 focus:ring-brand-500">
                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">
                            <x-icon name="chevron-right" class="size-4 rotate-90" />
                        </span>

                        <div id="student-options" x-show="open" x-cloak x-transition
                             x-ref="studentList" role="listbox" aria-label="Daftar siswa"
                             class="absolute z-20 mt-2 max-h-60 w-full overflow-y-auto overscroll-contain rounded-xl border border-slate-200 bg-white py-1 shadow-lift">
                            <template x-for="student in filteredStudents" :key="student.id">
                                <button type="button" role="option" :aria-selected="selectedId === student.id"
                                        @click="selectStudent(student)"
                                        class="block w-full px-3 py-2.5 text-left text-sm text-slate-700 hover:bg-brand-50 hover:text-brand-800"
                                        :class="selectedId === student.id ? 'bg-brand-50 font-semibold text-brand-800' : ''"
                                        x-text="student.label"></button>
                            </template>
                            <p x-show="filteredStudents.length === 0" class="px-3 py-3 text-sm text-slate-500">
                                Siswa tidak ditemukan.
                            </p>
                        </div>
                    </div>
                </div>
                <button class="w-full rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800 sm:w-auto">
                    Tampilkan rekap
                </button>
            </form>
            <div class="mt-4 border-t border-slate-100 pt-4 text-sm text-slate-600">
                Nilai diinput melalui menu <a href="{{ route('admin.assessments.index') }}" class="font-semibold text-brand-700 hover:text-brand-900">Akademik → Penilaian</a>.
                Buat komponen penilaian, lalu pilih <strong>Nilai</strong> untuk mengisi nilai seluruh siswa dalam rombel.
            </div>
        </section>

        @if ($student)
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
                <h2 class="font-bold text-brand-950">Rapor semester</h2>
                <form method="POST" action="{{ route('admin.student-reports.generate') }}" class="mt-4 flex flex-wrap items-end gap-3">@csrf
                    <input type="hidden" name="student_id" value="{{ $student->id }}">
                    <label class="text-sm">Tahun ajaran<select name="academic_year_id" class="mt-1 block rounded-lg border-slate-200">@foreach($years as $year)<option value="{{ $year->id }}" @selected($year->is_active)>{{ $year->name }}</option>@endforeach</select></label>
                    <label class="text-sm">Semester<select name="semester" class="mt-1 block rounded-lg border-slate-200"><option value="ganjil">Ganjil</option><option value="genap">Genap</option></select></label>
                    <label class="text-sm">KKM<input type="number" name="minimum_score" value="75" min="0" max="100" class="mt-1 block w-24 rounded-lg border-slate-200"></label>
                    <button class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white">Buat/perbarui draf</button>
                </form>
                <div class="mt-4 space-y-3">@forelse($reportCards as $report)<div class="rounded-xl bg-slate-50 p-4"><div class="flex flex-wrap items-center justify-between gap-2"><p class="font-semibold">{{ $report->academicYear->name }} · {{ ucfirst($report->semester) }} <span class="text-sm font-normal text-slate-500">{{ $report->published_at ? 'Terbit' : 'Draf' }}{{ $report->locked_at ? ' · Dikunci' : '' }}</span></p><span class="text-sm">{{ $report->items->count() }} mapel</span></div><form method="POST" action="{{ route('admin.student-reports.publish', $report) }}" class="mt-3 flex flex-wrap items-end gap-2">@csrf @method('PUT')<textarea name="homeroom_note" placeholder="Catatan wali kelas" class="min-w-64 rounded-lg border-slate-200 text-sm">{{ $report->homeroom_note }}</textarea><label class="text-sm"><input type="checkbox" name="publish" value="1" @checked($report->published_at)> Terbitkan</label><label class="text-sm"><input type="checkbox" name="lock" value="1"> Kunci</label><button class="rounded-lg border border-brand-200 px-3 py-2 text-sm font-semibold text-brand-700">Simpan</button></form></div>@empty<p class="text-sm text-slate-500">Belum ada rapor semester.</p>@endforelse</div>
            </section>
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card">
                <div class="border-b border-slate-100 p-5">
                    <h2 class="text-lg font-bold text-brand-950">{{ $student->nama }}</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        NIS {{ $student->nis }} ·
                        {{ $student->enrollments->last()?->schoolClass?->name ?? $student->kelas ?? 'Kelas belum ditetapkan' }}
                    </p>
                </div>
                <div class="grid gap-5 p-5 lg:grid-cols-[1fr_18rem]">
                    <div>
                        <h3 class="mb-3 font-bold text-brand-950">Nilai per mata pelajaran</h3>
                        <div class="overflow-x-auto">
                            <table data-smart-table data-smart-table-include-last-column data-table-name="rekap-nilai" class="w-full text-sm">
                                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                                    <tr>
                                        <th class="px-4 py-3">Mata pelajaran</th>
                                        <th class="px-4 py-3">Jumlah komponen</th>
                                        <th class="px-4 py-3">Rata-rata</th>
                                        <th class="px-4 py-3">Predikat</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @forelse ($scoresBySubject as $subject => $summary)
                                        @php($grade = $summary['average'] >= 90 ? 'A' : ($summary['average'] >= 80 ? 'B' : ($summary['average'] >= 70 ? 'C' : 'D')))
                                        <tr>
                                            <td class="px-4 py-3 font-semibold">{{ $subject }}</td>
                                            <td class="px-4 py-3">{{ $summary['count'] }}</td>
                                            <td class="px-4 py-3">{{ $summary['average'] }}</td>
                                            <td class="px-4 py-3 font-bold">{{ $grade }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="p-4 text-slate-500">Belum ada nilai untuk siswa ini.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <aside class="rounded-xl bg-slate-50 p-4">
                        <h3 class="font-bold text-brand-950">Rekap absensi</h3>
                        <dl class="mt-3 space-y-2 text-sm">
                            @foreach (\App\Models\StudentAttendance::STATUSES as $status => $label)
                                <div class="flex justify-between">
                                    <dt class="text-slate-600">{{ $label }}</dt>
                                    <dd class="font-bold">{{ $attendance[$status] ?? 0 }}</dd>
                                </div>
                            @endforeach
                        </dl>
                        <p class="mt-4 text-xs leading-5 text-slate-500">Predikat sementara: A ≥ 90, B ≥ 80, C ≥ 70, D &lt; 70.</p>
                    </aside>
                </div>
            </section>
        @endif
    </div>
@endsection
