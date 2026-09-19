<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';
import PageHeader from '../../../Components/AppShell/PageHeader.vue';
import AppShell from '../../../Layouts/AppShell.vue';
import { Badge, Button, Card, EmptyState, TableWrapper } from '../../../Components/UI';

import type { GradeGroup, Score, ScoreField } from '../../../types/assessment';

const props = withDefaults(defineProps<{
    kelasMapel?: { id: number; label: string }[];
    tahunAjaran?: { id: number; tahun: string } | null;
    semester?: string;
    groups?: GradeGroup[];
    storeUrl: string;
}>(), { kelasMapel: () => [], tahunAjaran: null, semester: '1', groups: () => [] });

const fieldGroups: { key: ScoreField; label: string; readonly?: boolean }[] = [
    { key: 'sum1', label: 'SUM1' },
    { key: 'sum2', label: 'SUM2' },
    { key: 'sum3', label: 'SUM3' },
    { key: 'sum4', label: 'SUM4' },
    { key: 'nilai_harian', label: 'Dari Tugas', readonly: true },
    { key: 'sts', label: 'Nilai' },
    { key: 'sas', label: 'Nilai' },
    { key: 'sat', label: 'Nilai' },
];
const editableFieldKeys = fieldGroups.filter((field) => !field.readonly).map((field) => field.key);

const selectedKelasMapelId = ref(props.kelasMapel[0]?.id ?? null);
const pasteStatus = ref('');

const form = useForm({
    semester: props.semester,
    kelas_mapel_ids: selectedKelasMapelId.value ? [selectedKelasMapelId.value] : [],
    nilai: buildNilai(),
});

const activeGroup = computed(() => props.groups.find((group) => group.kelas_mapel_id === selectedKelasMapelId.value) ?? null);

watch(() => props.groups, () => {
    form.semester = props.semester;
    selectedKelasMapelId.value = props.kelasMapel[0]?.id ?? null;
    form.kelas_mapel_ids = selectedKelasMapelId.value ? [selectedKelasMapelId.value] : [];
    form.nilai = buildNilai();
}, { deep: true });

watch(selectedKelasMapelId, (value) => {
    form.kelas_mapel_ids = value ? [value] : [];
    pasteStatus.value = '';
});

function buildNilai() {
    return Object.fromEntries(props.groups.map((group) => [
        String(group.kelas_mapel_id),
        Object.fromEntries(group.students.map((student) => [
            String(student.id),
            { ...student.scores },
        ])),
    ]));
}

function scoreClass(value: Score | undefined) {
    if (value === null || value === undefined || value === '') return '';
    if (Number(value) >= 92) return 'excellent';
    if (Number(value) >= 83) return 'good';
    if (Number(value) >= 75) return 'fair';
    return 'low';
}

function formatScore(value: Score | undefined) {
    if (value === null || value === undefined || value === '') return null;
    return Number(value).toFixed(1);
}

function normalizeScore(value: Score | undefined) {
    return String(value ?? '').trim().replace(',', '.');
}

function parseScoreText(text: string) {
    if (!/[\r\n\t\u2028\u2029]/.test(text)) return [];

    const rows = text.replace(/\r\n?|\u2028|\u2029/g, '\n').split('\n');
    while (rows.length > 1 && rows.at(-1) === '') rows.pop();

    return rows.map((row) => row.split('\t').map(normalizeScore));
}

function parseClipboardHtml(html: string) {
    if (!html || typeof DOMParser === 'undefined') return [];

    const document = new DOMParser().parseFromString(html, 'text/html');
    return Array.from(document.querySelectorAll('table tr'))
        .map((row) => Array.from(row.querySelectorAll('th, td')).map((cell) => normalizeScore(cell.textContent)))
        .filter((row) => row.length);
}

function parsePastedScoreGrid(event: ClipboardEvent) {
    const clipboard = event.clipboardData ?? (window as Window & { clipboardData?: DataTransfer }).clipboardData;
    const text = clipboard?.getData('text/plain') || clipboard?.getData('Text') || '';
    const textGrid = parseScoreText(text);

    return textGrid.length
        ? textGrid
        : parseClipboardHtml(clipboard?.getData('text/html') ?? '');
}

function setStudentScore(studentIndex: number, fieldKey: ScoreField, score: string) {
    const group = activeGroup.value;
    const student = group?.students[studentIndex];
    if (!group || !student || !editableFieldKeys.includes(fieldKey)) return false;

    const groupId = String(group.kelas_mapel_id);
    const studentId = String(student.id);
    const studentScores = form.nilai[groupId]?.[studentId];
    if (!studentScores) return false;

    form.nilai[groupId][studentId] = {
        ...studentScores,
        [fieldKey]: score,
    };

    return true;
}

async function applyScoreGrid(grid: string[][], studentIndex: number, fieldKey: ScoreField) {
    const startFieldIndex = editableFieldKeys.indexOf(fieldKey);
    if (startFieldIndex < 0) return;

    const isSingleColumn = grid.every((row) => row.length === 1);
    const targetFields = editableFieldKeys.slice(startFieldIndex);
    let pastedCount = 0;
    const lastTarget: { value: { studentIndex: number; fieldKey: ScoreField } | null } = { value: null };

    grid.forEach((row, rowOffset) => {
        const scores = isSingleColumn ? [row[0]] : row;
        const fields = isSingleColumn ? [fieldKey] : targetFields;

        scores.forEach((score, columnOffset) => {
            const targetField = fields[columnOffset];
            if (targetField && setStudentScore(studentIndex + rowOffset, targetField, score)) {
                pastedCount += 1;
                lastTarget.value = { studentIndex: studentIndex + rowOffset, fieldKey: targetField };
            }
        });
    });

    pasteStatus.value = pastedCount ? `${pastedCount} nilai ditempel` : '';

    await nextTick();
    if (lastTarget.value) {
        document.querySelector<HTMLTextAreaElement>(
            `.score-input[data-student-index="${lastTarget.value.studentIndex}"][data-field-key="${lastTarget.value.fieldKey}"]`,
        )?.focus();
    }
}

function handleScorePaste(event: ClipboardEvent, studentIndex: number, fieldKey: ScoreField) {
    const grid = parsePastedScoreGrid(event);
    if (!grid.length) return;

    event.preventDefault();
    applyScoreGrid(grid, studentIndex, fieldKey);
}

function handleScoreInput(event: Event, studentIndex: number, fieldKey: ScoreField) {
    if (!(event.target instanceof HTMLTextAreaElement)) return;
    const grid = parseScoreText(event.target.value);
    if (grid.length) applyScoreGrid(grid, studentIndex, fieldKey);
}

function handleScoreKeyup(event: KeyboardEvent) {
    const input = event.target;
    if (!(input instanceof HTMLTextAreaElement)) return;
    if (input.value.length < 3) return;

    const inputs = Array.from(document.querySelectorAll<HTMLTextAreaElement>('.score-input'));
    const next = inputs[inputs.indexOf(input) + 1];
    next?.focus();
}

function submit() {
    if (form.processing) {
        return;
    }

    form.kelas_mapel_ids = selectedKelasMapelId.value ? [selectedKelasMapelId.value] : [];

    const groupId = String(selectedKelasMapelId.value ?? '');
    Object.values(form.nilai[groupId] ?? {}).forEach((scores) => {
        editableFieldKeys.forEach((fieldKey) => {
            scores[fieldKey] = normalizeScore(scores[fieldKey]);
        });
    });

    form.post(props.storeUrl, { preserveScroll: true });
}
</script>

<template>
    <Head title="Nilai" />

    <AppShell title="Nilai">
        <PageHeader
            title="Input Nilai"
            subtitle="Pilih kelas penugasan, lalu input nilai siswa."
            icon="bi-pencil-square"
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
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Nilai' }}
                </Button>
            </div>

            <Card
                v-if="activeGroup"
                :title="`${activeGroup.mata_pelajaran} - ${activeGroup.kelas}`"
                icon="bi-table"
                body-class="p-0"
                class="mb-4"
            >
                <template #actions>
                    <div class="d-flex align-items-center gap-2">
                        <span v-if="pasteStatus" class="badge bg-soft-success">{{ pasteStatus }}</span>
                        <a :href="activeGroup.export_excel_url" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i> Excel
                        </a>
                        <a :href="activeGroup.export_pdf_url" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i> PDF
                        </a>
                    </div>
                </template>

                <TableWrapper>
                    <table class="table table-bordered table-hover app-table grade-table mb-0">
                        <colgroup>
                            <col class="grade-col-no">
                            <col class="grade-col-nis">
                            <col class="grade-col-student">
                            <col v-for="field in fieldGroups" :key="`col-${field.key}`" class="grade-col-score">
                            <col class="grade-col-total">
                        </colgroup>
                        <thead class="table-light">
                            <tr>
                                <th class="text-center w-row-number">#</th>
                                <th class="min-w-nis">NIS</th>
                                <th class="min-w-student">Nama Siswa</th>
                                <th colspan="4" class="text-center bg-soft-success">Sumatif Harian</th>
                                <th class="text-center bg-soft-success">Nilai Harian</th>
                                <th class="text-center bg-soft-warning">STS</th>
                                <th class="text-center bg-soft-warning">SAS</th>
                                <th class="text-center bg-soft-danger">SAT</th>
                                <th class="text-center bg-soft-muted">Rata-rata Akhir</th>
                            </tr>
                            <tr class="table-light">
                                <th></th>
                                <th></th>
                                <th></th>
                                <th v-for="field in fieldGroups" :key="field.key" class="text-center w-score">
                                    {{ field.label }}
                                </th>
                                <th class="text-center w-score-total">Auto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(student, studentIndex) in activeGroup.students" :key="`${activeGroup.kelas_mapel_id}-${student.id}`">
                                <td class="text-center text-muted">{{ student.no }}</td>
                                <td><code>{{ student.nis }}</code></td>
                                <td>{{ student.nama }}</td>
                                <td v-for="field in fieldGroups" :key="`${activeGroup.kelas_mapel_id}-${student.id}-${field.key}`" class="text-center">
                                    <span
                                        v-if="field.readonly"
                                        class="score-result readonly-score"
                                        :class="scoreClass(form.nilai[String(activeGroup.kelas_mapel_id)][String(student.id)][field.key])"
                                    >
                                        {{ formatScore(form.nilai[String(activeGroup.kelas_mapel_id)][String(student.id)][field.key]) ?? '-' }}
                                    </span>
                                    <textarea
                                        v-else
                                        v-model="form.nilai[String(activeGroup.kelas_mapel_id)][String(student.id)][field.key]"
                                        rows="1"
                                        inputmode="decimal"
                                        class="form-control form-control-sm score-input"
                                        autocomplete="off"
                                        placeholder="-"
                                        :data-student-index="studentIndex"
                                        :data-field-key="field.key"
                                        @keyup="handleScoreKeyup"
                                        @paste.stop="handleScorePaste($event, studentIndex, field.key)"
                                        @input="handleScoreInput($event, studentIndex, field.key)"
                                        @focus="($event.target as HTMLTextAreaElement).select()"
                                    ></textarea>
                                </td>
                                <td class="text-center">
                                    <strong v-if="formatScore(student.rata_akhir)" class="score-result" :class="scoreClass(student.rata_akhir)">
                                        {{ formatScore(student.rata_akhir) }}
                                    </strong>
                                    <span v-else class="text-muted">-</span>
                                </td>
                            </tr>
                            <tr v-if="!activeGroup.students.length">
                                <td colspan="12">
                                    <EmptyState title="Tidak ada siswa di kelas ini." icon="bi-people" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </TableWrapper>
            </Card>

            <Card v-else>
                <EmptyState title="Pilih kelas penugasan." icon="bi-funnel" />
            </Card>

            <div class="d-flex justify-content-end">
                <Button type="submit" color="success" icon="bi-save" :disabled="form.processing || !activeGroup">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Nilai' }}
                </Button>
            </div>
        </form>

        <Card v-else>
            <EmptyState title="Anda belum memiliki penugasan" icon="bi-bar-chart" />
        </Card>
    </AppShell>
</template>

<style scoped>
.grade-table {
    min-width: 1220px;
    table-layout: fixed;
}

.grade-col-no {
    width: 44px;
}

.grade-col-nis {
    width: 110px;
}

.grade-col-student {
    width: 320px;
}

.grade-col-score {
    width: 78px;
}

.grade-col-total {
    width: 108px;
}

.grade-table th,
.grade-table td {
    vertical-align: middle;
}

.grade-table th {
    padding: 0.65rem 0.45rem;
    line-height: 1.2;
    white-space: normal;
}

.grade-table td {
    padding: 0.55rem 0.45rem;
}

.grade-table td:nth-child(3) {
    white-space: normal;
}

.grade-table .score-input {
    width: 100% !important;
    min-width: 0;
    height: 31px;
    min-height: 31px;
    overflow: hidden;
    resize: none;
    text-align: center;
}

.readonly-score {
    display: inline-flex;
    width: 100%;
    min-width: 0;
    min-height: 31px;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--bs-border-color);
    border-radius: 6px;
    background: var(--surface-muted);
    font-weight: 700;
}
</style>
