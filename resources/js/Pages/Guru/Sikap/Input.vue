<script setup lang="ts">
import type { PropType } from 'vue';
interface AttitudeStudent { id: number; no: number; nis: string; nama: string; sosial: Record<string, number | string | null>; spiritual: Record<string, number | string | null> }
import { Head, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import PageHeader from '../../../Components/AppShell/PageHeader.vue';
import AppShell from '../../../Layouts/AppShell.vue';
import { Badge, Button, Card, EmptyState, TableWrapper } from '../../../Components/UI';

const props = defineProps({
    kelasMapel: { type: Object as PropType<{ id: number; kelas: string; mata_pelajaran: string; back_url: string; store_url: string; export_excel_url: string; export_pdf_url: string }>, required: true },
    tahunAjaran: { type: Object as PropType<{ tahun: string } | null>, default: null },
    semester: { type: String, default: '1' },
    students: { type: Array as PropType<AttitudeStudent[]>, default: () => [] },
});

const spiritualFields = [
    { key: 'taqwa', label: 'Taqwa' },
    { key: 'kejujuran', label: 'Kejujuran' },
    { key: 'disiplin', label: 'Disiplin' },
    { key: 'sabar', label: 'Sabar' },
    { key: 'syukur', label: 'Syukur' },
    { key: 'tawadhu', label: 'Tawadhu' },
];

const sosialFields = [
    { key: 'empati', label: 'Empati' },
    { key: 'kerjasama', label: 'Kerjasama' },
    { key: 'toleransi', label: 'Toleransi' },
    { key: 'percaya_diri', label: 'Percaya Diri' },
    { key: 'komunikasi', label: 'Komunikasi' },
];

const form = useForm({
    semester: props.semester,
    sosial: buildScores('sosial'),
    spiritual: buildScores('spiritual'),
});

const title = computed(() => `Input Sikap - ${props.kelasMapel.mata_pelajaran}`);

watch(() => props.students, () => {
    form.semester = props.semester;
    form.sosial = buildScores('sosial');
    form.spiritual = buildScores('spiritual');
}, { deep: true });

function buildScores(group: 'sosial' | 'spiritual') {
    return Object.fromEntries(props.students.map((student) => [
        String(student.id),
        { ...student[group] },
    ]));
}

function average(group: 'sosial' | 'spiritual', studentId: number, fields: { key: string }[]) {
    const values = fields
        .map((field) => form[group]?.[String(studentId)]?.[field.key])
        .filter((value) => value !== null && value !== undefined && value !== '')
        .map(Number);

    if (!values.length) {
        return null;
    }

    return values.reduce((sum, value) => sum + value, 0) / values.length;
}

function averageClass(value: number | null) {
    if (value === null) return '';
    if (value >= 4) return 'excellent';
    if (value >= 3) return 'fair';
    return 'low';
}

function formatAverage(value: number | null) {
    return value === null ? null : value.toFixed(1);
}

function submit() {
    if (form.processing) {
        return;
    }

    form.post(props.kelasMapel.store_url, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="title" />

    <AppShell title="Input Sikap">
        <PageHeader title="Input Nilai Sikap" icon="bi-emoji-smile-fill">
            <template #actions>
                <Badge color="primary">{{ kelasMapel.mata_pelajaran }}</Badge>
                <Badge color="secondary">{{ kelasMapel.kelas }}</Badge>
                <Badge color="info">
                    <template v-if="tahunAjaran">TA {{ tahunAjaran.tahun }}</template>
                    <template v-else>-</template>
                    &middot; Semester {{ semester }}
                </Badge>
                <a :href="kelasMapel.export_excel_url" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i> Excel
                </a>
                <a :href="kelasMapel.export_pdf_url" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i> PDF
                </a>
            </template>
        </PageHeader>

        <form @submit.prevent="submit">
            <input v-model="form.semester" type="hidden" name="semester">

            <Card title="Sikap Spiritual (KI-1)" icon="bi-star-fill" body-class="p-0" class="mb-3">
                <template #actions>
                    <span class="text-muted text-xs">Skala 1-5</span>
                </template>

                <TableWrapper>
                    <table class="table table-bordered table-hover attitude-table mb-0">
                        <colgroup>
                            <col class="attitude-col-no">
                            <col class="attitude-col-student">
                            <col
                                v-for="field in spiritualFields"
                                :key="`spiritual-col-${field.key}`"
                                class="attitude-col-score"
                            >
                            <col class="attitude-col-total">
                        </colgroup>
                        <thead class="table-light">
                            <tr>
                                <th class="text-center">#</th>
                                <th>Nama Siswa</th>
                                <th
                                    v-for="field in spiritualFields"
                                    :key="field.key"
                                    class="text-center"
                                >
                                    {{ field.label }}
                                </th>
                                <th class="text-center">Rata-rata</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="student in students" :key="`spiritual-${student.id}`">
                                <td class="text-center text-muted">{{ student.no }}</td>
                                <td>{{ student.nama }}</td>
                                <td v-for="field in spiritualFields" :key="`${student.id}-${field.key}`">
                                    <select
                                        v-model="form.spiritual[String(student.id)][field.key]"
                                        class="form-select form-select-sm attitude-select"
                                    >
                                        <option value="">-</option>
                                        <option v-for="value in 5" :key="value" :value="value">{{ value }}</option>
                                    </select>
                                </td>
                                <td class="text-center">
                                    <strong
                                        v-if="formatAverage(average('spiritual', student.id, spiritualFields))"
                                        class="score-result"
                                        :class="averageClass(average('spiritual', student.id, spiritualFields))"
                                    >
                                        {{ formatAverage(average('spiritual', student.id, spiritualFields)) }}
                                    </strong>
                                    <span v-else class="text-muted">-</span>
                                </td>
                            </tr>
                            <tr v-if="!students.length">
                                <td colspan="9">
                                    <EmptyState title="Tidak ada siswa." icon="bi-people" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </TableWrapper>
            </Card>

            <Card title="Sikap Sosial (KI-2)" icon="bi-people-fill" body-class="p-0" class="mb-3">
                <template #actions>
                    <span class="text-muted text-xs">Skala 1-5</span>
                </template>

                <TableWrapper>
                    <table class="table table-bordered table-hover attitude-table mb-0">
                        <colgroup>
                            <col class="attitude-col-no">
                            <col class="attitude-col-student">
                            <col
                                v-for="field in sosialFields"
                                :key="`sosial-col-${field.key}`"
                                class="attitude-col-score"
                            >
                            <col class="attitude-col-total">
                        </colgroup>
                        <thead class="table-light">
                            <tr>
                                <th class="text-center">#</th>
                                <th>Nama Siswa</th>
                                <th
                                    v-for="field in sosialFields"
                                    :key="field.key"
                                    class="text-center"
                                >
                                    {{ field.label }}
                                </th>
                                <th class="text-center">Rata-rata</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="student in students" :key="`sosial-${student.id}`">
                                <td class="text-center text-muted">{{ student.no }}</td>
                                <td>{{ student.nama }}</td>
                                <td v-for="field in sosialFields" :key="`${student.id}-${field.key}`">
                                    <select
                                        v-model="form.sosial[String(student.id)][field.key]"
                                        class="form-select form-select-sm attitude-select"
                                    >
                                        <option value="">-</option>
                                        <option v-for="value in 5" :key="value" :value="value">{{ value }}</option>
                                    </select>
                                </td>
                                <td class="text-center">
                                    <strong
                                        v-if="formatAverage(average('sosial', student.id, sosialFields))"
                                        class="score-result"
                                        :class="averageClass(average('sosial', student.id, sosialFields))"
                                    >
                                        {{ formatAverage(average('sosial', student.id, sosialFields)) }}
                                    </strong>
                                    <span v-else class="text-muted">-</span>
                                </td>
                            </tr>
                            <tr v-if="!students.length">
                                <td colspan="8">
                                    <EmptyState title="Tidak ada siswa." icon="bi-people" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </TableWrapper>
            </Card>

            <div class="d-flex justify-content-between align-items-center mb-4 gap-2 flex-wrap">
                <a :href="kelasMapel.back_url" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Kembali
                </a>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted text-xs">{{ students.length }} siswa</span>
                    <Button type="submit" color="success" size="" icon="bi-save" :disabled="form.processing">
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Semua' }}
                    </Button>
                </div>
            </div>
        </form>
    </AppShell>
</template>

<style scoped>
.attitude-table {
    min-width: 950px;
    table-layout: fixed;
    font-size: 0.82rem;
}

.attitude-col-no {
    width: 44px;
}

.attitude-col-student {
    width: 320px;
}

.attitude-col-score {
    width: 76px;
}

.attitude-col-total {
    width: 108px;
}

.attitude-table th,
.attitude-table td {
    vertical-align: middle;
}

.attitude-table th {
    padding: 0.65rem 0.45rem;
    line-height: 1.2;
    white-space: normal;
}

.attitude-table td {
    padding: 0.55rem 0.45rem;
}

.attitude-table td:nth-child(2) {
    white-space: normal;
}

.attitude-select {
    width: 100%;
    min-width: 0;
    text-align: center;
}
</style>
