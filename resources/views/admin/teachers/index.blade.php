@extends('layouts.admin')

@section('title', 'Data Guru')

@section('content')
    <div class="rounded-2xl border border-slate-200 bg-white shadow-card">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-4">
            <form method="GET" class="flex gap-2">
                @if (request('account'))
                    <input type="hidden" name="account" value="{{ request('account') }}">
                @endif
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama / NIP / mapel..."
                       class="w-64 rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-400 focus:ring-2 focus:ring-brand-100">
                <button class="rounded-lg bg-brand-950 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-900">Cari</button>
            </form>

            <a href="{{ route('admin.teachers.create') }}"
               class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800">
                + Tambah Guru
            </a>
        </div>

        <div class="flex flex-wrap gap-2 border-b border-slate-100 px-4 py-3">
            @foreach ([
                '' => ['label' => 'Semua', 'count' => $accountCounts['all']],
                'linked' => ['label' => 'Akun tertaut', 'count' => $accountCounts['linked']],
                'unlinked' => ['label' => 'Belum tertaut', 'count' => $accountCounts['unlinked']],
            ] as $value => $filter)
                <a href="{{ route('admin.teachers.index', array_filter(['account' => $value, 'q' => request('q')])) }}"
                   class="admin-chip {{ request('account', '') === $value ? 'admin-chip-active' : '' }}">
                    {{ $filter['label'] }}
                    <span class="rounded-full bg-white/70 px-1.5 py-0.5 text-[10px]">{{ $filter['count'] }}</span>
                </a>
            @endforeach
        </div>

        <div class="overflow-x-auto">
            <table data-smart-table data-table-name="guru" class="w-full min-w-[620px] text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <x-sort-header column="nip" label="NIP" />
                        <x-sort-header column="nama" label="Nama" />
                        <x-sort-header column="mata_pelajaran" label="Mata Pelajaran" />
                        <x-sort-header column="jabatan" label="Jabatan" class="hidden xl:table-cell" />
                        <th class="px-4 py-3">Akun</th>
                        <x-sort-header column="status" label="Status" />
                        <th class="sticky right-0 bg-slate-50 px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($items as $item)
                        <tr class="group hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $item->nip }}</td>
                            <td class="max-w-[13rem] px-4 py-3">
                                <p class="truncate font-semibold text-brand-950">{{ $item->nama }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $item->email ?: 'Tanpa email' }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $item->mata_pelajaran ?: '—' }}</td>
                            <td class="hidden px-4 py-3 text-slate-600 xl:table-cell">{{ $item->jabatan ?: '—' }}</td>
                            <td class="px-4 py-3">
                                @if ($item->user)
                                    @if (auth()->user()->isAdmin())
                                        <a href="{{ route('admin.users.edit', $item->user) }}"
                                           class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $item->user->is_active ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                                            {{ ucfirst($item->user->role) }} · {{ $item->user->is_active ? 'akun aktif' : 'akun nonaktif' }}
                                        </a>
                                    @else
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Akun tertaut</span>
                                    @endif
                                @else
                                    @if (auth()->user()->isAdmin())
                                        <a href="{{ route('admin.users.create', ['teacher_id' => $item->id]) }}"
                                           class="text-xs font-semibold text-brand-700 hover:text-brand-900">
                                            + Buat akun
                                        </a>
                                    @else
                                        <span class="text-xs text-slate-500">Belum tertaut</span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold capitalize {{ $item->status === 'aktif' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $item->status }}
                                </span>
                            </td>
                            <td class="sticky right-0 bg-white px-4 py-3 group-hover:bg-slate-50">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('admin.teachers.edit', $item) }}" class="font-semibold text-brand-700 hover:text-brand-900">Ubah</a>
                                    <form method="POST" action="{{ route('admin.teachers.destroy', $item) }}"
                                          onsubmit="return confirm('Hapus data guru ini?')">
                                        @csrf @method('DELETE')
                                        <button class="font-semibold text-rose-600 hover:text-rose-800">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7"><x-empty-state icon="academic" title="Belum ada data guru" description="Tambahkan tenaga pendidik pertama beserta penugasannya." :href="route('admin.teachers.create')" action="Tambah guru" /></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="smart-table-footer border-t border-slate-100 p-4">
            <p class="text-xs font-medium text-slate-500">Menampilkan {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }} dari {{ $items->total() }} guru</p>
            {{ $items->links() }}
        </div>
    </div>
@endsection
