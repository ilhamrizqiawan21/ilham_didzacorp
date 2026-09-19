<script setup lang="ts">
defineSlots<{ actions?: () => unknown }>();
import type { PropType } from 'vue';
import type { QueueItem } from '../../types/ui';
import { Link } from '@inertiajs/vue3';
import Badge from './Badge.vue';
import EmptyState from './EmptyState.vue';

defineProps({
    title: { type: String, default: '' },
    icon: { type: String, default: 'bi-list-check' },
    items: { type: Array as PropType<QueueItem[]>, default: () => [] },
    emptyTitle: { type: String, default: 'Tidak ada antrean' },
    emptyMessage: { type: String, default: '' },
});
</script>

<template>
    <section class="workspace-panel action-queue">
        <header v-if="title" class="workspace-panel-header">
            <span class="workspace-panel-title">
                <i class="bi" :class="icon" aria-hidden="true"></i>
                {{ title }}
            </span>
            <slot name="actions" />
        </header>

        <div v-if="items.length" class="action-queue-list">
            <component
                :is="item.href ? Link : 'div'"
                v-for="item in items"
                :key="item.id || `${item.title}-${item.meta}`"
                :href="item.href"
                class="action-queue-item"
                :class="{ 'action-queue-link': item.href }"
            >
                <span class="action-queue-icon" :style="{ '--queue-accent': item.accent || 'var(--primary-500)' }">
                    <i class="bi" :class="item.icon || 'bi-dot'" aria-hidden="true"></i>
                </span>
                <span class="action-queue-copy">
                    <strong>{{ item.title }}</strong>
                    <small v-if="item.meta">{{ item.meta }}</small>
                    <small v-if="item.detail" class="action-queue-detail">{{ item.detail }}</small>
                </span>
                <Badge v-if="item.badge" :color="item.badgeColor || 'secondary'">{{ item.badge }}</Badge>
            </component>
        </div>

        <EmptyState v-else :title="emptyTitle" :message="emptyMessage" :icon="icon" />
    </section>
</template>
