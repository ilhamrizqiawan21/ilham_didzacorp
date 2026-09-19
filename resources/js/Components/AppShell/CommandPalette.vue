<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

import type { SidebarItem, SidebarMenuEntry } from '../../types/navigation';

const props = withDefaults(defineProps<{ open?: boolean; items?: SidebarMenuEntry[] }>(), {
    open: false, items: () => [],
});
const emit = defineEmits<{ 'update:open': [open: boolean] }>();
const query = ref('');
const input = ref<HTMLInputElement | null>(null);
const activeIndex = ref(0);
const dialog = ref<HTMLElement | null>(null);
let previousFocus: HTMLElement | null = null;

const results = computed(() => {
    const term = query.value.trim().toLowerCase();
    const commands = props.items.filter((item) => item.type === 'item');
    return term ? commands.filter((item) => item.label.toLowerCase().includes(term)) : commands;
});

watch(() => props.open, async (isOpen) => {
    if (!isOpen) {
        previousFocus?.focus();
        return;
    }
    previousFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    query.value = '';
    activeIndex.value = 0;
    await nextTick();
    input.value?.focus();
});

watch(results, () => {
    activeIndex.value = 0;
});

function close() {
    emit('update:open', false);
}

function visit(item: SidebarItem) {
    close();
    if (item.inertia) router.visit(item.href);
    else window.location.assign(item.href);
}

function moveActive(direction: number) {
    if (!results.value.length) return;
    activeIndex.value = (activeIndex.value + direction + results.value.length) % results.value.length;
}

function visitActive() {
    const item = results.value[activeIndex.value];
    if (item) visit(item);
}

function trapFocus(event: KeyboardEvent) {
    const elements = dialog.value?.querySelectorAll<HTMLElement>('input, button');
    if (!elements?.length) return;
    const first = elements[0];
    const last = elements[elements.length - 1];
    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
}
</script>

<template>
    <div v-if="open" class="command-palette-backdrop" @click.self="close">
        <section ref="dialog" class="command-palette" role="dialog" aria-modal="true" aria-label="Akses cepat" @keydown.esc.stop="close" @keydown.tab="trapFocus">
            <div class="command-palette-input">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input
                    ref="input"
                    v-model="query"
                    type="search"
                    placeholder="Cari menu..."
                    @keydown.down.prevent="moveActive(1)"
                    @keydown.up.prevent="moveActive(-1)"
                    @keydown.enter.prevent="visitActive"
                >
                <kbd>Esc</kbd>
            </div>
            <div class="command-palette-results">
                <button v-for="(item, index) in results" :key="item.href" type="button" class="command-palette-item" :class="{ 'is-active': index === activeIndex }" @mouseenter="activeIndex = index" @click="visit(item)">
                    <i class="bi" :class="item.icon" aria-hidden="true"></i>
                    <span>{{ item.label }}</span>
                    <i class="bi bi-arrow-return-left command-palette-enter" aria-hidden="true"></i>
                </button>
                <p v-if="!results.length" class="command-palette-empty">Menu tidak ditemukan.</p>
            </div>
        </section>
    </div>
</template>
