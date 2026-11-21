@extends('layouts.admin')

@section('title', 'Pengaturan')

@section('content')
    <form method="POST" action="{{ route('admin.settings.update') }}"
          class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-card">
        @csrf
        @method('PUT')

        <div class="admin-form-section">
            <div class="admin-form-heading">
                <p>Tulisan Berjalan</p>
                <p>Atur teks informasi yang ditampilkan di bagian atas situs publik dan panel admin.</p>
            </div>

            <label class="flex items-start gap-3 rounded-lg border border-slate-200 px-4 py-3">
                <input type="checkbox" name="ticker_enabled" value="1" @checked($tickerEnabled)
                       class="mt-0.5 size-4 rounded border-slate-300 text-brand-700 focus:ring-brand-500">
                <span>
                    <span class="block text-sm font-semibold text-slate-800">Aktifkan tulisan berjalan</span>
                    <span class="mt-0.5 block text-xs text-slate-500">Jika dinonaktifkan, tulisan berjalan tidak ditampilkan.</span>
                </span>
            </label>

            <div class="mt-5">
                <x-form.textarea name="ticker_text" label="Isi Tulisan Berjalan" :value="$tickerText"
                                 rows="3" maxlength="500"
                                 hint="Maksimal 500 karakter." />
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <button class="admin-primary">Simpan Pengaturan</button>
        </div>
    </form>
@endsection
