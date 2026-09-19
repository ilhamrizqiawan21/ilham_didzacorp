<script setup lang="ts">
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const visible = ref(false);
type ConfirmOptions = NonNullable<Parameters<NonNullable<Window['confirmDialog']>>[1]>;
const options = ref<ConfirmOptions & { message?: string }>({});
const dialog = ref<HTMLElement | null>(null);
let resolver: ((result: boolean) => void) | null = null;
let previousFocus: Element | null = null;

function close(result: boolean) {
    visible.value = false;

    if (resolver) {
        resolver(result);
        resolver = null;
    }

    if (previousFocus instanceof HTMLElement) {
        previousFocus.focus();
    }
}

function confirm(message: string, config: ConfirmOptions = {}): Promise<boolean> {
    if (resolver) close(false);
    previousFocus = document.activeElement;
    options.value = {
        message,
        title: config.title || 'Konfirmasi',
        confirmText: config.confirmText || 'Ya, lanjutkan',
        cancelText: config.cancelText || 'Batal',
        danger: config.danger === true,
    };
    visible.value = true;

    return new Promise<boolean>((resolve) => {
        resolver = resolve;
    });
}

function handleKeydown(event: KeyboardEvent) {
    if (!visible.value) {
        return;
    }

    if (event.key === 'Escape') {
        close(false);
        return;
    }

    if (event.key !== 'Tab' || !dialog.value) {
        return;
    }

    const focusable = [...dialog.value.querySelectorAll<HTMLButtonElement>('button:not([disabled])')];
    if (!focusable.length) return;

    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
}

watch(visible, async (isVisible) => {
    document.body.classList.toggle('modal-open', isVisible);

    if (isVisible) {
        await nextTick();
        dialog.value?.querySelector('button')?.focus();
    }
});

const confirmAction: NonNullable<Window['confirmAction']> = (message, callback, config = {}) => {
    confirm(message, config).then(callback);
};

onMounted(() => {
    window.confirmAction = confirmAction;
    window.confirmDialog = confirm;
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    close(false);
    if (window.confirmDialog === confirm) delete window.confirmDialog;
    if (window.confirmAction === confirmAction) delete window.confirmAction;
    document.body.classList.remove('modal-open');
    document.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <div v-if="visible" class="confirm-overlay" @click.self="close(false)">
        <div
            ref="dialog"
            class="confirm-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="confirmTitle"
            aria-describedby="confirmMessage"
        >
            <div class="confirm-icon" :class="options.danger ? 'danger' : 'warning'">
                <i class="bi" :class="options.danger ? 'bi-exclamation-triangle-fill' : 'bi-question-circle-fill'" aria-hidden="true"></i>
            </div>
            <h5 id="confirmTitle" class="confirm-title">{{ options.title }}</h5>
            <p id="confirmMessage" class="confirm-message">{{ options.message }}</p>
            <div class="confirm-actions">
                <button type="button" class="btn btn-outline-secondary" @click="close(false)">{{ options.cancelText }}</button>
                <button type="button" class="btn" :class="options.danger ? 'btn-danger' : 'btn-success'" @click="close(true)">
                    {{ options.confirmText }}
                </button>
            </div>
        </div>
    </div>
</template>
