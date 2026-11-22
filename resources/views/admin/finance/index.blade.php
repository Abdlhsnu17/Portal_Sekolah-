@extends('layouts.admin')
@section('title', 'Keuangan Siswa')
@section('content')
<div class="mb-5 space-y-3">
    <div class="flex gap-2 overflow-x-auto border-b border-slate-200" role="tablist" aria-label="Tahun ajaran">
        @forelse($years as $year)
            <a href="{{ route('admin.finance.index', ['academic_year_id' => $year->id]) }}" role="tab" aria-selected="{{ $selectedYearId === $year->id ? 'true' : 'false' }}" class="shrink-0 border-b-2 px-4 py-3 text-sm font-semibold {{ $selectedYearId === $year->id ? 'border-brand-700 text-brand-800' : 'border-transparent text-slate-500 hover:text-brand-700' }}">{{ $year->name }}</a>
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
<div class="grid gap-5 xl:grid-cols-3">
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
        <h2 class="text-lg font-bold text-brand-950">Buat tagihan</h2>
        <form method="POST" action="{{ route('admin.finance.invoices.store') }}" class="mt-4 space-y-4">
            @csrf
            <div x-data="{ students: @js($students->map(fn ($student) => ['id' => (string) $student->id, 'label' => $student->nis.' — '.$student->nama])->values()), selectedId: @js((string) old('student_id', '')), search: '', open: false, get filteredStudents() { const query = this.search.trim().toLocaleLowerCase('id'); return query ? this.students.filter((student) => student.label.toLocaleLowerCase('id').includes(query)) : this.students; }, init() { this.search = this.students.find((student) => student.id === this.selectedId)?.label ?? ''; }, selectStudent(student) { this.selectedId = student.id; this.search = student.label; this.open = false; } }" @click.outside="open = false">
                <label for="invoice-student-search" class="mb-1.5 block text-sm font-semibold text-slate-700">Siswa <span class="text-rose-500">*</span></label>
                <div class="relative"><input type="hidden" name="student_id" :value="selectedId"><input id="invoice-student-search" type="search" autocomplete="off" required x-model="search" @focus="open = true" @input="selectedId = ''; open = true" @keydown.escape="open = false" @keydown.enter.prevent="if (filteredStudents.length === 1) selectStudent(filteredStudents[0]); else open = true" :aria-expanded="open.toString()" aria-controls="invoice-student-options" aria-autocomplete="list" role="combobox" placeholder="Ketik nama atau NIS siswa..." class="w-full rounded-lg border-slate-200 py-2.5 pl-3 pr-10 text-sm focus:border-brand-500 focus:ring-brand-500"><span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400"><x-icon name="chevron-right" class="size-4 rotate-90" /></span><div id="invoice-student-options" x-show="open" x-cloak x-transition role="listbox" aria-label="Daftar siswa" class="absolute z-20 mt-2 max-h-60 w-full overflow-y-auto overscroll-contain rounded-xl border border-slate-200 bg-white py-1 shadow-lift"><template x-for="student in filteredStudents" :key="student.id"><button type="button" role="option" :aria-selected="selectedId === student.id" @click="selectStudent(student)" class="block w-full px-3 py-2.5 text-left text-sm text-slate-700 hover:bg-brand-50 hover:text-brand-800" :class="selectedId === student.id ? 'bg-brand-50 font-semibold text-brand-800' : ''" x-text="student.label"></button></template><p x-show="filteredStudents.length === 0" class="px-3 py-3 text-sm text-slate-500">Siswa tidak ditemukan.</p></div></div>
            </div>
            <x-form.input name="title" label="Jenis tagihan" required placeholder="Contoh: SPP November"/>
            <div x-data="{ amount: @js((string) old('amount', '')), rupiah() { return this.amount > 0 ? new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(this.amount) : ''; } }">
                <label for="amount" class="block text-sm font-medium text-slate-700">Nominal (Rp) <span class="text-rose-500">*</span></label>
                <input id="amount" name="amount" type="number" inputmode="decimal" step="0.01" min="1" required x-model="amount" @input="$el.setCustomValidity(Number($el.value) <= 0 ? 'Nominal tagihan harus lebih dari Rp0.' : '')" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm outline-none transition focus:border-brand-400 focus:ring-2 focus:ring-brand-100">
                <p x-show="amount > 0" x-text="rupiah()" class="mt-1.5 text-sm font-semibold text-brand-700"></p>
                <p class="mt-1.5 text-xs text-slate-500">Nominal harus lebih dari Rp0.</p>
                @error('amount')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
            </div>
            <x-form.input name="due_date" type="date" label="Jatuh tempo"/>
            <button class="w-full rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white">Buat tagihan</button>
        </form>
    </section>
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card xl:col-span-2">
        <div class="border-b border-slate-100 p-4"><h2 class="font-bold text-brand-950">Tagihan siswa</h2></div>
        <div class="overflow-x-auto"><table data-smart-table data-table-name="tagihan-siswa" class="w-full min-w-[780px] text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Siswa/tagihan</th><th class="px-4 py-3">Jatuh tempo</th><th class="px-4 py-3">Tagihan</th><th class="px-4 py-3">Terbayar</th><th class="px-4 py-3">Status</th><th class="px-4 py-3"></th></tr></thead><tbody class="divide-y divide-slate-100">
            @forelse($invoices as $invoice)
                @php($remaining = max(0, (float) $invoice->amount - (float) ($invoice->payments_sum_amount ?? 0)))
                <tr><td class="px-4 py-3"><p class="font-semibold text-brand-950">{{ $invoice->student->nama }}</p><p class="text-xs text-slate-500">{{ $invoice->title }}</p></td><td class="px-4 py-3">{{ $invoice->due_date?->format('d/m/Y') ?? '—' }}</td><td class="px-4 py-3">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td><td class="px-4 py-3">Rp {{ number_format($invoice->payments_sum_amount ?? 0, 0, ',', '.') }}</td><td class="px-4 py-3"><span class="text-xs font-semibold capitalize {{ $invoice->status === 'lunas' ? 'text-emerald-700' : 'text-amber-700' }}">{{ str_replace('_', ' ', $invoice->status) }}</span></td><td class="px-4 py-3">
                    @if($invoice->status !== 'lunas')
                        <details><summary class="cursor-pointer font-semibold text-brand-700">Bayar</summary><form method="POST" action="{{ route('admin.finance.payments.store', $invoice) }}" class="mt-2 w-52 space-y-2" x-data="{ amount: '', maximum: {{ $remaining }}, rupiah(value) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value); } }">@csrf <p class="text-xs font-medium text-slate-600">Sisa tagihan: <span class="font-semibold text-brand-700" x-text="rupiah(maximum)"></span></p><input name="amount" type="number" inputmode="decimal" step="0.01" min="0.01" max="{{ $remaining }}" x-model="amount" @input="$el.setCustomValidity(Number($el.value) <= 0 ? 'Nominal pembayaran harus lebih dari Rp0.' : Number($el.value) > maximum ? 'Nominal melebihi sisa tagihan.' : '')" class="w-full rounded border-slate-300 text-xs" placeholder="Nominal pembayaran" required><p x-show="amount > 0" x-text="rupiah(amount)" class="text-xs font-semibold text-brand-700"></p><input name="paid_on" type="date" class="w-full rounded border-slate-300 text-xs" required><input name="method" class="w-full rounded border-slate-300 text-xs" placeholder="Metode"><button class="rounded bg-brand-700 px-2 py-1 text-xs font-semibold text-white">Simpan</button></form></details>
                    @endif
                </td></tr>
            @empty
                <tr><td colspan="6"><x-empty-state icon="clipboard" title="Belum ada tagihan" description="Buat tagihan pertama untuk siswa."/></td></tr>
            @endforelse
        </tbody></table></div>
    </section>
</div>
@endsection
