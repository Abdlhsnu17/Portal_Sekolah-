@extends('layouts.admin')

@section('title', 'Profil Sekolah')

@section('content')
    <form method="POST" action="{{ route('admin.profile.update') }}" class="max-w-4xl space-y-6">
        @csrf @method('PUT')

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card">
            <h2 class="text-base font-bold text-brand-950">Identitas Sekolah</h2>
            <p class="mt-1 text-sm text-slate-600">Data ini tampil pada header, footer, dan halaman profil situs publik.</p>

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <x-form.input name="nama" label="Nama Sekolah" required :value="$item->nama" class="sm:col-span-2" />
                <x-form.input name="npsn" label="NPSN" :value="$item->npsn" />
                <x-form.input name="jenjang" label="Jenjang" :value="$item->jenjang" placeholder="Contoh: SMA" />
                <x-form.input name="akreditasi" label="Akreditasi" :value="$item->akreditasi" placeholder="Contoh: A" />
                <x-form.input name="kepala_sekolah" label="Kepala Sekolah" :value="$item->kepala_sekolah" />
                <x-form.input name="email" label="Email" type="email" :value="$item->email" />
                <x-form.input name="telepon" label="Telepon" :value="$item->telepon" />
                <x-form.textarea name="alamat" label="Alamat" :value="$item->alamat" class="sm:col-span-2" />
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card">
            <h2 class="text-base font-bold text-brand-950">Visi, Misi &amp; Sejarah</h2>

            <div class="mt-5 space-y-5">
                <x-form.textarea name="visi" label="Visi" :value="$item->visi" rows="3" />
                <x-form.textarea name="misi" label="Misi" :value="$item->misi" rows="6"
                                 hint="Tulis satu poin misi per baris." />
                <x-form.textarea name="sejarah" label="Sejarah Singkat" :value="$item->sejarah" rows="8"
                                 hint="Pisahkan antar paragraf dengan satu baris kosong." />
            </div>
        </div>

        <button class="rounded-lg bg-brand-700 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">
            Simpan Profil Sekolah
        </button>
    </form>
@endsection
