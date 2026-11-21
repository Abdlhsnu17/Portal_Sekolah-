@extends('layouts.admin')

@section('title', $item->exists ? 'Ubah Data Guru' : 'Tambah Guru')

@section('content')
    <a href="{{ auth()->user()->isAdmin() ? route('admin.staff.index', ['type' => 'teacher']) : route('admin.teachers.index') }}" class="text-sm font-medium text-slate-500 hover:text-brand-700">
        &larr; Kembali ke data staff
    </a>

    <form method="POST"
          action="{{ $item->exists ? route('admin.teachers.update', $item) : route('admin.teachers.store') }}"
          class="mt-4 max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-card">
        @csrf
        @if ($item->exists) @method('PUT') @endif

        @if ($item->exists)
            <div class="mb-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-brand-950">Akun akses portal</p>
                        @if ($item->user)
                            <p class="mt-1 text-xs text-slate-600">
                                {{ $item->user->email }} · {{ ucfirst($item->user->role) }} ·
                                {{ $item->user->is_active ? 'aktif' : 'nonaktif' }}
                            </p>
                        @else
                            <p class="mt-1 text-xs text-slate-600">Guru ini belum memiliki akun yang tertaut.</p>
                        @endif
                    </div>
                    @if (auth()->user()->isAdmin())
                        <a href="{{ $item->user ? route('admin.users.edit', $item->user) : route('admin.users.create', ['teacher_id' => $item->id]) }}"
                           class="admin-chip">
                            {{ $item->user ? 'Kelola akun' : 'Buat akun' }}
                        </a>
                    @endif
                </div>
            </div>
        @endif

        <div class="admin-form-section">
            <div class="admin-form-heading"><p>Identitas dan kontak</p><p>Data pokok tenaga pendidik yang tercatat di sekolah.</p></div>
            <div class="grid gap-5 sm:grid-cols-2">
            <x-form.input name="nip" label="NIP" required :value="$item->nip" />
            <x-form.input name="nama" label="Nama Lengkap" required :value="$item->nama" />
            <x-form.input name="email" label="Email" type="email" :value="$item->email" />
            <x-form.input name="telepon" label="Telepon" :value="$item->telepon" />
            <x-form.select name="jenis_kelamin" label="Jenis Kelamin" required :value="$item->jenis_kelamin"
                           :options="['L' => 'Laki-laki', 'P' => 'Perempuan']" />
            </div>
        </div>
        <div class="admin-form-section">
            <div class="admin-form-heading"><p>Penugasan</p><p>Mata pelajaran, jabatan, dan status kepegawaian saat ini.</p></div>
            <div class="grid gap-5 sm:grid-cols-2">
            <x-form.input name="mata_pelajaran" label="Mata Pelajaran" :value="$item->mata_pelajaran" />
            <x-form.input name="jabatan" label="Jabatan" :value="$item->jabatan" placeholder="Contoh: Wali Kelas X-1" />
            <x-form.input name="tanggal_bergabung" label="Tanggal Bergabung" type="date"
                          :value="optional($item->tanggal_bergabung)->format('Y-m-d')" />
            <x-form.select name="status" label="Status" required :value="$item->status"
                           :options="['aktif' => 'Aktif', 'cuti' => 'Cuti', 'nonaktif' => 'Nonaktif']" />
            </div>
        </div>
        <div class="admin-form-section">
            <div class="admin-form-heading"><p>Alamat</p><p>Alamat tempat tinggal untuk kebutuhan administrasi sekolah.</p></div>
            <div class="grid gap-5 sm:grid-cols-2">
            <x-form.textarea name="alamat" label="Alamat" :value="$item->alamat" class="sm:col-span-2" />
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <button class="rounded-lg bg-brand-700 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">
                {{ $item->exists ? 'Simpan Perubahan' : 'Tambah Guru' }}
            </button>
            <a href="{{ auth()->user()->isAdmin() ? route('admin.staff.index', ['type' => 'teacher']) : route('admin.teachers.index') }}"
               class="rounded-lg bg-white px-6 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50">
                Batal
            </a>
        </div>
    </form>
@endsection
