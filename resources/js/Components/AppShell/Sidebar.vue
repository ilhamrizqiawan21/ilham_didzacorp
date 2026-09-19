<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import SidebarLink from './SidebarLink.vue';
import { sidebarMenu } from './sidebarMenu';
import type { AppPageProps, AuthUser, Capabilities, SchoolBranding, SidebarItem } from '../../types';

interface Props { open?: boolean; school: SchoolBranding; user?: AuthUser | null; capabilities?: Capabilities; }
const props = withDefaults(defineProps<Props>(), { open: false, user: null, capabilities: () => ({ has_wali_kelas: false }) });

const page = usePage<AppPageProps>();
const menu = computed(() => sidebarMenu(props.user?.role, props.capabilities));
const mobileMenu = computed<SidebarItem[]>(() => menu.value
    .filter((entry): entry is SidebarItem => entry.type === 'item' && Boolean(entry.inertia && entry.href))
    .slice(0, 5));

const currentPath = computed(() => {
    const url = page.url || '/';
    return url.split('?')[0].split('#')[0] || '/';
});

function isActive(entry: SidebarItem) {
    return entry.activePrefixes?.some((prefix) => currentPath.value === prefix || currentPath.value.startsWith(`${prefix}/`));
}
</script>

<template>
    <aside id="sidebar" class="sidebar modern-sidebar" :class="{ 'sidebar-open': open }">
        <div class="sidebar-header">
            <div class="sidebar-logo-icon">
                <img :src="school.logo_url" :alt="`Logo ${school.name}`" class="app-logo-md" width="36" height="36" decoding="async">
            </div>
            <div class="sidebar-logo-text">
                <span class="sidebar-logo-title">{{ school.app_name }}</span>
                <span class="sidebar-logo-sub">{{ school.name }}</span>
            </div>
        </div>

        <div class="sidebar-user">
            <img
                v-if="user?.foto_url"
                :src="user.foto_url"
                :alt="`Foto ${user.nama_lengkap ?? 'pengguna'}`"
                class="sidebar-user-avatar sidebar-user-photo"
                width="40"
                height="40"
                decoding="async"
            >
            <div v-else class="sidebar-user-avatar"><i class="bi bi-person-fill" aria-hidden="true"></i></div>
            <div>
                <div class="sidebar-user-name">{{ user?.nama_lengkap ?? '-' }}</div>
                <div class="sidebar-user-role">{{ user?.role_label ?? '-' }}</div>
            </div>
        </div>

        <nav class="sidebar-nav" aria-label="Navigasi utama">
            <ul class="sidebar-menu">
                <li v-for="(entry, index) in menu" :key="`${entry.type}-${entry.label}-${index}`">
                    <div v-if="entry.type === 'section'" class="nav-section">{{ entry.label }}</div>
                    <SidebarLink
                        v-else
                        :entry="entry"
                        :active="isActive(entry)"
                    />
                </li>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-footer-title">{{ school.short_name }}</div>
            <div class="sidebar-footer-sub">Tahun {{ new Date().getFullYear() }}</div>
        </div>
    </aside>

    <nav v-if="mobileMenu.length" class="mobile-bottom-nav" aria-label="Navigasi cepat">
        <Link
            v-for="entry in mobileMenu"
            :key="entry.href"
            :href="entry.href"
            prefetch="hover"
            class="mobile-bottom-link"
            :class="{ active: isActive(entry) }"
            :aria-current="isActive(entry) ? 'page' : undefined"
        >
            <i class="bi" :class="entry.icon" aria-hidden="true"></i>
            <span>{{ entry.label }}</span>
        </Link>
    </nav>
</template>

<style scoped>
.sidebar-user-photo {
    display: block;
    object-fit: cover;
}
</style>
