<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';
import AppShell from '../../../Layouts/AppShell.vue';
import { Badge, Button, Card, DashboardHero, EmptyState, QuickActionBar, TableWrapper } from '../../../Components/UI';

import type { GradeCourse, GradeStudent, Score, ScoreField } from '../../../types/assessment';

const props = withDefaults(defineProps<{
    kelasMapel: GradeCourse;
    tahunAjaran?: { id: number; tahun: string } | null;
    semester?: string;
    students?: GradeStudent[];
}>(), { tahunAjaran: null, semester: '1', students: () => [] });

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
const pasteStatus = ref('');

const form = useForm({
    semester: props.semester,
    nilai: buildNilai(),
});

const title = computed(() => `Input Nilai - ${props.kelasMapel.mata_pelajaran}`);

const courseTabs = computed(() => [
    { label: 'Ringkasan', href: props.kelasMapel.workspace_url, icon: 'bi-grid-1x2' },
    { label: 'Materi', href: `/guru/materi/${props.kelasMapel.id}/list`, icon: 'bi-file-earmark-text' },
    { label: 'Tugas', href: `/guru/tugas/${props.kelasMapel.id}/list`, icon: 'bi-journal-check' },
    { label: 'Nilai', href: '#', icon: 'bi-bar-chart', active: true },
    { label: 'Absensi', href: `/guru/absensi/${props.kelasMapel.id}/create`, icon: 'bi-clipboard-check' },
    { label: 'Chat', href: `/guru/chat/${props.kelasMapel.id}`, icon: 'bi-chat-dots' },
]);

watch(() => props.students, () => {
    form.semester = props.semester;
    form.nilai = buildNilai();
}, { deep: true });

function buildNilai() {
    return Object.fromEntries(props.students.map((student) => [
        String(student.id),
        { ...student.scores },
    ]));
}

function scoreClass(value: Score | undefined) {
    if (value === null || value === undefined || value === '') {
        return '';
    }

    if (Number(value) >= 92) return 'excellent';
    if (Number(value) >= 83) return 'good';
    if (Number(value) >= 75) return 'fair';
    return 'low';
}

function formatScore(value: Score | undefined) {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    return Number(value).toFixed(1);
}

function normalizeScore(value: Score | undefined) {
    return String(value ?? '').trim().replace(',', '.');
}

function parseClipboardHtml(html: string) {
    if (!html || typeof DOMParser === 'undefined') {
        return [];
    }

    const document = new DOMParser().parseFromString(html, 'text/html');

    return Array.from(document.querySelectorAll('table tr'))
        .map((row) => Array.from(row.querySelectorAll('th, td')).map((cell) => normalizeScore(cell.textContent)))
        .filter((row) => row.length);
}

function parseScoreText(text: string) {
    if (!/[\r\n\t\u2028\u2029]/.test(text)) {
        return [];
    }

    const rows = text
        .replace(/\r\n?|\u2028|\u2029/g, '\n')
        .split('\n');

    while (rows.length > 1 && rows.at(-1) === '') {
        rows.pop();
    }

    return rows.map((row) => row.split('\t').map(normalizeScore));
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
    const student = props.students[studentIndex];
    if (!student || !editableFieldKeys.includes(fieldKey)) {
        return false;
    }

    const studentId = String(student.id);
    const studentScores = form.nilai[studentId];
    if (!studentScores) {
        return false;
    }

    form.nilai[studentId] = {
        ...studentScores,
        [fieldKey]: score,
    };

    return true;
}

async function applyScoreGrid(grid: string[][], studentIndex: number, fieldKey: ScoreField) {
    const startFieldIndex = editableFieldKeys.indexOf(fieldKey);
    if (startFieldIndex < 0) {
        return;
    }

    const isSingleColumn = grid.every((row) => row.length === 1);
    const editableTargetFields = editableFieldKeys.slice(startFieldIndex);
    let pastedCount = 0;
    const lastTarget: { value: { studentIndex: number; fieldKey: ScoreField } | null } = { value: null };

    grid.forEach((row, rowOffset) => {
        if (isSingleColumn) {
            if (setStudentScore(studentIndex + rowOffset, fieldKey, row[0])) {
                pastedCount += 1;
                lastTarget.value = { studentIndex: studentIndex + rowOffset, fieldKey };
            }
            return;
        }

        row.forEach((score, colOffset) => {
            const targetFieldKey = editableTargetFields[colOffset];
            if (!targetFieldKey) {
                return;
            }

            if (setStudentScore(studentIndex + rowOffset, targetFieldKey, score)) {
                pastedCount += 1;
                lastTarget.value = { studentIndex: studentIndex + rowOffset, fieldKey: targetFieldKey };
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
    if (!grid.length) {
        return;
    }

    event.preventDefault();
    applyScoreGrid(grid, studentIndex, fieldKey);
}

function handleScoreInput(event: Event, studentIndex: number, fieldKey: ScoreField) {
    if (!(event.target instanceof HTMLTextAreaElement)) return;
    const grid = parseScoreText(event.target.value);
    if (grid.length) {
        applyScoreGrid(grid, studentIndex, fieldKey);
    }
}

function handleScoreKeyup(event: KeyboardEvent) {
    const input = event.target;
    if (!(input instanceof HTMLTextAreaElement)) return;

    if (input.value.length < 3) {
        return;
    }

    const inputs = Array.from(document.querySelectorAll<HTMLTextAreaElement>('.score-input'));
    const next = inputs[inputs.indexOf(input) + 1];

    next?.focus();
}

function submit() {
    if (form.processing) {
        return;
    }

    props.students.forEach((student) => {
        const scores = form.nilai[String(student.id)];
        if (!scores) {
            return;
        }

        editableFieldKeys.forEach((fieldKey) => {
            scores[fieldKey] = normalizeScore(scores[fieldKey]);
        });
    });

    form.post(props.kelasMapel.store_url, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="title" />

    <AppShell title="Input Nilai">
        <DashboardHero
            eyebrow="Workspace Kelas/Mapel"
            :title="kelasMapel.mata_pelajaran"
            :subtitle="`${kelasMapel.kelas} - Input dan pantau nilai siswa.`"
            icon="bi-bar-chart-fill"
            tone="teacher"
        >
            <template #actions>
                <QuickActionBar :actions="[{ label: 'Ringkasan', href: kelasMapel.workspace_url, icon: 'bi-grid-1x2', color: 'light' }]" />
            </template>
        </DashboardHero>

        <nav class="workspace-tabs" aria-label="Navigasi kelas dan mata pelajaran">
            <a v-for="tab in courseTabs" :key="tab.label" :href="tab.href" class="workspace-tab" :class="{ 'is-active': tab.active }">
                <i class="bi" :class="tab.icon" aria-hidden="true"></i>{{ tab.label }}
            </a>
        </nav>

        <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
            <div class="d-flex gap-2 flex-wrap">
                <Badge color="secondary">{{ kelasMapel.kelas }}</Badge>
                <Badge color="info">TA {{ tahunAjaran?.tahun ?? '-' }} &middot; Semester {{ semester }}</Badge>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a :href="kelasMapel.export_excel_url" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i>Excel</a>
                <a :href="kelasMapel.export_pdf_url" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i>PDF</a>
            </div>
        </div>

        <form @submit.prevent="submit">
            <Card title="Input Nilai Kurikulum Merdeka" icon="bi-table" body-class="p-0">
                <template #actions>
                    <Button type="submit" color="success" icon="bi-save" :disabled="form.processing">
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Semua' }}
                    </Button>
                </template>

                <TableWrapper>
                    <div class="p-3 border-bottom bg-light-subtle">
                        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                            <span class="text-muted small">Input nilai per siswa, gunakan tombol Enter untuk berpindah kolom.</span>
                            <div class="d-flex align-items-center gap-2">
                                <span v-if="pasteStatus" class="badge bg-soft-success">{{ pasteStatus }}</span>
                                <span class="badge bg-soft-primary">{{ students.length }} siswa</span>
                            </div>
                        </div>
                    </div>
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
                                <th
                                    v-for="field in fieldGroups"
                                    :key="field.key"
                                    class="text-center w-score"
                                >
                                    {{ field.label }}
                                </th>
                                <th class="text-center w-score-total">Auto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(student, studentIndex) in students" :key="student.id">
                                <td class="text-center text-muted">{{ student.no }}</td>
                                <td><code>{{ student.nis }}</code></td>
                                <td>{{ student.nama }}</td>
                                <td v-for="field in fieldGroups" :key="`${student.id}-${field.key}`" class="text-center">
                                    <span
                                        v-if="field.readonly"
                                        class="score-result readonly-score"
                                        :class="scoreClass(form.nilai[String(student.id)][field.key])"
                                        :title="'Nilai harian dihitung otomatis dari nilai tugas'"
                                    >
                                        {{ formatScore(form.nilai[String(student.id)][field.key]) ?? '-' }}
                                    </span>
                                    <textarea
                                        v-else
                                        v-model="form.nilai[String(student.id)][field.key]"
                                        rows="1"
                                        inputmode="decimal"
                                        class="form-control form-control-sm score-input"
                                        :class="{ 'border-danger border-opacity-25': student.rata_akhir && field.key === 'sat' }"
                                        autocomplete="off"
                                        pattern="^\\d{1,3}([,.]\\d{1,2})?$"
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
                                    <strong
                                        v-if="formatScore(student.rata_akhir)"
                                        class="score-result"
                                        :class="scoreClass(student.rata_akhir)"
                                    >
                                        {{ formatScore(student.rata_akhir) }}
                                    </strong>
                                    <span v-else class="text-muted">-</span>
                                </td>
                            </tr>
                            <tr v-if="!students.length">
                                <td colspan="12">
                                    <EmptyState title="Tidak ada siswa di kelas ini." icon="bi-people" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </TableWrapper>

                <template #footer>
                    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                        <a href="/guru/nilai" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Kembali
                        </a>
                        <div class="d-flex align-items-center gap-3">
                            <span class="text-muted text-xs">{{ students.length }} siswa</span>
                            <Button type="submit" color="success" icon="bi-save" :disabled="form.processing">
                                {{ form.processing ? 'Menyimpan...' : 'Simpan Semua' }}
                            </Button>
                        </div>
                    </div>
                </template>
            </Card>
        </form>
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

@media (max-width: 767px) {
    .score-input {
        min-width: 56px;
        padding: 0.25rem 0.35rem;
        font-size: 0.78rem;
    }
}
</style>
