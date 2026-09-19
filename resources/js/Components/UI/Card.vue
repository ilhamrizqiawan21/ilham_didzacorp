<script setup lang="ts">
defineSlots<{ default?: () => unknown; actions?: () => unknown; footer?: () => unknown }>();
interface Props {
    title?: string;
    icon?: string;
    headerClass?: string;
    bodyClass?: string;
    footerClass?: string;
}

withDefaults(defineProps<Props>(), {
    title: '', icon: '', headerClass: '', bodyClass: '', footerClass: '',
});
</script>

<template>
    <div class="card app-card">
        <div v-if="title || icon || $slots.actions" class="card-header" :class="headerClass">
            <div class="app-card-title">
                <i v-if="icon" class="bi" :class="icon" aria-hidden="true"></i>
                <span v-if="title">{{ title }}</span>
            </div>
            <div v-if="$slots.actions" class="app-card-actions">
                <slot name="actions" />
            </div>
        </div>

        <div class="card-body" :class="bodyClass">
            <slot />
        </div>

        <div v-if="$slots.footer" class="card-footer" :class="footerClass">
            <slot name="footer" />
        </div>
    </div>
</template>
