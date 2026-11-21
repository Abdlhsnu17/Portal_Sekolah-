@props(['admin' => false])

@php
    $items = $tickerAnnouncements ?? collect();
    $enabled = $tickerEnabled ?? true;
    $tickerText = trim((string) ($tickerText ?? ''));
    $fallback = $tickerText !== ''
        ? $tickerText
        : 'Portal sekolah — informasi akademik dan layanan sekolah dalam satu tempat.';
@endphp

@if ($enabled)
<section {{ $attributes->merge(['class' => 'announcement-ticker']) }} aria-label="Informasi terbaru">
    <div class="announcement-ticker-label">
        <span class="relative flex size-2" aria-hidden="true">
            <span class="absolute inline-flex size-full animate-ping rounded-full bg-accent-300 opacity-70"></span>
            <span class="relative inline-flex size-2 rounded-full bg-accent-400"></span>
        </span>
        Info
    </div>

    <p class="sr-only">
        {{ $fallback }}
    </p>

    <div class="announcement-ticker-viewport" aria-hidden="true">
        <div class="announcement-ticker-track">
            @foreach ([0, 1] as $copy)
                <div class="announcement-ticker-content">
                    <span>{{ $fallback }}</span>
                    <span class="text-white/30">•</span>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
