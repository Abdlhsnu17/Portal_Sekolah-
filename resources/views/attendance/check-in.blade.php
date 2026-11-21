@extends('layouts.admin')

@section('title', 'Absen Wajah Saya')

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card">
            <div class="flex items-center justify-between gap-4 border-b border-slate-100 pb-5">
                <div>
                    <p class="eyebrow text-slate-400">{{ $type === 'teacher' ? 'Guru' : 'Tenaga Kependidikan' }}</p>
                    <h2 class="mt-1 text-xl font-bold text-brand-950">{{ $person->nama }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $person->nip }} · {{ now()->translatedFormat('l, d F Y') }}</p>
                </div>
                <span class="rounded-full px-3 py-1.5 text-xs font-bold {{ $attendance ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                    {{ $attendance ? ($attendance->face_review_status === 'rejected' ? 'Perlu foto ulang' : 'Sudah absen') : 'Belum absen' }}
                </span>
            </div>

            @if ($attendance && $attendance->face_review_status !== 'rejected')
                <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-5 text-center">
                    <p class="text-sm font-semibold text-emerald-800">Kehadiran hari ini sudah tercatat.</p>
                    <p class="mt-2 metric text-3xl text-emerald-950">{{ $attendance->jam_masuk ? substr($attendance->jam_masuk, 0, 5) : '—' }}</p>
                    <p class="mt-1 text-xs text-emerald-700">Status {{ ucfirst($attendance->status) }}</p>
                    <p class="mt-2 text-xs font-semibold {{ $attendance->face_review_status === 'verified' ? 'text-emerald-800' : 'text-amber-700' }}">
                        Bukti wajah: {{ $attendance->face_review_status === 'verified' ? 'Terverifikasi' : 'Menunggu verifikasi admin' }}
                    </p>
                </div>
            @else
                @if ($attendance?->face_review_status === 'rejected')
                    <div class="mt-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
                        <p class="font-bold">Foto sebelumnya ditolak. Silakan ambil foto ulang.</p>
                        <p class="mt-1">{{ $attendance->face_review_note }}</p>
                    </div>
                @endif
                <form method="POST" action="{{ route('attendance.store') }}" enctype="multipart/form-data"
                      class="mt-6 space-y-5" x-data="{ preview: null }">
                    @csrf

                    <div>
                        <label for="face_photo" class="block text-sm font-semibold text-slate-700">Foto wajah langsung</label>
                        <input id="face_photo" name="face_photo" type="file" required accept="image/jpeg,image/png" capture="user"
                               @change="preview = URL.createObjectURL($event.target.files[0])"
                               class="mt-2 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:font-semibold file:text-brand-700">
                        <p class="mt-2 text-xs text-slate-500">Gunakan kamera depan dan pastikan wajah terlihat jelas. Maksimal 2 MB.</p>
                        @error('face_photo')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <img x-show="preview" x-cloak :src="preview" alt="Pratinjau foto wajah"
                         class="mx-auto max-h-80 rounded-xl border border-slate-200 object-contain shadow-sm">

                    <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                        <input type="checkbox" name="consent" value="1" required class="mt-0.5 size-4 rounded border-slate-300 text-brand-700 focus:ring-brand-500">
                        <span>Saya menyetujui foto ini disimpan secara privat sebagai bukti kehadiran dan dapat diperiksa administrator sekolah. Foto tidak digunakan untuk pengenalan wajah otomatis.</span>
                    </label>
                    @error('consent')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror

                    <button class="admin-primary w-full justify-center py-3">Catat kehadiran sekarang</button>
                </form>
            @endif
        </div>
    </div>
@endsection
