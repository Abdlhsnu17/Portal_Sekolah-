@extends('layouts.admin')
@section('title', 'Data Staff')
@section('content')
<div class="rounded-2xl border border-slate-200 bg-white shadow-card">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-4">
        <div><h2 class="font-bold text-brand-950">Seluruh Staff Sekolah</h2><p class="mt-1 text-xs text-slate-500">Guru dan staff non-pengajar dikelola dari satu tempat.</p></div>
        <a href="{{ route('admin.staff.create') }}" class="admin-primary">+ Tambah Staff</a>
    </div>
    <div class="flex flex-wrap items-end justify-between gap-3 border-b border-slate-100 p-4">
        <div class="flex flex-wrap gap-2">
        @foreach (['' => ['Semua', $counts['all']], 'teacher' => ['Guru', $counts['teacher']], 'staff' => ['Non-pengajar', $counts['staff']]] as $value => [$label, $count])
            <a href="{{ route('admin.staff.index', array_filter(['type' => $value, 'q' => request('q'), 'account' => request('account')])) }}" class="rounded-full px-3 py-1.5 text-xs font-semibold {{ request('type', '') === $value ? 'bg-brand-950 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $label }} <span class="ml-1 opacity-70">{{ $count }}</span></a>
        @endforeach
        </div>
        <form method="GET" class="flex flex-wrap gap-2">
            @if (request('type')) <input type="hidden" name="type" value="{{ request('type') }}"> @endif
            <select name="account" class="rounded-lg border border-slate-200 px-3 py-2 text-sm"><option value="">Semua akun</option><option value="linked" @selected(request('account') === 'linked')>Sudah punya akun</option><option value="unlinked" @selected(request('account') === 'unlinked')>Belum punya akun</option></select>
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama / NIP / penugasan..." class="w-64 rounded-lg border border-slate-200 px-3 py-2 text-sm">
            <button class="rounded-lg bg-brand-950 px-4 py-2 text-sm font-semibold text-white">Cari</button>
        </form>
    </div>
    <div class="overflow-x-auto">
        <table data-smart-table data-table-name="staff" class="w-full min-w-[760px] text-left text-sm">
            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><x-sort-header column="nip" label="NIP" /><x-sort-header column="nama" label="Nama" /><th class="px-4 py-3">Kategori</th><x-sort-header column="jabatan" label="Penugasan" /><th class="px-4 py-3">Akun</th><x-sort-header column="status" label="Status" /><th class="sticky right-0 bg-slate-50 px-4 py-3 text-right">Aksi</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse ($items as $item)
                @php
                    $isTeacher = $item->type === 'teacher';
                    $user = $item->user_id ? $users->get($item->user_id) : null;
                    $editRoute = $isTeacher ? route('admin.teachers.edit', $item->id) : route('admin.staff.edit', $item->id);
                    $deleteRoute = $isTeacher ? route('admin.teachers.destroy', $item->id) : route('admin.staff.destroy', $item->id);
                    $accountParams = $isTeacher ? ['teacher_id' => $item->id] : ['staff_member_id' => $item->id];
                @endphp
                <tr class="group hover:bg-slate-50">
                    <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $item->nip }}</td>
                    <td class="px-4 py-3"><p class="font-semibold text-brand-950">{{ $item->nama }}</p><p class="text-xs text-slate-500">{{ $item->email ?: 'Tanpa email' }}</p></td>
                    <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $isTeacher ? 'bg-emerald-100 text-emerald-700' : 'bg-sky-100 text-sky-700' }}">{{ $isTeacher ? 'Guru' : 'Non-pengajar' }}</span></td>
                    <td class="px-4 py-3 text-slate-600"><p>{{ $item->jabatan ?: '—' }}</p>@if ($isTeacher && $item->detail)<p class="text-xs text-slate-500">{{ $item->detail }}</p>@endif</td>
                    <td class="px-4 py-3 text-xs">@if ($user)<a href="{{ route('admin.users.edit', $user) }}" class="font-semibold text-sky-700">{{ ucfirst($user->role) }} · {{ $user->is_active ? 'aktif' : 'nonaktif' }}</a>@else<a href="{{ route('admin.users.create', $accountParams) }}" class="font-semibold text-brand-700">+ Buat akun</a>@endif</td>
                    <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold capitalize {{ $item->status === 'aktif' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $item->status }}</span></td>
                    <td class="sticky right-0 bg-white px-4 py-3 group-hover:bg-slate-50"><div class="flex justify-end gap-3"><a href="{{ $editRoute }}" class="font-semibold text-brand-700">Ubah</a><form method="POST" action="{{ $deleteRoute }}" onsubmit="return confirm('Hapus data staff ini?')">@csrf @method('DELETE')<button class="font-semibold text-rose-600">Hapus</button></form></div></td>
                </tr>
            @empty
                <tr><td colspan="7"><x-empty-state icon="users" title="Belum ada data staff" description="Tambahkan guru atau staff non-pengajar pertama." :href="route('admin.staff.create')" action="Tambah staff" /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="smart-table-footer border-t border-slate-100 p-4"><p class="text-xs font-medium text-slate-500">Menampilkan {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }} dari {{ $items->total() }} staff</p>{{ $items->links() }}</div>
</div>
@endsection
