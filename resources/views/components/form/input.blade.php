@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'required' => false,
    'placeholder' => null,
])

<div {{ $attributes->only('class') }} @if ($type === 'password') x-data="{ tampilkanSandi: false }" @endif>
    <label for="{{ $name }}" class="block text-sm font-medium text-slate-700">
        {{ $label }}
        @if ($required)<span class="text-rose-500">*</span>@endif
    </label>
    <div @if ($type === 'password') class="relative mt-1.5" @endif>
    <input @if ($type === 'password') :type="tampilkanSandi ? 'text' : 'password'" @else type="{{ $type }}" @endif name="{{ $name }}" id="{{ $name }}"
           value="{{ old($name, $value) }}"
           @if ($required) required @endif
           @if ($placeholder) placeholder="{{ $placeholder }}" @endif
           {{ $attributes->except('class') }}
           class="{{ $type === 'password' ? 'w-full pr-12' : 'mt-1.5 w-full' }} rounded-lg border px-3.5 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:ring-2 {{ $errors->has($name) ? 'border-rose-300 bg-rose-50/40 focus:border-rose-400 focus:ring-rose-100' : 'border-slate-200 focus:border-brand-400 focus:ring-brand-100' }}">
    @if ($type === 'password')
        <button type="button" @click="tampilkanSandi = !tampilkanSandi" :aria-label="tampilkanSandi ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'" :aria-pressed="tampilkanSandi" class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-500 transition hover:text-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500" autocomplete="off">
            <svg x-show="!tampilkanSandi" class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.5-6.75 9.75-6.75S21.75 12 21.75 12 18.25 18.75 12 18.75 2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg x-show="tampilkanSandi" x-cloak class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18M10.58 10.59a2 2 0 0 0 2.83 2.83M9.88 5.24A10.9 10.9 0 0 1 12 5.04c6.25 0 9.75 6.96 9.75 6.96a17 17 0 0 1-3.1 3.95M6.18 6.2C3.65 7.9 2.25 12 2.25 12s3.5 6.96 9.75 6.96c1.26 0 2.4-.3 3.4-.78"/></svg>
        </button>
    @endif
    </div>
    @error($name)
        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
    @enderror
</div>
