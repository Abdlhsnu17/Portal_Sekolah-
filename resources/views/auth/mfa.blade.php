<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Dua Faktor — {{ $profil->nama }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid min-h-screen place-items-center bg-slate-50 px-6 font-sans text-slate-700">
    <main class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-card">
        <x-logo class="size-12" />
        <h1 class="mt-6 text-2xl font-bold text-brand-950">Verifikasi dua faktor</h1>
        <p class="mt-2 text-sm text-slate-600">Masukkan kode 6 digit dari aplikasi autentikator atau salah satu kode pemulihan.</p>
        <x-alert />
        <form method="POST" action="{{ route('login.mfa.verify') }}" class="mt-6 space-y-5">
            @csrf
            <x-form.input name="code" label="Kode keamanan" required autofocus autocomplete="one-time-code" placeholder="123456" />
            <button class="w-full rounded-xl bg-brand-700 px-4 py-3 text-sm font-semibold text-white hover:bg-brand-800">Verifikasi</button>
        </form>
        <a href="{{ route('login') }}" class="mt-6 inline-block text-sm text-slate-500 hover:text-brand-700">Batalkan dan kembali</a>
    </main>
</body>
</html>
