@props(['admin' => false])

@php
    $items = $tickerAnnouncements ?? collect();
    $fallback = 'Portal sekolah — informasi akademik dan layanan sekolah dalam satu tempat.';
@endphp

<section {{ $attributes->merge(['class' => 'announcement-ticker']) }} aria-label="Informasi terbaru">
    <div class="announcement-ticker-label">
        <span class="relative flex size-2" aria-hidden="true">
            <span class="absolute inline-flex size-full animate-ping rounded-full bg-accent-300 opacity-70"></span>
            <span class="relative inline-flex size-2 rounded-full bg-accent-400"></span>
        </span>
        Info
    </div>

    <p class="sr-only">
        {{ $items->isNotEmpty() ? $items->pluck('judul')->join('. ') : $fallback }}
    </p>

    <div class="announcement-ticker-viewport" aria-hidden="true">
        <div class="announcement-ticker-track">
            @foreach ([0, 1] as $copy)
                <div class="announcement-ticker-content">
                    @forelse ($items as $item)
                        <a href="{{ $admin ? route('admin.announcements.edit', $item) : route('berita.detail', $item->slug) }}" tabindex="-1">
                            <span class="capitalize text-accent-300">{{ $item->kategori }}</span>
                            <span>{{ $item->judul }}</span>
                        </a>
                        <span class="text-white/30">•</span>
                    @empty
                        <span>{{ $fallback }}</span><span class="text-white/30">•</span>
                    @endforelse
                </div>
            @endforeach
        </div>
    </div>
</section>
