<script setup lang="ts">
import type { PropType } from 'vue';
import type { QuickAction } from '../../types/ui';
import Button from './Button.vue';

defineProps({
    actions: { type: Array as PropType<QuickAction[]>, default: () => [] },
});
</script>

<template>
    <div class="quick-action-bar">
        <Button
            v-for="action in actions"
            :key="action.label"
            :href="action.href"
            :method="action.method || 'get'"
            :color="action.color || 'light'"
            size=""
            :icon="action.icon"
            class="quick-action"
        >
            {{ action.label }}
        </Button>
    </div>
</template>
