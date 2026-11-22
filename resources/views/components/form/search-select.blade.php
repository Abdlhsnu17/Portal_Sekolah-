@props([
    'name',
    'label',
    'options' => [],
    'value' => null,
    'required' => false,
    'placeholder' => 'Cari pilihan...',
    'emptyMessage' => 'Data tidak ditemukan.',
])

@php($inputId = str_replace(['[', ']'], '-', $name).'-search')
<div
    x-data="{
        options: @js(collect($options)->map(fn ($label, $value) => ['value' => (string) $value, 'label' => (string) $label])->values()),
        selected: @js((string) old($name, $value ?? '')),
        search: '',
        open: false,
        get filteredOptions() {
            const query = this.search.trim().toLocaleLowerCase('id');
            return query ? this.options.filter((option) => option.label.toLocaleLowerCase('id').includes(query)) : this.options;
        },
        init() {
            this.search = this.options.find((option) => option.value === this.selected)?.label ?? '';
        },
        selectOption(option) {
            this.selected = option.value;
            this.search = option.label;
            this.open = false;
            $refs.search.setCustomValidity('');
        },
    }"
    @click.outside="open = false"
    {{ $attributes->only('class') }}
>
    <label for="{{ $inputId }}" class="mb-1.5 block text-sm font-medium text-slate-700">
        {{ $label }}
        @if ($required)<span class="text-rose-500">*</span>@endif
    </label>
    <div class="relative">
        <input type="hidden" name="{{ $name }}" :value="selected">
        <input id="{{ $inputId }}" x-ref="search" type="search" autocomplete="off" x-model="search"
               @if ($required) required @endif
               @focus="open = true"
               @input="selected = ''; open = true; $el.setCustomValidity('{{ $required ? 'Pilih dari daftar yang tersedia.' : '' }}')"
               @keydown.escape="open = false"
               @keydown.enter.prevent="if (filteredOptions.length === 1) selectOption(filteredOptions[0]); else open = true"
               :aria-expanded="open.toString()" aria-controls="{{ $inputId }}-options" aria-autocomplete="list" role="combobox"
               placeholder="{{ $placeholder }}"
               class="w-full rounded-lg border bg-white px-3.5 py-2.5 pr-10 text-sm outline-none transition {{ $errors->has($name) ? 'border-rose-300 bg-rose-50/40 focus:border-rose-400 focus:ring-2 focus:ring-rose-100' : 'border-slate-200 focus:border-brand-400 focus:ring-2 focus:ring-brand-100' }}">
        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400"><x-icon name="chevron-right" class="size-4 rotate-90" /></span>
        <div id="{{ $inputId }}-options" x-show="open" x-cloak x-transition role="listbox" aria-label="Daftar {{ strtolower($label) }}" class="absolute z-20 mt-2 max-h-60 w-full overflow-y-auto overscroll-contain rounded-xl border border-slate-200 bg-white py-1 shadow-lift">
            @unless ($required)
                <button type="button" role="option" @click="selected = ''; search = ''; open = false; $refs.search.setCustomValidity('')" class="block w-full px-3 py-2.5 text-left text-sm text-slate-500 hover:bg-brand-50 hover:text-brand-800">{{ $placeholder }}</button>
            @endunless
            <template x-for="option in filteredOptions" :key="option.value">
                <button type="button" role="option" :aria-selected="selected === option.value" @click="selectOption(option)" class="block w-full px-3 py-2.5 text-left text-sm text-slate-700 hover:bg-brand-50 hover:text-brand-800" :class="selected === option.value ? 'bg-brand-50 font-semibold text-brand-800' : ''" x-text="option.label"></button>
            </template>
            <p x-show="filteredOptions.length === 0" class="px-3 py-3 text-sm text-slate-500">{{ $emptyMessage }}</p>
        </div>
    </div>
    @error($name)<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
</div>
