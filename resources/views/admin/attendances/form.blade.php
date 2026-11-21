@extends('layouts.admin')

@section('title', $item->exists ? 'Ubah Absensi' : 'Catat Absensi')

@section('content')
    <form method="POST" action="{{ $item->exists ? route('admin.attendances.update', $item) : route('admin.attendances.store') }}"
          class="mt-4 max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-card">
        @csrf
        @if ($item->exists) @method('PUT') @endif

        <div class="admin-form-section">
            <div class="admin-form-heading"><p>Kehadiran pegawai</p><p>Satu pegawai hanya dapat memiliki satu catatan pada tanggal yang sama.</p></div>
            <div class="grid gap-5 sm:grid-cols-2">
                @php
                    $selectedPerson = $item->teacher_id ? 'teacher:'.$item->teacher_id : ($item->staff_member_id ? 'staff:'.$item->staff_member_id : null);
                @endphp
                <x-form.select name="person" label="Guru / Staf" required :value="$selectedPerson" :options="$people" placeholder="Pilih pegawai" class="sm:col-span-2" />
                <x-form.input name="tanggal" label="Tanggal" type="date" required :value="optional($item->tanggal)->format('Y-m-d')" />
                <x-form.select name="status" label="Status" required :value="$item->status"
                               :options="['hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'alpa' => 'Alpa', 'dinas' => 'Dinas']" />
                <x-form.input name="jam_masuk" label="Jam Masuk" type="time" :value="$item->jam_masuk ? substr($item->jam_masuk, 0, 5) : null" />
                <x-form.input name="jam_keluar" label="Jam Keluar" type="time" :value="$item->jam_keluar ? substr($item->jam_keluar, 0, 5) : null" />
                <p class="-mt-3 text-xs text-slate-500 sm:col-span-2">Jam masuk wajib untuk status hadir. Status selain hadir tidak menyimpan jam masuk atau keluar.</p>
                <x-form.textarea name="catatan" label="Catatan" :value="$item->catatan" maxlength="1000" class="sm:col-span-2" />
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <button class="admin-primary">{{ $item->exists ? 'Simpan Perubahan' : 'Catat Absensi' }}</button>
            <a href="{{ route('admin.attendances.index') }}" class="rounded-lg bg-white px-6 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">Batal</a>
        </div>
    </form>
@endsection
