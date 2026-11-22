<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') — Panel Admin</title>
    <meta name="theme-color" content="#111d34">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=inter:400,500,600,700|plus-jakarta-sans:600,700,800&display=swap">
    {{-- Dijalankan sebelum render agar sidebar tidak berkedip lebar saat pindah halaman --}}
    <script>
        (function () {
            try {
                if (localStorage.getItem('sidebar') === 'collapsed') {
                    document.documentElement.dataset.sidebar = 'collapsed';
                }
            } catch (e) { /* localStorage diblokir — biarkan sidebar terbentang */ }

            window.toggleSidebar = function () {
                var html = document.documentElement;
                var ciut = html.dataset.sidebar === 'collapsed';

                if (ciut) {
                    delete html.dataset.sidebar;
                } else {
                    html.dataset.sidebar = 'collapsed';
                }

                try {
                    localStorage.setItem('sidebar', ciut ? 'expanded' : 'collapsed');
                } catch (e) { /* abaikan bila penyimpanan tidak tersedia */ }
            };
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-shell min-h-screen font-sans text-slate-700 antialiased" x-data="{ sidebar: false }">

<a href="#konten-utama" class="skip-link">Lewati ke konten utama</a>

@php
    $pengguna = auth()->user();

    if ($pengguna?->isTeacher()) {
        $menu = [
            ['grup' => 'Akademik', 'rute' => 'teaching.index', 'label' => 'Pengajaran Saya', 'aktif' => 'teaching.*', 'ikon' => 'academic'],
        ];
    } elseif ($pengguna?->isStudent()) {
        $menu = [
            ['grup' => 'Akademik', 'rute' => 'student.index', 'label' => 'Belajar Saya', 'aktif' => 'student.*', 'ikon' => 'academic'],
        ];
    } else {
        $menu = [
            ['grup' => 'Beranda', 'rute' => 'admin.dashboard', 'label' => 'Dashboard', 'aktif' => 'admin.dashboard', 'ikon' => 'home'],
            ['grup' => 'Penerimaan Siswa', 'rute' => 'admin.registrations.index', 'label' => 'Pendaftaran', 'aktif' => 'admin.registrations.*', 'ikon' => 'clipboard'],
        ];
    }

    if ($pengguna?->isAdmin()) {
        // Susun berdasarkan urutan kerja sekolah: siapkan struktur akademik,
        // kelola siswa dan pengajar, lalu jalankan kegiatan pembelajaran.
        $menu[] = ['grup' => 'Penerimaan Siswa', 'rute' => 'admin.admission-settings.index', 'label' => 'Pengaturan PSB', 'aktif' => ['admin.admission-settings.*', 'admin.admission-periods.*', 'admin.admission-paths.*'], 'ikon' => 'clipboard'];
        $menu[] = ['grup' => 'Akademik', 'rute' => 'admin.students.index', 'label' => 'Data Siswa', 'aktif' => 'admin.students.*', 'ikon' => 'users'];
        $menu[] = ['grup' => 'Akademik', 'rute' => 'admin.academic-structure.index', 'label' => 'Tahun Ajaran & Kelas', 'aktif' => ['admin.academic-structure.*', 'admin.academic-years.*', 'admin.school-classes.*', 'admin.student-enrollments.*'], 'ikon' => 'academic'];
        $menu[] = ['grup' => 'Akademik', 'rute' => 'admin.teaching-assignments.index', 'label' => 'Penugasan Mengajar', 'aktif' => ['admin.teaching-assignments.*', 'admin.subjects.*'], 'ikon' => 'academic'];
        $menu[] = ['grup' => 'Akademik', 'rute' => 'admin.class-schedules.index', 'label' => 'Jadwal Pelajaran', 'aktif' => 'admin.class-schedules.*', 'ikon' => 'clock'];
        $menu[] = ['grup' => 'Akademik', 'rute' => 'admin.student-attendances.index', 'label' => 'Absensi Siswa', 'aktif' => 'admin.student-attendances.*', 'ikon' => 'users'];
        $menu[] = ['grup' => 'Akademik', 'rute' => 'admin.assessments.index', 'label' => 'Penilaian', 'aktif' => 'admin.assessments.*', 'ikon' => 'academic'];
        $menu[] = ['grup' => 'Akademik', 'rute' => 'admin.student-reports.index', 'label' => 'Rekap Nilai & Rapor', 'aktif' => 'admin.student-reports.*', 'ikon' => 'clipboard'];
        $menu[] = ['grup' => 'Kepegawaian', 'rute' => 'admin.staff.index', 'label' => 'Data Staf', 'aktif' => ['admin.staff.*', 'admin.teachers.*'], 'ikon' => 'users'];
        $menu[] = ['grup' => 'Kepegawaian', 'rute' => 'admin.attendances.index', 'label' => 'Absensi Pegawai', 'aktif' => 'admin.attendances.*', 'ikon' => 'clock'];
        $menu[] = ['grup' => 'Operasional', 'rute' => 'admin.finance.index', 'label' => 'Keuangan', 'aktif' => 'admin.finance.*', 'ikon' => 'clipboard'];
        $menu[] = ['grup' => 'Operasional', 'rute' => 'admin.library.index', 'label' => 'Perpustakaan', 'aktif' => 'admin.library.*', 'ikon' => 'academic'];
        $menu[] = ['grup' => 'Operasional', 'rute' => 'admin.assets.index', 'label' => 'Inventaris Aset', 'aktif' => 'admin.assets.*', 'ikon' => 'building'];
    } elseif (! $pengguna?->isTeacher() && ! $pengguna?->isStudent()) {
        $menu[] = ['grup' => 'Akademik', 'rute' => 'admin.students.index', 'label' => 'Data Siswa', 'aktif' => 'admin.students.*', 'ikon' => 'users'];
    }

    $pemilikAkun = auth()->user();
    $dapatAbsensi = $pemilikAkun && (
        ($pemilikAkun->isTeacher() && $pemilikAkun->teacher()->where('status', 'aktif')->exists())
        || ($pemilikAkun->isAdmin() && ($pemilikAkun->teacher()->where('status', 'aktif')->exists() || $pemilikAkun->staffMember()->where('status', 'aktif')->exists()))
        || ($pemilikAkun->role === 'staf' && $pemilikAkun->staffMember()->where('status', 'aktif')->exists())
    );
    if ($dapatAbsensi && ! $pengguna?->isAdmin()) {
        $menu[] = ['grup' => 'Kepegawaian', 'rute' => 'attendance.check-in', 'label' => 'Absensi', 'aktif' => 'attendance.*', 'ikon' => 'camera'];
    }

    if (! $pengguna?->isTeacher() && ! $pengguna?->isStudent()) {
        $menu[] = ['grup' => 'Konten Website', 'rute' => 'admin.profile.edit', 'label' => 'Profil Sekolah', 'aktif' => 'admin.profile.*', 'ikon' => 'building'];
        $menu[] = ['grup' => 'Konten Website', 'rute' => 'admin.announcements.index', 'label' => 'Berita', 'aktif' => 'admin.announcements.*', 'ikon' => 'newspaper'];
        $menu[] = ['grup' => 'Konten Website', 'rute' => 'admin.galleries.index', 'label' => 'Galeri', 'aktif' => 'admin.galleries.*', 'ikon' => 'photo'];
    }

    if ($pengguna?->isAdmin()) {
        $menu[] = ['grup' => 'Administrasi', 'rute' => 'admin.users.index', 'label' => 'Pengguna', 'aktif' => 'admin.users.*', 'ikon' => 'users'];
        $menu[] = ['grup' => 'Administrasi', 'rute' => 'admin.guardians.index', 'label' => 'Akun Wali', 'aktif' => 'admin.guardians.*', 'ikon' => 'users'];
        $menu[] = ['grup' => 'Administrasi', 'rute' => 'admin.reports.index', 'label' => 'Laporan & Ekspor', 'aktif' => 'admin.reports.*', 'ikon' => 'clipboard'];
        if ($dapatAbsensi) {
            $menu[] = ['grup' => 'Administrasi', 'rute' => 'attendance.check-in', 'label' => 'Absensi Saya', 'aktif' => 'attendance.*', 'ikon' => 'camera'];
        }
    }

    $ruteBeranda = $pengguna?->isTeacher() ? 'teaching.index' : ($pengguna?->isStudent() ? 'student.index' : 'admin.dashboard');
    $menu[] = ['grup' => 'Administrasi', 'rute' => 'security.index', 'label' => 'Pengaturan', 'aktif' => ['security.*', 'admin.settings.*'], 'ikon' => 'cog'];
@endphp

<div class="admin-layout flex overflow-hidden">
    {{-- Sidebar --}}
    {{-- Posisi awal ditulis statis agar tidak menutupi konten sebelum Alpine aktif --}}
    <aside :class="sidebar && 'translate-x-0!'"
           class="admin-sidebar fixed inset-y-0 left-0 z-40 flex shrink-0 -translate-x-full flex-col overflow-hidden border-r border-white/10 bg-brand-950 shadow-2xl shadow-brand-950/20 transition-transform lg:static lg:translate-x-0 lg:shadow-none">
        <div class="sidebar-link flex h-16 items-center justify-center border-b border-white/10 px-5">
            <x-logo tone="light" class="size-11 shrink-0" />
        </div>

        <nav data-sidebar-navigation class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-2.5" aria-label="Navigasi admin">
            @php $grupAktif = null; @endphp
            @foreach ($menu as $item)
                @if ($grupAktif !== $item['grup'])
                    <p class="sidebar-label mb-1 mt-3 px-3 text-[10px] font-bold uppercase tracking-[0.16em] text-brand-200/40 first:mt-0.5">
                        {{ $item['grup'] }}
                    </p>
                    @php $grupAktif = $item['grup']; @endphp
                @endif
                @php $aktif = request()->routeIs($item['aktif']); @endphp
                <a href="{{ route($item['rute']) }}" title="{{ $item['label'] }}" @if($aktif) aria-current="page" @endif
                   class="sidebar-link group relative mb-0.5 flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-semibold transition {{ $aktif ? 'bg-white text-brand-950 shadow-[0_8px_24px_-12px_rgba(0,0,0,.55)]' : 'text-brand-100/65 hover:bg-white/8 hover:text-white' }}">
                    @if ($aktif)
                        <span class="absolute -left-3 top-1/2 h-7 w-1 -translate-y-1/2 rounded-r-full bg-accent-400 shadow-[0_0_12px_rgba(217,169,78,.65)]"></span>
                    @endif
                    <span class="grid size-7 shrink-0 place-items-center rounded-md {{ $aktif ? 'bg-brand-100 text-brand-800' : 'text-brand-200/70 group-hover:bg-white/10 group-hover:text-white' }}">
                        <x-icon :name="$item['ikon']" class="size-4.5" />
                    </span>
                    <span class="sidebar-label whitespace-nowrap">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <div class="space-y-2 border-t border-white/10 p-2.5">
            <a href="{{ route('home') }}" target="_blank" title="Lihat Situs"
               class="sidebar-link flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-semibold text-brand-100/70 transition hover:bg-white/10 hover:text-white">
                <x-icon name="external" class="size-5 shrink-0" />
                <span class="sidebar-label whitespace-nowrap">Lihat Situs</span>
            </a>
            <div class="sidebar-label rounded-lg border border-white/10 bg-white/5 px-3 py-2">
                <p class="truncate text-xs font-semibold text-white">{{ auth()->user()->name }}</p>
            </div>
        </div>
    </aside>

    <div x-show="sidebar" x-cloak @click="sidebar = false" x-transition.opacity
         class="fixed inset-0 z-30 bg-brand-950/60 lg:hidden"></div>

    {{-- Konten --}}
    <div class="admin-content relative flex min-w-0 flex-1 flex-col overflow-y-auto overscroll-contain">
        <header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-4 border-b border-slate-200/80 bg-white/88 px-4 shadow-[0_1px_0_rgba(15,23,42,0.03)] backdrop-blur-xl sm:px-6">
            <div class="flex min-w-0 items-center gap-3">
                {{-- Layar kecil: buka laci sidebar --}}
                <button @click="sidebar = !sidebar" aria-label="Buka menu"
                        class="grid size-9 shrink-0 place-items-center rounded-lg border border-slate-200 text-slate-600 transition hover:bg-slate-50 lg:hidden">
                    <x-icon name="menu" />
                </button>

                {{-- Layar lebar: ciutkan sidebar agar area kerja melebar --}}
                <button type="button" onclick="toggleSidebar()" aria-label="Ciutkan menu"
                        title="Ciutkan / bentangkan menu"
                        class="hidden size-9 shrink-0 place-items-center rounded-lg border border-slate-200 text-slate-600 transition hover:bg-slate-50 lg:grid">
                    <x-icon name="menu" />
                </button>
                <div class="min-w-0">
                    <h1 class="truncate text-base font-bold tracking-tight text-brand-950">@yield('title', 'Dashboard')</h1>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <x-date-time-card compact />

                <div x-data="{ open: false }" @keydown.escape.window="open = false" class="relative shrink-0">
                    <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-haspopup="menu"
                            aria-label="Pemberitahuan{{ $unreadNotificationCount ? ' belum dibaca: '.$unreadNotificationCount : '' }}"
                            class="relative grid size-9 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-brand-200 hover:text-brand-700 hover:shadow-card">
                        <x-icon name="bell" class="size-5" />
                        @if ($unreadNotificationCount)
                            <span class="absolute -right-1.5 -top-1.5 grid min-w-5 place-items-center rounded-full border-2 border-white bg-rose-500 px-1 text-[10px] font-bold leading-4 text-white">
                                {{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}
                            </span>
                        @endif
                    </button>

                    <div x-show="open" x-cloak @click.outside="open = false" x-transition.opacity style="display: none;" role="menu"
                         class="absolute right-0 z-30 mt-2 w-[min(22rem,calc(100vw-2rem))] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lift">
                        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                            <div>
                                <p class="text-sm font-bold text-brand-950">Pemberitahuan</p>
                                <p class="text-[11px] text-slate-500">{{ $unreadNotificationCount }} belum dibaca</p>
                            </div>
                            <div class="flex items-center gap-3">
                                @if ($unreadNotificationCount)
                                    <form method="POST" action="{{ route('notifications.read-all') }}">
                                        @csrf
                                        <button class="text-xs font-semibold text-brand-700 hover:text-brand-900">Tandai semua dibaca</button>
                                    </form>
                                @endif
                                @if ($headerNotifications->isNotEmpty())
                                    <form method="POST" action="{{ route('notifications.destroy-all') }}"
                                          data-confirm-delete
                                          data-delete-title="Hapus semua pemberitahuan?"
                                          data-delete-description="Seluruh pemberitahuan akan dihapus permanen dan tidak dapat dikembalikan.">
                                    @csrf
                                    @method('DELETE')
                                        <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-800">Hapus semua</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                        <div class="max-h-96 overflow-y-auto">
                            @forelse ($headerNotifications as $notification)
                                <div class="flex items-start gap-2 border-b border-slate-100 px-4 py-3 transition hover:bg-slate-50 {{ $notification->read_at ? '' : 'bg-brand-50/60' }}">
                                  <a href="{{ route('notifications.read', $notification) }}" role="menuitem"
                                   class="flex min-w-0 flex-1 gap-3">
                                    <span class="mt-0.5 grid size-9 shrink-0 place-items-center rounded-lg {{ $notification->read_at ? 'bg-slate-100 text-slate-500' : 'bg-brand-100 text-brand-700' }}">
                                        <x-icon :name="$notification->data['icon'] ?? 'bell'" class="size-4.5" />
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="flex items-start justify-between gap-2">
                                            <span class="truncate text-sm font-semibold text-slate-800">{{ $notification->data['title'] ?? 'Aktivitas baru' }}</span>
                                            @unless ($notification->read_at)<span class="mt-1.5 size-2 shrink-0 rounded-full bg-brand-600"></span>@endunless
                                        </span>
                                        <span class="mt-0.5 block text-xs leading-5 text-slate-500">{{ $notification->data['message'] ?? '' }}</span>
                                        <span class="mt-1 block text-[10px] font-medium text-slate-400">{{ $notification->created_at->diffForHumans() }}</span>
                                    </span>
                                  </a>
                                  @if ($notification->read_at)
                                      <form method="POST" action="{{ route('notifications.destroy', $notification) }}" class="shrink-0" data-confirm-delete>
                                          @csrf
                                          @method('DELETE')
                                          <button type="submit" aria-label="Hapus pemberitahuan" title="Hapus pemberitahuan"
                                                  class="grid size-8 place-items-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-600">
                                              <x-icon name="trash" class="size-4" />
                                          </button>
                                      </form>
                                  @endif
                                </div>
                            @empty
                                <div class="px-6 py-10 text-center">
                                    <span class="mx-auto mb-2 grid size-10 place-items-center rounded-full bg-slate-100 text-slate-400"><x-icon name="bell" /></span>
                                    <p class="text-sm font-semibold text-slate-700">Belum ada pemberitahuan</p>
                                    <p class="mt-1 text-xs text-slate-500">Aktivitas yang sesuai role Anda akan tampil di sini.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div x-data="{ open: false }" @keydown.escape.window="open = false" class="relative shrink-0">
                <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-haspopup="menu"
                        class="flex items-center gap-2.5 rounded-lg border border-transparent px-2 py-1.5 transition hover:border-slate-200 hover:bg-white hover:shadow-card">
                    <span class="grid size-8 place-items-center rounded-lg bg-brand-700 text-xs font-bold text-white shadow-card">
                        {{ auth()->user()->initials }}
                    </span>
                    <span class="hidden text-left sm:block">
                        <span class="block text-sm font-semibold leading-4 text-slate-800">{{ auth()->user()->name }}</span>
                        <span class="block text-[11px] font-medium capitalize text-slate-500">{{ auth()->user()->role }}</span>
                    </span>
                </button>

                <div x-show="open" x-cloak @click.outside="open = false" x-transition.opacity
                     style="display: none;" role="menu"
                     class="absolute right-0 z-30 mt-2 w-64 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lift">
                    <div class="border-b border-slate-100 px-4 py-3">
                        <p class="text-sm font-semibold text-brand-950">{{ auth()->user()->name }}</p>
                        <p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
                        <p class="mt-2 inline-block rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold capitalize text-brand-700">
                            {{ auth()->user()->role }}
                        </p>
                    </div>
                    <a href="{{ route('security.index') }}" class="mx-1.5 mt-1.5 flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        <x-icon name="cog" class="size-4" /> Pengaturan
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="p-1.5">
                        @csrf
                        <button class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm font-medium text-rose-600 transition hover:bg-rose-50">
                            <x-icon name="logout" class="size-4" /> Keluar
                        </button>
                    </form>
                </div>
                </div>
            </div>
        </header>

        <x-announcement-ticker admin />

        <main id="konten-utama" class="relative flex-1 p-4 sm:p-6 lg:p-8" tabindex="-1">
            @unless (request()->routeIs($ruteBeranda))
                <nav class="mb-4 flex items-center gap-1.5 text-xs font-medium text-slate-500" aria-label="Breadcrumb">
                    <a href="{{ route($ruteBeranda) }}" class="transition hover:text-brand-700">{{ $pengguna?->isTeacher() ? 'Pengajaran Saya' : ($pengguna?->isStudent() ? 'Belajar Saya' : 'Dashboard') }}</a>
                    <x-icon name="chevron-right" class="size-3.5 text-slate-300" />
                    @php
                        $induk = collect($menu)->first(fn ($item) => request()->routeIs($item['aktif']));
                    @endphp
                    @if ($induk && ! request()->routeIs($induk['rute']))
                        <a href="{{ route($induk['rute']) }}" class="transition hover:text-brand-700">{{ $induk['label'] }}</a>
                        <x-icon name="chevron-right" class="size-3.5 text-slate-300" />
                    @endif
                    <span class="font-semibold text-brand-950" aria-current="page">@yield('title', 'Halaman')</span>
                </nav>
            @endunless
            <x-alert />
            <div class="mt-4">
                @yield('content')
            </div>
        </main>
    </div>
</div>

<x-confirm-delete-dialog />

</body>
</html>
