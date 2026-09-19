<script setup lang="ts">
defineSlots<{ default?: () => unknown }>();
interface Props {
    responsive?: boolean;
    minWidth?: string | number;
    scrollHint?: boolean;
}

withDefaults(defineProps<Props>(), { responsive: true, minWidth: 720, scrollHint: true });
</script>

<template>
    <div
        :class="[responsive ? 'table-responsive' : '', 'app-table-wrapper']"
        :style="responsive ? { '--app-table-min-width': typeof minWidth === 'number' ? `${minWidth}px` : minWidth } : undefined"
        :tabindex="responsive ? 0 : undefined"
        :aria-label="responsive ? 'Tabel dapat digeser secara horizontal pada layar kecil' : undefined"
    >
        <div
            v-if="responsive && scrollHint"
            class="d-flex d-md-none align-items-center gap-2 px-3 py-2 border-bottom bg-body-tertiary text-muted small"
        >
            <i class="bi bi-arrow-left-right" aria-hidden="true"></i>
            <span>Geser tabel ke samping untuk melihat kolom lainnya.</span>
        </div>
        <slot />
    </div>
</template>
