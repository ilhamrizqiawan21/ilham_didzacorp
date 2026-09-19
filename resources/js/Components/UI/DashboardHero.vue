<script setup lang="ts">
defineSlots<{ actions?: () => unknown }>();
defineProps({
    eyebrow: { type: String, default: '' },
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    icon: { type: String, default: 'bi-stars' },
    tone: { type: String, default: 'primary' },
    photoUrl: { type: String, default: '' },
    photoAlt: { type: String, default: 'Foto profil' },
});
</script>

<template>
    <section class="dashboard-hero" :class="`dashboard-hero-${tone}`">
        <div class="dashboard-hero-copy">
            <span v-if="eyebrow" class="dashboard-eyebrow">{{ eyebrow }}</span>
            <h1>{{ title }}</h1>
            <p v-if="subtitle">{{ subtitle }}</p>
            <div v-if="$slots.actions" class="dashboard-hero-actions">
                <slot name="actions" />
            </div>
        </div>
        <div class="dashboard-hero-orbit" :class="{ 'dashboard-hero-orbit-photo': photoUrl }" :aria-label="photoUrl ? photoAlt : undefined" :aria-hidden="photoUrl ? undefined : 'true'">
            <img v-if="photoUrl" :src="photoUrl" :alt="photoAlt" class="dashboard-hero-photo">
            <i v-else class="bi" :class="icon"></i>
        </div>
    </section>
</template>

<style scoped>
.dashboard-hero-orbit-photo {
    overflow: hidden;
    padding: 0;
}

.dashboard-hero-photo {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}
</style>
