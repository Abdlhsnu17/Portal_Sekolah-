@extends('layouts.admin')

@section('title', $item->exists ? 'Ubah Staff Non-pengajar' : 'Tambah Staff')

@section('content')
    <a href="{{ route('admin.staff.index') }}" class="text-sm font-medium text-slate-500 hover:text-brand-700">&larr; Kembali ke data staff</a>

    <form method="POST" action="{{ $item->exists ? route('admin.staff.update', $item) : route('admin.staff.store') }}"
          class="mt-4 max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-card">
        @csrf
        @if ($item->exists) @method('PUT') @endif

        @if (! $item->exists)
            <div class="admin-form-section">
                <div class="admin-form-heading"><p>Jenis staff</p><p>Pilih jenis yang benar agar data masuk ke daftar yang sesuai.</p></div>
                <x-form.select name="employment_type" label="Jenis Staff" required
                               :value="old('employment_type', request('type'))"
                               :options="['teacher' => 'Guru / tenaga pendidik', 'staff' => 'Staff non-pengajar']"
                               placeholder="Pilih jenis staff" />
            </div>
        @endif

        @if ($item->exists)
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div>
                    <p class="text-sm font-semibold text-brand-950">Akun akses portal</p>
                    <p class="mt-1 text-xs text-slate-600">
                        {{ $item->user ? $item->user->email.' · '.ucfirst($item->user->role).' · '.($item->user->is_active ? 'aktif' : 'nonaktif') : 'Staf ini belum memiliki akun yang tertaut.' }}
                    </p>
                </div>
                <a href="{{ $item->user ? route('admin.users.edit', $item->user) : route('admin.users.create', ['staff_member_id' => $item->id]) }}" class="admin-chip">
                    {{ $item->user ? 'Kelola akun' : 'Buat akun' }}
                </a>
            </div>
        @endif

        <div class="admin-form-section">
            <div class="admin-form-heading"><p>Identitas dan kontak</p><p>Data tenaga kependidikan, terpisah dari hak akses akun.</p></div>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.input name="nip" label="NIP" required :value="$item->nip" />
                <x-form.input name="nama" label="Nama Lengkap" required :value="$item->nama" />
                <x-form.input name="email" label="Email" type="email" :value="$item->email" />
                <x-form.input name="telepon" label="Telepon" :value="$item->telepon" />
                <x-form.select name="jenis_kelamin" label="Jenis Kelamin" required :value="$item->jenis_kelamin" :options="['L' => 'Laki-laki', 'P' => 'Perempuan']" />
            </div>
        </div>

        <div class="admin-form-section">
            <div class="admin-form-heading"><p>Kepegawaian</p><p>Jabatan dan status tenaga kependidikan saat ini.</p></div>
            <div class="grid gap-5 sm:grid-cols-2">
                @if (! $item->exists)
                    <div data-teacher-field class="hidden sm:col-span-2">
                        <x-form.input name="mata_pelajaran" label="Mata Pelajaran (khusus guru)" :value="old('mata_pelajaran')" />
                    </div>
                @endif
                <x-form.input name="jabatan" label="Jabatan" :value="$item->jabatan" placeholder="Contoh: Tata Usaha" />
                <x-form.input name="tanggal_bergabung" label="Tanggal Bergabung" type="date" :value="optional($item->tanggal_bergabung)->format('Y-m-d')" />
                <x-form.select name="status" label="Status" required :value="$item->status" :options="['aktif' => 'Aktif', 'cuti' => 'Cuti', 'nonaktif' => 'Nonaktif']" />
                <x-form.textarea name="alamat" label="Alamat" :value="$item->alamat" class="sm:col-span-2" />
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <button class="admin-primary">{{ $item->exists ? 'Simpan Perubahan' : 'Tambah Staff' }}</button>
            <a href="{{ route('admin.staff.index') }}" class="rounded-lg bg-white px-6 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">Batal</a>
        </div>
    </form>

    @if (! $item->exists)
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const type = document.querySelector('[name="employment_type"]');
                const teacherField = document.querySelector('[data-teacher-field]');
                const syncType = () => teacherField?.classList.toggle('hidden', type?.value !== 'teacher');
                type?.addEventListener('change', syncType);
                syncType();
            });
        </script>
    @endif
@endsection
