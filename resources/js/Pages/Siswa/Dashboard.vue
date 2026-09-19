<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppShell from '../../Layouts/AppShell.vue';
import { computed } from 'vue';
import { ActionQueue, CourseCard, DashboardHero, MetricStrip, QuickActionBar } from '../../Components/UI';
import type { AppPageProps } from '../../types';
interface StudentStats { total_tugas?: number; tugas_selesai?: number; tugas_belum?: number; total_materi?: number; }
interface StudentCourse { id: number; title: string; subtitle: string; meta: string; href: string; badges?: Array<{ label: string; color?: string }>; }
interface StudentTask { id: number; judul: string; mata_pelajaran: string; batas_waktu: string; show_url?: string | null; selesai?: boolean; }
interface StudentNotification { id: number; judul: string; created_at: string; pesan: string; is_read: boolean; tipe: string; }
interface StudentAnnouncement { id: number; judul: string; created_at: string; show_url?: string | null; }
interface OnlineClass { id: number; judul: string; mata_pelajaran: string; tanggal: string; pelajaran_ke: number; workspace_url: string; }
interface StudentLinks { tugas?: string; materi?: string; jadwal_pelajaran?: string; kelas_daring?: string; notifikasi?: string; pengumuman?: string; }

interface Props { stats: StudentStats; courses?: StudentCourse[]; tugasTerbaru?: StudentTask[]; notifikasi?: StudentNotification[]; pengumuman?: StudentAnnouncement[]; kelasDaring?: OnlineClass[]; links?: StudentLinks; }
const props = withDefaults(defineProps<Props>(), { courses: () => [], tugasTerbaru: () => [], notifikasi: () => [], pengumuman: () => [], kelasDaring: () => [], links: () => ({}) });
const page = usePage<AppPageProps>();
const siswaName = computed(() => page.props.auth?.user?.nama_lengkap || 'Siswa');
const siswaPhoto = computed(() => page.props.auth?.user?.foto_url || '');

const iconMap = {
    tugas_baru: { icon: 'bi-journal-plus', color: '#3b82f6' }, nilai_baru: { icon: 'bi-bar-chart-fill', color: '#22c55e' },
    chat_baru: { icon: 'bi-chat-dots-fill', color: '#8b5cf6' }, komentar_tugas: { icon: 'bi-chat-square-text-fill', color: '#f59e0b' },
    kumpul_tugas: { icon: 'bi-check-circle-fill', color: '#06b6d4' }, absensi: { icon: 'bi-clipboard-check-fill', color: '#ef4444' },
};
function iconFor(type: string) { return iconMap[type as keyof typeof iconMap] ?? { icon: 'bi-bell-fill', color: '#6b7280' }; }

const metrics = computed(() => [
    { label: 'Total tugas', value: props.stats.total_tugas ?? 0, icon: 'bi-journal-fill', tone: 'primary', href: props.links.tugas },
    { label: 'Selesai', value: props.stats.tugas_selesai ?? 0, icon: 'bi-check-circle-fill', tone: 'success', href: props.links.tugas },
    { label: 'Belum', value: props.stats.tugas_belum ?? 0, icon: 'bi-exclamation-circle-fill', tone: 'warning', href: props.links.tugas },
    { label: 'Materi', value: props.stats.total_materi ?? 0, icon: 'bi-file-earmark-text-fill', tone: 'info', href: props.links.materi },
]);
const quickActions = computed(() => [
    { label: 'Tugas Saya', href: props.links.tugas || '/siswa/tugas', icon: 'bi-journal-check', color: 'primary' },
    { label: 'Jadwal', href: props.links.jadwal_pelajaran || '/siswa/jadwal-pelajaran', icon: 'bi-calendar-week', color: 'light' },
    { label: 'Daring', href: props.links.kelas_daring || '/siswa/kelas-daring', icon: 'bi-camera-video', color: 'light' },
    { label: 'Materi', href: props.links.materi || '/siswa/materi', icon: 'bi-file-earmark-text', color: 'light' },
    { label: 'Nilai', href: '/siswa/nilai', icon: 'bi-bar-chart', color: 'light' },
]);
const taskItems = computed(() => props.tugasTerbaru.map((item) => ({ id: item.id, title: item.judul, meta: item.mata_pelajaran, detail: `Deadline ${item.batas_waktu}`, href: item.show_url || props.links.tugas || '/siswa/tugas', badge: item.selesai ? 'Selesai' : 'Belum', badgeColor: item.selesai ? 'success' : 'warning text-dark', icon: item.selesai ? 'bi-check-circle' : 'bi-journal-text', accent: item.selesai ? '#16a34a' : '#f59e0b' })));
const onlineClassItems = computed(() => props.kelasDaring.map((item) => ({ id: item.id, title: item.judul, meta: item.mata_pelajaran, detail: `${item.tanggal} · Pelajaran ke-${item.pelajaran_ke}`, href: item.workspace_url, badge: 'Daring', badgeColor: 'primary', icon: 'bi-camera-video', accent: '#0d6efd' })));
const notificationItems = computed(() => props.notifikasi.map((item) => ({ id: item.id, title: item.judul, meta: item.created_at, detail: item.pesan, href: props.links.notifikasi, badge: item.is_read ? '' : 'Baru', badgeColor: 'danger', icon: iconFor(item.tipe).icon, accent: iconFor(item.tipe).color })));
const announcementItems = computed(() => props.pengumuman.map((item) => ({ id: item.id, title: item.judul, meta: item.created_at, href: item.show_url, icon: 'bi-megaphone-fill', accent: '#2563eb' })));
</script>

<template>
    <Head title="Dashboard Siswa" />
    <AppShell title="Dashboard Siswa">
        <DashboardHero eyebrow="Hari Ini" title="Siap lanjut belajar?" :subtitle="`${stats.tugas_belum ?? 0} tugas belum dikerjakan dan ${stats.total_materi ?? 0} materi tersedia untuk dipelajari.`" icon="bi-mortarboard" tone="student" :photo-url="siswaPhoto" :photo-alt="`Foto ${siswaName}`">
            <template #actions><QuickActionBar :actions="quickActions" /></template>
        </DashboardHero>
        <MetricStrip :items="metrics" />

        <div class="dashboard-grid dashboard-grid-student student-priority-grid">
            <ActionQueue title="Tugas Terdekat" icon="bi-journal-check" :items="taskItems" empty-title="Belum ada tugas" />
            <ActionQueue title="Kelas Daring" icon="bi-camera-video-fill" :items="onlineClassItems" empty-title="Belum ada kelas daring">
                <template #actions><Link :href="links.kelas_daring || '/siswa/kelas-daring'" class="app-card-action-link">Lihat Semua</Link></template>
            </ActionQueue>
            <ActionQueue title="Notifikasi" icon="bi-bell-fill" :items="notificationItems" empty-title="Belum ada notifikasi">
                <template v-if="notifikasi.length" #actions><Link :href="links.notifikasi" class="app-card-action-link">Lihat Semua</Link></template>
            </ActionQueue>
        </div>

        <section class="workspace-panel student-courses-panel">
            <header class="workspace-panel-header">
                <span class="workspace-panel-title"><i class="bi bi-compass" aria-hidden="true"></i> Lanjutkan Belajar</span>
                <Link :href="links.materi || '/siswa/materi'" class="app-card-action-link">Buka Materi</Link>
            </header>
            <div v-if="courses.length" class="course-card-grid">
                <CourseCard v-for="(course, index) in courses" :key="course.id" :title="course.title" :subtitle="course.subtitle" :meta="course.meta" :href="course.href" icon="bi-book" :badges="course.badges" :accent="['#2563eb', '#16a34a', '#f59e0b', '#dc2626'][index % 4]" />
            </div>
            <ActionQueue v-else :items="[]" empty-title="Belum ada kelas aktif" icon="bi-book" />
        </section>

        <ActionQueue title="Pengumuman" icon="bi-megaphone-fill" :items="announcementItems" empty-title="Tidak ada pengumuman">
            <template v-if="pengumuman.length" #actions><Link :href="links.pengumuman" class="app-card-action-link">Lihat Semua</Link></template>
        </ActionQueue>
    </AppShell>
</template>

<style scoped>
.student-priority-grid { align-items: stretch; }
.student-courses-panel { min-width: 0; }
@media (max-width: 767.98px) {
    .student-priority-grid { grid-template-columns: minmax(0, 1fr); }
    .student-courses-panel .course-card-grid { grid-template-columns: minmax(0, 1fr); }
}
</style>
