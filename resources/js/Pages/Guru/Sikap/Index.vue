<script setup lang="ts">
import type { PropType } from 'vue';
interface AttitudeStudent { id: number; no: number; nis: string; nama: string; sosial: Record<string, number | string | null>; spiritual: Record<string, number | string | null> }
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import PageHeader from '../../../Components/AppShell/PageHeader.vue';
import AppShell from '../../../Layouts/AppShell.vue';
import { Badge, Button, Card, EmptyState, TableWrapper } from '../../../Components/UI';

const props = defineProps({
    kelasMapel: { type: Array as PropType<{ id: number; label: string }[]>, default: () => [] },
    tahunAjaran: { type: Object as PropType<{ tahun: string } | null>, default: null },
    semester: { type: String, default: '1' },
    groups: { type: Array as PropType<{ kelas_mapel_id: number; kelas: string; mata_pelajaran: string; export_excel_url: string; export_pdf_url: string; students: AttitudeStudent[] }[]>, default: () => [] },
    storeUrl: { type: String, required: true },
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

const selectedKelasMapelId = ref(props.kelasMapel[0]?.id ?? null);

const form = useForm({
    semester: props.semester,
    kelas_mapel_ids: selectedKelasMapelId.value ? [selectedKelasMapelId.value] : [],
    sosial: buildScores('sosial'),
    spiritual: buildScores('spiritual'),
});

const activeGroup = computed(() => props.groups.find((group) => group.kelas_mapel_id === selectedKelasMapelId.value) ?? null);

watch(() => props.groups, () => {
    form.semester = props.semester;
    selectedKelasMapelId.value = props.kelasMapel[0]?.id ?? null;
    form.kelas_mapel_ids = selectedKelasMapelId.value ? [selectedKelasMapelId.value] : [];
    form.sosial = buildScores('sosial');
    form.spiritual = buildScores('spiritual');
}, { deep: true });

watch(selectedKelasMapelId, (value) => {
    form.kelas_mapel_ids = value ? [value] : [];
});

function buildScores(group: 'sosial' | 'spiritual') {
    return Object.fromEntries(props.groups.map((kelasGroup) => [
        String(kelasGroup.kelas_mapel_id),
        Object.fromEntries(kelasGroup.students.map((student) => [
            String(student.id),
            { ...student[group] },
        ])),
    ]));
}

function average(group: 'sosial' | 'spiritual', studentId: number, fields: { key: string }[]) {
    const values = fields
        .map((field) => form[group]?.[String(activeGroup.value?.kelas_mapel_id)]?.[String(studentId)]?.[field.key])
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

    form.kelas_mapel_ids = selectedKelasMapelId.value ? [selectedKelasMapelId.value] : [];
    form.post(props.storeUrl, { preserveScroll: true });
}
</script>

<template>
    <Head title="Sikap" />

    <AppShell title="Sikap">
        <PageHeader
            title="Input Nilai Sikap"
            subtitle="Pilih kelas penugasan, lalu input nilai sikap siswa."
            icon="bi-emoji-smile-fill"
        >
            <template #actions>
                <Badge color="info">
                    <template v-if="tahunAjaran">TA {{ tahunAjaran.tahun }}</template>
                    <template v-else>-</template>
                    &middot; Semester {{ semester }}
                </Badge>
            </template>
        </PageHeader>

        <form v-if="kelasMapel.length" @submit.prevent="submit">
            <Card title="Kelas dan Mata Pelajaran" icon="bi-funnel" class="mb-4">
                <label for="kelas-mapel" class="form-label">Kelas Aktif</label>
                <select id="kelas-mapel" v-model="selectedKelasMapelId" class="form-select">
                    <option v-for="item in kelasMapel" :key="item.id" :value="item.id">
                        {{ item.label }}
                    </option>
                </select>
                <div v-if="form.errors.kelas_mapel_ids" class="text-danger small mt-2">
                    {{ form.errors.kelas_mapel_ids }}
                </div>
            </Card>

            <div class="d-flex justify-content-end mb-3">
                <Button type="submit" color="success" icon="bi-save" :disabled="form.processing || !activeGroup">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Sikap' }}
                </Button>
            </div>

            <template v-if="activeGroup">
                <Card
                    :title="`${activeGroup.mata_pelajaran} - ${activeGroup.kelas}`"
                    icon="bi-emoji-smile"
                    class="mb-3"
                >
                    <template #actions>
                        <div class="d-flex align-items-center gap-2">
                            <a :href="activeGroup.export_excel_url" class="btn btn-sm btn-outline-success">
                                <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i> Excel
                            </a>
                            <a :href="activeGroup.export_pdf_url" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i> PDF
                            </a>
                        </div>
                    </template>

                    <div class="text-muted text-sm">
                        Skala penilaian 1-5. Nilai rata-rata dihitung otomatis dari kolom yang terisi.
                    </div>
                </Card>

                <Card title="Sikap Spiritual (KI-1)" icon="bi-star-fill" body-class="p-0" class="mb-3">
                    <template #actions>
                        <span class="text-muted text-xs">Skala 1-5</span>
                    </template>

                    <TableWrapper>
                        <table class="table table-bordered table-hover app-table attitude-table mb-0">
                            <colgroup>
                                <col class="attitude-col-no">
                                <col class="attitude-col-nis">
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
                                    <th>NIS</th>
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
                                <tr v-for="student in activeGroup.students" :key="`spiritual-${activeGroup.kelas_mapel_id}-${student.id}`">
                                    <td class="text-center text-muted">{{ student.no }}</td>
                                    <td><code>{{ student.nis }}</code></td>
                                    <td>{{ student.nama }}</td>
                                    <td v-for="field in spiritualFields" :key="`${activeGroup.kelas_mapel_id}-${student.id}-${field.key}`">
                                        <select
                                            v-model="form.spiritual[String(activeGroup.kelas_mapel_id)][String(student.id)][field.key]"
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
                                <tr v-if="!activeGroup.students.length">
                                    <td colspan="10">
                                        <EmptyState title="Tidak ada siswa di kelas ini." icon="bi-people" />
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
                        <table class="table table-bordered table-hover app-table attitude-table mb-0">
                            <colgroup>
                                <col class="attitude-col-no">
                                <col class="attitude-col-nis">
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
                                    <th>NIS</th>
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
                                <tr v-for="student in activeGroup.students" :key="`sosial-${activeGroup.kelas_mapel_id}-${student.id}`">
                                    <td class="text-center text-muted">{{ student.no }}</td>
                                    <td><code>{{ student.nis }}</code></td>
                                    <td>{{ student.nama }}</td>
                                    <td v-for="field in sosialFields" :key="`${activeGroup.kelas_mapel_id}-${student.id}-${field.key}`">
                                        <select
                                            v-model="form.sosial[String(activeGroup.kelas_mapel_id)][String(student.id)][field.key]"
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
                                <tr v-if="!activeGroup.students.length">
                                    <td colspan="9">
                                        <EmptyState title="Tidak ada siswa di kelas ini." icon="bi-people" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </TableWrapper>
                </Card>
            </template>

            <Card v-else>
                <EmptyState title="Pilih kelas penugasan." icon="bi-funnel" />
            </Card>

            <div class="d-flex justify-content-end">
                <Button type="submit" color="success" icon="bi-save" :disabled="form.processing || !activeGroup">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Sikap' }}
                </Button>
            </div>
        </form>

        <Card v-else>
            <EmptyState title="Anda belum memiliki penugasan" icon="bi-book" />
        </Card>
    </AppShell>
</template>

<style scoped>
.attitude-table {
    min-width: 1060px;
    table-layout: fixed;
}

.attitude-col-no {
    width: 44px;
}

.attitude-col-nis {
    width: 110px;
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

.attitude-table td:nth-child(3) {
    white-space: normal;
}

.attitude-select {
    width: 100%;
    min-width: 0;
    text-align: center;
}
</style>
