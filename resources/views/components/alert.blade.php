@php
    $toastAwal = [];

    if (session('success')) {
        $toastAwal[] = ['type' => 'success', 'title' => 'Berhasil', 'message' => session('success')];
    }

    if ($errors->any()) {
        $toastAwal[] = [
            'type' => 'error',
            'title' => 'Periksa kembali isian Anda',
            'message' => $errors->count() === 1 ? $errors->first() : $errors->count().' isian masih perlu diperbaiki.',
        ];
    }
@endphp

<div x-data="toastCenter(@js($toastAwal))" @toast.window="add($event.detail)"
     class="pointer-events-none fixed inset-x-4 bottom-4 z-[100] flex flex-col-reverse items-end gap-3 sm:left-auto sm:right-5 sm:bottom-5 sm:w-[24rem]"
     role="region" aria-label="Notifikasi">
    <template x-for="toast in toasts" :key="toast.id">
        <section x-show="toast.visible"
                 x-transition:enter="transition duration-300 ease-out"
                 x-transition:enter-start="translate-y-3 opacity-0 sm:translate-x-5 sm:translate-y-0"
                 x-transition:enter-end="translate-x-0 translate-y-0 opacity-100"
                 x-transition:leave="transition duration-200 ease-in"
                 x-transition:leave-start="translate-x-0 opacity-100"
                 x-transition:leave-end="translate-y-2 opacity-0 sm:translate-x-5 sm:translate-y-0"
                 class="toast-premium pointer-events-auto relative w-full overflow-hidden rounded-2xl border bg-white/95 shadow-[0_20px_55px_-18px_rgba(15,23,42,.38)] backdrop-blur-xl"
                 :class="toastClass(toast.type)" role="status" aria-live="polite">
            <div class="flex gap-3.5 p-4">
                <div class="grid size-10 shrink-0 place-items-center rounded-xl ring-1" :class="iconClass(toast.type)">
                    <svg x-show="toast.type === 'success'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" class="size-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" /></svg>
                    <svg x-show="toast.type === 'error'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" class="size-5" aria-hidden="true"><path stroke-linecap="round" d="M12 8v5m0 3h.01" /><circle cx="12" cy="12" r="9" /></svg>
                    <svg x-show="toast.type !== 'success' && toast.type !== 'error'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-5" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path stroke-linecap="round" d="M12 11v5m0-8h.01" /></svg>
                </div>

                <div class="min-w-0 flex-1 pt-0.5">
                    <div class="flex items-center gap-2">
                        <p class="font-display text-sm font-bold text-brand-950" x-text="toast.title"></p>
                        <span class="rounded-full bg-accent-50 px-2 py-0.5 text-[9px] font-extrabold uppercase tracking-[.13em] text-accent-700 ring-1 ring-accent-200/80">Portal</span>
                    </div>
                    <p class="mt-1 text-[13px] leading-5 text-slate-600" x-text="toast.message"></p>
                </div>

                <button type="button" @click="remove(toast.id)" class="grid size-7 shrink-0 place-items-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Tutup notifikasi">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4" aria-hidden="true"><path stroke-linecap="round" d="m7 7 10 10M17 7 7 17" /></svg>
                </button>
            </div>

            <div class="absolute inset-x-0 bottom-0 h-1 bg-slate-100/80">
                <div class="toast-progress h-full rounded-r-full" :class="progressClass(toast.type)" :style="`animation-duration: ${toast.duration}ms`"></div>
            </div>
        </section>
    </template>
</div>
