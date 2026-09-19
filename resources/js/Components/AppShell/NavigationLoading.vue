<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';

const loading = ref(false);
let removeStartListener: (() => void) | null = null;
let removeFinishListener: (() => void) | null = null;

onMounted(() => {
    removeStartListener = router.on('start', () => {
        loading.value = true;
    });
    removeFinishListener = router.on('finish', () => {
        loading.value = false;
    });
});

onUnmounted(() => {
    removeStartListener?.();
    removeFinishListener?.();
});
</script>

<template>
    <div v-show="loading" class="inertia-loading-bar" aria-hidden="true"></div>
</template>

<style scoped>
.inertia-loading-bar {
    position: fixed;
    top: 0;
    left: 0;
    z-index: 100000;
    width: 100%;
    height: 3px;
    background: linear-gradient(90deg, var(--primary-300), var(--gold-400), var(--primary-500));
    animation: loading-slide 1s ease-in-out infinite;
}

@keyframes loading-slide {
    0% { transform: translateX(-70%); }
    100% { transform: translateX(70%); }
}
</style>
