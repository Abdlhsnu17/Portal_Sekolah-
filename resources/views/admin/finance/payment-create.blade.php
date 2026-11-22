@extends('layouts.admin')

@section('title', 'Catat Pembayaran')

@section('content')
    <div class="mx-auto max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-card">
        <a href="{{ route('admin.finance.index', $filters) }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">← Kembali ke tagihan siswa</a>
        <h2 class="mt-3 text-lg font-bold text-brand-950">Bayar tagihan</h2>
        <p class="mt-1 text-sm text-slate-500">{{ $invoice->student->nama }} · {{ $invoice->title }}</p>
        <p class="mt-2 text-sm font-semibold text-brand-700">Sisa tagihan: Rp {{ number_format($remaining, 0, ',', '.') }}</p>

        <form method="POST" action="{{ route('admin.finance.payments.store', array_merge(['invoice' => $invoice], $filters)) }}" x-data="{ optionsOpen: {{ $errors->any() ? 'true' : 'false' }} }" class="mt-5 space-y-4">
            @csrf
            @foreach($filters as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
            <div>
                <label for="amount" class="block text-sm font-semibold text-slate-700">Nominal dibayar <span class="text-rose-500">*</span></label>
                <input id="amount" name="amount" type="number" inputmode="decimal" step="0.01" min="0.01" max="{{ $remaining }}" required value="{{ old('amount', $remaining) }}" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm outline-none transition focus:border-brand-400 focus:ring-2 focus:ring-brand-100">
                <p class="mt-1.5 text-xs text-slate-500">Otomatis diisi sesuai sisa tagihan. Ubah hanya bila siswa membayar sebagian.</p>
                @error('amount')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
            </div>

            <details :open="optionsOpen" @toggle="optionsOpen = $el.open" class="rounded-lg border border-slate-200 px-4 py-3">
                <summary class="cursor-pointer text-sm font-semibold text-brand-700">Opsi pembayaran lain</summary>
                <p class="mt-1 text-xs text-slate-500">Ubah bila bukan tunai, tanggalnya bukan hari ini, atau ada nomor referensi.</p>
                <div class="mt-4 space-y-4">
                    <x-form.input name="paid_on" label="Tanggal pembayaran" type="date" :required="false" :value="old('paid_on', now()->toDateString())" x-bind:disabled="!optionsOpen" />
                    <div>
                        <label for="method" class="block text-sm font-medium text-slate-700">Metode pembayaran</label>
                        <select id="method" name="method" x-bind:disabled="!optionsOpen" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm outline-none transition focus:border-brand-400 focus:ring-2 focus:ring-brand-100">
                            @foreach(['Tunai', 'Transfer bank', 'QRIS', 'Lainnya'] as $method)
                                <option value="{{ $method }}" @selected(old('method', 'Tunai') === $method)>{{ $method }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-form.input name="reference" label="Nomor referensi (opsional)" maxlength="100" :value="old('reference')" placeholder="Contoh: nomor transfer" x-bind:disabled="!optionsOpen" />
                </div>
            </details>
            <input type="hidden" name="paid_on" value="{{ old('paid_on', now()->toDateString()) }}" x-bind:disabled="optionsOpen">
            <input type="hidden" name="method" value="{{ old('method', 'Tunai') }}" x-bind:disabled="optionsOpen">
            <button class="w-full rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Simpan pembayaran</button>
        </form>

        @if($invoice->payments->isNotEmpty())
            <div class="mt-6 border-t border-slate-100 pt-4">
                <h3 class="font-semibold text-brand-950">Riwayat pembayaran</h3>
                <div class="mt-3 space-y-2">
                    @foreach($invoice->payments->sortByDesc('paid_on') as $payment)
                        <p class="rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700">Rp {{ number_format($payment->amount, 0, ',', '.') }} · {{ $payment->paid_on->format('d/m/Y') }}{{ $payment->method ? ' · '.$payment->method : '' }}</p>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection
