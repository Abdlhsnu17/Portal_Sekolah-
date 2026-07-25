@extends('layouts.public')

@section('title', 'Beranda')

@section('content')
    {{-- Hero --}}
    <section class="relative overflow-hidden bg-brand-950">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute -left-40 -top-40 size-136 rounded-full bg-brand-600/25 blur-3xl"></div>
            <div class="absolute -bottom-52 right-0 size-120 rounded-full bg-accent-500/10 blur-3xl"></div>
            <div class="absolute inset-0 opacity-[0.07]"
                 style="background-image: linear-gradient(white 1px, transparent 1px), linear-gradient(90deg, white 1px, transparent 1px); background-size: 64px 64px;"></div>
        </div>

        <div class="relative mx-auto grid max-w-6xl gap-16 px-6 py-24 lg:grid-cols-12 lg:items-center lg:py-32">
            <div class="lg:col-span-7">
                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3.5 py-1.5 text-xs font-semibold text-brand-100 ring-1 ring-white/20">
                    <span class="relative flex size-2">
                        <span class="absolute inline-flex size-full animate-ping rounded-full bg-accent-400 opacity-75"></span>
                        <span class="relative inline-flex size-2 rounded-full bg-accent-400"></span>
                    </span>
                    Penerimaan Siswa Baru {{ date('Y') }} Telah Dibuka
                </span>

                <h1 class="mt-7 text-4xl font-bold leading-[1.1] tracking-tight text-white sm:text-5xl lg:text-6xl">
                    Membangun Generasi<br class="hidden sm:block">
                    <span class="text-accent-400">Berkarakter</span> &amp; Berprestasi
                </h1>

                <p class="mt-6 max-w-xl text-lg leading-relaxed text-brand-100/80">
                    {{ $profil->visi ?: 'Membentuk generasi berkarakter, berprestasi, dan siap menghadapi tantangan masa depan.' }}
                </p>

                <div class="mt-9 flex flex-wrap gap-3">
                    <a href="{{ route('pendaftaran.form') }}"
                       class="group inline-flex items-center gap-2 rounded-xl bg-white px-7 py-3.5 text-sm font-semibold text-brand-800 shadow-lift transition hover:bg-brand-50">
                        Daftar Sekarang
                        <x-icon name="arrow-right" class="size-4 transition group-hover:translate-x-0.5" />
                    </a>
                    <a href="{{ route('profil') }}"
                       class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-7 py-3.5 text-sm font-semibold text-white ring-1 ring-white/20 backdrop-blur transition hover:bg-white/15">
                        Kenali Sekolah Kami
                    </a>
                </div>

                @if ($profil?->akreditasi || $profil?->npsn)
                    <div class="mt-10 flex flex-wrap items-center gap-x-8 gap-y-3 border-t border-white/10 pt-6 text-sm text-brand-100/70">
                        @if ($profil->akreditasi)
                            <span class="flex items-center gap-2"><x-icon name="shield-check" class="size-4 text-accent-400" />Akreditasi {{ $profil->akreditasi }}</span>
                        @endif
                        @if ($profil->npsn)
                            <span class="flex items-center gap-2"><x-icon name="building" class="size-4 text-accent-400" />NPSN {{ $profil->npsn }}</span>
                        @endif
                        @if ($profil->jenjang)
                            <span class="flex items-center gap-2"><x-icon name="academic" class="size-4 text-accent-400" />Jenjang {{ $profil->jenjang }}</span>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Angka kunci: label + nilai, tanpa hiasan warna --}}
            <div class="lg:col-span-5">
                <div class="grid grid-cols-2 gap-px overflow-hidden rounded-2xl bg-white/10 ring-1 ring-white/15">
                    @foreach ([
                        ['label' => 'Siswa aktif', 'nilai' => $statistik['siswa'], 'ikon' => 'users'],
                        ['label' => 'Tenaga pendidik', 'nilai' => $statistik['guru'], 'ikon' => 'academic'],
                        ['label' => 'Rombongan belajar', 'nilai' => $statistik['kelas'], 'ikon' => 'building'],
                        ['label' => 'Prestasi terdokumentasi', 'nilai' => $statistik['prestasi'], 'ikon' => 'trophy'],
                    ] as $kartu)
                        <div class="bg-brand-950/80 p-6 backdrop-blur">
                            <x-icon :name="$kartu['ikon']" class="size-5 text-brand-300" />
                            <p class="mt-4 text-4xl font-semibold text-white">{{ number_format($kartu['nilai']) }}</p>
                            <p class="mt-1.5 text-sm text-brand-100/60">{{ $kartu['label'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- Alur pendaftaran --}}
    <section class="mx-auto max-w-6xl px-6 py-20">
        <div class="max-w-2xl">
            <p class="eyebrow text-brand-600">Penerimaan Siswa Baru</p>
            <h2 class="mt-3 text-3xl font-bold tracking-tight text-brand-950">Tiga langkah, seluruhnya daring</h2>
            <p class="mt-3 text-base leading-relaxed text-slate-600">
                Tidak perlu datang ke sekolah pada tahap awal. Semua proses dapat diselesaikan dari rumah.
            </p>
        </div>

        <div class="mt-12 grid gap-6 md:grid-cols-3">
            @foreach ([
                ['no' => '01', 'ikon' => 'clipboard', 'judul' => 'Isi Formulir', 'teks' => 'Lengkapi data calon siswa secara daring. Sistem langsung menerbitkan nomor pendaftaran Anda.', 'rute' => route('pendaftaran.form'), 'aksi' => 'Mulai mendaftar'],
                ['no' => '02', 'ikon' => 'upload', 'judul' => 'Unggah Dokumen', 'teks' => 'Kirim berkas persyaratan langsung dari perangkat Anda dalam format PDF maupun foto.', 'rute' => route('pendaftaran.status'), 'aksi' => 'Kelola dokumen'],
                ['no' => '03', 'ikon' => 'shield-check', 'judul' => 'Pantau Status', 'teks' => 'Periksa perkembangan seleksi kapan saja memakai nomor pendaftaran dan email Anda.', 'rute' => route('pendaftaran.status'), 'aksi' => 'Cek status'],
            ] as $langkah)
                <a href="{{ $langkah['rute'] }}"
                   class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-7 shadow-card transition hover:-translate-y-1 hover:border-brand-200 hover:shadow-lift">
                    <span class="absolute right-6 top-5 text-4xl font-bold text-slate-100 transition group-hover:text-brand-50">{{ $langkah['no'] }}</span>
                    <span class="grid size-12 place-items-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100">
                        <x-icon :name="$langkah['ikon']" class="size-6" />
                    </span>
                    <h3 class="mt-5 text-lg font-bold tracking-tight text-brand-950">{{ $langkah['judul'] }}</h3>
                    <p class="mt-2.5 text-sm leading-relaxed text-slate-600">{{ $langkah['teks'] }}</p>
                    <span class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700">
                        {{ $langkah['aksi'] }}
                        <x-icon name="arrow-right" class="size-4 transition group-hover:translate-x-0.5" />
                    </span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Berita --}}
    <section class="border-y border-slate-200 bg-slate-50/70">
        <div class="mx-auto max-w-6xl px-6 py-20">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="eyebrow text-brand-600">Informasi Terkini</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-brand-950">Berita &amp; Pengumuman</h2>
                </div>
                <a href="{{ route('berita') }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-brand-700 shadow-card transition hover:border-brand-200 hover:bg-brand-50">
                    Lihat semua <x-icon name="arrow-right" class="size-4" />
                </a>
            </div>

            <div class="mt-10 grid gap-6 md:grid-cols-3">
                @forelse ($sorotan as $item)
                    <article class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card transition hover:-translate-y-1 hover:shadow-lift">
                        <a href="{{ route('berita.detail', $item->slug) }}" class="block aspect-16/10 overflow-hidden bg-slate-100">
                            @if ($item->gambar)
                                <img src="{{ asset('storage/' . $item->gambar) }}" alt="{{ $item->judul }}"
                                     class="size-full object-cover transition duration-500 group-hover:scale-105">
                            @else
                                <span class="grid size-full place-items-center bg-brand-900 text-brand-300">
                                    <x-icon name="newspaper" class="size-10" />
                                </span>
                            @endif
                        </a>
                        <div class="flex flex-1 flex-col p-6">
                            <div class="flex items-center gap-2 text-xs">
                                <span class="rounded-full bg-brand-50 px-2.5 py-1 font-semibold capitalize text-brand-700">{{ $item->kategori }}</span>
                                <span class="text-slate-400">{{ optional($item->published_at)->translatedFormat('d M Y') }}</span>
                            </div>
                            <h3 class="mt-3.5 text-base font-bold leading-snug tracking-tight text-brand-950">
                                <a href="{{ route('berita.detail', $item->slug) }}" class="transition hover:text-brand-700">{{ $item->judul }}</a>
                            </h3>
                            <p class="mt-2.5 flex-1 text-sm leading-relaxed text-slate-600">{{ Str::limit($item->ringkasan_teks, 120) }}</p>
                            <a href="{{ route('berita.detail', $item->slug) }}"
                               class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700">
                                Baca selengkapnya <x-icon name="arrow-right" class="size-4 transition group-hover:translate-x-0.5" />
                            </a>
                        </div>
                    </article>
                @empty
                    <p class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white p-14 text-center text-sm text-slate-500">
                        Belum ada berita yang dipublikasikan.
                    </p>
                @endforelse
            </div>
        </div>
    </section>

    {{-- Galeri --}}
    <section class="mx-auto max-w-6xl px-6 py-20">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="eyebrow text-brand-600">Dokumentasi</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-brand-950">Galeri Kegiatan</h2>
            </div>
            <a href="{{ route('galeri') }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-brand-700 shadow-card transition hover:border-brand-200 hover:bg-brand-50">
                Lihat semua <x-icon name="arrow-right" class="size-4" />
            </a>
        </div>

        <div class="mt-10 grid grid-cols-2 gap-4 md:grid-cols-3">
            @forelse ($galeri as $foto)
                <figure class="group relative aspect-4/3 overflow-hidden rounded-2xl bg-slate-100 ring-1 ring-slate-200">
                    <img src="{{ asset('storage/' . $foto->path) }}" alt="{{ $foto->judul }}"
                         class="size-full object-cover transition duration-500 group-hover:scale-105">
                    <div class="absolute inset-0 bg-linear-to-t from-brand-950/85 via-brand-950/10 to-transparent"></div>
                    <figcaption class="absolute inset-x-0 bottom-0 p-4">
                        <p class="text-sm font-semibold text-white">{{ $foto->judul }}</p>
                        <p class="mt-0.5 text-xs capitalize text-white/60">{{ $foto->kategori }}</p>
                    </figcaption>
                </figure>
            @empty
                <p class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white p-14 text-center text-sm text-slate-500">
                    Galeri masih kosong.
                </p>
            @endforelse
        </div>
    </section>

    {{-- Ajakan bertindak --}}
    <section class="mx-auto max-w-6xl px-6 pb-8">
        <div class="relative overflow-hidden rounded-3xl bg-brand-900 px-8 py-14 text-center sm:px-16">
            <div class="pointer-events-none absolute -right-24 -top-24 size-72 rounded-full bg-brand-600/30 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-32 -left-16 size-72 rounded-full bg-accent-500/10 blur-3xl"></div>
            <div class="relative">
                <h2 class="text-3xl font-bold tracking-tight text-white">Siap bergabung bersama kami?</h2>
                <p class="mx-auto mt-4 max-w-xl text-base leading-relaxed text-brand-100/75">
                    Pendaftaran siswa baru dibuka sepenuhnya secara daring. Isi formulir hari ini dan
                    dapatkan nomor pendaftaran Anda seketika.
                </p>
                <a href="{{ route('pendaftaran.form') }}"
                   class="group mt-8 inline-flex items-center gap-2 rounded-xl bg-accent-500 px-8 py-4 text-sm font-semibold text-brand-950 shadow-lift transition hover:bg-accent-400">
                    Isi Formulir Pendaftaran
                    <x-icon name="arrow-right" class="size-4 transition group-hover:translate-x-0.5" />
                </a>
            </div>
        </div>
    </section>
@endsection
