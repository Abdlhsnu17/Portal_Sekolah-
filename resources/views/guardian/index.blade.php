@extends('layouts.public')

@section('title', 'Dashboard Wali')

@section('content')
    <section class="mx-auto max-w-6xl px-6 py-12">
        <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-brand-700">Dashboard Wali</p>
                <h1 class="mt-1 text-3xl font-bold text-brand-950">Informasi siswa</h1>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('guardian.notes') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-brand-700 hover:bg-brand-50">Catatan siswa</a>
                <a href="{{ route('messages.index') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-brand-700 hover:bg-brand-50">Pesan guru</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-lg border px-4 py-2 text-sm font-semibold">Keluar</button></form>
            </div>
        </div>
        <div class="space-y-5">
            @forelse($data as $item)
                <article class="rounded-2xl border bg-white p-6 shadow-card">
                    <h2 class="text-xl font-bold text-brand-950">{{ $item['student']->nama }}</h2>
                    <p class="mt-1 text-sm text-slate-500">NIS {{ $item['student']->nis }} · {{ $item['student']->enrollments->last()?->schoolClass?->name ?? 'Kelas belum ditetapkan' }}</p>
                    <div class="mt-5 grid gap-5 md:grid-cols-4">
                        <div>
                            <h3 class="font-bold">Absensi</h3>
                            <p class="mt-2 text-sm text-slate-600">Hadir {{ $item['attendance']['hadir'] ?? 0 }} · Sakit {{ $item['attendance']['sakit'] ?? 0 }} · Izin {{ $item['attendance']['izin'] ?? 0 }} · Alpa {{ $item['attendance']['alpa'] ?? 0 }}</p>
                        </div>
                        <div>
                            <h3 class="font-bold">Nilai rata-rata</h3>
                            <ul class="mt-2 text-sm text-slate-600">
                                @forelse($item['scores'] as $mapel => $nilai)<li>{{ $mapel }}: <b>{{ $nilai }}</b></li>@empty<li>Belum ada nilai.</li>@endforelse
                            </ul>
                            @foreach($item['reports'] as $report)<p class="mt-2 text-sm text-brand-700">Rapor {{ $report->academicYear->name }} · {{ ucfirst($report->semester) }} telah terbit.</p>@endforeach
                        </div>
                        <div>
                            <h3 class="font-bold">Tagihan</h3>
                            <ul class="mt-2 text-sm text-slate-600">
                                @forelse($item['invoices'] as $invoice)
                                    @php
                                        $paymentRequest = $invoice->paymentRequests->first();
                                        $paymentStep = $invoice->status === 'lunas' ? 3 : ($paymentRequest?->status === 'pending' ? 2 : 1);
                                    @endphp
                                    <li class="mb-3 rounded-lg border border-slate-100 p-3 last:mb-0">
                                        <p>{{ $invoice->title }}: <b>Rp {{ number_format(max(0, (float) $invoice->amount - (float) ($invoice->payments_sum_amount ?? 0)), 0, ',', '.') }}</b></p>
                                        <x-progress-steps class="mt-3 text-xs" :steps="['Pilih tagihan', 'Bayar', 'Terverifikasi']" :active="$paymentStep" />
                                        @if($invoice->status !== 'lunas')
                                            <form method="POST" action="{{ route('guardian.invoices.pay', $invoice) }}" class="mt-3">@csrf<button class="text-xs font-semibold text-brand-700">{{ $paymentRequest?->status === 'pending' ? 'Buat ulang referensi bayar' : 'Buat referensi bayar' }}</button></form>
                                        @endif
                                    </li>
                                @empty<li>Tidak ada tagihan.</li>@endforelse
                            </ul>
                        </div>
                        <div>
                            <h3 class="font-bold">Catatan terbaru</h3>
                            @forelse($item['notes'] as $note)
                                <p class="mt-2 text-sm font-semibold text-brand-800">{{ $note->title }}</p>
                                <p class="mt-1 text-sm text-slate-600">{{ \Illuminate\Support\Str::limit($note->content, 100) }}</p>
                            @empty
                                <p class="mt-2 text-sm text-slate-500">Belum ada catatan terbit.</p>
                            @endforelse
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border bg-white p-8 text-slate-500">Akun wali belum ditautkan dengan siswa. Hubungi sekolah.</div>
            @endforelse
        </div>
    </section>
@endsection
