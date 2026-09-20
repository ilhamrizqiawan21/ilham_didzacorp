<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import type { AuthUser, NotificationsShare, SchoolBranding, UserRole } from '../../types';

interface Props { school: SchoolBranding; user?: AuthUser | null; pageTitle?: string; notifications?: NotificationsShare; sidebarOpen?: boolean; }
const props = withDefaults(defineProps<Props>(), { user: null, pageTitle: 'Dashboard', notifications: () => ({ route: null, mark_all_route: null, unread_count: 0, latest: [] }), sidebarOpen: false });
const emit = defineEmits<{ 'toggle-sidebar': []; 'open-command': [] }>();
function logout() { router.post('/logout'); }
function profileHref(role: UserRole | null | undefined): string | undefined { if (role === 'guru') return '/guru/pengaturan'; if (role === 'siswa') return '/siswa/pengaturan'; return undefined; }
function profileIsInertia(role: UserRole | null | undefined): boolean { return ['guru', 'siswa'].includes(role as string); }
</script>

<template>
    <header class="topbar modern-topbar">
        <button class="topbar-toggle-btn" type="button" :aria-label="sidebarOpen ? 'Tutup menu' : 'Buka menu'" aria-controls="sidebar" :aria-expanded="sidebarOpen" @click="emit('toggle-sidebar')"><i class="bi bi-list" aria-hidden="true"></i></button>
        <div class="topbar-brand">
            <div class="topbar-logo-icon"><img :src="school.logo_url" :alt="`Logo ${school.name}`" class="app-logo-sm" width="32" height="32" decoding="async"></div>
            <div class="topbar-title">
                <span class="topbar-title-main">LMS {{ school.name }}</span>
                <span v-if="school.short_name && school.short_name !== school.name" class="topbar-title-sub">{{ school.short_name }}</span>
            </div>
        </div>
        <div class="topbar-context"><span class="topbar-context-label">{{ user?.role_label ?? '-' }}</span><span class="topbar-context-title">{{ pageTitle }}</span></div>
        <button class="topbar-search" type="button" aria-label="Buka akses cepat" @click="emit('open-command')"><i class="bi bi-search" aria-hidden="true"></i><span>Cari menu...</span><kbd>/</kbd></button>
        <div class="topbar-actions">
            <div v-if="notifications.route" class="dropdown">
                <button class="btn btn-sm position-relative topbar-icon-btn" type="button" data-bs-toggle="dropdown" title="Notifikasi" aria-label="Notifikasi"><i class="bi bi-bell-fill" aria-hidden="true"></i><span v-if="notifications.unread_count > 0" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-count">{{ notifications.unread_count > 99 ? '99+' : notifications.unread_count }}</span></button>
                <ul class="dropdown-menu dropdown-menu-end notification-menu">
                    <li class="dropdown-item-text d-flex justify-content-between align-items-center"><strong class="notification-title">Notifikasi</strong><Link v-if="notifications.unread_count > 0 && notifications.mark_all_route" :href="notifications.mark_all_route" method="post" as="button" type="button" class="btn btn-link btn-sm text-decoration-none notification-mark-all">Tandai semua dibaca</Link></li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li v-for="notification in notifications.latest ?? []" :key="notification.id"><Link v-if="notification.mark_read_route" :href="notification.mark_read_route" method="post" as="button" type="button" class="dropdown-item notification-link" :class="{ unread: !notification.is_read }"><div class="notification-item-title">{{ notification.judul }}</div><div class="notification-item-message">{{ notification.pesan }}</div><small class="notification-item-time">{{ notification.created_at }}</small></Link></li>
                    <li v-if="!notifications.latest?.length"><span class="dropdown-item-text text-muted text-center notification-action-link">Belum ada notifikasi</span></li>
                    <li><hr class="dropdown-divider my-1"></li><li><Link :href="notifications.route" class="dropdown-item text-center notification-action-link">Lihat Semua Notifikasi</Link></li>
                </ul>
            </div>
            <button class="btn btn-sm topbar-icon-btn theme-toggle-btn" type="button" data-theme-toggle aria-label="Aktifkan mode gelap" title="Aktifkan mode gelap" aria-pressed="false"><i class="bi bi-moon-stars-fill" data-theme-toggle-icon aria-hidden="true"></i></button>
            <span class="d-none d-lg-inline me-2 topbar-user-name">{{ user?.nama_lengkap ?? '-' }}</span>
            <div class="dropdown">
                <button class="btn btn-sm dropdown-toggle topbar-account-btn" type="button" data-bs-toggle="dropdown" aria-label="Menu akun"><img v-if="user?.foto_url" :src="user.foto_url" :alt="`Foto ${user.nama_lengkap ?? 'pengguna'}`" class="topbar-account-avatar" width="24" height="24" decoding="async"><i v-else class="bi bi-person-circle me-1" aria-hidden="true"></i><span class="topbar-account-label">{{ user?.nama_lengkap ?? 'Akun' }}</span></button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text fw-bold">{{ user?.nama_lengkap ?? '-' }}</span></li><li><span class="dropdown-item-text text-muted small">{{ user?.role_label ?? '-' }}</span></li><li><hr class="dropdown-divider"></li>
                    <li v-if="profileHref(user?.role)"><Link v-if="profileIsInertia(user?.role)" :href="profileHref(user?.role)" class="dropdown-item"><i class="bi bi-person-gear me-1" aria-hidden="true"></i> Pengaturan</Link><a v-else :href="profileHref(user?.role)" class="dropdown-item"><i class="bi bi-person-gear me-1" aria-hidden="true"></i> Pengaturan</a></li>
                    <li><button type="button" class="dropdown-item text-danger" @click="logout"><i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i> Logout</button></li>
                </ul>
            </div>
        </div>
    </header>
</template>

<style scoped>
.topbar-search { min-width: 0; max-width: 420px; flex: 1 1 320px; }
.topbar-account-avatar { width: 24px; height: 24px; border-radius: 999px; object-fit: cover; margin-right: .35rem; }
.topbar-account-label { max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; vertical-align: middle; }
@media (max-width: 991.98px) {
    .topbar { gap: .5rem; padding-inline: .65rem; }
    .topbar-brand { gap: .45rem; }
    .topbar-logo-icon { width: 32px; height: 32px; }
    .topbar-title-main { font-size: .78rem; }
    .topbar-title-sub { display: none; }
    .topbar-actions { margin-left: auto; }
    .topbar-account-label { display: none; }
    .topbar-account-btn { width: 38px; height: 38px; padding: 0; }
    .topbar-account-btn i, .topbar-account-avatar { margin: 0 !important; }
    .topbar-search { flex: 0 0 38px; width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; }
    .topbar-search span, .topbar-search kbd { display: none; }
}
@media (max-width: 575.98px) {
    .topbar { gap: .35rem; padding-inline: .5rem; }
    .topbar-toggle-btn { width: 34px; height: 34px; flex: 0 0 34px; }
    .topbar-brand { flex: 1 1 auto; min-width: 0; }
    .topbar-title-main { font-size: .74rem; }
    .topbar-actions { gap: .2rem; }
    .topbar-actions > .dropdown:first-child .topbar-icon-btn { width: 34px; height: 34px; padding: 0; }
    .topbar-search { flex: 0 0 34px; width: 34px; height: 34px; }
    .notification-menu { width: min(340px, calc(100vw - 1rem)); max-height: min(60vh, 400px); }
}
@media (min-width: 992px) { .topbar-search { flex: 0 1 420px; } }
</style>
