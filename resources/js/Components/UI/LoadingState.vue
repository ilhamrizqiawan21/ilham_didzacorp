<script setup lang="ts">
defineProps({
    title: { type: String, default: 'Memuat data...' },
    message: { type: String, default: '' },
});
</script>

<template>
    <div class="loading-state" role="status" aria-live="polite">
        <span class="spinner-border loading-state-spinner" aria-hidden="true"></span>
        <div class="loading-state-title">{{ title }}</div>
        <div v-if="message" class="loading-state-message">{{ message }}</div>
    </div>
</template>
