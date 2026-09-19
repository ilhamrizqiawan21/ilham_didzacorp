<script setup lang="ts">
import type { PropType } from 'vue';


import type { ReportOption, ExportUrls, AttitudeReportRow, SocialAspect, SpiritualAspect } from '../../../types/reports';
import { Head, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import PageHeader from '../../../Components/AppShell/PageHeader.vue';
import { SearchableSelect } from '../../../Components/Form';
import AppShell from '../../../Layouts/AppShell.vue';
import { Badge, Button, Card, EmptyState, TableWrapper } from '../../../Components/UI';

const props = defineProps({
    sikapSosial: { type: Array as PropType<AttitudeReportRow<SocialAspect>[]>, default: () => [] },
    sikapSpiritual: { type: Array as PropType<AttitudeReportRow<SpiritualAspect>[]>, default: () => [] },
    kelasOptions: { type: Array as PropType<ReportOption[]>, default: () => [] },
    filters: { type: Object as PropType<{ kelas_id?: string }>, default: () => ({}) },
    semester: { type: [String, Number], default: '' },
    taAktif: { type: Object as PropType<{ id: number; tahun: string } | null>, default: null },
    resetUrl: { type: String, required: true },
    exportUrls: { type: Object as PropType<ExportUrls>, default: () => ({}) },
});

const filterForm = reactive({
    kelas_id: props.filters.kelas_id ?? '',
});

const sosialAspects: { key: SocialAspect; label: string }[] = [
    { key: 'empati', label: 'Empati' },
    { key: 'kerjasama', label: 'Kerja Sama' },
    { key: 'toleransi', label: 'Toleransi' },
    { key: 'percaya_diri', label: 'Percaya Diri' },
    { key: 'komunikasi', label: 'Komunikasi' },
];
const spiritualAspects: { key: SpiritualAspect; label: string }[] = [
    { key: 'taqwa', label: 'Taqwa' },
    { key: 'kejujuran', label: 'Kejujuran' },
    { key: 'disiplin', label: 'Disiplin' },
    { key: 'sabar', label: 'Sabar' },
    { key: 'syukur', label: 'Syukur' },
    { key: 'tawadhu', label: 'Tawadhu' },
];

const sosialSummary = computed(() => averages(props.sikapSosial, sosialAspects));
const spiritualSummary = computed(() => averages(props.sikapSpiritual, spiritualAspects));
const hasSummary = computed(() => props.sikapSosial.length > 0 || props.sikapSpiritual.length > 0);

function averages<K extends string>(rows: Record<K, number>[], aspects: { key: K; label: string }[]) {
    return aspects.map((aspect) => {
        const total = rows.reduce((sum, row) => sum + Number(row[aspect.key] ?? 0), 0);
        return {
            ...aspect,
            value: rows.length ? (total / rows.length).toFixed(1) : '0.0',
        };
    });
}

function applyFilters() {
    router.get(props.resetUrl, filterForm.kelas_id ? { kelas_id: filterForm.kelas_id } : {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function resetFilters() {
    filterForm.kelas_id = '';
    router.get(props.resetUrl, {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function scoreBadge(value: number) {
    if (value >= 4) return 'success';
    if (value >= 3) return 'warning text-dark';
    return 'danger';
}

function exportUrl(format: 'excel' | 'pdf') {
    const base = props.exportUrls[format];
    const params = filterForm.kelas_id ? new URLSearchParams({ kelas_id: filterForm.kelas_id }).toString() : '';
    return params ? `${base}?${params}` : base;
}
</script>

<template>
    <Head title="Rekap Sikap" />

    <AppShell title="Rekap Sikap">
        <PageHeader
            title="Rekap Sikap Spiritual dan Sosial"
            icon="bi-heart-fill"
            :subtitle="taAktif ? `TA ${taAktif.tahun} - Semester ${semester}` : `Semester ${semester}`"
        />

        <Card title="Filter" icon="bi-funnel" class="mb-3">
            <form class="row g-2 app-table-filter" @submit.prevent="applyFilters">
                <div class="col-md-3">
                    <SearchableSelect
                        v-model="filterForm.kelas_id"
                        name="kelas_id"
                        wrapper-class="mb-0"
                        placeholder="Semua Kelas"
                        search-placeholder="Cari kelas..."
                        :options="kelasOptions"
                    />
                </div>
                <div class="col-md-2">
                    <Button type="submit" color="primary" icon="bi-search" class="w-100">Filter</Button>
                </div>
                <div class="col-md-2">
                    <Button type="button" color="outline-secondary" icon="bi-arrow-clockwise" class="w-100" @click="resetFilters">Reset</Button>
                </div>
            </form>
        </Card>

        <div v-if="hasSummary" class="d-flex flex-wrap gap-2 mb-3">
            <a :href="exportUrl('excel')" class="btn btn-sm btn-outline-success">
                <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i> Excel
            </a>
            <a :href="exportUrl('pdf')" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i> PDF
            </a>
        </div>

        <Card title="Sikap Sosial (KI-2)" icon="bi-people-fill" body-class="p-0" class="mb-3">
            <template #actions>
                <Badge color="secondary">{{ sikapSosial.length }} siswa</Badge>
            </template>

            <TableWrapper v-if="sikapSosial.length">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px;">#</th>
                            <th>Nama Siswa</th>
                            <th class="d-none d-md-table-cell">Kelas</th>
                            <th v-for="aspect in sosialAspects" :key="aspect.key">{{ aspect.label }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in sikapSosial" :key="`sosial-${item.nomor}-${item.nama_siswa}`">
                            <td class="text-center">{{ item.nomor }}</td>
                            <td>{{ item.nama_siswa }}</td>
                            <td class="d-none d-md-table-cell">{{ item.kelas }}</td>
                            <td v-for="aspect in sosialAspects" :key="aspect.key" class="text-center">
                                <Badge :color="scoreBadge(item[aspect.key])">{{ item[aspect.key] }}</Badge>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </TableWrapper>

            <EmptyState v-else title="Belum ada data sikap sosial." icon="bi-people" />
        </Card>

        <Card title="Sikap Spiritual (KI-1)" icon="bi-star-fill" body-class="p-0" class="mb-3">
            <template #actions>
                <Badge color="secondary">{{ sikapSpiritual.length }} siswa</Badge>
            </template>

            <TableWrapper v-if="sikapSpiritual.length">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px;">#</th>
                            <th>Nama Siswa</th>
                            <th class="d-none d-md-table-cell">Kelas</th>
                            <th v-for="aspect in spiritualAspects" :key="aspect.key">{{ aspect.label }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in sikapSpiritual" :key="`spiritual-${item.nomor}-${item.nama_siswa}`">
                            <td class="text-center">{{ item.nomor }}</td>
                            <td>{{ item.nama_siswa }}</td>
                            <td class="d-none d-md-table-cell">{{ item.kelas }}</td>
                            <td v-for="aspect in spiritualAspects" :key="aspect.key" class="text-center">
                                <Badge :color="scoreBadge(item[aspect.key])">{{ item[aspect.key] }}</Badge>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </TableWrapper>

            <EmptyState v-else title="Belum ada data sikap spiritual." icon="bi-star" />
        </Card>

        <Card v-if="hasSummary" title="Ringkasan" icon="bi-info-circle">
            <div class="row">
                <div v-if="sikapSosial.length" class="col-md-6 mb-3 mb-md-0">
                    <strong>Sikap Sosial - Rata-rata Semua Siswa:</strong>
                    <div class="summary-grid mt-2">
                        <div v-for="item in sosialSummary" :key="item.key" class="summary-item">
                            <div class="summary-value">{{ item.value }}</div>
                            <small class="text-muted">{{ item.label }}</small>
                        </div>
                    </div>
                </div>
                <div v-if="sikapSpiritual.length" class="col-md-6">
                    <strong>Sikap Spiritual - Rata-rata Semua Siswa:</strong>
                    <div class="summary-grid mt-2">
                        <div v-for="item in spiritualSummary" :key="item.key" class="summary-item">
                            <div class="summary-value">{{ item.value }}</div>
                            <small class="text-muted">{{ item.label }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </Card>
    </AppShell>
</template>

<style scoped>
.summary-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.summary-item {
    min-width: 80px;
    text-align: center;
}

.summary-value {
    font-size: 1.2rem;
    font-weight: 700;
}
</style>
