<script setup lang="ts">
defineSlots<{ default?: () => unknown }>();
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ConfirmDialog from '../Components/AppShell/ConfirmDialog.vue';
import CommandPalette from '../Components/AppShell/CommandPalette.vue';
import NavigationLoading from '../Components/AppShell/NavigationLoading.vue';
import Sidebar from '../Components/AppShell/Sidebar.vue';
import ToastStack from '../Components/AppShell/ToastStack.vue';
import Topbar from '../Components/AppShell/Topbar.vue';
import { sidebarMenu } from '../Components/AppShell/sidebarMenu';
import type { AppPageProps } from '../types';

interface Props { title?: string; }
const props = withDefaults(defineProps<Props>(), { title: '' });

const page = usePage<AppPageProps>();
const sidebarOpen = ref(false);
const isMobileViewport = ref(false);
const commandOpen = ref(false);
const school = computed(() => page.props.school);
const user = computed(() => page.props.auth?.user ?? null);
const notifications = computed(() => page.props.notifications);
const capabilities = computed(() => page.props.capabilities);
const pageTitle = computed(() => props.title || document.title.replace(' - LMS Sekolah', '') || 'Dashboard');
const shellClass = computed(() => `app-shell app-shell-${user.value?.role ?? 'guest'}`);
const sidebarOverlayVisible = computed(() => isMobileViewport.value && sidebarOpen.value);
const commandItems = computed(() => sidebarMenu(user.value?.role, capabilities.value));

function syncSidebarWithViewport() {
    const isDesktop = window.matchMedia
        ? window.matchMedia('(min-width: 992px)').matches
        : window.innerWidth >= 992;

    isMobileViewport.value = !isDesktop;
    sidebarOpen.value = isDesktop;
}

onMounted(() => {
    syncSidebarWithViewport();
    window.addEventListener('resize', syncSidebarWithViewport, { passive: true });
    window.addEventListener('keydown', openCommandShortcut);
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', syncSidebarWithViewport);
    window.removeEventListener('keydown', openCommandShortcut);
});

function openCommandShortcut(event: KeyboardEvent) {
    const target = event.target as HTMLElement | null;
    const typing = Boolean(target && (['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName) || target.isContentEditable));
    if (!typing && (event.key === '/' || ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k'))) {
        event.preventDefault();
        commandOpen.value = true;
    }
}

function closeSidebar() {
    sidebarOpen.value = false;
}
</script>

<template>
    <div :class="[shellClass, { 'sidebar-is-open': sidebarOpen }]" @keydown.esc.window="closeSidebar">
        <a href="#mainContent" class="skip-link">Lewati ke konten utama</a>

        <div class="sidebar-overlay" :class="{ show: sidebarOverlayVisible }" @click="closeSidebar"></div>

        <Topbar
            :school="school"
            :user="user"
            :page-title="pageTitle"
            :notifications="notifications"
            :sidebar-open="sidebarOpen"
            @toggle-sidebar="sidebarOpen = !sidebarOpen"
            @open-command="commandOpen = true"
        />

        <Sidebar
            :open="sidebarOpen"
            :school="school"
            :user="user"
            :capabilities="capabilities"
        />

        <main id="mainContent" class="main-content" tabindex="-1">
            <div class="page-content">
                <slot />
            </div>
            <footer class="app-footer">
                <span>&copy; {{ new Date().getFullYear() }} {{ school.name }}</span>
                <span v-if="school.app_name" class="app-footer-separator" aria-hidden="true">•</span>
                <span v-if="school.app_name">{{ school.app_name }}</span>
            </footer>
        </main>

        <ToastStack />
        <ConfirmDialog />
        <CommandPalette v-model:open="commandOpen" :items="commandItems" />
        <NavigationLoading />
    </div>
</template>

<style scoped>
.app-footer {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    min-height: 52px;
    padding: 0.9rem 1rem 1.2rem;
    color: var(--text-muted, #64748b);
    font-size: 0.72rem;
    text-align: center;
}

.app-footer-separator {
    opacity: 0.5;
}

@media (max-width: 991.98px) {
    .app-footer {
        padding-bottom: 5.75rem;
    }
}

@media (max-width: 575.98px) {
    .app-footer {
        flex-wrap: wrap;
        line-height: 1.4;
    }
}
</style>
