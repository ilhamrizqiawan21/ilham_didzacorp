<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppShell from '../../Layouts/AppShell.vue';
import { ActionQueue, Badge, DashboardHero, MetricStrip, QuickActionBar, TableWrapper } from '../../Components/UI';
import type { AppPageProps } from '../../types';

interface AdminStats { total_siswa?: number; total_kelas?: number; total_mapel?: number; total_materi?: number; }
interface LoginRecord { id: number; nama_lengkap: string; role: string; login_time?: string | null; ip_address?: string | null; }
interface Announcement { id: number; judul: string; created_at?: string | null; creator?: string | null; }
interface QuickAction { label: string; href: string; icon: string; color: string; }

interface Props { statistik?: AdminStats; loginTerbaru?: LoginRecord[]; pengumuman?: Announcement[]; }
const props = withDefaults(defineProps<Props>(), { statistik: () => ({}), loginTerbaru: () => [], pengumuman: () => [] });
const page = usePage<AppPageProps>();
const guruName = computed(() => page.props.auth?.user?.nama_lengkap || 'Guru');
const guruPhoto = computed(() => page.props.auth?.user?.foto_url || '');

const metrics = computed(() => [
    { label: 'Siswa aktif', value: props.statistik.total_siswa ?? 0, icon: 'bi-mortarboard-fill', tone: 'success', href: '/admin/kelas-siswa' },
    { label: 'Kelas', value: props.statistik.total_kelas ?? 0, icon: 'bi-building', tone: 'info', href: '/admin/kelas' },
    { label: 'Mapel', value: props.statistik.total_mapel ?? 0, icon: 'bi-book-fill', tone: 'warning', href: '/admin/mata-pelajaran' },
    { label: 'Materi', value: props.statistik.total_materi ?? 0, icon: 'bi-file-earmark-text-fill', tone: 'primary', href: '/guru/materi' },
]);

const quickActions: QuickAction[] = [
    { label: 'Kelola Siswa', href: '/admin/kelas-siswa', icon: 'bi-people-fill', color: 'primary' },
    { label: 'Atur Mata Pelajaran', href: '/admin/mata-pelajaran', icon: 'bi-book-fill', color: 'light' },
    { label: 'Kalender', href: '/admin/kalender', icon: 'bi-calendar3', color: 'light' },
    { label: 'Pengaturan', href: '/admin/pengaturan', icon: 'bi-gear-fill', color: 'light' },
];

const announcementItems = computed(() => props.pengumuman.map((item) => ({
    id: item.id, title: item.judul, meta: item.created_at ?? '',
    detail: item.creator ? `Dibuat oleh ${item.creator}` : '',
    icon: 'bi-megaphone-fill', accent: '#f59e0b',
})));

function roleBadgeColor(role: string): string {
    return { admin: 'danger', guru: 'primary', siswa: 'success', kepala_sekolah: 'warning' }[role] ?? 'secondary';
}
</script>

<template>
    <Head title="Beranda" />
    <AppShell title="Dashboard">
        <DashboardHero eyebrow="Dashboard Guru" :title="`Selamat Datang, ${guruName}`" subtitle="Kelola pembelajaran, siswa, materi, tugas, dan aktivitas sekolah dari satu tempat." icon="bi-emoji-smile-fill" tone="admin" :photo-url="guruPhoto" :photo-alt="`Foto ${guruName}`">
            <template #actions><QuickActionBar :actions="quickActions" /></template>
        </DashboardHero>

        <MetricStrip :items="metrics" />

        <div class="dashboard-grid dashboard-grid-admin admin-dashboard-workspace">
            <section class="workspace-panel">
                <header class="workspace-panel-header">
                    <span class="workspace-panel-title"><i class="bi bi-clock-history" aria-hidden="true"></i> Login Terbaru</span>
                    <a href="/admin/log-login" class="app-card-action-link">Lihat Semua</a>
                </header>
                <div class="workspace-panel-body p-0">
                    <TableWrapper v-if="loginTerbaru.length">
                        <table class="table table-hover mb-0 admin-login-table">
                            <thead><tr><th>Nama</th><th>Role</th><th>Waktu</th><th>IP</th></tr></thead>
                            <tbody>
                                <tr v-for="log in loginTerbaru" :key="log.id">
                                    <td><strong>{{ log.nama_lengkap }}</strong></td>
                                    <td><Badge :color="roleBadgeColor(log.role)">{{ log.role }}</Badge></td>
                                    <td class="text-muted small">{{ log.login_time ?? '-' }}</td>
                                    <td class="text-muted small admin-login-ip">{{ log.ip_address ?? '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </TableWrapper>
                    <ActionQueue v-else :items="[]" empty-title="Belum ada data login" icon="bi-clock-history" />
                </div>
            </section>
            <ActionQueue title="Pengumuman Terbaru" icon="bi-megaphone-fill" :items="announcementItems" empty-title="Belum ada pengumuman" />
        </div>
    </AppShell>
</template>

<style scoped>
.admin-dashboard-workspace { align-items: stretch; }
.admin-login-table { min-width: 560px; }
@media (max-width: 767.98px) {
    .admin-dashboard-workspace { grid-template-columns: minmax(0, 1fr); }
    .admin-login-table { min-width: 0; table-layout: fixed; }
    .admin-login-table th, .admin-login-table td { padding: .65rem .55rem; }
    .admin-login-table th:nth-child(1), .admin-login-table td:nth-child(1) { width: 38%; }
    .admin-login-table th:nth-child(2), .admin-login-table td:nth-child(2) { width: 24%; }
    .admin-login-table th:nth-child(3), .admin-login-table td:nth-child(3) { width: 38%; }
    .admin-login-ip { display: none; }
}
@media (max-width: 575.98px) {
    .admin-login-table { font-size: .76rem; }
    .admin-login-table th, .admin-login-table td { padding: .55rem .4rem; }
    .admin-login-table th:nth-child(1), .admin-login-table td:nth-child(1) { width: 43%; }
    .admin-login-table th:nth-child(2), .admin-login-table td:nth-child(2) { width: 23%; }
    .admin-login-table th:nth-child(3), .admin-login-table td:nth-child(3) { width: 34%; }
}
</style>
