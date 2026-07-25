@extends('layouts.admin')

@section('title', 'Berita & Pengumuman')

@section('content')
    <div class="rounded-2xl border border-slate-200 bg-white shadow-card">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-4">
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.announcements.index') }}"
                   class="rounded-lg px-3 py-2 text-sm font-medium transition {{ ! $filterKategori ? 'bg-brand-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    Semua
                </a>
                @foreach (\App\Models\Announcement::KATEGORI as $kategori)
                    <a href="{{ route('admin.announcements.index', ['kategori' => $kategori]) }}"
                       class="rounded-lg px-3 py-2 text-sm font-medium capitalize transition {{ $filterKategori === $kategori ? 'bg-brand-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        {{ $kategori }}
                    </a>
                @endforeach
            </div>

            <div class="flex flex-wrap gap-2">
                <form method="GET" class="flex gap-2">
                    @if ($filterKategori)<input type="hidden" name="kategori" value="{{ $filterKategori }}">@endif
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari judul..."
                           class="w-48 rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-400 focus:ring-2 focus:ring-brand-100">
                    <button class="rounded-lg bg-brand-950 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-900">Cari</button>
                </form>
                <a href="{{ route('admin.announcements.create') }}"
                   class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800">
                    + Tulis Baru
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Judul</th>
                        <th class="px-4 py-3">Kategori</th>
                        <th class="px-4 py-3">Publikasi</th>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($items as $item)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-brand-950">{{ $item->judul }}</p>
                                <p class="truncate text-xs text-slate-500">{{ Str::limit($item->ringkasan_teks, 70) }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold capitalize text-brand-800">
                                    {{ $item->kategori }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $item->is_published ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $item->is_published ? 'Terbit' : 'Draf' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-600">
                                {{ optional($item->published_at ?? $item->created_at)->translatedFormat('d M Y') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-3">
                                    @if ($item->is_published)
                                        <a href="{{ route('berita.detail', $item->slug) }}" target="_blank"
                                           class="font-semibold text-slate-500 hover:text-slate-700">Lihat</a>
                                    @endif
                                    <a href="{{ route('admin.announcements.edit', $item) }}" class="font-semibold text-brand-700 hover:text-brand-900">Ubah</a>
                                    <form method="POST" action="{{ route('admin.announcements.destroy', $item) }}"
                                          onsubmit="return confirm('Hapus konten ini?')">
                                        @csrf @method('DELETE')
                                        <button class="font-semibold text-rose-600 hover:text-rose-800">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-14 text-center text-slate-500">Belum ada konten.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-100 p-4">{{ $items->links() }}</div>
    </div>
@endsection
