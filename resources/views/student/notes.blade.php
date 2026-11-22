@extends('layouts.admin')

@section('title', 'Catatan Saya')

@section('content')
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card">
        <div class="border-b border-slate-100 p-5">
            <h2 class="font-bold text-brand-950">Catatan untuk {{ $student->nama }}</h2>
            <p class="mt-1 text-sm text-slate-500">Hanya catatan yang sudah diterbitkan guru yang ditampilkan.</p>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse($notes as $note)
                <article class="p-5">
                    <h3 class="font-semibold text-brand-950">{{ $note->title }}</h3>
                    <p class="mt-1 text-xs text-slate-500">{{ $note->teacher?->nama ?? 'Guru' }} · {{ $note->published_at->format('d M Y') }}</p>
                    <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $note->content }}</p>
                </article>
            @empty
                <div class="p-5"><x-empty-state icon="clipboard" title="Belum ada catatan" description="Catatan yang diterbitkan guru akan tampil di sini." /></div>
            @endforelse
        </div>
    </section>
@endsection
