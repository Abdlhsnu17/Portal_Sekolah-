@extends('layouts.admin')

@section('title', 'Absensi Saya')

@section('content')
    <div class="mx-auto max-w-4xl space-y-5">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="eyebrow text-slate-400">{{ $type === 'teacher' ? 'Guru' : 'Tenaga Kependidikan' }}</p>
                <h2 class="mt-1 text-2xl font-bold text-brand-950">Riwayat Absensi</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $person->nama }} · {{ $start->translatedFormat('F Y') }}</p>
            </div>
            <a href="{{ route('attendance.check-in') }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-brand-700 hover:bg-slate-50">Kembali ke absensi</a>
        </div>

        <form method="GET" action="{{ route('attendance.history') }}" class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-card sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label for="bulan" class="mb-1 block text-xs font-semibold text-slate-600">Pilih bulan</label>
                <input id="bulan" name="bulan" type="month" value="{{ $month }}" max="{{ now()->format('Y-m') }}" class="rounded-lg border-slate-200 text-sm focus:border-brand-500 focus:ring-brand-500">
            </div>
            <div>
                <label for="status" class="mb-1 block text-xs font-semibold text-slate-600">Status</label>
                <select id="status" name="status" class="w-full rounded-lg border-slate-200 text-sm focus:border-brand-500 focus:ring-brand-500">
                    <option value="">Semua status</option>
                    @foreach (['hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'alpa' => 'Alpa', 'dinas' => 'Dinas', 'belum_absen' => 'Belum absen'] as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="q" class="mb-1 block text-xs font-semibold text-slate-600">Cari</label>
                <input id="q" name="q" type="search" value="{{ $search }}" placeholder="Tanggal atau status" class="w-full rounded-lg border-slate-200 text-sm focus:border-brand-500 focus:ring-brand-500">
            </div>
            <div>
                <label for="urut" class="mb-1 block text-xs font-semibold text-slate-600">Urutkan</label>
                <select id="urut" name="urut" class="w-full rounded-lg border-slate-200 text-sm focus:border-brand-500 focus:ring-brand-500">
                    <option value="desc" @selected($order === 'desc')>Terbaru</option>
                    <option value="asc" @selected($order === 'asc')>Terlama</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button class="admin-primary">Terapkan</button>
                <a href="{{ route('attendance.history') }}" class="admin-chip">Reset</a>
            </div>
        </form>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            @foreach (['hadir' => ['Hadir', 'text-emerald-700'], 'izin' => ['Izin', 'text-sky-700'], 'sakit' => ['Sakit', 'text-amber-700'], 'alpa' => ['Alpa', 'text-rose-700'], 'dinas' => ['Dinas', 'text-violet-700'], 'belum_absen' => ['Belum absen', 'text-slate-700']] as $key => [$label, $textClass])
                <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-card">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                    <p class="mt-1 metric text-2xl {{ $textClass }}">{{ number_format($summary[$key]) }}</p>
                </div>
            @endforeach
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-card">
            <div class="overflow-x-auto">
                <table data-smart-table data-table-name="riwayat-absensi" class="w-full min-w-[760px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr><th class="px-4 py-3">Tanggal</th><th class="px-4 py-3">Masuk</th><th class="px-4 py-3">Pulang</th><th class="px-4 py-3">Bukti pulang</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Verifikasi wajah</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($days as $day)
                            @php
                                $item = $day['attendance'];
                            @endphp
                            <tr>
                                <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-800">{{ $day['date']->translatedFormat('l, d F Y') }}</td>
                                <td class="px-4 py-3">{{ $item?->jam_masuk ? substr($item->jam_masuk, 0, 5) : '—' }}</td>
                                <td class="px-4 py-3">{{ $item?->jam_keluar ? substr($item->jam_keluar, 0, 5) : '—' }}</td>
                                <td class="px-4 py-3 text-xs">{{ $item?->face_checkout_photo_path ? 'Tersimpan' : '—' }}</td>
                                <td class="px-4 py-3">
                                    @if ($item)
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ ucfirst($item->status) }}</span>
                                        @if (in_array($item->status, ['izin', 'sakit', 'dinas'], true))
                                            <span class="mt-1 block text-xs font-semibold {{ $item->approval_status === 'approved' ? 'text-emerald-700' : ($item->approval_status === 'rejected' ? 'text-rose-700' : 'text-amber-700') }}">{{ $item->approval_status === 'approved' ? 'Disetujui' : ($item->approval_status === 'rejected' ? 'Ditolak' : 'Menunggu persetujuan') }}</span>
                                            @if ($item->approval_note)<span class="mt-1 block text-xs text-slate-500">{{ $item->approval_note }}</span>@endif
                                        @endif
                                    @else
                                        <span class="text-slate-400">Belum absen</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs {{ $item?->face_review_status === 'verified' ? 'text-emerald-700' : ($item?->face_review_status === 'rejected' ? 'text-rose-700' : 'text-amber-700') }}">
                                    {{ match($item?->face_review_status) { 'verified' => 'Terverifikasi', 'rejected' => 'Ditolak', 'pending' => 'Menunggu', default => '—' } }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">Tidak ada riwayat yang sesuai dengan filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="smart-table-footer border-t border-slate-100 p-4">
                <p class="text-xs font-medium text-slate-500">Menampilkan {{ $days->count() }} dari {{ $start->daysInMonth }} hari pada periode ini</p>
            </div>
        </div>
    </div>
@endsection
