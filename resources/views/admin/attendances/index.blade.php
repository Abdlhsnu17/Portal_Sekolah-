@extends('layouts.admin')

@section('title', 'Absensi Pegawai')

@section('content')
    @if (auth()->user()->isAdmin() && ! auth()->user()->staffMember && ! auth()->user()->teacher)
        <section class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-brand-200 bg-brand-50 p-4">
            <div>
                <h2 class="font-bold text-brand-950">Absensi pribadi belum aktif</h2>
                <p class="mt-1 text-sm text-brand-800">Lengkapi identitas kepegawaian agar akun administrator ini dapat absen masuk dan pulang.</p>
            </div>
            <a href="{{ route('admin.staff.activate-attendance.create') }}" class="admin-primary">Aktifkan absensi saya</a>
        </section>
    @endif

    @if ($requestQueue->isNotEmpty())
        <section class="mb-4 rounded-2xl border border-amber-200 bg-white shadow-card">
            <div class="border-b border-amber-100 bg-amber-50 px-4 py-3">
                <h2 class="font-bold text-amber-950">Pengajuan menunggu persetujuan <span class="ml-1 rounded-full bg-amber-200 px-2 py-0.5 text-xs">{{ $requestQueue->count() }}</span></h2>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach ($requestQueue as $requestItem)
                    <div class="flex flex-wrap items-center justify-between gap-4 p-4">
                        <div class="min-w-48 flex-1">
                            <p class="font-semibold text-brand-950">{{ $requestItem->person_name }} · {{ ucfirst($requestItem->status) }} · {{ $requestItem->tanggal->translatedFormat('d F Y') }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ $requestItem->catatan }}</p>
                            @if ($requestItem->request_evidence_path)
                                <a href="{{ route('admin.attendances.request-evidence', $requestItem) }}" target="_blank" rel="noopener" class="mt-1 inline-block text-xs font-semibold text-sky-700">Lihat bukti pendukung</a>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('admin.attendances.request-review', $requestItem) }}" class="flex flex-wrap items-center gap-2">
                            @csrf @method('PUT')
                            <input type="text" name="approval_note" maxlength="500" placeholder="Alasan jika ditolak" class="w-48 rounded-lg border-slate-200 px-3 py-2 text-xs">
                            <button name="decision" value="approved" class="rounded-lg bg-emerald-700 px-3 py-2 text-xs font-bold text-white">Setujui</button>
                            <button name="decision" value="rejected" class="rounded-lg border border-rose-200 px-3 py-2 text-xs font-bold text-rose-700">Tolak</button>
                        </form>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

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
                                @if ($item->face_checkout_photo_path)
                                    <a href="{{ route('admin.attendances.face', ['attendance' => $item, 'jenis' => 'pulang']) }}" target="_blank" rel="noopener"
                                       class="mt-1 block text-xs font-semibold text-violet-700 hover:text-violet-900">Lihat bukti wajah pulang</a>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $item->person_type }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $item->jam_masuk ? substr($item->jam_masuk, 0, 5) : '—' }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $item->jam_keluar ? substr($item->jam_keluar, 0, 5) : '—' }}</td>
                            <td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold capitalize text-slate-700">{{ $item->status }}</span></td>
                            <td class="max-w-[14rem] px-4 py-3 text-xs text-slate-500">
                                <span class="block truncate" title="{{ $item->catatan }}">{{ $item->catatan ?: '—' }}</span>
                                @if (in_array($item->status, ['izin', 'sakit', 'dinas'], true))
                                    <span class="mt-1 inline-block rounded-full px-2 py-0.5 text-[10px] font-bold {{ $item->approval_status === 'approved' ? 'bg-emerald-100 text-emerald-700' : ($item->approval_status === 'rejected' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">{{ $item->approval_status === 'approved' ? 'Disetujui' : ($item->approval_status === 'rejected' ? 'Ditolak' : 'Menunggu persetujuan') }}</span>
                                    @if ($item->request_evidence_path)
                                        <a href="{{ route('admin.attendances.request-evidence', $item) }}" target="_blank" rel="noopener" class="mt-1 block font-semibold text-sky-700">Lihat bukti pengajuan</a>
                                    @endif
                                @endif
                            </td>
                            <td class="sticky right-0 bg-white px-4 py-3 group-hover:bg-slate-50">
                                @if ($item->approval_status === 'pending' && in_array($item->status, ['izin', 'sakit', 'dinas'], true))
                                    <form method="POST" action="{{ route('admin.attendances.request-review', $item) }}" class="mb-2 flex items-center justify-end gap-1">
                                        @csrf @method('PUT')
                                        <input type="text" name="approval_note" maxlength="500" placeholder="Alasan jika ditolak" class="w-36 rounded border border-slate-200 px-2 py-1 text-xs">
                                        <button name="decision" value="approved" class="text-xs font-semibold text-emerald-700">Setujui</button>
                                        <button name="decision" value="rejected" class="text-xs font-semibold text-rose-600">Tolak</button>
                                    </form>
                                @endif
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
                                    <form method="POST" action="{{ route('admin.attendances.destroy', $item) }}" data-confirm-delete>
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
