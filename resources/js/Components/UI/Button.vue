<script setup lang="ts">
defineSlots<{ default?: () => unknown }>();
import { Link } from '@inertiajs/vue3';
import type { Method } from '@inertiajs/core';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    type?: 'button' | 'submit' | 'reset';
    color?: string;
    size?: string;
    icon?: string;
    href?: string;
    method?: Method;
    as?: string;
}

withDefaults(defineProps<Props>(), {
    type: 'button', color: 'primary', size: 'sm', icon: '', href: '', method: 'get', as: '',
});
</script>

<template>
    <Link
        v-if="href"
        :href="href"
        :method="method"
        :as="as || (method.toLowerCase() === 'get' ? 'a' : 'button')"
        class="btn"
        :class="[`btn-${color}`, size ? `btn-${size}` : '']"
        v-bind="$attrs"
    >
        <i v-if="icon" class="bi" :class="[icon, $slots.default ? 'me-1' : '']" aria-hidden="true"></i>
        <slot />
    </Link>
    <button
        v-else
        :type="type"
        class="btn"
        :class="[`btn-${color}`, size ? `btn-${size}` : '']"
        v-bind="$attrs"
    >
        <i v-if="icon" class="bi" :class="[icon, $slots.default ? 'me-1' : '']" aria-hidden="true"></i>
        <slot />
    </button>
</template>
