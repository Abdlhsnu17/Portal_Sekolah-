@props(['years', 'classes', 'selectedYearId', 'selectedClassId'])

<div class="mb-5 space-y-3">
    <div class="flex gap-2 overflow-x-auto border-b border-slate-200" role="tablist" aria-label="Tahun ajaran">
        @forelse($years as $year)
            <a href="{{ route('admin.finance.index', ['academic_year_id' => $year->id]) }}" role="tab" aria-selected="{{ (int) $selectedYearId === $year->id ? 'true' : 'false' }}" class="shrink-0 border-b-2 px-4 py-3 text-sm font-semibold {{ (int) $selectedYearId === $year->id ? 'border-brand-700 text-brand-800' : 'border-transparent text-slate-500 hover:text-brand-700' }}">{{ $year->name }}</a>
        @empty
            <span class="px-4 py-3 text-sm text-slate-500">Belum ada tahun ajaran.</span>
        @endforelse
    </div>
    @if($classes->isNotEmpty())
        <div class="flex flex-wrap gap-2" aria-label="Filter rombel">
            <a href="{{ route('admin.finance.index', ['academic_year_id' => $selectedYearId]) }}" class="rounded-full px-3 py-1.5 text-sm font-semibold {{ ! $selectedClassId ? 'bg-brand-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">Semua rombel</a>
            @foreach($classes as $class)
                <a href="{{ route('admin.finance.index', ['academic_year_id' => $selectedYearId, 'school_class_id' => $class->id]) }}" class="rounded-full px-3 py-1.5 text-sm font-semibold {{ (int) $selectedClassId === $class->id ? 'bg-brand-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">{{ $class->name }}</a>
            @endforeach
        </div>
    @endif
</div>
