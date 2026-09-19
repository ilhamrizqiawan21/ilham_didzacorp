<script setup lang="ts">
import type { PropType } from 'vue';
import type { PaginationLink } from '../../../types/pagination';
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import PageHeader from '../../../Components/AppShell/PageHeader.vue';
import { SelectInput, TextInput } from '../../../Components/Form';
import AppShell from '../../../Layouts/AppShell.vue';
import { Badge, Button, Card, EmptyState, Pagination, TableWrapper } from '../../../Components/UI';

const props = defineProps({
    logs: { type: Object as PropType<{ data: { id: number; created_at: string | null; module: string; actor: string; metadata: Record<string, unknown>; before_values: Record<string, unknown> | null; after_values: Record<string, unknown> | null }[]; links: PaginationLink[] }>, default: () => ({ data: [], links: [] }) },
    filters: { type: Object as PropType<{ module?: string; search?: string }>, default: () => ({}) },
});

const filterForm = reactive({
    module: props.filters.module ?? '',
    search: props.filters.search ?? '',
});

function applyFilters() {
    const params: Record<string, string> = {};
    if (filterForm.module) params.module = filterForm.module;
    if (filterForm.search) params.search = filterForm.search;

    router.get('/admin/log-akademik', params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function resetFilters() {
    filterForm.module = '';
    filterForm.search = '';
    router.get('/admin/log-akademik', {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function moduleColor(module: string) {
    return module === 'nilai' ? 'info text-dark' : 'success';
}

function formatValues(values: Record<string, unknown> | null) {
    const entries = Object.entries(values ?? {}).filter(([, value]) => value !== null && value !== undefined && value !== '');
    if (!entries.length) return '-';

    return entries.map(([key, value]) => `${key}: ${value}`).join(', ');
}
</script>

<template>
    <Head title="Log Akademik" />

    <AppShell title="Log Akademik">
        <PageHeader title="Log Akademik" icon="bi-clipboard-data-fill" />

        <Card title="Riwayat Perubahan Nilai dan Absensi" icon="bi-list-ul" body-class="p-0">
            <template #actions>
                <form class="d-flex flex-wrap gap-2" @submit.prevent="applyFilters">
                    <SelectInput
                        v-model="filterForm.module"
                        name="module"
                        wrapper-class="mb-0"
                        placeholder="Semua Modul"
                        :options="[
                            { value: 'absensi', label: 'Absensi' },
                            { value: 'nilai', label: 'Nilai' },
                        ]"
                    />
                    <TextInput
                        v-model="filterForm.search"
                        name="search"
                        wrapper-class="mb-0"
                        placeholder="Cari pengubah..."
                    />
                    <Button type="submit" color="primary" icon="bi-funnel" aria-label="Filter log akademik" />
                    <Button type="button" color="outline-secondary" icon="bi-arrow-clockwise" aria-label="Reset filter" @click="resetFilters" />
                </form>
            </template>

            <TableWrapper v-if="logs.data?.length">
                <table class="table table-hover mb-0 small">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Modul</th>
                            <th>Pengubah</th>
                            <th>Data</th>
                            <th>Sebelum</th>
                            <th>Sesudah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="log in logs.data" :key="log.id">
                            <td class="text-nowrap">{{ log.created_at ?? '-' }}</td>
                            <td><Badge :color="moduleColor(log.module)">{{ log.module }}</Badge></td>
                            <td><strong>{{ log.actor }}</strong></td>
                            <td>
                                <div>{{ log.metadata.siswa ?? '-' }}</div>
                                <small class="text-muted">
                                    {{ log.metadata.kelas ?? '-' }} - {{ log.metadata.mata_pelajaran ?? '-' }}
                                </small>
                            </td>
                            <td class="audit-values">{{ formatValues(log.before_values) }}</td>
                            <td class="audit-values">{{ formatValues(log.after_values) }}</td>
                        </tr>
                    </tbody>
                </table>
            </TableWrapper>
            <EmptyState v-else title="Belum ada perubahan akademik tercatat" icon="bi-clipboard-check" />

            <template v-if="logs.links?.length" #footer>
                <Pagination :links="logs.links" />
            </template>
        </Card>
    </AppShell>
</template>

<style scoped>
.audit-values {
    max-width: 260px;
    white-space: normal;
}
</style>
