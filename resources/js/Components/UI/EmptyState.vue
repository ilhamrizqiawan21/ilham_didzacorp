<script setup lang="ts">
defineSlots<{ default?: () => unknown }>();
interface Props {
    title?: string;
    message?: string;
    icon?: string;
    tone?: string;
}

withDefaults(defineProps<Props>(), {
    title: 'Data belum tersedia', message: '', icon: 'bi-inbox', tone: 'neutral',
});
</script>

<template>
    <div class="empty-state" :class="`empty-state-${tone}`" role="status">
        <div class="empty-state-icon">
            <i class="bi" :class="icon" aria-hidden="true"></i>
        </div>
        <div class="empty-state-title">{{ title }}</div>
        <div v-if="message" class="empty-state-message">{{ message }}</div>
        <div v-if="$slots.default" class="empty-state-action">
            <slot />
        </div>
    </div>
</template>
