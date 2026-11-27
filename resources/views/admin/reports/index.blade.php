@extends('layouts.admin')
@section('title', 'Laporan & Ekspor')
@section('content')
<div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
@foreach ([
    ['Data siswa', 'Unduh data induk siswa, wali, dan status.', 'admin.reports.students'],
    ['Data guru', 'Unduh data guru aktif maupun nonaktif.', 'admin.reports.teachers'],
    ['Pendaftar PSB', 'Unduh daftar dan status penerimaan siswa baru.', 'admin.reports.registrations'],
    ['Nilai siswa', 'Unduh seluruh nilai yang telah diinput.', 'admin.reports.scores'],
    ['Keuangan siswa', 'Tagihan, pembayaran, dan sisa saldo per siswa.', 'admin.reports.finance'],
    ['Inventaris aset', 'Daftar aset beserta kondisi dan status operasional.', 'admin.reports.assets'],
    ['Indikator risiko siswa', 'Alpa, poin pelanggaran, dan tindak lanjut BK.', 'admin.reports.student-risks'],
] as [$title, $description, $route])
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card"><h2 class="font-bold text-brand-950">{{ $title }}</h2><p class="mt-2 min-h-10 text-sm leading-5 text-slate-500">{{ $description }}</p><a href="{{ route($route) }}" class="mt-5 inline-flex rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">Unduh CSV</a></div>
@endforeach
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card"><h2 class="font-bold text-brand-950">Absensi siswa</h2><p class="mt-2 text-sm text-slate-500">Batasi rentang tanggal bila diperlukan.</p><form method="GET" action="{{ route('admin.reports.attendances') }}" class="mt-4 grid gap-3"><x-form.input name="from" type="date" label="Dari tanggal" /><x-form.input name="until" type="date" label="Sampai tanggal" /><button class="rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white">Unduh CSV</button></form></div>
</div>
@endsection
