<script setup lang="ts">
import type { PropType } from 'vue';

import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import PageHeader from '../../../Components/AppShell/PageHeader.vue';
import AppShell from '../../../Layouts/AppShell.vue';
import { Badge, Card, EmptyState, MetricStrip, TableWrapper } from '../../../Components/UI';

const props = defineProps({
    kelas: { type: Object as PropType<{ nama: string }>, required: true },
    courses: { type: Array as PropType<{ id: number; label: string; url: string }[]>, default: () => [] },
    selectedCourseId: { type: [Number, String], default: null },
    sessions: { type: Array as PropType< { id: number; judul: string; tanggal: string; pelajaran_ke: number; status: string; meeting_url: string; is_upcoming: boolean; mata_pelajaran: string; guru: string; deskripsi: string | null; workspace_url: string | null }[]>, default: () => [] },
    links: { type: Object as PropType<{ jadwal?: string; all?: string }>, default: () => ({}) },
});

const upcomingCount = computed(() => props.sessions.filter((item) => item.is_upcoming).length);
const metrics = computed(() => [
    { label: 'Sesi tersedia', value: props.sessions.length, icon: 'bi-camera-video-fill', tone: 'primary' },
    { label: 'Akan datang', value: upcomingCount.value, icon: 'bi-calendar-check-fill', tone: 'success' },
    { label: 'Mata pelajaran', value: props.courses.length, icon: 'bi-book-fill', tone: 'info' },
]);

function statusColor(status: string) {
    if (status === 'selesai') return 'success';
    if (status === 'dibatalkan') return 'danger';
    return 'primary';
}
</script>

<template>
    <Head title="Kelas Daring" />

    <AppShell title="Kelas Daring">
        <PageHeader
            title="Kelas Daring"
            :subtitle="`Kelas ${kelas.nama}`"
            icon="bi-camera-video-fill"
        />

        <MetricStrip :items="metrics" />

        <section class="workspace-panel mb-4">
            <header class="workspace-panel-header">
                <span class="workspace-panel-title"><i class="bi bi-funnel" aria-hidden="true"></i> Filter Mata Pelajaran</span>
                <Link :href="links.jadwal" class="app-card-action-link">Lihat Jadwal</Link>
            </header>
            <div class="course-filter-list">
                <Link :href="links.all" class="course-filter" :class="{ active: !selectedCourseId }">Semua</Link>
                <Link
                    v-for="course in courses"
                    :key="course.id"
                    :href="course.url"
                    class="course-filter"
                    :class="{ active: Number(selectedCourseId) === Number(course.id) }"
                >
                    {{ course.label }}
                </Link>
            </div>
        </section>

        <Card title="Daftar Sesi" icon="bi-camera-video" body-class="p-0">
            <TableWrapper v-if="sessions.length" min-width="820">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Sesi</th>
                            <th>Jadwal</th>
                            <th>Status</th>
                            <th class="text-end">Akses</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="session in sessions" :key="session.id">
                            <td>
                                <strong>{{ session.judul }}</strong>
                                <div class="text-muted small">{{ session.mata_pelajaran }} - {{ session.guru }}</div>
                                <div v-if="session.deskripsi" class="text-muted small">{{ session.deskripsi }}</div>
                            </td>
                            <td>{{ session.tanggal }}<div class="text-muted small">Pelajaran ke-{{ session.pelajaran_ke }}</div></td>
                            <td><Badge :color="statusColor(session.status)">{{ session.status }}</Badge></td>
                            <td class="text-end">
                                <a
                                    v-if="session.status === 'terjadwal'"
                                    :href="session.meeting_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="btn btn-sm btn-success"
                                >
                                    <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                                    Buka Link
                                </a>
                                <Link v-else-if="session.workspace_url" :href="session.workspace_url" class="btn btn-sm btn-outline-secondary">
                                    Ruang Kelas
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </TableWrapper>
            <EmptyState
                v-else
                title="Belum ada kelas daring"
                message="Sesi daring akan tampil setelah guru menjadwalkan link meeting."
                icon="bi-camera-video"
            />
        </Card>
    </AppShell>
</template>

<style scoped>
.course-filter-list {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
}

.course-filter {
    display: inline-flex;
    align-items: center;
    min-height: 34px;
    border: 1px solid var(--gray-200);
    border-radius: 999px;
    padding: .35rem .75rem;
    text-decoration: none;
    color: var(--text-body);
    background: var(--surface-card);
}

.course-filter.active,
.course-filter:hover {
    border-color: var(--primary-500);
    background: color-mix(in srgb, var(--primary-500) 12%, var(--surface-card));
    color: var(--primary-700, #0d6efd);
}
</style>
