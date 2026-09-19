<script setup lang="ts">
defineSlots<{ default?: () => unknown }>();
interface Props {
    color?: string;
    icon?: string;
    label?: string;
}

withDefaults(defineProps<Props>(), { color: 'primary', icon: '', label: '' });
</script>

<template>
    <span class="badge app-badge" :class="`bg-${color}`" :aria-label="label || undefined">
        <i v-if="icon" class="bi me-1" :class="icon" aria-hidden="true"></i>
        <slot />
    </span>
</template>
