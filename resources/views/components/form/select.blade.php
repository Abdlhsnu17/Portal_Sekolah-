@props([
    'name',
    'label',
    'options' => [],
    'value' => null,
    'required' => false,
    'placeholder' => null,
])

<x-form.search-select
    :name="$name"
    :label="$label"
    :options="$options"
    :value="$value"
    :required="$required"
    :placeholder="$placeholder ?? 'Cari pilihan...'"
    {{ $attributes }}
/>
