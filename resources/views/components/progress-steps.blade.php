@props(['steps' => [], 'active' => 1, 'class' => ''])

<ol {{ $attributes->merge(['class' => 'flex items-center gap-3 overflow-x-auto pb-1 text-sm '.$class]) }} aria-label="Tahapan proses">
    @foreach ($steps as $index => $step)
        @php
            $number = $index + 1;
            $label = is_array($step) ? $step['label'] : $step;
            $isComplete = $number < $active;
            $isActive = $number === $active;
        @endphp
        <li class="flex shrink-0 items-center gap-3">
            <span class="flex items-center gap-2.5 {{ $isActive ? 'text-brand-800' : ($isComplete ? 'text-emerald-700' : 'text-slate-400') }}">
                <span class="grid size-9 shrink-0 place-items-center rounded-full text-sm font-bold {{ $isActive ? 'bg-brand-700 text-white' : ($isComplete ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500') }}">
                    @if ($isComplete)
                        <span aria-hidden="true">✓</span><span class="sr-only">Selesai: </span>
                    @else
                        {{ $number }}
                    @endif
                </span>
                <span class="font-semibold">{{ $label }}</span>
            </span>
            @if (! $loop->last)
                <span class="h-px w-8 bg-slate-200 sm:w-14" aria-hidden="true"></span>
            @endif
        </li>
    @endforeach
</ol>
