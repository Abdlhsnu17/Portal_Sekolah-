<dialog id="confirm-delete-dialog" aria-labelledby="confirm-delete-title"
        class="confirm-delete-dialog rounded-2xl border border-slate-200 bg-white p-0 text-slate-700 shadow-2xl backdrop:bg-slate-950/50">
    <div class="relative p-6">
        <button type="button" data-delete-cancel aria-label="Tutup" title="Tutup" class="absolute right-4 top-4 inline-flex size-9 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
            <x-icon name="close" class="size-5" />
        </button>
        <span class="mb-4 grid size-11 place-items-center rounded-xl bg-rose-50 text-rose-600"><x-icon name="trash" class="size-5" /></span>
        <h2 id="confirm-delete-title" class="text-lg font-bold text-brand-950">Hapus data ini?</h2>
        <p data-delete-description class="mt-1 text-sm text-slate-500">Data yang dihapus tidak dapat dikembalikan.</p>
        <div data-delete-reason-field class="mt-4 hidden">
            <label for="delete-reason" class="mb-1.5 block text-sm font-semibold text-slate-700">Alasan penghapusan <span class="text-rose-500">*</span></label>
            <textarea id="delete-reason" data-delete-reason-input rows="3" maxlength="500" class="w-full rounded-lg border-slate-200 text-sm focus:border-rose-500 focus:ring-rose-500" placeholder="Tuliskan alasan penghapusan..."></textarea>
        </div>
    </div>
    <div class="flex justify-end border-t border-slate-100 bg-slate-50/70 px-6 py-4">
        <button type="button" data-delete-confirm aria-label="Hapus" title="Hapus" class="inline-flex size-10 items-center justify-center rounded-lg bg-rose-600 text-white transition hover:bg-rose-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500">
            <x-icon name="trash" data-delete-confirm-icon class="size-5" />
            <span data-delete-confirm-label class="sr-only">Hapus</span>
        </button>
    </div>
</dialog>
<script>
    (() => {
        const dialog = document.getElementById('confirm-delete-dialog');
        if (!dialog) return;

        let pendingForm = null;
        document.addEventListener('submit', (event) => {
            if (!event.target.matches('form[data-confirm-delete], form[data-confirm-action]')) return;
            event.preventDefault();
            pendingForm = event.target;
            const isActionConfirmation = pendingForm.hasAttribute('data-confirm-action');
            const title = isActionConfirmation ? pendingForm.dataset.confirmTitle : pendingForm.dataset.deleteTitle;
            const description = isActionConfirmation ? pendingForm.dataset.confirmDescription : pendingForm.dataset.deleteDescription;
            const confirmButton = dialog.querySelector('[data-delete-confirm]');
            const confirmIcon = dialog.querySelector('[data-delete-confirm-icon]');
            const confirmLabel = dialog.querySelector('[data-delete-confirm-label]');
            const reasonField = dialog.querySelector('[data-delete-reason-field]');
            const reasonInput = dialog.querySelector('[data-delete-reason-input]');
            const requiresReason = pendingForm.hasAttribute('data-delete-reason-required');

            dialog.querySelector('#confirm-delete-title').textContent = title || 'Hapus data ini?';
            dialog.querySelector('[data-delete-description]').textContent = description || 'Data yang dihapus tidak dapat dikembalikan.';
            const confirmText = isActionConfirmation ? (pendingForm.dataset.confirmButton || 'Lanjutkan') : 'Hapus';
            confirmButton.setAttribute('aria-label', confirmText);
            confirmButton.setAttribute('title', confirmText);
            confirmIcon.hidden = isActionConfirmation;
            confirmLabel.textContent = confirmText;
            confirmLabel.classList.toggle('sr-only', !isActionConfirmation);
            confirmButton.classList.toggle('size-10', !isActionConfirmation);
            confirmButton.classList.toggle('px-4', isActionConfirmation);
            confirmButton.classList.toggle('bg-rose-600', !isActionConfirmation);
            confirmButton.classList.toggle('hover:bg-rose-700', !isActionConfirmation);
            confirmButton.classList.toggle('bg-emerald-700', isActionConfirmation);
            confirmButton.classList.toggle('hover:bg-emerald-800', isActionConfirmation);
            reasonField.classList.toggle('hidden', !requiresReason);
            reasonInput.required = requiresReason;
            reasonInput.value = '';
            dialog.showModal();
            if (requiresReason) reasonInput.focus();
        });

        dialog.querySelectorAll('[data-delete-cancel]').forEach((button) => button.addEventListener('click', () => dialog.close()));
        dialog.querySelector('[data-delete-confirm]').addEventListener('click', () => {
            if (!pendingForm) return;

            const reasonInput = dialog.querySelector('[data-delete-reason-input]');
            if (pendingForm.hasAttribute('data-delete-reason-required')) {
                if (!reasonInput.reportValidity()) return;
                let hiddenReason = pendingForm.querySelector('input[name="deletion_reason"]');
                if (!hiddenReason) {
                    hiddenReason = document.createElement('input');
                    hiddenReason.type = 'hidden';
                    hiddenReason.name = 'deletion_reason';
                    pendingForm.append(hiddenReason);
                }
                hiddenReason.value = reasonInput.value.trim();
            }

            HTMLFormElement.prototype.submit.call(pendingForm);
        });
        dialog.addEventListener('close', () => {
            pendingForm = null;
            dialog.querySelector('#confirm-delete-title').textContent = 'Hapus data ini?';
            dialog.querySelector('[data-delete-description]').textContent = 'Data yang dihapus tidak dapat dikembalikan.';
            const confirmButton = dialog.querySelector('[data-delete-confirm]');
            confirmButton.setAttribute('aria-label', 'Hapus');
            confirmButton.setAttribute('title', 'Hapus');
            dialog.querySelector('[data-delete-confirm-icon]').hidden = false;
            const confirmLabel = dialog.querySelector('[data-delete-confirm-label]');
            confirmLabel.textContent = 'Hapus';
            confirmLabel.classList.add('sr-only');
            confirmButton.classList.add('size-10');
            confirmButton.classList.remove('px-4');
            confirmButton.classList.remove('bg-emerald-700', 'hover:bg-emerald-800');
            confirmButton.classList.add('bg-rose-600', 'hover:bg-rose-700');
            const reasonField = dialog.querySelector('[data-delete-reason-field]');
            const reasonInput = dialog.querySelector('[data-delete-reason-input]');
            reasonField.classList.add('hidden');
            reasonInput.required = false;
            reasonInput.value = '';
        });
    })();
</script>
