<script setup lang="ts">
defineSlots<{ default?: () => unknown }>();
defineProps({
    title: { type: String, default: 'Terjadi kesalahan' },
    message: { type: String, default: 'Data tidak dapat dimuat saat ini.' },
    retryLabel: { type: String, default: 'Coba lagi' },
    showRetry: { type: Boolean, default: true },
});

defineEmits<{ retry: [] }>();
</script>

<template>
    <div class="error-state" role="alert">
        <div class="error-state-icon">
            <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
        </div>
        <div class="error-state-title">{{ title }}</div>
        <div class="error-state-message">{{ message }}</div>
        <button v-if="showRetry" type="button" class="btn btn-sm btn-outline-danger" @click="$emit('retry')">
            <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>{{ retryLabel }}
        </button>
        <div v-if="$slots.default" class="error-state-action">
            <slot />
        </div>
    </div>
</template>
