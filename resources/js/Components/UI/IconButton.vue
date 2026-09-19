<script setup lang="ts">
import Button from './Button.vue';
import type { Method } from '@inertiajs/core';

interface Props {
    icon: string;
    label: string;
    color?: string;
    href?: string;
    method?: Method;
    type?: 'button' | 'submit' | 'reset';
}

withDefaults(defineProps<Props>(), {
    color: 'outline-secondary', href: '', method: 'get', type: 'button',
});
</script>

<template>
    <Button
        class="btn-icon"
        :href="href"
        :method="method"
        :type="type"
        :color="color"
        size=""
        :icon="icon"
        :aria-label="label"
        :title="label"
    />
</template>
