<script setup lang="ts">
import type { PropType } from 'vue';
import type { TeacherTask } from '../../../types/tasks';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { TextareaInput, TextInput } from '../../../Components/Form';
import AppShell from '../../../Layouts/AppShell.vue';
import { Button, Card, DashboardHero, EmptyState, MetricStrip } from '../../../Components/UI';

const props = defineProps({
    kelasMapel: { type: Array as PropType<{ id: number; kelas: string; mata_pelajaran: string; semester: string; label: string; href: string }[]>, default: () => [] },
    tugas: { type: Array as PropType<TeacherTask[]>, default: () => [] },
    storeUrl: { type: String, required: true },
});

const form = useForm({
    kelas_mapel_ids: [] as number[],
    judul: '',
    deskripsi: '',
    batas_waktu: '',
});

const courseSearch = ref('');

const courseSummaries = computed(() => props.kelasMapel.map((course) => {
    const courseTasks = props.tugas.filter((item) => Number(item.kelas_mapel_id) === Number(course.id));
    const submitted = courseTasks.reduce((total, item) => total + Number(item.sudah_mengumpulkan || 0), 0);
    const pendingGrades = courseTasks.reduce((total, item) => total + Number(item.perlu_dinilai || 0), 0);
    const overdue = courseTasks.filter((item) => item.is_overdue).length;
    const latestTask = courseTasks[0] ?? null;

    return {
        ...course,
        total_tugas: courseTasks.length,
        total_pengumpulan: submitted,
        perlu_dinilai: pendingGrades,
        lewat_deadline: overdue,
        tugas_terbaru: latestTask?.judul ?? 'Belum ada tugas',
    };
}));

const filteredCourseSummaries = computed(() => {
    const keyword = courseSearch.value.trim().toLowerCase();

    if (!keyword) {
        return courseSummaries.value;
    }

    return courseSummaries.value.filter((item) => [
        item.kelas,
        item.mata_pelajaran,
        item.semester,
        item.label,
        item.tugas_terbaru,
    ].filter(Boolean).join(' ').toLowerCase().includes(keyword));
});

const metrics = computed(() => {
    const submitted = props.tugas.reduce((total, item) => total + Number(item.sudah_mengumpulkan || 0), 0);
    const pendingGrades = props.tugas.reduce((total, item) => total + Number(item.perlu_dinilai || 0), 0);
    const overdue = props.tugas.filter((item) => item.is_overdue).length;

    return [
        { label: 'Tugas aktif', value: props.tugas.length, icon: 'bi-journal-check', tone: 'primary' },
        { label: 'Penugasan', value: props.kelasMapel.length, icon: 'bi-diagram-3', tone: 'info' },
        { label: 'Pengumpulan', value: submitted, icon: 'bi-inbox-fill', tone: 'success' },
        { label: 'Perlu dinilai', value: pendingGrades, icon: 'bi-pencil-square', tone: pendingGrades ? 'danger' : 'muted' },
        { label: 'Lewat deadline', value: overdue, icon: 'bi-exclamation-triangle', tone: overdue ? 'warning' : 'muted' },
    ];
});

function toggleAllCourses() {
    if (form.kelas_mapel_ids.length === props.kelasMapel.length) {
        form.kelas_mapel_ids = [];
        return;
    }

    form.kelas_mapel_ids = props.kelasMapel.map((item) => item.id);
}

function submit() {
    if (form.processing) {
        return;
    }

    form.post(props.storeUrl, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

</script>

<template>
    <Head title="Tugas" />

    <AppShell title="Tugas">
        <DashboardHero
            eyebrow="Teaching Workspace"
            title="Penugasan Guru"
            subtitle="Buat, bagikan, dan pantau tugas lintas kelas dari satu tempat."
            icon="bi-journal-fill"
            tone="teacher"
        >
            <template #actions>
                <a v-if="kelasMapel.length" :href="kelasMapel[0].href" class="btn btn-light btn-sm">
                    <i class="bi bi-arrow-right-circle me-1" aria-hidden="true"></i> Buka kelas pertama
                </a>
            </template>
        </DashboardHero>

        <MetricStrip v-if="kelasMapel.length" :items="metrics" />

        <div v-if="kelasMapel.length" class="row">
            <div class="col-md-5 mb-4">
                <Card title="Buat Tugas Baru" icon="bi-plus-circle">
                    <form @submit.prevent="submit">
                        <div class="form-help-panel">
                            <i class="bi bi-lightbulb-fill" aria-hidden="true"></i>
                            <span>
                                <span class="form-help-panel-title">Pilih satu atau beberapa kelas tujuan.</span>
                                Tugas akan dibuat untuk setiap kelas dan mata pelajaran yang dipilih.
                            </span>
                        </div>

                        <TextInput v-model="form.judul" name="judul" label="Judul" required :error="form.errors.judul" />
                        <TextareaInput v-model="form.deskripsi" name="deskripsi" label="Deskripsi" :rows="3" :error="form.errors.deskripsi" />
                        <TextInput v-model="form.batas_waktu" type="date" name="batas_waktu" label="Deadline" required :error="form.errors.batas_waktu" />

                        <div class="mb-3">
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                <label class="form-label mb-0">Kelas Tujuan <span class="text-danger">*</span></label>
                                <button class="btn btn-sm btn-outline-secondary" type="button" @click="toggleAllCourses">
                                    {{ form.kelas_mapel_ids.length === kelasMapel.length ? 'Kosongkan' : 'Pilih semua' }}
                                </button>
                            </div>
                            <div class="assignment-list">
                                <label
                                    v-for="item in kelasMapel"
                                    :key="item.id"
                                    class="assignment-option"
                                    :class="{ selected: form.kelas_mapel_ids.includes(item.id) }"
                                >
                                    <input
                                        v-model="form.kelas_mapel_ids"
                                        class="form-check-input"
                                        type="checkbox"
                                        :value="item.id"
                                    >
                                    <span>{{ item.label }}</span>
                                    <a :href="item.href" class="assignment-option-link" @click.stop>
                                        <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                                        <span class="visually-hidden">Buka {{ item.label }}</span>
                                    </a>
                                </label>
                            </div>
                            <div v-if="form.errors.kelas_mapel_ids" class="text-danger small mt-1">
                                {{ form.errors.kelas_mapel_ids }}
                            </div>
                        </div>

                        <Button type="submit" color="success" size="" icon="bi-save" class="w-100 assignment-submit" :disabled="form.processing">
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Tugas' }}
                        </Button>
                    </form>
                </Card>
            </div>

            <div class="col-md-7 mb-4">
                <Card title="Pilih Kelas" icon="bi-grid-1x2">
                    <template #actions>
                        <div class="assignment-search">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input v-model="courseSearch" class="form-control form-control-sm" type="search" placeholder="Cari kelas/mapel" aria-label="Cari kelas atau mata pelajaran">
                        </div>
                    </template>

                    <div v-if="filteredCourseSummaries.length" class="assignment-course-grid">
                        <a
                            v-for="item in filteredCourseSummaries"
                            :key="item.id"
                            :href="item.href"
                            class="assignment-course-card"
                        >
                            <span class="assignment-course-main">
                                <span class="assignment-course-kicker">Semester {{ item.semester ?? '-' }}</span>
                                <strong>{{ item.kelas }}</strong>
                                <span>{{ item.mata_pelajaran }}</span>
                            </span>
                            <span class="assignment-course-stats">
                                <span><strong>{{ item.total_tugas }}</strong> tugas</span>
                                <span><strong>{{ item.total_pengumpulan }}</strong> kumpul</span>
                                <span :class="{ danger: item.perlu_dinilai > 0 }"><strong>{{ item.perlu_dinilai }}</strong> dinilai</span>
                                <span :class="{ warning: item.lewat_deadline > 0 }"><strong>{{ item.lewat_deadline }}</strong> lewat</span>
                            </span>
                            <span class="assignment-course-latest">{{ item.tugas_terbaru }}</span>
                        </a>
                    </div>

                    <EmptyState v-else :title="courseSearch ? 'Kelas tidak ditemukan' : 'Belum ada kelas mengajar'" icon="bi-grid-1x2" />
                </Card>
            </div>
        </div>

        <Card v-else>
            <EmptyState title="Anda belum memiliki penugasan mengajar semester ini" icon="bi-journal" />
        </Card>
    </AppShell>
</template>

<style scoped>
.assignment-list {
    display: grid;
    gap: 8px;
}

.assignment-option {
    display: flex;
    gap: 8px;
    align-items: flex-start;
    padding: 9px 10px;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-md);
    background: var(--surface-card);
    cursor: pointer;
    transition: var(--transition-fast);
}

.assignment-option input {
    margin-top: 2px;
}

.assignment-option:hover,
.assignment-option.selected {
    border-color: var(--primary-300);
    background: var(--primary-50);
}

.assignment-option span {
    color: var(--text-body);
    font-size: 0.84rem;
    line-height: 1.35;
}

.assignment-option-link {
    margin-left: auto;
    color: var(--text-muted);
    line-height: 1;
}

.assignment-option-link:hover {
    color: var(--primary-600);
}

.assignment-submit {
    margin-top: 0.25rem;
}

.assignment-search {
    position: relative;
    width: min(220px, 100%);
}

.assignment-search .bi {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    font-size: 0.8rem;
}

.assignment-search .form-control {
    padding-left: 30px;
}

.assignment-course-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 220px), 1fr));
    gap: 10px;
    max-height: min(58vh, 520px);
    overflow-y: auto;
    padding-right: 2px;
}

.assignment-course-card {
    display: grid;
    gap: 10px;
    width: 100%;
    padding: 12px;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-md);
    background: var(--surface-card);
    color: var(--text-body);
    text-align: left;
    text-decoration: none;
    transition: var(--transition-fast);
}

.assignment-course-card:hover,
.assignment-course-card:focus-visible {
    border-color: var(--primary-300);
    background: var(--primary-50);
}

.assignment-course-main {
    display: grid;
    gap: 2px;
    min-width: 0;
}

.assignment-course-main strong,
.assignment-course-main span,
.assignment-course-latest {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.assignment-course-main strong {
    color: var(--text-strong);
    font-size: 0.95rem;
}

.assignment-course-main span:not(.assignment-course-kicker),
.assignment-course-latest {
    color: var(--text-muted);
    font-size: 0.78rem;
}

.assignment-course-kicker {
    color: var(--primary-700);
    font-size: 0.66rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.assignment-course-stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 6px;
}

.assignment-course-stats span {
    min-width: 0;
    padding: 6px 8px;
    border-radius: var(--radius-sm);
    background: var(--gray-50);
    color: var(--text-muted);
    font-size: 0.72rem;
}

.assignment-course-stats strong {
    color: var(--text-strong);
}

.assignment-course-stats .danger strong {
    color: var(--status-danger-text);
}

.assignment-course-stats .warning strong {
    color: var(--status-warning-text);
}

@media (max-width: 767.98px) {
    .assignment-search {
        width: 100%;
    }

    .assignment-course-grid {
        max-height: 420px;
    }
}
</style>
