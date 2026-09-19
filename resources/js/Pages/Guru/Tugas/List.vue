<script setup lang="ts">
import type { PropType } from 'vue';
import type { TeacherTask } from '../../../types/tasks';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { TextareaInput, TextInput } from '../../../Components/Form';
import AppShell from '../../../Layouts/AppShell.vue';
import { Badge, Button, Card, DashboardHero, EmptyState, IconButton, MetricStrip, QuickActionBar, TableWrapper } from '../../../Components/UI';

const props = defineProps({
    kelasMapel: { type: Object as PropType<{ id: number; kelas: string; mata_pelajaran: string; workspace_url: string; store_url: string; back_url: string; export_excel_url: string; export_pdf_url: string }>, required: true },
    tugas: { type: Array as PropType<TeacherTask[]>, default: () => [] },
    totalSiswa: { type: Number, default: 0 },
});

const form = useForm({
    judul: '',
    deskripsi: '',
    batas_waktu: '',
});

const search = ref('');

const courseTabs = [
    { label: 'Ringkasan', href: props.kelasMapel.workspace_url, icon: 'bi-grid-1x2' },
    { label: 'Materi', href: `/guru/materi/${props.kelasMapel.id}/list`, icon: 'bi-file-earmark-text' },
    { label: 'Tugas', href: '#', icon: 'bi-journal-check', active: true },
    { label: 'Nilai', href: `/guru/nilai/${props.kelasMapel.id}/input`, icon: 'bi-bar-chart' },
    { label: 'Absensi', href: `/guru/absensi/${props.kelasMapel.id}/create`, icon: 'bi-clipboard-check' },
    { label: 'Chat', href: `/guru/chat/${props.kelasMapel.id}`, icon: 'bi-chat-dots' },
];

const filteredTugas = computed(() => {
    const keyword = search.value.trim().toLowerCase();

    if (!keyword) {
        return props.tugas;
    }

    return props.tugas.filter((item) => [
        item.judul,
        item.deskripsi,
        item.batas_waktu,
    ].filter(Boolean).join(' ').toLowerCase().includes(keyword));
});

const metrics = computed(() => {
    const submitted = props.tugas.reduce((total, item) => total + Number(item.sudah_mengumpulkan || 0), 0);
    const pendingGrades = props.tugas.reduce((total, item) => total + Number(item.perlu_dinilai || 0), 0);
    const overdue = props.tugas.filter((item) => item.is_overdue).length;

    return [
        { label: 'Tugas', value: props.tugas.length, icon: 'bi-journal-check', tone: 'primary' },
        { label: 'Siswa aktif', value: props.totalSiswa, icon: 'bi-people-fill', tone: 'info' },
        { label: 'Pengumpulan', value: submitted, icon: 'bi-inbox-fill', tone: 'success' },
        { label: 'Perlu dinilai', value: pendingGrades, icon: 'bi-pencil-square', tone: pendingGrades ? 'danger' : 'muted' },
        { label: 'Lewat deadline', value: overdue, icon: 'bi-exclamation-triangle', tone: overdue ? 'warning' : 'muted' },
    ];
});

function submit() {
    if (form.processing) {
        return;
    }

    form.post(props.kelasMapel.store_url, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

async function destroy(item: TeacherTask) {
    const confirmed = await window.confirmDialog?.('Hapus tugas ini?', {
        title: 'Hapus Tugas',
        confirmText: 'Ya, hapus',
        danger: true,
    });

    if (!confirmed) {
        return;
    }

    router.delete(item.delete_url, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="`Tugas: ${kelasMapel.mata_pelajaran} - ${kelasMapel.kelas}`" />

    <AppShell title="Tugas">
        <DashboardHero
            eyebrow="Workspace Kelas/Mapel"
            :title="kelasMapel.mata_pelajaran"
            :subtitle="`${kelasMapel.kelas} - Buat tugas dan pantau pengumpulan siswa.`"
            icon="bi-journal-fill"
            tone="teacher"
        >
            <template #actions>
                <QuickActionBar
                    :actions="[
                        { label: 'Kembali', href: kelasMapel.back_url, icon: 'bi-arrow-left', color: 'light' },
                        { label: 'Ringkasan', href: kelasMapel.workspace_url, icon: 'bi-grid-1x2', color: 'light' },
                    ]"
                />
            </template>
        </DashboardHero>

        <nav class="workspace-tabs" aria-label="Navigasi kelas dan mata pelajaran">
            <a v-for="tab in courseTabs" :key="tab.label" :href="tab.href" class="workspace-tab" :class="{ 'is-active': tab.active }">
                <i class="bi" :class="tab.icon" aria-hidden="true"></i>{{ tab.label }}
            </a>
        </nav>

        <div class="d-flex justify-content-end gap-2 mb-3 flex-wrap">
            <a :href="kelasMapel.export_excel_url" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i>Excel</a>
            <a :href="kelasMapel.export_pdf_url" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i>PDF</a>
        </div>

        <MetricStrip :items="metrics" />

        <div class="row">
            <div class="col-md-5 mb-4">
                <Card title="Buat Tugas Baru" icon="bi-plus-circle">
                    <form @submit.prevent="submit">
                        <TextInput
                            v-model="form.judul"
                            name="judul"
                            label="Judul"
                            required
                            :error="form.errors.judul"
                        />
                        <TextareaInput
                            v-model="form.deskripsi"
                            name="deskripsi"
                            label="Deskripsi"
                            :rows="3"
                            :error="form.errors.deskripsi"
                        />
                        <TextInput
                            v-model="form.batas_waktu"
                            type="date"
                            name="batas_waktu"
                            label="Deadline"
                            required
                            :error="form.errors.batas_waktu"
                        />
                        <Button
                            type="submit"
                            color="success"
                            size=""
                            icon="bi-save"
                            class="w-100 mt-2"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Tugas' }}
                        </Button>
                    </form>
                </Card>
            </div>

            <div class="col-md-7 mb-4">
                <Card title="Daftar Tugas" icon="bi-list-ul" body-class="p-0">
                    <template #actions>
                        <div class="assignment-search">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input v-model="search" class="form-control form-control-sm" type="search" placeholder="Cari tugas" aria-label="Cari tugas">
                        </div>
                    </template>
                    <template #default>
                        <TableWrapper v-if="filteredTugas.length" class="d-none d-md-block">
                            <table class="table table-hover mb-0 app-table-proportional">
                                <colgroup>
                                    <col style="width:44%">
                                    <col style="width:14%">
                                    <col style="width:20%">
                                    <col style="width:22%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Judul</th>
                                        <th>Deadline</th>
                                        <th>Kumpul</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in filteredTugas" :key="item.id">
                                        <td class="app-table-judul">
                                            <strong>{{ item.judul }}</strong>
                                            <div v-if="item.deskripsi" class="text-muted small">{{ item.deskripsi }}</div>
                                        </td>
                                        <td class="text-nowrap small">
                                            <Badge :color="item.is_overdue ? 'danger' : 'secondary'">{{ item.batas_waktu ?? '-' }}</Badge>
                                        </td>
                                        <td>
                                            <div class="assignment-progress">
                                                <span>{{ item.sudah_mengumpulkan ?? 0 }}/{{ totalSiswa }}</span>
                                                <div class="progress" role="progressbar" :aria-valuenow="item.progress_percent" aria-valuemin="0" aria-valuemax="100">
                                                    <div class="progress-bar" :style="{ width: `${item.progress_percent || 0}%` }"></div>
                                                </div>
                                                <small v-if="item.perlu_dinilai" class="text-danger">{{ item.perlu_dinilai }} perlu dinilai</small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-inline-flex align-items-center gap-1">
                                                <a :href="item.pengumpulan_url" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye me-1" aria-hidden="true"></i> Nilai
                                                </a>
                                                <IconButton
                                                    icon="bi-trash"
                                                    :label="`Hapus ${item.judul}`"
                                                    color="outline-danger"
                                                    @click="destroy(item)"
                                                />
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </TableWrapper>
                        <div v-if="filteredTugas.length" class="app-mobile-list d-md-none">
                            <div v-for="item in filteredTugas" :key="item.id" class="app-mobile-list-item">
                                <div class="app-mobile-list-row">
                                    <span class="app-mobile-list-title">{{ item.judul }}</span>
                                    <Badge color="primary">{{ item.sudah_mengumpulkan ?? 0 }}/{{ totalSiswa }}</Badge>
                                </div>
                                <span v-if="item.deskripsi" class="app-mobile-list-meta">{{ item.deskripsi }}</span>
                                <div class="progress my-2" role="progressbar" :aria-valuenow="item.progress_percent" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar" :style="{ width: `${item.progress_percent || 0}%` }"></div>
                                </div>
                                <div class="app-mobile-list-row">
                                    <span class="app-mobile-list-meta">
                                        Deadline {{ item.batas_waktu ?? '-' }}
                                        <span v-if="item.perlu_dinilai" class="text-danger">- {{ item.perlu_dinilai }} perlu dinilai</span>
                                    </span>
                                    <span class="d-inline-flex align-items-center gap-1">
                                        <a :href="item.pengumpulan_url" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye me-1" aria-hidden="true"></i> Nilai
                                        </a>
                                        <IconButton
                                            icon="bi-trash"
                                            :label="`Hapus ${item.judul}`"
                                            color="outline-danger"
                                            @click="destroy(item)"
                                        />
                                    </span>
                                </div>
                            </div>
                        </div>
                        <EmptyState v-else :title="search ? 'Tugas tidak ditemukan' : 'Belum ada tugas'" icon="bi-journal" />
                    </template>
                </Card>
            </div>
        </div>
    </AppShell>
</template>

<style scoped>
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

.assignment-progress {
    display: grid;
    gap: 4px;
    min-width: 120px;
}

.assignment-progress .progress,
.app-mobile-list .progress {
    height: 6px;
    background: var(--gray-100);
}

@media (max-width: 767.98px) {
    .assignment-search {
        width: 100%;
    }
}
</style>
