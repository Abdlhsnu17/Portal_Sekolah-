@extends('layouts.admin')

@section('title', 'Absensi Saya')

@section('content')
    <div x-data="{ tab: ['absen', 'riwayat', 'pengajuan'].includes(location.hash.slice(1)) ? location.hash.slice(1) : 'absen', select(value) { this.tab = value; history.replaceState(null, '', `#${value}`) } }"
         class="mx-auto grid max-w-5xl items-start gap-4 lg:grid-cols-[13rem_minmax(0,1fr)]">
        <nav class="flex gap-2 overflow-x-auto rounded-xl border border-slate-200 bg-white p-2 shadow-card lg:sticky lg:top-28 lg:flex-col lg:overflow-visible" role="tablist" aria-label="Bagian absensi">
            <button type="button" role="tab" @click="select('absen')" :aria-selected="tab === 'absen'"
                    :class="tab === 'absen' ? 'bg-brand-50 text-brand-800 ring-1 ring-brand-100' : 'text-slate-600 hover:bg-slate-50 hover:text-brand-800'"
                    class="flex shrink-0 items-center gap-2.5 rounded-lg px-3 py-2.5 text-left text-sm font-semibold transition lg:w-full">
                <x-icon name="camera" class="size-4 shrink-0" /><span>Absensi hari ini</span>
            </button>
            <button type="button" role="tab" @click="select('riwayat')" :aria-selected="tab === 'riwayat'"
                    :class="tab === 'riwayat' ? 'bg-brand-50 text-brand-800 ring-1 ring-brand-100' : 'text-slate-600 hover:bg-slate-50 hover:text-brand-800'"
                    class="flex shrink-0 items-center gap-2.5 rounded-lg px-3 py-2.5 text-left text-sm font-semibold transition lg:w-full">
                <x-icon name="clock" class="size-4 shrink-0" /><span>Riwayat bulanan</span>
            </button>
            <button type="button" role="tab" @click="select('pengajuan')" :aria-selected="tab === 'pengajuan'"
                    :class="tab === 'pengajuan' ? 'bg-brand-50 text-brand-800 ring-1 ring-brand-100' : 'text-slate-600 hover:bg-slate-50 hover:text-brand-800'"
                    class="flex shrink-0 items-center gap-2.5 rounded-lg px-3 py-2.5 text-left text-sm font-semibold transition lg:w-full">
                <x-icon name="clipboard" class="size-4 shrink-0" /><span>Ajukan Izin/Sakit/Dinas</span>
            </button>
        </nav>

        <div class="min-w-0">
        <section x-show="tab === 'absen'" x-cloak x-transition.opacity role="tabpanel">
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

            @if ($attendance && $attendance->status !== 'hadir' && $attendance->face_review_status !== 'rejected')
                <div class="mt-6 rounded-2xl border border-sky-200 bg-sky-50 p-5">
                    <p class="font-bold text-sky-950">Status hari ini: {{ ucfirst($attendance->status) }}</p>
                    @if (in_array($attendance->status, ['izin', 'sakit', 'dinas'], true))
                        <p class="mt-1 text-sm font-semibold {{ $attendance->approval_status === 'approved' ? 'text-emerald-700' : ($attendance->approval_status === 'rejected' ? 'text-rose-700' : 'text-amber-700') }}">{{ $attendance->approval_status === 'approved' ? 'Pengajuan disetujui' : ($attendance->approval_status === 'rejected' ? 'Pengajuan ditolak' : 'Menunggu persetujuan admin') }}</p>
                        @if ($attendance->catatan)<p class="mt-3 text-sm text-slate-700">{{ $attendance->catatan }}</p>@endif
                        @if ($attendance->approval_note)<p class="mt-2 text-sm text-rose-700">Catatan admin: {{ $attendance->approval_note }}</p>@endif
                    @elseif ($attendance->status === 'alpa')
                        <p class="mt-1 text-sm text-slate-700">Status Alpa dicatat administrator.</p>
                    @endif
                </div>
            @elseif ($attendance && $attendance->face_review_status !== 'rejected')
                <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50/60 p-4 sm:p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-700">
                                <x-icon name="check-circle" class="size-5" />
                            </span>
                            <div>
                                <p class="text-sm font-bold text-emerald-950">Kehadiran hari ini sudah tercatat</p>
                                <p class="mt-0.5 text-xs text-emerald-700">Status: {{ ucfirst($attendance->status) }}</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-bold {{ $attendance->face_review_status === 'verified' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                            <x-icon name="{{ $attendance->face_review_status === 'verified' ? 'shield-check' : 'clock' }}" class="size-3.5" />
                            {{ $attendance->face_review_status === 'verified' ? 'Wajah terverifikasi' : 'Menunggu verifikasi' }}
                        </span>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-xl border border-emerald-100 bg-white px-5 py-4">
                            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-emerald-700">
                                <x-icon name="clock" class="size-4" />
                                Jam masuk
                            </div>
                            <p class="mt-2 metric text-4xl leading-none text-emerald-950">{{ $attendance->jam_masuk ? substr($attendance->jam_masuk, 0, 5) : '—' }}</p>
                        </div>

                    @if ($attendance->status === 'hadir')
                        @if ($attendance->jam_keluar)
                            <div class="rounded-xl border border-emerald-100 bg-white px-5 py-4">
                                <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-emerald-700">
                                    <x-icon name="clock" class="size-4" />
                                    Jam pulang
                                </div>
                                <p class="mt-2 metric text-4xl leading-none text-emerald-950">{{ substr($attendance->jam_keluar, 0, 5) }}</p>
                                @if ($attendance->face_checkout_photo_path)
                                    <p class="mt-2 text-xs font-semibold text-emerald-700">Bukti foto pulang tersimpan</p>
                                @endif
                            </div>
                        @else
                            <form method="POST" action="{{ route('attendance.check-out') }}" enctype="multipart/form-data" class="flex flex-col justify-between rounded-xl border border-brand-100 bg-white px-5 py-4" x-data="{ checkoutPreview: null }">
                                @csrf
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Jam pulang</p>
                                    <p class="mt-2 text-sm leading-5 text-slate-600">Selesai bertugas? Ambil foto wajah sebagai bukti, lalu catat waktu pulang.</p>
                                    <label for="checkout_face_photo" class="mt-3 block text-xs font-semibold text-slate-700">Foto wajah saat pulang</label>
                                    <input id="checkout_face_photo" name="face_photo" type="file" required accept="image/jpeg,image/png" capture="user" @change="checkoutPreview = URL.createObjectURL($event.target.files[0])" class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-2 py-2 text-xs file:mr-2 file:rounded-md file:border-0 file:bg-brand-50 file:px-2 file:py-1.5 file:font-semibold file:text-brand-700">
                                    <p class="mt-1 text-[11px] text-slate-500">JPG/PNG, maksimal 2 MB.</p>
                                    @error('face_photo')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                                    <img x-show="checkoutPreview" x-cloak :src="checkoutPreview" alt="Pratinjau foto wajah saat pulang" class="mt-3 max-h-36 rounded-lg border border-slate-200 object-contain">
                                    <label class="mt-3 flex items-start gap-2 text-xs text-slate-600">
                                        <input type="checkbox" name="consent" value="1" required class="mt-0.5 size-4 rounded border-slate-300 text-brand-700 focus:ring-brand-500">
                                        <span>Saya menyetujui foto ini disimpan secara privat sebagai bukti kehadiran dan dapat diperiksa administrator sekolah. Foto tidak digunakan untuk pengenalan wajah otomatis.</span>
                                    </label>
                                    @error('consent')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                                </div>
                                <button class="admin-primary mt-4 w-full justify-center py-2.5">Kirim foto & absen pulang</button>
                            </form>
                        @endif
                    @endif
                    </div>
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

            @if ($attendance)
                <details class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <summary class="cursor-pointer text-sm font-semibold text-brand-700">Koreksi jam masuk hari ini</summary>
                    <p class="mt-2 text-xs text-slate-500">Perubahan akan memperbarui catatan hari ini dan tersimpan di audit akun.</p>
                    <form method="POST" action="{{ route('attendance.correct') }}" class="mt-4 space-y-3">
                        @csrf
                        @method('PUT')
                        <div>
                            <label for="jam_masuk_koreksi" class="block text-sm font-semibold text-slate-700">Jam masuk yang benar</label>
                            <input id="jam_masuk_koreksi" name="jam_masuk" type="time" required value="{{ old('jam_masuk', $attendance->jam_masuk ? substr($attendance->jam_masuk, 0, 5) : '') }}" class="mt-1 block w-full rounded-lg border-slate-200 text-sm focus:border-brand-500 focus:ring-brand-500">
                            @error('jam_masuk')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="alasan_koreksi" class="block text-sm font-semibold text-slate-700">Alasan koreksi</label>
                            <textarea id="alasan_koreksi" name="alasan_koreksi" required minlength="5" maxlength="500" rows="2" class="mt-1 block w-full rounded-lg border-slate-200 text-sm focus:border-brand-500 focus:ring-brand-500">{{ old('alasan_koreksi') }}</textarea>
                            @error('alasan_koreksi')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <button class="rounded-lg border border-brand-200 bg-white px-4 py-2 text-sm font-semibold text-brand-700 hover:bg-brand-50">Simpan koreksi</button>
                    </form>
                </details>
            @endif

            @if ($upcomingRequests->isNotEmpty())
                <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50/60 p-4">
                    <h3 class="text-sm font-bold text-amber-950">Pengajuan mendatang</h3>
                    <div class="mt-2 divide-y divide-amber-100">
                        @foreach ($upcomingRequests as $upcoming)
                            <div class="flex flex-wrap justify-between gap-2 py-2 text-sm">
                                <span class="font-semibold text-slate-700">{{ $upcoming->tanggal->translatedFormat('d F Y') }} · {{ ucfirst($upcoming->status) }}</span>
                                <span class="font-semibold {{ $upcoming->approval_status === 'approved' ? 'text-emerald-700' : ($upcoming->approval_status === 'rejected' ? 'text-rose-700' : 'text-amber-700') }}">{{ $upcoming->approval_status === 'approved' ? 'Disetujui' : ($upcoming->approval_status === 'rejected' ? 'Ditolak' : 'Menunggu persetujuan') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
        </section>

        <section x-show="tab === 'riwayat'" x-cloak x-transition.opacity role="tabpanel" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card">
            <div class="flex flex-wrap items-end justify-between gap-3 border-b border-slate-100 p-5">
                <div>
                    <p class="eyebrow text-slate-400">{{ $type === 'teacher' ? 'Guru' : 'Tenaga Kependidikan' }}</p>
                    <h2 class="mt-1 text-xl font-bold text-brand-950">Riwayat Absensi Bulanan</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $person->nama }} · {{ $start->translatedFormat('F Y') }}</p>
                </div>
                <a href="{{ route('attendance.history') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">Pilih bulan lain →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr><th class="px-4 py-3">Tanggal</th><th class="px-4 py-3">Masuk</th><th class="px-4 py-3">Pulang</th><th class="px-4 py-3">Bukti pulang</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Verifikasi wajah</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($days as $day)
                            @php
                                $record = $day['attendance'];
                            @endphp
                            <tr>
                                <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-800">{{ $day['date']->translatedFormat('l, d F Y') }}</td>
                                <td class="px-4 py-3">{{ $record?->jam_masuk ? substr($record->jam_masuk, 0, 5) : '—' }}</td>
                                <td class="px-4 py-3">{{ $record?->jam_keluar ? substr($record->jam_keluar, 0, 5) : '—' }}</td>
                                <td class="px-4 py-3 text-xs">{{ $record?->face_checkout_photo_path ? 'Tersimpan' : '—' }}</td>
                                <td class="px-4 py-3">
                                    {{ $record ? ucfirst($record->status) : 'Belum absen' }}
                                    @if ($record && in_array($record->status, ['izin', 'sakit', 'dinas'], true))
                                        <span class="mt-1 block text-xs font-semibold {{ $record->approval_status === 'approved' ? 'text-emerald-700' : ($record->approval_status === 'rejected' ? 'text-rose-700' : 'text-amber-700') }}">{{ $record->approval_status === 'approved' ? 'Disetujui' : ($record->approval_status === 'rejected' ? 'Ditolak' : 'Menunggu persetujuan') }}</span>
                                        @if ($record->approval_note)<span class="mt-1 block text-xs text-slate-500">{{ $record->approval_note }}</span>@endif
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs">{{ match($record?->face_review_status) { 'verified' => 'Terverifikasi', 'rejected' => 'Ditolak', 'pending' => 'Menunggu', default => '—' } }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section x-show="tab === 'pengajuan'" x-cloak x-transition.opacity role="tabpanel" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card">
            <div class="border-b border-slate-100 pb-4">
                <p class="eyebrow text-slate-400">{{ $type === 'teacher' ? 'Guru' : 'Tenaga Kependidikan' }}</p>
                <h2 class="mt-1 text-xl font-bold text-brand-950">Ajukan Izin, Sakit, atau Dinas</h2>
                <p class="mt-1 text-sm text-slate-600">Pengajuan untuk hari ini atau tanggal mendatang akan ditinjau admin. Status Alpa hanya dicatat admin.</p>
            </div>
            <form method="POST" action="{{ route('attendance.request') }}" enctype="multipart/form-data" class="mt-5 grid gap-4 sm:grid-cols-2">
                @csrf
                <div>
                    <label for="request_date" class="block text-sm font-semibold text-slate-700">Tanggal</label>
                    <input id="request_date" name="tanggal" type="date" required min="{{ now()->toDateString() }}" value="{{ old('tanggal', $attendance ? now()->addDay()->toDateString() : now()->toDateString()) }}" class="mt-1 block w-full rounded-lg border-slate-200 text-sm focus:border-brand-500 focus:ring-brand-500">
                    @error('tanggal')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="request_status" class="block text-sm font-semibold text-slate-700">Jenis pengajuan</label>
                    <select id="request_status" name="status" required class="mt-1 block w-full rounded-lg border-slate-200 text-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="izin" @selected(old('status') === 'izin')>Izin</option>
                        <option value="sakit" @selected(old('status') === 'sakit')>Sakit</option>
                        <option value="dinas" @selected(old('status') === 'dinas')>Dinas</option>
                    </select>
                    @error('status')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="request_note" class="block text-sm font-semibold text-slate-700">Alasan / keterangan</label>
                    <textarea id="request_note" name="catatan" required minlength="5" maxlength="1000" rows="4" class="mt-1 block w-full rounded-lg border-slate-200 text-sm focus:border-brand-500 focus:ring-brand-500">{{ old('catatan') }}</textarea>
                    @error('catatan')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="request_evidence" class="block text-sm font-semibold text-slate-700">Bukti pendukung <span class="font-normal text-slate-500">(opsional, PDF/JPG/PNG maks. 5 MB)</span></label>
                    <input id="request_evidence" name="bukti" type="file" accept="application/pdf,image/jpeg,image/png" class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:font-semibold file:text-brand-700">
                    <p class="mt-1 text-xs text-slate-500">Bukti disimpan privat dan hanya dapat diakses administrator.</p>
                    @error('bukti')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>
                <button class="admin-primary sm:col-span-2 sm:justify-self-start">Kirim pengajuan</button>
            </form>
        </section>
        </div>
    </div>
@endsection
