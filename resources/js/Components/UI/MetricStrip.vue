<script setup lang="ts">
import type { PropType } from 'vue';
import type { MetricItem } from '../../types/ui';
import { Link } from '@inertiajs/vue3';

defineProps({
    items: { type: Array as PropType<MetricItem[]>, default: () => [] },
});
</script>

<template>
    <div class="metric-strip">
        <component
            :is="item.href ? Link : 'div'"
            v-for="item in items"
            :key="item.label"
            :href="item.href"
            class="metric-tile"
            :class="[`metric-${item.tone || 'primary'}`, { 'metric-tile-link': item.href }]"
        >
            <span class="metric-icon">
                <i class="bi" :class="item.icon || 'bi-activity'" aria-hidden="true"></i>
            </span>
            <span class="metric-copy">
                <strong>{{ item.value }}</strong>
                <small>{{ item.label }}</small>
            </span>
        </component>
    </div>
</template>
