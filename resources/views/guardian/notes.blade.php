@extends('layouts.public')

@section('title', 'Catatan Siswa')

@section('content')
    <section class="mx-auto max-w-6xl px-6 py-12">
        <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-brand-700">Portal Wali</p>
                <h1 class="mt-1 text-3xl font-bold text-brand-950">Catatan siswa</h1>
                <p class="mt-2 text-sm text-slate-500">Hanya catatan yang telah diterbitkan guru untuk siswa yang tertaut ke akun Anda.</p>
            </div>
            <a href="{{ route('guardian.index') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-brand-700 hover:bg-brand-50">Kembali ke dashboard</a>
        </div>
        <div class="space-y-5">
            @forelse($data as $item)
                <article class="overflow-hidden rounded-2xl border bg-white shadow-card">
                    <div class="border-b border-slate-100 p-5">
                        <h2 class="font-bold text-brand-950">{{ $item['student']->nama }}</h2>
                        <p class="mt-1 text-sm text-slate-500">NIS {{ $item['student']->nis }}</p>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @forelse($item['notes'] as $note)
                            <div class="p-5">
                                <h3 class="font-semibold text-brand-950">{{ $note->title }}</h3>
                                <p class="mt-1 text-xs text-slate-500">{{ $note->teacher?->nama ?? 'Guru' }} · {{ $note->published_at->format('d M Y') }}</p>
                                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $note->content }}</p>
                            </div>
                        @empty
                            <p class="p-5 text-sm text-slate-500">Belum ada catatan yang diterbitkan untuk siswa ini.</p>
                        @endforelse
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border bg-white p-8 text-slate-500">Akun wali belum ditautkan dengan siswa. Hubungi sekolah.</div>
            @endforelse
        </div>
    </section>
@endsection
