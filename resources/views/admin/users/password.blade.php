@extends('layouts.admin')

@section('title', 'Ganti Kata Sandi Pengguna')

@section('content')
    <form method="POST" action="{{ route('admin.users.password.update', $user) }}" class="mt-4 max-w-xl rounded-2xl border border-slate-200 bg-white p-6 shadow-card">
        @csrf
        @method('PUT')

        <div class="border-b border-slate-100 pb-5">
            <h1 class="text-lg font-bold text-brand-950">Ganti kata sandi</h1>
            <p class="mt-1 text-sm text-slate-600">Atur kata sandi baru untuk <span class="font-semibold text-brand-950">{{ $user->name }}</span> ({{ ucfirst($user->role) }}).</p>
            <p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs leading-relaxed text-amber-800">Setelah disimpan, seluruh sesi aktif akun ini akan dikeluarkan. Peran dan data pengguna tidak akan berubah.</p>
        </div>

        <div class="mt-5 space-y-5" x-data="{ tampilkanSandi: false }">
            <div>
                <label for="password" class="block text-sm font-medium text-slate-700">Kata Sandi Baru <span class="text-rose-500">*</span></label>
                <input :type="tampilkanSandi ? 'text' : 'password'" name="password" id="password" required autocomplete="new-password" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-400 focus:ring-2 focus:ring-brand-100">
                <p class="mt-1 text-xs text-slate-500">Minimal 12 karakter dengan huruf besar, huruf kecil, angka, dan simbol.</p>
                @error('password')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Konfirmasi Kata Sandi Baru <span class="text-rose-500">*</span></label>
                <input :type="tampilkanSandi ? 'text' : 'password'" name="password_confirmation" id="password_confirmation" required autocomplete="new-password" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-400 focus:ring-2 focus:ring-brand-100">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" @change="tampilkanSandi = $event.target.checked" class="size-4 rounded border-slate-300 text-brand-700 focus:ring-brand-500"> Tampilkan kata sandi</label>
        </div>

        <div class="mt-6 flex gap-3">
            <button class="rounded-lg bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">Simpan Kata Sandi</button>
            <a href="{{ route('admin.users.index') }}" class="rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50">Batal</a>
        </div>
    </form>
@endsection
