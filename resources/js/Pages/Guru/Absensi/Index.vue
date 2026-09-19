<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, reactive, watch } from 'vue';
import PageHeader from '../../../Components/AppShell/PageHeader.vue';
import { SearchableSelect, TextInput } from '../../../Components/Form';
import AppShell from '../../../Layouts/AppShell.vue';
import { Badge, Button, Card, DashboardHero, EmptyState, QuickActionBar, TableWrapper } from '../../../Components/UI';

import type { AttendanceMeeting, AttendanceStatus, AttendanceStudent, GradeCourse } from '../../../types/assessment';

const props = withDefaults(defineProps<{
    kelasMapel?: { id: number; label: string }[];
    filters?: { kelas_mapel_id?: string; bulan?: string; siswa_id?: string };
    highlightedSiswaId?: number | string | null;
    selected?: (GradeCourse & { has_schedule: boolean; schedule_url: string }) | null;
    weeks?: AttendanceMeeting[];
    students?: AttendanceStudent[];
}>(), {
    kelasMapel: () => [], filters: () => ({}), highlightedSiswaId: null,
    selected: null, weeks: () => [], students: () => [],
});

const filterForm = reactive({
    kelas_mapel_id: props.filters.kelas_mapel_id ?? '',
    bulan: props.filters.bulan ?? '',
});

const form = useForm({
    bulan: props.filters.bulan ?? '',
    absensi: buildAbsensi(),
});

const statusOptions = [
    { value: '', label: '-' },
    { value: 'hadir', label: 'H' },
    { value: 'sakit', label: 'S' },
    { value: 'izin', label: 'I' },
    { value: 'alpha', label: 'A' },
];

const meetingKeys = computed(() => props.weeks.map((week) => String(week.key)));
const highlightedStudent = computed(() => props.students.find(
    (student) => Number(student.id) === Number(props.highlightedSiswaId)
) ?? null);

const courseTabs = computed(() => {
    if (!props.selected) return [];

    return [
        { label: 'Ringkasan', href: props.selected.workspace_url, icon: 'bi-grid-1x2' },
        { label: 'Materi', href: `/guru/materi/${props.selected.id}/list`, icon: 'bi-file-earmark-text' },
        { label: 'Tugas', href: `/guru/tugas/${props.selected.id}/list`, icon: 'bi-journal-check' },
        { label: 'Nilai', href: `/guru/nilai/${props.selected.id}/input`, icon: 'bi-bar-chart' },
        { label: 'Absensi', href: '#', icon: 'bi-clipboard-check', active: true },
        { label: 'Chat', href: `/guru/chat/${props.selected.id}`, icon: 'bi-chat-dots' },
    ];
});

watch(() => [props.filters.bulan, props.students], () => {
    form.bulan = props.filters.bulan ?? '';
    form.absensi = buildAbsensi();
}, { deep: true });

watch(() => [props.highlightedSiswaId, props.students.length], async () => {
    if (!props.highlightedSiswaId || typeof document === 'undefined') {
        return;
    }

    await nextTick();
    document.getElementById(`attendance-student-${props.highlightedSiswaId}`)?.scrollIntoView({
        behavior: 'smooth',
        block: 'center',
    });
}, { immediate: true });

function buildAbsensi() {
    return Object.fromEntries(props.students.map((student) => [
        String(student.id),
        { ...(student.absensi ?? {}) },
    ]));
}

function applyFilters() {
    router.get('/guru/absensi', cleanFilters(), {
        preserveScroll: true,
        replace: true,
    });
}

function resetFilters() {
    router.get('/guru/absensi', {}, {
        preserveScroll: true,
        replace: true,
    });
}

function cleanFilters() {
    return Object.fromEntries(Object.entries(filterForm).filter(([, value]) => value !== '' && value !== null));
}

function fillColumn(meetingKey: string | number, event: Event) {
    if (!(event.target instanceof HTMLSelectElement)) return;
    const value = event.target.value;
    event.target.value = '';
    if (!['hadir', 'sakit', 'izin', 'alpha'].includes(value)) return;
    if (!value) {
        return;
    }

    props.students.forEach((student) => {
        if (form.absensi[String(student.id)] && meetingHasDate(meetingKey)) {
            form.absensi[String(student.id)][String(meetingKey)] = value as AttendanceStatus;
        }
    });
}

function meetingHasDate(meetingKey: string | number) {
    return props.weeks.some((week) => String(week.key) === String(meetingKey) && week.date);
}

function counts(studentId: number) {
    const row = form.absensi[String(studentId)] ?? {};

    return meetingKeys.value.reduce((summary, meetingKey) => {
        const status = row[meetingKey];

        if (status === 'hadir') summary.hadir += 1;
        if (status === 'sakit') summary.sakit += 1;
        if (status === 'izin') summary.izin += 1;
        if (status === 'alpha') summary.alpha += 1;

        return summary;
    }, { hadir: 0, sakit: 0, izin: 0, alpha: 0 });
}

function submit() {
    if (!props.selected?.store_url || form.processing) {
        return;
    }

    form.post(props.selected.store_url, {
        preserveScroll: true,
    });
}

function selectedExportUrl(format: 'excel' | 'pdf') {
    if (!props.selected) {
        return '#';
    }

    const base = format === 'excel' ? props.selected.export_excel_url : props.selected.export_pdf_url;
    const params = new URLSearchParams({ bulan: filterForm.bulan || props.filters.bulan || '' }).toString();
    return params ? `${base}?${params}` : base;
}
</script>

<template>
    <Head title="Absensi" />

    <AppShell title="Absensi">
        <DashboardHero
            v-if="selected"
            eyebrow="Workspace Kelas/Mapel"
            :title="selected.mata_pelajaran"
            :subtitle="`${selected.kelas} - Catat dan pantau kehadiran siswa.`"
            icon="bi-clipboard2-check-fill"
            tone="teacher"
        >
            <template #actions>
                <QuickActionBar :actions="[{ label: 'Ringkasan', href: selected.workspace_url, icon: 'bi-grid-1x2', color: 'light' }]" />
            </template>
        </DashboardHero>
        <PageHeader v-else title="Absensi" icon="bi-clipboard-check-fill" />

        <nav v-if="selected" class="workspace-tabs" aria-label="Navigasi kelas dan mata pelajaran">
            <a v-for="tab in courseTabs" :key="tab.label" :href="tab.href" class="workspace-tab" :class="{ 'is-active': tab.active }">
                <i class="bi" :class="tab.icon" aria-hidden="true"></i>{{ tab.label }}
            </a>
        </nav>

        <div class="row gy-4">
            <div class="col-12">
                <Card title="Filter Absensi" icon="bi-funnel-fill">
                    <form class="row g-3 align-items-end" @submit.prevent="applyFilters">
                        <div class="col-md-6">
                            <SearchableSelect
                                v-model="filterForm.kelas_mapel_id"
                                name="kelas_mapel_id"
                                label="Kelas dan Mata Pelajaran"
                                placeholder="-- Pilih --"
                                search-placeholder="Cari kelas atau mapel..."
                                wrapper-class="mb-0"
                                :options="kelasMapel.map((item) => ({ value: item.id, label: item.label }))"
                            />
                        </div>
                        <div class="col-md-3">
                            <TextInput
                                v-model="filterForm.bulan"
                                type="month"
                                name="bulan"
                                label="Bulan"
                                wrapper-class="mb-0"
                            />
                        </div>
                        <div class="col-md-3 d-grid">
                            <Button type="submit" color="primary" icon="bi-search">Tampilkan</Button>
                        </div>
                    </form>
                </Card>
            </div>

            <div v-if="kelasMapel.length === 0" class="col-12">
                <Card>
                    <EmptyState
                        title="Belum ada penugasan mengajar"
                        message="Anda belum memiliki penugasan mengajar semester ini."
                        icon="bi-clipboard-check"
                    />
                </Card>
            </div>

            <div v-else-if="selected && !selected.has_schedule" class="col-12">
                <Card>
                    <EmptyState
                        title="Jadwal mengajar belum tersedia"
                        message="Isi jadwal mengajar Senin-Jumat terlebih dahulu agar tanggal absensi otomatis sesuai kalender."
                        icon="bi-calendar-week"
                    >
                        <a :href="selected.schedule_url" class="btn btn-primary btn-sm">
                            <i class="bi bi-calendar-plus me-1" aria-hidden="true"></i> Atur Jadwal
                        </a>
                    </EmptyState>
                </Card>
            </div>

            <div v-else-if="selected" class="col-12">
                <form @submit.prevent="submit">
                    <Card
                        :title="`Absensi ${selected.kelas} - ${selected.mata_pelajaran}`"
                        icon="bi-table"
                        body-class="p-0"
                    >
                        <div v-if="highlightedStudent" class="attendance-focus-note" role="status">
                            <i class="bi bi-person-check" aria-hidden="true"></i>
                            <span>Menyorot <strong>{{ highlightedStudent.nama }}</strong> dari dashboard guru.</span>
                        </div>

                        <div class="p-3 attendance-legend d-flex flex-wrap gap-2 align-items-center">
                            <Badge color="success">H=Hadir</Badge>
                            <Badge color="warning" class="text-dark">S=Sakit</Badge>
                            <Badge color="info" class="text-dark">I=Izin</Badge>
                            <Badge color="danger">A=Alpha</Badge>
                            <Badge color="secondary">Mengikuti jadwal mengajar</Badge>
                            <span class="ms-auto d-flex flex-wrap gap-2">
                                <a :href="selectedExportUrl('excel')" class="btn btn-sm btn-outline-success">
                                    <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i> Excel
                                </a>
                                <a :href="selectedExportUrl('pdf')" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i> PDF
                                </a>
                            </span>
                        </div>

                        <TableWrapper>
                            <table class="table table-bordered table-hover mb-0 attendance-table">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width:44px;">No</th>
                                        <th class="text-center" style="width:70px;">NIS</th>
                                        <th>Nama</th>
                                        <th
                                            v-for="week in weeks"
                                            :key="week.key"
                                            class="text-center"
                                            style="min-width:72px;"
                                        >
                                            {{ week.title }}<br>
                                            <small class="text-muted">{{ week.label }}</small>
                                            <small v-if="week.lesson_title" class="text-muted d-block">{{ week.lesson_title }}</small>
                                        </th>
                                        <th class="text-center" style="width:42px;">H</th>
                                        <th class="text-center" style="width:42px;">S</th>
                                        <th class="text-center" style="width:42px;">I</th>
                                        <th class="text-center" style="width:42px;">A</th>
                                    </tr>
                                    <tr>
                                        <td colspan="3"></td>
                                        <td
                                            v-for="week in weeks"
                                            :key="`fill-${week.key}`"
                                            class="text-center py-2"
                                        >
                                            <select
                                                v-if="week.date"
                                                class="form-select form-select-sm attendance-select"
                                                @change="fillColumn(week.key, $event)"
                                            >
                                                <option
                                                    v-for="option in statusOptions"
                                                    :key="option.value"
                                                    :value="option.value"
                                                >
                                                    {{ option.label }}
                                                </option>
                                            </select>
                                        </td>
                                        <td colspan="4"></td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="student in students"
                                        :id="`attendance-student-${student.id}`"
                                        :key="student.id"
                                        :class="{ 'attendance-row-highlighted': Number(student.id) === Number(highlightedSiswaId) }"
                                    >
                                        <td class="text-center text-muted align-middle">{{ student.no }}</td>
                                        <td class="align-middle">{{ student.nis }}</td>
                                        <td class="align-middle">
                                            <strong>{{ student.nama }}</strong>
                                            <span v-if="Number(student.id) === Number(highlightedSiswaId)" class="visually-hidden">Siswa yang dipilih dari dashboard</span>
                                        </td>
                                        <td
                                            v-for="week in weeks"
                                            :key="`${student.id}-${week.key}`"
                                            class="p-0 text-center align-middle"
                                        >
                                            <select
                                                v-if="week.date"
                                                v-model="form.absensi[String(student.id)][String(week.key)]"
                                                class="form-select form-select-sm attendance-select"
                                                :class="form.absensi[String(student.id)][String(week.key)]"
                                            >
                                                <option
                                                    v-for="option in statusOptions"
                                                    :key="option.value"
                                                    :value="option.value"
                                                >
                                                    {{ option.label }}
                                                </option>
                                            </select>
                                            <span v-else class="text-muted">-</span>
                                        </td>
                                        <td class="text-center align-middle text-success fw-bold">{{ counts(student.id).hadir }}</td>
                                        <td class="text-center align-middle text-warning">{{ counts(student.id).sakit }}</td>
                                        <td class="text-center align-middle text-info">{{ counts(student.id).izin }}</td>
                                        <td class="text-center align-middle text-danger fw-bold">{{ counts(student.id).alpha }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </TableWrapper>

                        <template #footer>
                            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                                <Button type="button" color="outline-secondary" icon="bi-arrow-left" @click="resetFilters">Reset</Button>
                                <Button type="submit" color="success" icon="bi-save" :disabled="form.processing">
                                    {{ form.processing ? 'Menyimpan...' : 'Simpan Absensi' }}
                                </Button>
                            </div>
                        </template>
                    </Card>
                </form>
            </div>

            <div v-else class="col-12">
                <Card>
                    <EmptyState
                        title="Pilih filter absensi"
                        message="Pilih kelas dan bulan untuk menampilkan data absensi."
                        icon="bi-info-circle"
                    />
                </Card>
            </div>
        </div>
    </AppShell>
</template>

<style scoped>
.attendance-select {
    font-size:0.72rem;
    min-width:70px;
    padding:0.35rem 0.5rem;
    text-align:center;
}
.attendance-select.hadir { background:var(--status-success-bg); color:var(--status-success-text); }
.attendance-select.sakit { background:var(--status-warning-bg); color:var(--status-warning-text); }
.attendance-select.izin { background:var(--status-info-bg); color:var(--status-info-text); }
.attendance-select.alpha { background:var(--status-danger-bg); color:var(--status-danger-text); }
.attendance-legend .badge { font-size:0.78rem; }
.attendance-table th { vertical-align: middle; }

.attendance-focus-note {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.7rem 0.9rem;
    border-bottom: 1px solid var(--gray-200, #e5e7eb);
    background: var(--primary-50, #f0fdf4);
    color: var(--text-body, #374151);
    font-size: 0.8rem;
}

.attendance-focus-note i {
    color: var(--app-primary);
}

.attendance-row-highlighted > td {
    background: var(--primary-50, #f0fdf4) !important;
}

.attendance-row-highlighted > td:first-child {
    box-shadow: inset 3px 0 0 var(--app-primary);
}

@media (max-width: 767px) {
    .attendance-table {
        min-width: 760px;
    }

    .attendance-table th:nth-child(-n + 3),
    .attendance-table td:nth-child(-n + 3) {
        position: sticky;
        z-index: 2;
        background: var(--surface-card, #fff);
    }

    .attendance-table th:nth-child(1),
    .attendance-table td:nth-child(1) { left: 0; min-width: 44px; }
    .attendance-table th:nth-child(2),
    .attendance-table td:nth-child(2) { left: 44px; min-width: 70px; }
    .attendance-table th:nth-child(3),
    .attendance-table td:nth-child(3) { left: 114px; min-width: 180px; }

    .attendance-table thead th:nth-child(-n + 3) {
        z-index: 4;
        background: var(--surface-muted, #f8fafc);
    }

    .attendance-table tbody tr:hover > td:nth-child(-n + 3),
    .attendance-table .attendance-row-highlighted > td:nth-child(-n + 3) {
        background: var(--surface-hover, var(--surface-card, #fff)) !important;
    }

    .attendance-select {
        min-width: 54px;
        padding: 0.25rem 0.35rem;
        font-size: 0.7rem;
    }

    .attendance-legend {
        gap: 0.4rem;
    }
}
</style>
