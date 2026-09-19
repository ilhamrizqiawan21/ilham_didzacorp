import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import '../css/responsive-polish.css';
import { initColorMode } from './theme';

interface ConfirmOptions { title?: string; confirmText?: string; cancelText?: string; danger?: boolean }

function initLegacySidebar(): void {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggle = document.querySelector('[data-sidebar-toggle]');

    if (!sidebar || !overlay || !toggle) {
        return;
    }

    const setOpen = (open: boolean): void => {
        sidebar.classList.toggle('sidebar-open', open);
        overlay.classList.toggle('show', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    setOpen(window.innerWidth >= 992);
    toggle.addEventListener('click', () => setOpen(!sidebar.classList.contains('sidebar-open')));
    overlay.addEventListener('click', () => setOpen(false));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setOpen(false);
        }
    });
}

function escapeHtml(value: unknown): string {
    const entities: Record<string, string> = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
    };

    return String(value).replace(/[&<>"']/g, (match) => entities[match] ?? match);
}

const confirmAction = (message: string, callback: (confirmed: boolean) => void, options: ConfirmOptions = {}): void => {
    const title = options.title || 'Konfirmasi';
    const confirmText = options.confirmText || 'Ya, lanjutkan';
    const cancelText = options.cancelText || 'Batal';
    const isDanger = options.danger === true;
    const overlay = document.createElement('div');
    const previousFocus = document.activeElement as HTMLElement | null;

    overlay.className = 'confirm-overlay';
    overlay.innerHTML = '<div class="confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="confirmTitle" aria-describedby="confirmMessage">' +
        `<div class="confirm-icon ${isDanger ? 'danger' : 'warning'}"><i class="bi ${isDanger ? 'bi-exclamation-triangle-fill' : 'bi-question-circle-fill'}" aria-hidden="true"></i></div>` +
        `<h5 id="confirmTitle" class="confirm-title">${escapeHtml(title)}</h5>` +
        `<p id="confirmMessage" class="confirm-message">${escapeHtml(message)}</p>` +
        '<div class="confirm-actions">' +
        `<button type="button" id="confirmCancel" class="btn btn-outline-secondary">${escapeHtml(cancelText)}</button>` +
        `<button type="button" id="confirmOk" class="btn ${isDanger ? 'btn-danger' : 'btn-success'}">${escapeHtml(confirmText)}</button>` +
        '</div></div>';

    const close = (result: boolean): void => {
        if (overlay.parentNode) {
            overlay.remove();
        }
        document.removeEventListener('keydown', escapeHandler);
        if (previousFocus && typeof previousFocus.focus === 'function') {
            previousFocus.focus();
        }
        callback(result);
    };
    const escapeHandler = (event: KeyboardEvent): void => {
        if (event.key === 'Escape') {
            close(false);
            return;
        }

        if (event.key === 'Tab') {
            const focusable = overlay.querySelectorAll<HTMLButtonElement>('button');
            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last?.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first?.focus();
            }
        }
    };

    document.body.appendChild(overlay);
    const confirmButton = overlay.querySelector<HTMLButtonElement>('#confirmOk');
    const cancelButton = overlay.querySelector<HTMLButtonElement>('#confirmCancel');
    if (!confirmButton || !cancelButton) return;
    confirmButton.onclick = () => close(true);
    cancelButton.onclick = () => close(false);
    overlay.onclick = (event) => {
        if (event.target === overlay) {
            close(false);
        }
    };
    document.addEventListener('keydown', escapeHandler);
    confirmButton.focus();
};
window.confirmAction = confirmAction;

function setFormLoading(form: HTMLFormElement | null): void {
    if (!form || form.dataset.loading === 'true' || form.dataset.noLoading === 'true') {
        return;
    }

    form.dataset.loading = 'true';
    form.querySelectorAll<HTMLButtonElement | HTMLInputElement>('button[type="submit"], input[type="submit"]').forEach((button) => {
        button.dataset.originalHtml = button.innerHTML;
        button.disabled = true;
        button.classList.add('is-loading');

        if (button.tagName === 'BUTTON') {
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span><span>Memproses...</span>';
        }
    });
}

document.addEventListener('click', (event) => {
    const el = (event.target as Element | null)?.closest<HTMLElement>('[data-confirm]');

    if (!el) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();

    const msg = el.getAttribute('data-confirm') || 'Anda yakin ingin melanjutkan?';
    const danger = el.className.indexOf('danger') !== -1 || el.getAttribute('data-danger') === 'true';

    confirmAction(msg, (ok) => {
        if (!ok) {
            return;
        }

        const parentForm = el.closest('form');
        const formAction = el.getAttribute('data-action');
        const method = (el.getAttribute('data-method') || 'get').toUpperCase();

        if (parentForm) {
            if (el instanceof HTMLButtonElement && el.name) {
                const submitValue = document.createElement('input');
                submitValue.type = 'hidden';
                submitValue.name = el.name;
                submitValue.value = el.value;
                parentForm.appendChild(submitValue);
            }
            setFormLoading(parentForm);
            parentForm.submit();
        } else if (formAction) {
            const form = document.createElement('form');
            form.method = method === 'POST' ? 'POST' : 'GET';
            form.action = formAction;

            if (method === 'DELETE') {
                form.innerHTML = '<input type="hidden" name="_method" value="DELETE">';
                form.method = 'POST';
            }

            const csrf = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]');
            if (csrf && method !== 'GET') {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = '_token';
                input.value = csrf.content;
                form.appendChild(input);
            }

            document.body.appendChild(form);
            setFormLoading(form);
            form.submit();
        } else if (el instanceof HTMLAnchorElement && el.href) {
            window.location.href = el.href;
        }
    }, { danger, title: el.getAttribute('data-title') || 'Konfirmasi' });
});

document.addEventListener('submit', (event) => {
    const submitEvent = event as SubmitEvent;
    const form = submitEvent.target as HTMLFormElement | null;
    const submitter = submitEvent.submitter as HTMLButtonElement | HTMLInputElement | null;

    if (form && submitter?.name && !form.querySelector(`input[type="hidden"][data-submit-proxy="${submitter.name}"]`)) {
        const submitValue = document.createElement('input');
        submitValue.type = 'hidden';
        submitValue.name = submitter.name;
        submitValue.value = submitter.value;
        submitValue.dataset.submitProxy = submitter.name;
        form.appendChild(submitValue);
    }

    setFormLoading(form);
});

document.addEventListener('DOMContentLoaded', async () => {
    initColorMode();
    initLegacySidebar();
});
