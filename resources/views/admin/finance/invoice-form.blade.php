@extends('layouts.admin')

@section('title', 'Ubah Tagihan')

@section('content')
    <div class="mx-auto max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-card">
        <div class="mb-5">
            <a href="{{ route('admin.finance.index', $filters) }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">← Kembali ke keuangan siswa</a>
            <h2 class="mt-3 text-lg font-bold text-brand-950">{{ $invoice->student->nama }}</h2>
            <p class="mt-1 text-sm text-slate-500">Pembayaran tercatat: Rp {{ number_format($paidAmount, 0, ',', '.') }}</p>
        </div>

        <form method="POST" action="{{ route('admin.finance.invoices.update', array_merge(['invoice' => $invoice], $filters)) }}" class="space-y-4">
            @csrf
            @method('PUT')
            @foreach($filters as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <div>
                <label for="invoice-category" class="mb-1.5 block text-sm font-semibold text-slate-700">Kategori tagihan <span class="text-rose-500">*</span></label>
                <select id="invoice-category" name="category" required class="w-full rounded-lg border-slate-200 py-2.5 text-sm focus:border-brand-500 focus:ring-brand-500">
                    <option value="" disabled @selected(! old('category', $invoice->category))>Pilih kategori tagihan</option>
                    @foreach($categories as $value => $label)
                        <option value="{{ $value }}" @selected(old('category', $invoice->category) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('category')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
            </div>
            <x-form.input name="title" label="Nama / detail tagihan" required :value="old('title', $invoice->title)" placeholder="Contoh: SPP November 2026" />
            <x-form.input name="amount" label="Nominal (Rp)" type="number" step="0.01" min="{{ $paidAmount > 0 ? $paidAmount : 1 }}" required :value="old('amount', $invoice->amount)" />
            <x-form.input name="due_date" label="Jatuh tempo" type="date" :value="old('due_date', $invoice->due_date?->format('Y-m-d'))" />
            <p class="text-xs text-slate-500">Nominal tidak boleh lebih kecil dari total pembayaran yang sudah diterima.</p>
            <div class="flex gap-3 pt-2">
                <button class="rounded-lg bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">Simpan perubahan</button>
                <a href="{{ route('admin.finance.index', $filters) }}" class="rounded-lg px-5 py-2.5 text-sm font-semibold text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50">Batal</a>
            </div>
        </form>
    </div>
@endsection
