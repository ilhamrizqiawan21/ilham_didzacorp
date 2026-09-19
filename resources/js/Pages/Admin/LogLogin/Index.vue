<script setup lang="ts">
import type { PropType } from 'vue';
import type { PaginationLink } from '../../../types/pagination';
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import PageHeader from '../../../Components/AppShell/PageHeader.vue';
import { TextInput } from '../../../Components/Form';
import AppShell from '../../../Layouts/AppShell.vue';
import { Badge, Button, Card, EmptyState, Pagination, TableWrapper } from '../../../Components/UI';

const props = defineProps({
    logs: { type: Object as PropType<{ data: { id: number; login_time: string | null; username: string; nama_lengkap: string; role: string | null; ip_address: string; user_agent: string }[]; links: PaginationLink[] }>, default: () => ({ data: [], links: [] }) },
    filters: { type: Object as PropType<{ search?: string }>, default: () => ({}) },
    exportUrl: { type: String, required: true },
});

const filterForm = reactive({
    search: props.filters.search ?? '',
});

function applyFilters() {
    router.get('/admin/log-login', filterForm.search ? { search: filterForm.search } : {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function resetFilters() {
    filterForm.search = '';
    router.get('/admin/log-login', {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function exportExcelUrl() {
    const params = new URLSearchParams();
    if (filterForm.search) params.set('search', filterForm.search);

    const query = params.toString();
    return query ? `${props.exportUrl}?${query}` : props.exportUrl;
}

function roleLabel(role: string | null) {
    return role ? role.replaceAll('_', ' ') : '-';
}

function truncate(value: string | null, length = 56) {
    if (!value) return '-';
    return value.length > length ? `${value.slice(0, length)}...` : value;
}
</script>

<template>
    <Head title="Log Login" />

    <AppShell title="Log Login">
        <PageHeader title="Riwayat Login" icon="bi-clock-history" />

        <Card title="Daftar Login" icon="bi-list-ul" body-class="p-0">
            <template #actions>
                <form class="d-flex flex-wrap gap-2" @submit.prevent="applyFilters">
                    <TextInput v-model="filterForm.search" name="search" wrapper-class="mb-0" placeholder="Cari user..." />
                    <Button type="submit" color="primary" icon="bi-search" aria-label="Cari riwayat login" />
                    <Button type="button" color="outline-secondary" icon="bi-arrow-clockwise" aria-label="Reset filter" @click="resetFilters" />
                    <a :href="exportExcelUrl()" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i> Excel
                    </a>
                </form>
            </template>

            <TableWrapper v-if="logs.data?.length">
                <table class="table table-hover mb-0 small">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Username</th>
                            <th>Nama</th>
                            <th>Role</th>
                            <th>IP Address</th>
                            <th>User Agent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="log in logs.data" :key="log.id">
                            <td class="text-nowrap">{{ log.login_time ?? '-' }}</td>
                            <td><strong>{{ log.username }}</strong></td>
                            <td>{{ log.nama_lengkap }}</td>
                            <td><Badge color="primary">{{ roleLabel(log.role) }}</Badge></td>
                            <td><code>{{ log.ip_address }}</code></td>
                            <td class="text-muted user-agent" :title="log.user_agent">{{ truncate(log.user_agent) }}</td>
                        </tr>
                    </tbody>
                </table>
            </TableWrapper>
            <EmptyState v-else title="Belum ada data login" icon="bi-clock-history" />

            <template v-if="logs.links?.length" #footer>
                <Pagination :links="logs.links" />
            </template>
        </Card>
    </AppShell>
</template>

<style scoped>
.user-agent {
    max-width: 240px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>
