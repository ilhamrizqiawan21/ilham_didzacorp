<script setup lang="ts">
import type { PropType } from 'vue';
import type { PaginationLink } from '../../../types/pagination';
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import PageHeader from '../../../Components/AppShell/PageHeader.vue';
import { SelectInput } from '../../../Components/Form';
import AppShell from '../../../Layouts/AppShell.vue';
import { Badge, Button, Card, EmptyState, Pagination, TableWrapper } from '../../../Components/UI';

const props = defineProps({
    errors: { type: Object as PropType<{ data: { id: number; error_level: string; created_at: string | null; message: string; file: string; line: number; url: string }[]; links: PaginationLink[] }>, default: () => ({ data: [], links: [] }) },
    levels: { type: Array as PropType<string[]>, default: () => [] },
    filters: { type: Object as PropType<{ level?: string }>, default: () => ({}) },
});

const filterForm = reactive({
    level: props.filters.level ?? '',
});

function applyFilters() {
    router.get('/admin/log-error', filterForm.level ? { level: filterForm.level } : {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function resetFilters() {
    filterForm.level = '';
    router.get('/admin/log-error', {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function levelColor(level: string) {
    if (level === 'EXCEPTION') return 'danger';
    if (level === 'WARNING') return 'warning text-dark';
    if (level === 'DEPRECATED') return 'secondary';
    return 'info text-dark';
}

function truncate(value: string | null, length = 72) {
    if (!value) return '-';
    return value.length > length ? `${value.slice(0, length)}...` : value;
}
</script>

<template>
    <Head title="Log Error" />

    <AppShell title="Log Error">
        <PageHeader title="Log Error Sistem" icon="bi-bug-fill" />

        <Card title="Daftar Error" icon="bi-list-ul" body-class="p-0">
            <template #actions>
                <form class="d-flex gap-2" @submit.prevent="applyFilters">
                    <SelectInput
                        v-model="filterForm.level"
                        name="level"
                        wrapper-class="mb-0"
                        placeholder="Semua Level"
                        :options="levels.map((level) => ({ value: level, label: level }))"
                    />
                    <Button type="submit" color="primary" icon="bi-funnel" aria-label="Filter error" />
                    <Button type="button" color="outline-secondary" icon="bi-arrow-clockwise" aria-label="Reset filter" @click="resetFilters" />
                </form>
            </template>

            <TableWrapper v-if="errors.data?.length">
                <table class="table table-hover mb-0 small">
                    <thead>
                        <tr>
                            <th>Level</th>
                            <th>Waktu</th>
                            <th>Message</th>
                            <th>File</th>
                            <th>Line</th>
                            <th>URL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in errors.data" :key="item.id">
                            <td><Badge :color="levelColor(item.error_level)">{{ item.error_level }}</Badge></td>
                            <td class="text-nowrap">{{ item.created_at ?? '-' }}</td>
                            <td><strong :title="item.message">{{ truncate(item.message, 100) }}</strong></td>
                            <td class="text-muted" :title="item.file">{{ truncate(item.file, 44) }}</td>
                            <td class="text-center">{{ item.line }}</td>
                            <td class="text-muted" :title="item.url">{{ truncate(item.url, 44) }}</td>
                        </tr>
                    </tbody>
                </table>
            </TableWrapper>
            <EmptyState v-else title="Tidak ada error - sistem berjalan normal" icon="bi-check-circle" />

            <template v-if="errors.links?.length" #footer>
                <Pagination :links="errors.links" />
            </template>
        </Card>
    </AppShell>
</template>
