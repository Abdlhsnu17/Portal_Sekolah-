@extends('layouts.admin')

@section('title', 'Ubah Pembayaran')

@section('content')
    <div class="mx-auto max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-card">
        <div class="mb-5">
            <a href="{{ route('admin.finance.index', $filters) }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">← Kembali ke keuangan siswa</a>
            <h2 class="mt-3 text-lg font-bold text-brand-950">{{ $invoice->student->nama }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $invoice->title }} · Total tagihan Rp {{ number_format($invoice->amount, 0, ',', '.') }}</p>
        </div>

        <form method="POST" action="{{ route('admin.finance.payments.update', array_merge(['invoice' => $invoice, 'payment' => $payment], $filters)) }}" class="space-y-4">
            @csrf
            @method('PUT')
            @foreach($filters as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <x-form.input name="amount" label="Nominal pembayaran (Rp)" type="number" step="0.01" min="0.01" max="{{ $maximumAmount }}" required :value="old('amount', $payment->amount)" />
            <x-form.input name="paid_on" label="Tanggal pembayaran" type="date" required :value="old('paid_on', $payment->paid_on->format('Y-m-d'))" />
            <x-form.input name="method" label="Metode pembayaran" maxlength="50" :value="old('method', $payment->method)" />
            <x-form.input name="reference" label="Referensi pembayaran" maxlength="100" :value="old('reference', $payment->reference)" />
            <p class="text-xs text-slate-500">Sisa maksimum yang dapat dicatat pada pembayaran ini: Rp {{ number_format($maximumAmount, 0, ',', '.') }}.</p>
            <div class="flex gap-3 pt-2">
                <button class="rounded-lg bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">Simpan perubahan</button>
                <a href="{{ route('admin.finance.index', $filters) }}" class="rounded-lg px-5 py-2.5 text-sm font-semibold text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50">Batal</a>
            </div>
        </form>
    </div>
@endsection
