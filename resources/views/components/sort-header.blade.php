@props(['column', 'label', 'align' => 'left', 'class' => ''])

@php
    $active = request('sort') === $column;
    $direction = $active && request('direction', 'asc') === 'asc' ? 'desc' : 'asc';
    $url = request()->fullUrlWithQuery(['sort' => $column, 'direction' => $direction, 'page' => 1]);
@endphp

<th scope="col"
    aria-sort="{{ $active ? (request('direction', 'asc') === 'asc' ? 'ascending' : 'descending') : 'none' }}"
    {{ $attributes->merge(['class' => "px-4 py-3 text-{$align} {$class}"]) }}>
    <a href="{{ $url }}"
       class="smart-sort {{ $align === 'right' ? 'justify-end' : '' }} {{ $active ? 'smart-sort-active' : '' }}"
       title="Urutkan berdasarkan {{ $label }}">
        <span>{{ $label }}</span>
        <span class="smart-sort-icon" aria-hidden="true">{{ $active ? (request('direction', 'asc') === 'asc' ? '↑' : '↓') : '↕' }}</span>
    </a>
</th>
