@extends('layouts.admin')

@section('title', $item->exists ? 'Ubah Pengguna' : 'Tambah Pengguna')

@section('content')
    <form method="POST"
          action="{{ $item->exists ? route('admin.users.update', $item) : route('admin.users.store') }}"
          class="mt-4 max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-card">
        @csrf
        @if ($item->exists) @method('PUT') @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.input name="name" label="Nama Lengkap" required :value="$item->name" class="sm:col-span-2" />
            <x-form.input name="email" label="Email" type="email" required :value="$item->email" />
            <x-form.input name="telepon" label="Telepon" :value="$item->telepon" />
            <x-form.select name="role" label="Peran" required :value="$item->role"
                           :options="['admin' => 'Administrator', 'staf' => 'Staf', 'guru' => 'Guru', 'wali' => 'Wali/Orang Tua', 'siswa' => 'Siswa']" />
            <x-form.select name="teacher_id" label="Data Guru Terkait"
                           :value="$item->teacher?->id ?? $selectedTeacherId" :options="$teachers"
                           placeholder="Tidak ditautkan" />
            <x-form.select name="staff_member_id" label="Data Staf Terkait"
                           :value="$item->staffMember?->id ?? $selectedStaffId" :options="$staffMembers"
                           placeholder="Tidak ditautkan" />
            <x-form.select name="student_id" label="Data Siswa Terkait"
                           :value="$item->student?->id ?? $selectedStudentId" :options="$students"
                           placeholder="Tidak ditautkan" />
            <p class="-mt-3 text-xs leading-relaxed text-slate-500 sm:col-span-2">
                Akun siswa wajib ditautkan ke satu data siswa. Satu data siswa hanya dapat memiliki satu akun.
            </p>

            <div class="flex items-end pb-2">
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true))
                           class="size-4 rounded border-slate-300 text-brand-700 focus:ring-brand-500">
                    Akun aktif
                </label>
            </div>

            <div class="sm:col-span-2" x-data="{ tampilkanSandi: false }">
                <label for="password" class="block text-sm font-medium text-slate-700">
                    Kata Sandi @unless ($item->exists)<span class="text-rose-500">*</span>@endunless
                </label>
                <div class="relative mt-1.5">
                    <input :type="tampilkanSandi ? 'text' : 'password'" name="password" id="password" @required(! $item->exists)
                           class="w-full rounded-lg border border-slate-200 px-3 py-2 pr-12 text-sm outline-none focus:border-brand-400 focus:ring-2 focus:ring-brand-100">
                    <button type="button" @click="tampilkanSandi = !tampilkanSandi" :aria-label="tampilkanSandi ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'" :aria-pressed="tampilkanSandi" class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-500 hover:text-brand-700 focus-visible:ring-2 focus-visible:ring-brand-500" autocomplete="off">
                        <svg x-show="!tampilkanSandi" class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.5-6.75 9.75-6.75S21.75 12 21.75 12 18.25 18.75 12 18.75 2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg x-show="tampilkanSandi" x-cloak class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18M10.58 10.59a2 2 0 0 0 2.83 2.83M9.88 5.24A10.9 10.9 0 0 1 12 5.04c6.25 0 9.75 6.96 9.75 6.96s-1 2-3.1 3.95M6.18 6.2C3.65 7.9 2.25 12 2.25 12s3.5 6.96 9.75 6.96c1.26 0 2.4-.3 3.4-.78"/></svg>
                    </button>
                </div>
                <p class="mt-1 text-xs text-slate-500">
                    Minimal 12 karakter dengan huruf besar, huruf kecil, angka, dan simbol.{{ $item->exists ? ' Kosongkan bila tidak ingin mengubah kata sandi.' : '' }}
                </p>
                @error('password')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div class="sm:col-span-2" x-data="{ tampilkanSandi: false }">
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Konfirmasi Kata Sandi</label>
                <div class="relative mt-1.5">
                    <input :type="tampilkanSandi ? 'text' : 'password'" name="password_confirmation" id="password_confirmation" @required(! $item->exists)
                           class="w-full rounded-lg border border-slate-200 px-3 py-2 pr-12 text-sm outline-none focus:border-brand-400 focus:ring-2 focus:ring-brand-100">
                    <button type="button" @click="tampilkanSandi = !tampilkanSandi" :aria-label="tampilkanSandi ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'" :aria-pressed="tampilkanSandi" class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-500 hover:text-brand-700 focus-visible:ring-2 focus-visible:ring-brand-500" autocomplete="off">
                        <svg x-show="!tampilkanSandi" class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.5-6.75 9.75-6.75S21.75 12 21.75 12 18.25 18.75 12 18.75 2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg x-show="tampilkanSandi" x-cloak class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18M10.58 10.59a2 2 0 0 0 2.83 2.83M9.88 5.24A10.9 10.9 0 0 1 12 5.04c6.25 0 9.75 6.96 9.75 6.96s-1 2-3.1 3.95M6.18 6.2C3.65 7.9 2.25 12 2.25 12s3.5 6.96 9.75 6.96c1.26 0 2.4-.3 3.4-.78"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <button class="rounded-lg bg-brand-700 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">
                {{ $item->exists ? 'Simpan Perubahan' : 'Tambah Pengguna' }}
            </button>
            <a href="{{ route('admin.users.index') }}"
               class="rounded-lg bg-white px-6 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50">
                Batal
            </a>
        </div>
    </form>
@endsection
