@extends('layouts.admin')

@section('title', 'Keuangan Siswa')

@section('content')
    @php
        $financeFilters = array_filter([
            'academic_year_id' => $selectedYearId,
            'school_class_id' => $selectedClassId,
        ]);
    @endphp

    <div class="mb-5 space-y-3">
        <div class="flex gap-2 overflow-x-auto border-b border-slate-200" role="tablist" aria-label="Tahun ajaran">
            @forelse($years as $year)
                <a href="{{ route('admin.finance.index', ['academic_year_id' => $year->id]) }}" role="tab" aria-selected="{{ (int) $selectedYearId === $year->id ? 'true' : 'false' }}" class="shrink-0 border-b-2 px-4 py-3 text-sm font-semibold {{ (int) $selectedYearId === $year->id ? 'border-brand-700 text-brand-800' : 'border-transparent text-slate-500 hover:text-brand-700' }}">{{ $year->name }}</a>
            @empty
                <span class="px-4 py-3 text-sm text-slate-500">Belum ada tahun ajaran.</span>
            @endforelse
        </div>
        @if($classes->isNotEmpty())
            <div class="flex flex-wrap gap-2" aria-label="Filter rombel">
                <a href="{{ route('admin.finance.index', ['academic_year_id' => $selectedYearId]) }}" class="rounded-full px-3 py-1.5 text-sm font-semibold {{ ! $selectedClassId ? 'bg-brand-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">Semua rombel</a>
                @foreach($classes as $class)
                    <a href="{{ route('admin.finance.index', ['academic_year_id' => $selectedYearId, 'school_class_id' => $class->id]) }}" class="rounded-full px-3 py-1.5 text-sm font-semibold {{ (int) $selectedClassId === $class->id ? 'bg-brand-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">{{ $class->name }}</a>
                @endforeach
            </div>
        @endif
    </div>

    <div x-data="{ activeSection: ['create', 'invoices'].includes(location.hash.slice(1)) ? location.hash.slice(1) : 'create', select(section) { this.activeSection = section; history.replaceState(null, '', `#${section}`); } }" @hashchange.window="activeSection = ['create', 'invoices'].includes(location.hash.slice(1)) ? location.hash.slice(1) : 'create'">
        <div class="flex gap-2 border-b border-slate-200" role="tablist" aria-label="Keuangan siswa">
            <button type="button" @click="select('create')" :aria-selected="(activeSection === 'create').toString()" :class="activeSection === 'create' ? 'border-brand-700 text-brand-800' : 'border-transparent text-slate-500 hover:text-brand-700'" class="border-b-2 px-4 py-3 text-sm font-semibold" role="tab" aria-controls="finance-create-panel">Buat tagihan</button>
            <button type="button" @click="select('invoices')" :aria-selected="(activeSection === 'invoices').toString()" :class="activeSection === 'invoices' ? 'border-brand-700 text-brand-800' : 'border-transparent text-slate-500 hover:text-brand-700'" class="border-b-2 px-4 py-3 text-sm font-semibold" role="tab" aria-controls="finance-invoices-panel">Tagihan siswa</button>
        </div>
        <div class="mt-5">
        <section id="finance-create-panel" x-show="activeSection === 'create'" x-cloak x-transition.opacity class="mx-auto max-w-3xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card" role="tabpanel">
            <div class="border-b border-slate-100 bg-linear-to-r from-brand-50 to-white px-5 py-4 sm:px-6">
                <p class="text-xs font-bold uppercase tracking-wider text-brand-700">Keuangan siswa</p>
                <h2 class="mt-1 text-xl font-bold text-brand-950">Buat tagihan baru</h2>
                <p class="mt-0.5 text-sm text-slate-500">Tentukan siswa, kategori, dan rincian pembayaran.</p>
            </div>
            <form method="POST" action="{{ route('admin.finance.invoices.store') }}" class="space-y-4 p-5 sm:p-6">
                @csrf
                @foreach($financeFilters as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <div x-data="{ students: @js($students->map(fn ($student) => ['id' => (string) $student->id, 'label' => $student->nis.' — '.$student->nama])->values()), selectedId: @js((string) old('student_id', '')), search: '', open: false, get filteredStudents() { const query = this.search.trim().toLocaleLowerCase('id'); return query ? this.students.filter((student) => student.label.toLocaleLowerCase('id').includes(query)) : this.students; }, init() { this.search = this.students.find((student) => student.id === this.selectedId)?.label ?? ''; }, selectStudent(student) { this.selectedId = student.id; this.search = student.label; this.open = false; } }" @click.outside="open = false">
                    <div class="mb-2 flex items-baseline justify-between gap-3"><label for="invoice-student-search" class="block text-sm font-semibold text-slate-800">Siswa <span class="text-rose-500">*</span></label><span class="text-xs text-slate-400">Cari berdasarkan nama atau NIS</span></div>
                    <div class="relative">
                        <input type="hidden" name="student_id" :value="selectedId">
                        <input id="invoice-student-search" type="search" autocomplete="off" required x-model="search" @focus="open = true" @input="selectedId = ''; open = true" @keydown.escape="open = false" @keydown.enter.prevent="if (filteredStudents.length === 1) selectStudent(filteredStudents[0]); else open = true" :aria-expanded="open.toString()" aria-controls="invoice-student-options" aria-autocomplete="list" role="combobox" placeholder="Ketik nama atau NIS siswa..." class="w-full rounded-lg border-slate-200 py-2.5 pl-3 pr-10 text-sm focus:border-brand-500 focus:ring-brand-500">
                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400"><x-icon name="chevron-right" class="size-4 rotate-90" /></span>
                        <div id="invoice-student-options" x-show="open" x-cloak x-transition role="listbox" aria-label="Daftar siswa" class="absolute z-20 mt-2 max-h-60 w-full overflow-y-auto overscroll-contain rounded-xl border border-slate-200 bg-white py-1 shadow-lift">
                            <template x-for="student in filteredStudents" :key="student.id">
                                <button type="button" role="option" :aria-selected="selectedId === student.id" @click="selectStudent(student)" class="block w-full px-3 py-2.5 text-left text-sm text-slate-700 hover:bg-brand-50 hover:text-brand-800" :class="selectedId === student.id ? 'bg-brand-50 font-semibold text-brand-800' : ''" x-text="student.label"></button>
                            </template>
                            <p x-show="filteredStudents.length === 0" class="px-3 py-3 text-sm text-slate-500">Siswa tidak ditemukan.</p>
                        </div>
                    </div>
                </div>
                <div class="border-t border-slate-100 pt-4">
                    <div class="mb-3 flex items-baseline justify-between gap-3"><h3 class="text-sm font-bold text-slate-800">Rincian tagihan</h3><p class="text-xs text-slate-500">Kategori untuk laporan</p></div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="invoice-category" class="mb-1.5 block text-sm font-semibold text-slate-700">Kategori tagihan <span class="text-rose-500">*</span></label>
                            <select id="invoice-category" name="category" required class="w-full rounded-lg border-slate-200 py-2.5 text-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="" disabled @selected(! old('category'))>Pilih kategori tagihan</option>
                                @foreach(\App\Models\StudentInvoice::CATEGORIES as $value => $label)
                                    <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('category')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <x-form.input name="title" label="Nama / detail tagihan" required placeholder="Contoh: SPP November 2026" />
                        <div x-data="{ amount: @js((string) old('amount', '')), rupiah() { return this.amount > 0 ? new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(this.amount) : ''; } }">
                            <label for="amount" class="block text-sm font-medium text-slate-700">Nominal <span class="text-rose-500">*</span></label>
                            <div class="mt-1.5 flex overflow-hidden rounded-lg border border-slate-200 bg-white transition focus-within:border-brand-400 focus-within:ring-2 focus-within:ring-brand-100"><span class="flex items-center border-r border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-500">Rp</span><input id="amount" name="amount" type="number" inputmode="decimal" step="0.01" min="1" required x-model="amount" @input="$el.setCustomValidity(Number($el.value) <= 0 ? 'Nominal tagihan harus lebih dari Rp0.' : '')" class="min-w-0 flex-1 border-0 px-3.5 py-2.5 text-sm outline-none focus:ring-0" placeholder="0"></div>
                            <p x-show="amount > 0" x-text="rupiah()" class="mt-1.5 text-xs font-semibold text-brand-700"></p>
                            <p x-show="! amount" class="mt-1.5 text-xs text-slate-500">Masukkan nominal lebih dari Rp0.</p>
                            @error('amount')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div><x-form.input name="due_date" type="date" label="Jatuh tempo" /><p class="mt-1.5 text-xs text-slate-500">Opsional — kosongkan jika tanpa batas pembayaran.</p></div>
                    </div>
                </div>
                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between"><p class="text-xs text-slate-500"><span class="text-rose-500">*</span> Wajib diisi</p><button class="inline-flex justify-center rounded-lg bg-brand-700 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">Buat tagihan</button></div>
            </form>
        </section>

        <section id="finance-invoices-panel" x-show="activeSection === 'invoices'" x-cloak x-transition.opacity class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card" role="tabpanel">
            <div class="border-b border-slate-100 p-4"><h2 class="font-bold text-brand-950">Tagihan siswa</h2></div>
            <div class="overflow-x-auto">
                <table data-smart-table data-table-name="tagihan-siswa" class="w-full min-w-245 text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr><th class="px-4 py-3">Siswa/tagihan</th><th class="px-4 py-3">Jatuh tempo</th><th class="px-4 py-3">Tagihan</th><th class="px-4 py-3">Terbayar</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Aksi</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($invoices as $invoice)
                            @php($remaining = max(0, (float) $invoice->amount - (float) ($invoice->payments_sum_amount ?? 0)))
                            <tr>
                                <td class="px-4 py-3"><p class="font-semibold text-brand-950">{{ $invoice->student->nama }}</p><p class="text-xs text-slate-500">{{ \App\Models\StudentInvoice::CATEGORIES[$invoice->category] ?? 'Belum dikategorikan' }} · {{ $invoice->title }}</p></td>
                                <td class="px-4 py-3">{{ $invoice->due_date?->format('d/m/Y') ?? '—' }}</td>
                                <td class="px-4 py-3">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
                                <td class="px-4 py-3">Rp {{ number_format($invoice->payments_sum_amount ?? 0, 0, ',', '.') }}</td>
                                <td class="px-4 py-3"><span class="text-xs font-semibold capitalize {{ $invoice->status === 'lunas' ? 'text-emerald-700' : 'text-amber-700' }}">{{ str_replace('_', ' ', $invoice->status) }}</span></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.finance.invoices.edit', array_merge(['invoice' => $invoice], $financeFilters)) }}" aria-label="Ubah tagihan" title="Ubah" class="inline-flex size-8 items-center justify-center rounded-lg text-brand-700 transition hover:bg-brand-50 hover:text-brand-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"><x-icon name="pencil" class="size-4" /></a>
                                        @if($remaining > 0)
                                            <a href="{{ route('admin.finance.payments.create', array_merge(['invoice' => $invoice], $financeFilters)) }}" class="rounded-lg bg-emerald-700 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-800">Bayar</a>
                                        @endif
                                        @if($invoice->status !== 'lunas')
                                            <details class="hidden relative">
                                                <summary class="cursor-pointer text-xs font-semibold text-emerald-700">Catat bayar</summary>
                                                <form method="POST" action="{{ route('admin.finance.payments.store', array_merge(['invoice' => $invoice], $financeFilters)) }}" class="absolute right-0 z-20 mt-2 w-64 space-y-2 rounded-xl border border-slate-200 bg-white p-3 shadow-lift" x-data="{ amount: '', maximum: {{ $remaining }}, rupiah(value) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value); } }">
                                                    @csrf
                                                    @foreach($financeFilters as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
                                                    <p class="text-xs font-medium text-slate-600">Sisa tagihan: <span class="font-semibold text-brand-700" x-text="rupiah(maximum)"></span></p>
                                                    <input name="amount" type="number" inputmode="decimal" step="0.01" min="0.01" max="{{ $remaining }}" x-model="amount" @input="$el.setCustomValidity(Number($el.value) <= 0 ? 'Nominal pembayaran harus lebih dari Rp0.' : Number($el.value) > maximum ? 'Nominal melebihi sisa tagihan.' : '')" class="w-full rounded border-slate-300 text-xs" placeholder="Nominal pembayaran" required>
                                                    <p x-show="amount > 0" x-text="rupiah(amount)" class="text-xs font-semibold text-brand-700"></p>
                                                    <input name="paid_on" type="date" value="{{ old('paid_on', now()->toDateString()) }}" class="w-full rounded border-slate-300 text-xs" required>
                                                    <input name="method" class="w-full rounded border-slate-300 text-xs" placeholder="Metode">
                                                    <input name="reference" class="w-full rounded border-slate-300 text-xs" placeholder="Referensi (opsional)">
                                                    <button class="rounded bg-brand-700 px-2 py-1 text-xs font-semibold text-white">Simpan pembayaran</button>
                                                </form>
                                            </details>
                                        @endif
                                        <form method="POST" action="{{ route('admin.finance.invoices.destroy', array_merge(['invoice' => $invoice], $financeFilters)) }}">
                                            @csrf
                                            @method('DELETE')
                                            @foreach($financeFilters as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
                                            <button aria-label="Hapus tagihan" title="Hapus" class="inline-flex size-8 items-center justify-center rounded-lg text-rose-600 transition hover:bg-rose-50 hover:text-rose-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500"><x-icon name="trash" class="size-4" /></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><x-empty-state icon="clipboard" title="Belum ada tagihan" description="Buat tagihan pertama untuk siswa." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        </div>
    </div>
@endsection
