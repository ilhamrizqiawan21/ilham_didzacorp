<script setup lang="ts">
import type { PropType } from 'vue';

import { Head, Link } from '@inertiajs/vue3';
import PageHeader from '../../../Components/AppShell/PageHeader.vue';
import AppShell from '../../../Layouts/AppShell.vue';
import { Badge, Card, EmptyState, MetricStrip, TableWrapper } from '../../../Components/UI';

const props = defineProps({
    kelas: { type: Object as PropType<{ nama: string }>, required: true },
    days: { type: Array as PropType<{ value: number; label: string; is_today: boolean; slots: { slot: number; course: { mata_pelajaran: string; guru: string; workspace_url: string | null; kelas_daring_url: string | null } | null }[] }[]>, default: () => [] },
    summary: { type: Object as PropType<{ total_jadwal: number; total_mapel: number; hari_aktif: number }>, required: true },
    links: { type: Object as PropType<Record<string, string>>, default: () => ({}) },
});

const metrics = [
    { label: 'Jadwal aktif', value: props.summary.total_jadwal ?? 0, icon: 'bi-calendar-check-fill', tone: 'primary' },
    { label: 'Mata pelajaran', value: props.summary.total_mapel ?? 0, icon: 'bi-book-fill', tone: 'success' },
    { label: 'Hari belajar', value: props.summary.hari_aktif ?? 0, icon: 'bi-calendar-week-fill', tone: 'info' },
];
</script>

<template>
    <Head title="Jadwal Pelajaran" />

    <AppShell title="Jadwal Pelajaran">
        <PageHeader
            title="Jadwal Pelajaran"
            :subtitle="`Kelas ${kelas.nama}`"
            icon="bi-calendar-week-fill"
        />

        <MetricStrip :items="metrics" />

        <Card title="Jadwal Mingguan" icon="bi-table" body-class="p-0">
            <TableWrapper v-if="summary.total_jadwal > 0" min-width="900">
                <table class="table table-hover align-middle mb-0 student-schedule-table">
                    <thead>
                        <tr>
                            <th>Hari</th>
                            <th v-for="slot in 5" :key="slot">Pelajaran {{ slot }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="day in days" :key="day.value" :class="{ 'table-active': day.is_today }">
                            <th>
                                {{ day.label }}
                                <Badge v-if="day.is_today" color="success">Hari ini</Badge>
                            </th>
                            <td v-for="slot in day.slots" :key="`${day.value}-${slot.slot}`">
                                <div v-if="slot.course" class="schedule-cell">
                                    <strong>{{ slot.course.mata_pelajaran }}</strong>
                                    <small>{{ slot.course.guru }}</small>
                                    <div class="schedule-actions">
                                        <Link v-if="slot.course.workspace_url" :href="slot.course.workspace_url">Ruang kelas</Link>
                                        <Link v-if="slot.course.kelas_daring_url" :href="slot.course.kelas_daring_url">Daring</Link>
                                    </div>
                                </div>
                                <span v-else class="text-muted small">-</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </TableWrapper>
            <EmptyState
                v-else
                title="Jadwal pelajaran belum tersedia"
                message="Jadwal akan tampil setelah guru atau admin mengatur jadwal mengajar."
                icon="bi-calendar-week"
            />
        </Card>
    </AppShell>
</template>

<style scoped>
.student-schedule-table th,
.student-schedule-table td {
    min-width: 140px;
}

.student-schedule-table th:first-child {
    min-width: 110px;
}

.schedule-cell {
    display: grid;
    gap: .2rem;
}

.schedule-cell small {
    color: var(--text-muted);
}

.schedule-actions {
    display: flex;
    gap: .6rem;
    flex-wrap: wrap;
    font-size: .78rem;
}
</style>
