<script setup lang="ts">
import type { PropType } from 'vue';
import type { LaravelPaginator } from '../../../types/pagination';

import type { AttendanceReportRow, ReportOption, ExportUrls } from '../../../types/reports';
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import PageHeader from '../../../Components/AppShell/PageHeader.vue';
import { SearchableSelect, SelectInput, TextInput } from '../../../Components/Form';
import AppShell from '../../../Layouts/AppShell.vue';
import { Badge, Button, Card, EmptyState, Pagination, TableWrapper } from '../../../Components/UI';

const props = defineProps({
    absensi: { type: Object as PropType<LaravelPaginator<AttendanceReportRow>>, required: true },
    kelasMapelOptions: { type: Array as PropType<ReportOption[]>, default: () => [] },
    filters: { type: Object as PropType<Partial<Record<'kelas_mapel_id' | 'tanggal_awal' | 'tanggal_akhir' | 'status', string>>>, default: () => ({}) },
    resetUrl: { type: String, required: true },
    exportUrls: { type: Object as PropType<ExportUrls>, default: () => ({}) },
});

const filterForm = reactive({
    kelas_mapel_id: props.filters.kelas_mapel_id ?? '',
    tanggal_awal: props.filters.tanggal_awal ?? '',
    tanggal_akhir: props.filters.tanggal_akhir ?? '',
    status: props.filters.status ?? '',
});

const statusOptions = [
    { value: 'hadir', label: 'Hadir' },
    { value: 'sakit', label: 'Sakit' },
    { value: 'izin', label: 'Izin' },
    { value: 'alpha', label: 'Alpha' },
];

function cleanFilters() {
    return Object.fromEntries(Object.entries(filterForm).filter(([, value]) => value !== '' && value !== null));
}

function applyFilters() {
    router.get(props.resetUrl, cleanFilters(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function resetFilters() {
    filterForm.kelas_mapel_id = '';
    filterForm.tanggal_awal = '';
    filterForm.tanggal_akhir = '';
    filterForm.status = '';

    router.get(props.resetUrl, {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function statusBadge(status: string) {
    const colors: Record<string, string> = {
        hadir: 'success',
        sakit: 'warning text-dark',
        izin: 'info text-dark',
        alpha: 'danger',
    };
    return colors[status] ?? 'secondary';
}

function statusLabel(status: string | null) {
    return status ? status.charAt(0).toUpperCase() + status.slice(1) : '-';
}

function exportUrl(format: 'excel' | 'pdf') {
    const base = props.exportUrls[format];
    const params = new URLSearchParams(cleanFilters()).toString();
    return params ? `${base}?${params}` : base;
}
</script>

<template>
    <Head title="Laporan Absensi" />

    <AppShell title="Laporan Absensi">
        <PageHeader
            title="Laporan Absensi"
            icon="bi-clipboard-data-fill"
        />

        <Card title="Filter" icon="bi-funnel" class="mb-3">
            <form class="row g-2 app-table-filter" @submit.prevent="applyFilters">
                <div class="col-md-3">
                    <SearchableSelect
                        v-model="filterForm.kelas_mapel_id"
                        name="kelas_mapel_id"
                        wrapper-class="mb-0"
                        placeholder="Semua Kelas dan Mapel"
                        search-placeholder="Cari kelas atau mapel..."
                        :options="kelasMapelOptions"
                    />
                </div>
                <div class="col-md-2">
                    <TextInput
                        v-model="filterForm.tanggal_awal"
                        type="date"
                        name="tanggal_awal"
                        wrapper-class="mb-0"
                    />
                </div>
                <div class="col-md-2">
                    <TextInput
                        v-model="filterForm.tanggal_akhir"
                        type="date"
                        name="tanggal_akhir"
                        wrapper-class="mb-0"
                    />
                </div>
                <div class="col-md-2">
                    <SelectInput
                        v-model="filterForm.status"
                        name="status"
                        wrapper-class="mb-0"
                        placeholder="Semua Status"
                        :options="statusOptions"
                    />
                </div>
                <div class="col-md-2">
                    <Button type="submit" color="primary" icon="bi-search" class="w-100">Filter</Button>
                </div>
                <div class="col-md-1">
                    <Button
                        type="button"
                        color="outline-secondary"
                        icon="bi-arrow-clockwise"
                        class="w-100"
                        title="Reset filter"
                        aria-label="Reset filter laporan absensi"
                        @click="resetFilters"
                    />
                </div>
            </form>
        </Card>

        <div v-if="absensi.data.length" class="content-summary">
            <div>
                <div class="content-summary-title">Hasil pencarian siap diekspor</div>
                <div class="content-summary-text">Menampilkan {{ absensi.data.length }} baris data berdasarkan filter aktif saat ini.</div>
            </div>
            <div class="content-summary-actions">
                <Button :href="exportUrl('excel')" color="outline-success" icon="bi-file-earmark-excel" size="sm">Excel</Button>
                <Button :href="exportUrl('pdf')" color="outline-danger" icon="bi-file-earmark-pdf" size="sm">PDF</Button>
            </div>
        </div>

        <Card body-class="p-0">
            <TableWrapper v-if="absensi.data.length">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px;">#</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Mapel</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in absensi.data" :key="item.id">
                            <td class="text-center">{{ item.nomor }}</td>
                            <td>{{ item.nama_siswa }}</td>
                            <td>{{ item.kelas }}</td>
                            <td>{{ item.mapel }}</td>
                            <td>{{ item.tanggal }}</td>
                            <td><Badge :color="statusBadge(item.status)">{{ statusLabel(item.status) }}</Badge></td>
                            <td>{{ item.keterangan }}</td>
                        </tr>
                    </tbody>
                </table>
            </TableWrapper>

            <EmptyState v-else title="Tidak ada data absensi." icon="bi-clipboard-data" />

            <template v-if="absensi.links?.length" #footer>
                <Pagination :links="absensi.links" />
            </template>
        </Card>
    </AppShell>
</template>
