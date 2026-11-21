@props(['icon' => 'document', 'title', 'description' => null, 'href' => null, 'action' => null])

<div {{ $attributes->merge(['class' => 'mx-auto flex max-w-sm flex-col items-center px-4 py-10 text-center']) }}>
    <span class="grid size-12 place-items-center rounded-xl bg-slate-100 text-slate-400 ring-1 ring-slate-200">
        <x-icon :name="$icon" class="size-5" />
    </span>
    <p class="mt-4 text-sm font-bold text-brand-950">{{ $title }}</p>
    @if ($description)
        <p class="mt-1.5 text-xs leading-relaxed text-slate-500">{{ $description }}</p>
    @endif
    @if ($href && $action)
        <a href="{{ $href }}" class="admin-primary mt-4">{{ $action }}</a>
    @endif
</div>
