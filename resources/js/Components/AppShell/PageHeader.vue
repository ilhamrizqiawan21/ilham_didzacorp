<script setup lang="ts">
defineSlots<{ actions?: () => unknown }>();
defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    icon: { type: String, default: '' },
});
</script>

<template>
    <div class="page-header">
        <div>
            <h4>
                <i v-if="icon" class="bi me-2" :class="icon" aria-hidden="true"></i>
                {{ title }}
            </h4>
            <p v-if="subtitle" class="text-muted mb-0">{{ subtitle }}</p>
        </div>
        <slot name="actions" />
    </div>
</template>
