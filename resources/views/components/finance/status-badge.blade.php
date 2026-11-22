@props(['status'])

<span {{ $attributes->class([
    'text-xs font-semibold capitalize',
    'text-emerald-700' => $status === 'lunas',
    'text-amber-700' => $status !== 'lunas',
]) }}>{{ str_replace('_', ' ', $status) }}</span>
