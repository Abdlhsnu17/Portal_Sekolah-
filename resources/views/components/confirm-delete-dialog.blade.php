<dialog id="confirm-delete-dialog" aria-labelledby="confirm-delete-title"
        class="confirm-delete-dialog rounded-2xl border border-slate-200 bg-white p-0 text-slate-700 shadow-2xl backdrop:bg-slate-950/50">
    <div class="p-6">
        <span class="mb-4 grid size-11 place-items-center rounded-xl bg-rose-50 text-rose-600"><x-icon name="trash" class="size-5" /></span>
        <h2 id="confirm-delete-title" class="text-lg font-bold text-brand-950">Hapus data ini?</h2>
        <p data-delete-description class="mt-1 text-sm text-slate-500">Data yang dihapus tidak dapat dikembalikan.</p>
    </div>
    <div class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50/70 px-6 py-4">
        <button type="button" data-delete-cancel class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-100">Batal</button>
        <button type="button" data-delete-confirm class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700">Hapus</button>
    </div>
</dialog>
<script>
    (() => {
        const dialog = document.getElementById('confirm-delete-dialog');
        if (!dialog) return;

        let pendingForm = null;
        document.addEventListener('submit', (event) => {
            if (!event.target.matches('form[data-confirm-delete]')) return;
            event.preventDefault();
            pendingForm = event.target;
            const title = pendingForm.dataset.deleteTitle;
            const description = pendingForm.dataset.deleteDescription;
            if (title) dialog.querySelector('#confirm-delete-title').textContent = title;
            if (description) dialog.querySelector('[data-delete-description]').textContent = description;
            dialog.showModal();
        });

        dialog.querySelector('[data-delete-cancel]').addEventListener('click', () => dialog.close());
        dialog.querySelector('[data-delete-confirm]').addEventListener('click', () => {
            if (pendingForm) HTMLFormElement.prototype.submit.call(pendingForm);
        });
        dialog.addEventListener('close', () => {
            pendingForm = null;
            dialog.querySelector('#confirm-delete-title').textContent = 'Hapus data ini?';
            dialog.querySelector('[data-delete-description]').textContent = 'Data yang dihapus tidak dapat dikembalikan.';
        });
    })();
</script>
