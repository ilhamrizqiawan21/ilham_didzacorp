<script setup lang="ts">
interface Props {
    label: string;
    value: string | number;
    icon?: string;
}

withDefaults(defineProps<Props>(), { icon: 'bi-bar-chart-fill' });
</script>

<template>
    <div class="stat-card">
        <div class="stat-icon">
            <i class="bi" :class="icon" aria-hidden="true"></i>
        </div>
        <div>
            <div class="stat-number">{{ value }}</div>
            <div class="stat-label">{{ label }}</div>
        </div>
    </div>
</template>
