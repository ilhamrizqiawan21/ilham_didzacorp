<script setup lang="ts">
defineSlots<{ action?: () => unknown }>();
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import Badge from './Badge.vue';

const props = defineProps({
    title: { type: String, default: '' },
    message: { type: String, default: '' },
    meta: { type: String, default: '' },
    icon: { type: String, default: '' },
    accent: { type: String, default: 'var(--primary-500)' },
    href: { type: String, default: '' },
    unread: { type: Boolean, default: false },
    compact: { type: Boolean, default: false },
});

const itemClass = computed(() => [
    'app-info-item',
    {
        'app-info-item-unread': props.unread,
        'app-info-item-compact': props.compact,
        'app-info-item-link': props.href,
    },
]);

const itemStyle = computed(() => ({
    '--info-accent': props.accent,
}));
</script>

<template>
    <Link v-if="href" :href="href" :class="itemClass" :style="itemStyle">
        <span v-if="icon" class="app-info-icon">
            <i class="bi" :class="icon" aria-hidden="true"></i>
        </span>
        <span class="app-info-body">
            <span class="app-info-title-row">
                <strong class="app-info-title">{{ title }}</strong>
                <Badge v-if="unread" color="danger" class="app-info-badge">Baru</Badge>
            </span>
            <span v-if="message" class="app-info-message">{{ message }}</span>
            <small v-if="meta" class="app-info-meta">{{ meta }}</small>
        </span>
        <span v-if="$slots.action" class="app-info-action">
            <slot name="action" />
        </span>
    </Link>

    <div v-else :class="itemClass" :style="itemStyle">
        <span v-if="icon" class="app-info-icon">
            <i class="bi" :class="icon" aria-hidden="true"></i>
        </span>
        <span class="app-info-body">
            <span class="app-info-title-row">
                <strong class="app-info-title">{{ title }}</strong>
                <Badge v-if="unread" color="danger" class="app-info-badge">Baru</Badge>
            </span>
            <span v-if="message" class="app-info-message">{{ message }}</span>
            <small v-if="meta" class="app-info-meta">{{ meta }}</small>
        </span>
        <span v-if="$slots.action" class="app-info-action">
            <slot name="action" />
        </span>
    </div>
</template>
