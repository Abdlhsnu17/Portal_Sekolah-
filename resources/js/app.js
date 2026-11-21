import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('toastCenter', (initialToasts = []) => ({
    toasts: [],
    nextId: 1,
    init() { initialToasts.forEach((toast) => this.add(toast)); },
    add(detail = {}) {
        const toast = {
            id: this.nextId++,
            type: ['success', 'error', 'info'].includes(detail.type) ? detail.type : 'info',
            title: detail.title || 'Informasi',
            message: detail.message || 'Aksi sedang diproses.',
            duration: Number(detail.duration) > 0 ? Number(detail.duration) : 3000,
            visible: true,
        };
        this.toasts.push(toast);
        window.setTimeout(() => this.remove(toast.id), toast.duration);
    },
    remove(id) {
        const toast = this.toasts.find((item) => item.id === id);
        if (!toast || !toast.visible) return;
        toast.visible = false;
        window.setTimeout(() => { this.toasts = this.toasts.filter((item) => item.id !== id); }, 250);
    },
    toastClass(type) {
        return type === 'success' ? 'border-emerald-200/90' : type === 'error' ? 'border-rose-200/90' : 'border-brand-200/90';
    },
    iconClass(type) {
        return type === 'success' ? 'bg-emerald-50 text-emerald-600 ring-emerald-200' : type === 'error' ? 'bg-rose-50 text-rose-600 ring-rose-200' : 'bg-brand-50 text-brand-700 ring-brand-200';
    },
    progressClass(type) {
        return type === 'success' ? 'bg-emerald-500' : type === 'error' ? 'bg-rose-500' : 'bg-brand-600';
    },
}));

window.showToast = (message, type = 'info', title = null) => {
    window.dispatchEvent(new CustomEvent('toast', {
        detail: { message, type, title: title || (type === 'success' ? 'Berhasil' : 'Informasi') },
    }));
};

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.checkValidity()) return;
    const submitter = event.submitter;
    const label = submitter?.dataset.toastMessage || submitter?.textContent?.trim().replace(/\s+/g, ' ') || form.dataset.toastMessage || 'Permintaan';
    window.showToast(`${label} sedang diproses…`, 'info', 'Memproses');
}, true);

document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-toast-message]:not([type="submit"])');
    if (!trigger) return;
    window.showToast(trigger.dataset.toastMessage, trigger.dataset.toastType || 'info', trigger.dataset.toastTitle || null);
});

Alpine.start();

/**
 * Progressive enhancement untuk tabel admin. Tabel tetap berfungsi penuh tanpa
 * JavaScript; kontrol berikut hanya menambah ekspor, kepadatan, dan visibilitas
 * kolom pada halaman yang sedang ditampilkan.
 */
document.querySelectorAll('[data-smart-table]').forEach((table) => {
    const tableName = table.dataset.tableName || 'data';
    const headers = [...table.querySelectorAll('thead th')];
    const storageKey = `smart-table:${tableName}`;

    let preferences = { compact: false, hidden: [] };
    try {
        preferences = { ...preferences, ...JSON.parse(localStorage.getItem(storageKey) || '{}') };
    } catch (_) { /* Penyimpanan browser tidak tersedia. */ }

    const save = () => {
        try { localStorage.setItem(storageKey, JSON.stringify(preferences)); } catch (_) { /* abaikan */ }
    };

    const setColumnVisibility = (index, visible) => {
        table.querySelectorAll('tr').forEach((row) => {
            row.children[index]?.classList.toggle('smart-column-hidden', !visible);
        });
    };

    const toolbar = document.createElement('div');
    toolbar.className = 'smart-table-tools';
    toolbar.innerHTML = `
        <div class="smart-table-tools-top">
            <p class="smart-table-tools-label"><span class="smart-table-dot"></span>Kontrol tabel</p>
            <div class="smart-table-actions">
            <button type="button" data-density class="smart-table-button" aria-pressed="false">Baris ringkas</button>
            <button type="button" data-export class="smart-table-button">Ekspor CSV</button>
            </div>
        </div>
        <div class="smart-table-column-scroll" role="group" aria-label="Atur kolom yang ditampilkan">
            <span class="smart-table-column-caption">Kolom</span>
            <div class="smart-table-column-tabs"></div>
        </div>`;

    table.closest('.overflow-x-auto')?.before(toolbar);

    const densityButton = toolbar.querySelector('[data-density]');
    const applyDensity = () => {
        table.classList.toggle('smart-table-compact', preferences.compact);
        densityButton.setAttribute('aria-pressed', String(preferences.compact));
        densityButton.classList.toggle('smart-table-button-active', preferences.compact);
        densityButton.textContent = preferences.compact ? 'Baris normal' : 'Baris ringkas';
    };
    applyDensity();

    densityButton.addEventListener('click', () => {
        preferences.compact = !preferences.compact;
        applyDensity();
        save();
    });

    const columnTabs = toolbar.querySelector('.smart-table-column-tabs');
    headers.forEach((header, index) => {
        if (header.dataset.noColumnToggle !== undefined || index === headers.length - 1) return;

        const label = header.textContent.replace(/[↑↓↕]/g, '').trim();
        const button = document.createElement('button');
        const visible = !preferences.hidden.includes(index);
        button.type = 'button';
        button.className = `smart-table-column-tab ${visible ? 'smart-table-column-tab-active' : ''}`;
        button.setAttribute('aria-pressed', String(visible));
        button.innerHTML = `<span class="smart-table-column-check" aria-hidden="true">${visible ? '✓' : '+'}</span><span>${label}</span>`;
        setColumnVisibility(index, visible);
        button.addEventListener('click', () => {
            const nextVisible = button.getAttribute('aria-pressed') !== 'true';
            preferences.hidden = nextVisible
                ? preferences.hidden.filter((value) => value !== index)
                : [...new Set([...preferences.hidden, index])];
            setColumnVisibility(index, nextVisible);
            button.setAttribute('aria-pressed', String(nextVisible));
            button.classList.toggle('smart-table-column-tab-active', nextVisible);
            button.querySelector('.smart-table-column-check').textContent = nextVisible ? '✓' : '+';
            save();
        });
        columnTabs.append(button);
    });

    toolbar.querySelector('[data-export]').addEventListener('click', () => {
        const visibleIndexes = headers
            .map((header, index) => ({ header, index }))
            .filter(({ header, index }) => index !== headers.length - 1 && !header.classList.contains('smart-column-hidden'));
        const escapeCsv = (value) => `"${value.replace(/\s+/g, ' ').trim().replace(/"/g, '""')}"`;
        const rows = [visibleIndexes.map(({ header }) => escapeCsv(header.textContent.replace(/[↑↓↕]/g, '')))];

        table.querySelectorAll('tbody tr').forEach((row) => {
            if (row.children.length !== headers.length) return;
            rows.push(visibleIndexes.map(({ index }) => escapeCsv(row.children[index]?.innerText || '')));
        });

        const blob = new Blob([`\uFEFF${rows.map((row) => row.join(',')).join('\n')}`], { type: 'text/csv;charset=utf-8' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `${tableName}-${new Date().toISOString().slice(0, 10)}.csv`;
        link.click();
        window.setTimeout(() => URL.revokeObjectURL(link.href), 1000);
    });
});
