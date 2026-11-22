@extends('layouts.admin')
@section('title','Perpustakaan')
@section('content')
<div x-data="{tab:'buku'}" class="space-y-5"><div class="flex gap-2 border-b"><button @click="tab='buku'" class="px-4 py-3 text-sm font-semibold">Katalog Buku</button><button @click="tab='pinjam'" class="px-4 py-3 text-sm font-semibold">Peminjaman</button></div><section x-show="tab==='buku'" class="grid gap-5 lg:grid-cols-3"><div class="rounded-2xl border bg-white p-5 shadow-card"><h2 class="font-bold text-brand-950">Tambah buku</h2><form method="POST" action="{{route('admin.library.books.store')}}" class="mt-4 space-y-3">@csrf <x-form.input name="code" label="Kode" required/><x-form.input name="title" label="Judul" required/><x-form.input name="author" label="Penulis"/><x-form.input name="publisher" label="Penerbit"/><x-form.input name="stock" type="number" min="0" label="Stok" required/><button class="w-full rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white">Simpan buku</button></form></div><div class="overflow-hidden rounded-2xl border bg-white shadow-card lg:col-span-2"><div class="overflow-x-auto"><table data-smart-table data-table-name="katalog-buku" data-smart-table-include-last-column class="w-full text-sm"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Buku</th><th class="px-4 py-3">Penulis</th><th class="px-4 py-3">Stok tersedia</th></tr></thead><tbody class="divide-y">@forelse($books as $book)<tr><td class="px-4 py-3"><b>{{$book->title}}</b><span class="block text-xs text-slate-500">{{$book->code}}</span></td><td class="px-4 py-3">{{$book->author ?: '—'}}</td><td class="px-4 py-3">{{max(0,$book->stock-$book->borrowed_count)}} / {{$book->stock}}</td></tr>@empty<tr><td colspan="3" class="p-5 text-slate-500">Belum ada buku.</td></tr>@endforelse</tbody></table></div></div></section><section x-show="tab==='pinjam'" x-cloak class="grid gap-5 lg:grid-cols-3"><div class="rounded-2xl border bg-white p-5 shadow-card"><h2 class="font-bold text-brand-950">Catat peminjaman</h2><form method="POST" action="{{route('admin.library.loans.store')}}" class="mt-4 space-y-3">@csrf <x-form.select name="library_book_id" label="Buku" required :options="$books->mapWithKeys(fn($b)=>[$b->id=>$b->code.' — '.$b->title])->all()"/><x-form.select name="student_id" label="Siswa" required :options="$students->mapWithKeys(fn($s)=>[$s->id=>$s->nis.' — '.$s->nama])->all()"/><x-form.input name="borrowed_on" type="date" label="Tanggal pinjam" required/><x-form.input name="due_on" type="date" label="Jatuh tempo"/><button class="w-full rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white">Simpan</button></form></div><div class="overflow-hidden rounded-2xl border bg-white shadow-card lg:col-span-2"><div class="overflow-x-auto"><table data-smart-table data-table-name="peminjaman-buku" class="w-full text-sm"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Siswa/Buku</th><th class="px-4 py-3">Pinjam</th><th class="px-4 py-3">Status</th></tr></thead><tbody class="divide-y">@forelse($loans as $loan)<tr><td class="px-4 py-3"><b>{{$loan->student->nama}}</b><span class="block text-xs text-slate-500">{{$loan->book->title}}</span></td><td class="px-4 py-3">{{$loan->borrowed_on->format('d/m/Y')}}</td><td class="px-4 py-3">@if($loan->returned_on)Kembali {{$loan->returned_on->format('d/m/Y')}}@else<form method="POST" action="{{route('admin.library.loans.return',$loan)}}" class="flex gap-1">@csrf @method('PUT')<input name="returned_on" type="date" required class="rounded border text-xs"><input name="fine" type="number" min="0" step="0.01" placeholder="Denda" class="w-20 rounded border text-xs"><button class="text-xs font-semibold text-brand-700">Kembalikan</button></form>@endif</td></tr>@empty<tr><td colspan="3" class="p-5 text-slate-500">Belum ada peminjaman.</td></tr>@endforelse</tbody></table></div></div></section></div>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.nextTick(() => {
            const library = document.querySelector('[x-data*="tab:\'buku\'"]');
            if (!library) return;

            const state = Alpine.$data(library);
            const tabs = ['buku', 'pinjam'];
            const select = (tab) => {
                if (tabs.includes(tab)) state.tab = tab;
            };

            select(location.hash.slice(1));
            window.addEventListener('hashchange', () => select(location.hash.slice(1)));
            library.querySelectorAll(':scope > div > button').forEach((button, index) => {
                button.addEventListener('click', () => history.replaceState(null, '', `#${tabs[index]}`));
            });
        });
    });
</script>
@endsection
