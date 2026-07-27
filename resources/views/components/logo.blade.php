@props([
    /*
     * Monogram "NJ" Nusantara Jaya. Digambar sebagai path agar tetap tajam pada
     * ukuran kecil (favicon, sidebar yang diciutkan) tanpa berkas gambar tambahan.
     *
     * tone  'color' — di atas permukaan terang
     *       'light' — di atas permukaan gelap (brand-900/950)
     *       'mono'  — satu warna, mengikuti currentColor (cetak, watermark)
     */
    'tone' => 'color',
    'class' => 'size-9',
])

@php
    // Celah tipis di sekeliling sabit memisahkannya dari huruf di bawahnya —
    // tanpa itu, sabit lenyap saat menimpa huruf N yang sewarna.
    $palet = match ($tone) {
        'light' => ['n' => '#ffffff', 'j' => '#e0a94a', 'sabit' => '#ffffff', 'celah' => '#16264d'],
        'mono' => ['n' => 'currentColor', 'j' => 'currentColor', 'sabit' => 'currentColor', 'celah' => 'none'],
        default => ['n' => '#1f345f', 'j' => '#c8953c', 'sabit' => '#1f345f', 'celah' => '#ffffff'],
    };

    // Sabit dipakai dua kali: sekali sebagai garis celah, sekali sebagai isian.
    $sabit = 'M9 57Q26 31 61 20Q35 33 17 57Z';
@endphp

<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 64 64" fill="none"
     role="img" aria-label="Logo Nusantara Jaya">
    {{-- Huruf J digambar lebih dahulu: mangkuknya lewat di belakang diagonal N --}}
    <path fill="{{ $palet['j'] }}"
          d="M45 8h14v30q0 18-16 18-10 0-15.5-8.5L38 41c2 3 7 4 7-3z" />
    {{-- Huruf N: batang kiri + diagonal; batang kanan diambil alih huruf J --}}
    <path fill="{{ $palet['n'] }}" d="M4 8h22l14 48H29L15 10v46H4z" />
    {{-- Sabit: kesan gerak maju, menyapu dari kiri bawah ke kanan atas --}}
    @if ($palet['celah'] !== 'none')
        <path d="{{ $sabit }}" fill="none" stroke="{{ $palet['celah'] }}" stroke-width="4"
              stroke-linejoin="round" />
    @endif
    <path d="{{ $sabit }}" fill="{{ $palet['sabit'] }}" />
</svg>
