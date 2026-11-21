@extends('layouts.admin')

@section('title', 'Absensi Pegawai')

@section('content')
    <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
        @foreach (['hadir' => 'emerald', 'izin' => 'sky', 'sakit' => 'amber', 'alpa' => 'rose', 'dinas' => 'violet'] as $status => $tone)
            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-card">
                <p class="text-xs font-semibold uppercase text-slate-500">{{ ucfirst($status) }}</p>
                <p class="mt-1 metric text-2xl text-brand-950">{{ number_format($summary[$status] ?? 0) }}</p>
            </div>
        @endforeach
        <div class="grid gap-2">
            <a href="{{ route('admin.attendances.create') }}" class="grid place-items-center rounded-lg bg-brand-700 p-3 text-sm font-bold text-white shadow-card hover:bg-brand-800">+ Catat absensi</a>
            <a href="{{ route('admin.attendances.export', request()->query()) }}" class="grid place-items-center rounded-lg border border-slate-200 bg-white p-2 text-xs font-bold text-brand-700 shadow-card hover:bg-slate-50">Ekspor CSV</a>
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-slate-200 bg-white shadow-card">
        <form method="GET" class="grid gap-3 border-b border-slate-100 p-4 sm:grid-cols-2 lg:grid-cols-6">
            <x-form.input name="date_from" label="Dari tanggal" type="date" :value="$dateFrom" />
            <x-form.input name="date_to" label="Sampai tanggal" type="date" :value="$dateTo" />
            <x-form.select name="type" label="Jenis" :value="request('type')" :options="['teacher' => 'Guru', 'staff' => 'Staf']" placeholder="Semua pegawai" />
            <x-form.select name="status" label="Status" :value="request('status')" :options="array_combine($statuses, array_map('ucfirst', $statuses))" placeholder="Semua status" />
            <x-form.input name="q" label="Cari pegawai" :value="request('q')" placeholder="Nama atau NIP" />
            <div class="flex items-end gap-2">
                <button class="admin-primary">Terapkan</button>
                <a href="{{ route('admin.attendances.index') }}" class="admin-chip">Reset</a>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px] text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Tanggal</th><th class="px-4 py-3">Pegawai</th><th class="px-4 py-3">Jenis</th>
                        <th class="px-4 py-3">Masuk</th><th class="px-4 py-3">Keluar</th><th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Catatan</th><th class="sticky right-0 bg-slate-50 px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($items as $item)
                        <tr class="group hover:bg-slate-50">
                            <td class="px-4 py-3 whitespace-nowrap">{{ $item->tanggal->format('d M Y') }}</td>
                            <td class="px-4 py-3 font-semibold text-brand-950">
                                {{ $item->person_name }}
                                @if ($item->face_photo_path)
                                    <a href="{{ route('admin.attendances.face', $item) }}" target="_blank" rel="noopener"
                                       class="mt-1 block text-xs font-semibold text-sky-700 hover:text-sky-900">Lihat bukti wajah</a>
                                    <span class="mt-1 inline-block rounded-full px-2 py-0.5 text-[10px] font-bold {{ $item->face_review_status === 'verified' ? 'bg-emerald-100 text-emerald-700' : ($item->face_review_status === 'rejected' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">
                                        {{ $item->face_review_status === 'verified' ? 'Wajah terverifikasi' : ($item->face_review_status === 'rejected' ? 'Foto ditolak' : 'Menunggu verifikasi') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $item->person_type }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $item->jam_masuk ? substr($item->jam_masuk, 0, 5) : '—' }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $item->jam_keluar ? substr($item->jam_keluar, 0, 5) : '—' }}</td>
                            <td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold capitalize text-slate-700">{{ $item->status }}</span></td>
                            <td class="max-w-[14rem] truncate px-4 py-3 text-xs text-slate-500" title="{{ $item->catatan }}">{{ $item->catatan ?: '—' }}</td>
                            <td class="sticky right-0 bg-white px-4 py-3 group-hover:bg-slate-50">
                                @if ($item->face_photo_path)
                                    <form method="POST" action="{{ route('admin.attendances.face-review', $item) }}" class="mb-2 flex items-center justify-end gap-1">
                                        @csrf @method('PUT')
                                        <input type="text" name="face_review_note" maxlength="500" placeholder="Alasan jika ditolak"
                                               class="w-36 rounded border border-slate-200 px-2 py-1 text-xs">
                                        <button name="decision" value="verified" class="text-xs font-semibold text-emerald-700">Setujui</button>
                                        <button name="decision" value="rejected" class="text-xs font-semibold text-rose-600">Tolak</button>
                                    </form>
                                @endif
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.attendances.edit', $item) }}" aria-label="Ubah catatan absensi" title="Ubah" class="inline-flex size-9 items-center justify-center rounded-lg text-brand-700 transition hover:bg-brand-50 hover:text-brand-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                                        <x-icon name="pencil" class="size-5" />
                                    </a>
                                    <form method="POST" action="{{ route('admin.attendances.destroy', $item) }}" onsubmit="return confirm('Hapus catatan absensi ini?')">
                                        @csrf @method('DELETE')
                                        <button aria-label="Hapus catatan absensi" title="Hapus" class="inline-flex size-9 items-center justify-center rounded-lg text-rose-600 transition hover:bg-rose-50 hover:text-rose-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500">
                                            <x-icon name="trash" class="size-5" />
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><x-empty-state icon="clock" title="Belum ada absensi" description="Catat kehadiran guru atau staf untuk periode ini." :href="route('admin.attendances.create')" action="Catat absensi" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="smart-table-footer border-t border-slate-100 p-4">
            <p class="text-xs font-medium text-slate-500">Menampilkan {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }} dari {{ $items->total() }} catatan</p>
            {{ $items->links() }}
        </div>
    </div>
@endsection
