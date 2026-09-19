<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppShell from '../../../Layouts/AppShell.vue';
import { Badge, Button, Card, DashboardHero, EmptyState, MetricStrip, QuickActionBar, TableWrapper } from '../../../Components/UI';
import SubmissionGradeForm from './Partials/SubmissionGradeForm.vue';
import SubmissionRow from './Partials/SubmissionRow.vue';
import type { AssignmentContext, AssignmentSubmission, AssignmentSummary, SubmissionStatus } from '../../../types';

interface Props { kelasMapel: AssignmentContext; tugas: AssignmentSummary; pengumpulan?: AssignmentSubmission[] }
const props = withDefaults(defineProps<Props>(), { pengumpulan: () => [] });

const detail = ref<AssignmentSubmission | null>(null);
const search = ref('');
const statusFilter = ref<'semua' | SubmissionStatus>('semua');

const filteredPengumpulan = computed(() => {
    const keyword = search.value.trim().toLowerCase();

    return props.pengumpulan.filter((item) => {
        const matchStatus = statusFilter.value === 'semua' || item.status === statusFilter.value;
        const matchKeyword = !keyword || [item.siswa, item.nis, item.teks_jawaban, item.catatan]
            .filter(Boolean)
            .join(' ')
            .toLowerCase()
            .includes(keyword);

        return matchStatus && matchKeyword;
    });
});

const metrics = computed(() => {
    const submitted = props.pengumpulan.filter((item) => ['sudah', 'terlambat', 'dinilai', 'perlu_perbaikan'].includes(item.status)).length;
    const graded = props.pengumpulan.filter((item) => item.nilai !== null).length;
    const pendingGrades = props.pengumpulan.filter((item) => ['sudah', 'terlambat'].includes(item.status) && item.nilai === null).length;
    const missing = props.pengumpulan.filter((item) => item.status === 'belum').length;

    return [
        { label: 'Siswa', value: props.pengumpulan.length, icon: 'bi-people-fill', tone: 'info' },
        { label: 'Sudah kumpul', value: submitted, icon: 'bi-inbox-fill', tone: 'success' },
        { label: 'Sudah dinilai', value: graded, icon: 'bi-check-circle-fill', tone: 'primary' },
        { label: 'Perlu dinilai', value: pendingGrades, icon: 'bi-pencil-square', tone: pendingGrades ? 'danger' : 'muted' },
        { label: 'Belum kumpul', value: missing, icon: 'bi-hourglass-split', tone: missing ? 'warning' : 'muted' },
    ];
});

const statusMap: Record<string, { color: string; label: string }> = {
    belum: { color: 'secondary', label: 'Belum' },
    sudah: { color: 'success', label: 'Sudah' },
    terlambat: { color: 'danger', label: 'Terlambat' },
    dinilai: { color: 'primary', label: 'Dinilai' },
    perlu_perbaikan: { color: 'warning', label: 'Perlu Perbaikan' },
};

function statusColor(status: SubmissionStatus): string {
    return statusMap[status]?.color ?? 'secondary';
}

function statusLabel(status: SubmissionStatus): string {
    return statusMap[status]?.label ?? (status ? status.replace(/\b\w/g, (char) => char.toUpperCase()) : '-');
}

async function prepareWhatsApp(item: AssignmentSubmission): Promise<void> {
    if (!item.whatsapp_url) return;
    const response = await fetch(item.whatsapp_url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    if (!response.ok) {
        const error = await response.json().catch(() => null) as { message?: string } | null;
        window.showToast?.(error?.message ?? 'Nomor WhatsApp belum valid atau belum disetujui.', 'error');
        return;
    }
    const data = await response.json() as { url: string; log_id: number };
    window.open(data.url, '_blank', 'noopener,noreferrer');
    const confirmed = await window.confirmDialog?.('Sudah menekan tombol kirim di WhatsApp?', { title: 'Tandai Pengingat', confirmText: 'Ya, sudah dikirim' });
    if (confirmed) router.post(`/guru/tugas/whatsapp/${data.log_id}/mark-sent`, {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Pengumpulan Tugas" />

    <AppShell title="Pengumpulan Tugas">
        <DashboardHero
            eyebrow="Penilaian Tugas"
            :title="tugas.judul"
            :subtitle="`${kelasMapel.mata_pelajaran} - ${kelasMapel.kelas}. Deadline ${tugas.batas_waktu ?? '-'}.`"
            icon="bi-journal-check"
            tone="teacher"
        >
            <template #actions>
                <QuickActionBar
                    :actions="[
                        { label: 'Daftar tugas', href: kelasMapel.back_url, icon: 'bi-arrow-left', color: 'light' },
                        { label: 'Ringkasan', href: kelasMapel.workspace_url, icon: 'bi-grid-1x2', color: 'light' },
                    ]"
                />
            </template>
        </DashboardHero>

        <MetricStrip :items="metrics" />

        <Card title="Daftar Pengumpulan" icon="bi-inbox" body-class="p-0">
            <template #actions>
                <div class="assignment-review-actions">
                    <div class="assignment-search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input v-model="search" class="form-control form-control-sm" type="search" placeholder="Cari siswa" aria-label="Cari siswa">
                    </div>
                    <select v-model="statusFilter" class="form-select form-select-sm" aria-label="Filter status pengumpulan">
                        <option value="semua">Semua status</option>
                        <option value="belum">Belum</option>
                        <option value="sudah">Sudah</option>
                        <option value="terlambat">Terlambat</option>
                        <option value="perlu_perbaikan">Perlu Perbaikan</option>
                        <option value="dinilai">Dinilai</option>
                    </select>
                    <a :href="kelasMapel.export_excel_url" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i>Excel
                    </a>
                    <a :href="kelasMapel.export_pdf_url" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i>PDF
                    </a>
                </div>
            </template>

            <p v-if="tugas.deskripsi" class="text-muted small px-3 pt-3 mb-0">{{ tugas.deskripsi }}</p>

            <TableWrapper v-if="filteredPengumpulan.length" class="d-none d-md-block">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Siswa</th>
                            <th>Status</th>
                            <th>Tanggal Kumpul</th>
                            <th>Terlambat</th>
                            <th>File</th>
                            <th>Jawaban</th>
                            <th>Nilai</th>
                            <th>Catatan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <SubmissionRow
                            v-for="item in filteredPengumpulan"
                            :key="item.key"
                            :item="item"
                            :status-color="statusColor"
                            :status-label="statusLabel"
                            @detail="detail = item"
                            @whatsapp="prepareWhatsApp(item)"
                        />
                    </tbody>
                </table>
            </TableWrapper>

            <div v-if="filteredPengumpulan.length" class="app-mobile-list d-md-none">
                <div v-for="item in filteredPengumpulan" :key="item.key" class="app-mobile-list-item">
                    <div class="app-mobile-list-row">
                        <span>
                            <span class="app-mobile-list-title">{{ item.siswa }}</span>
                            <span class="app-mobile-list-meta d-block">{{ item.nis }}</span>
                        </span>
                        <Badge :color="statusColor(item.status)">{{ statusLabel(item.status) }}</Badge>
                    </div>
                    <div class="app-mobile-list-row mt-2">
                        <span class="app-mobile-list-meta">Kumpul {{ item.tanggal_kumpul ?? '-' }}</span>
                        <span v-if="item.hari_terlambat" class="app-mobile-list-meta text-danger">Terlambat {{ item.hari_terlambat }} hari (perkiraan -{{ item.penalty_perkiraan }} poin)</span>
                        <span class="app-mobile-list-meta">Nilai {{ item.nilai ?? '-' }}</span>
                    </div>
                    <div v-if="item.files.length || item.legacy_file_url || item.teks_jawaban" class="d-flex flex-wrap gap-2 mt-3">
                        <a
                            v-for="file in item.files"
                            :key="file.id"
                            :href="file.url"
                            class="btn btn-sm btn-outline-primary"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <i class="bi bi-paperclip me-1" aria-hidden="true"></i>File
                        </a>
                        <a v-if="item.legacy_file_url" :href="item.legacy_file_url" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener noreferrer">
                            <i class="bi bi-download me-1" aria-hidden="true"></i>File
                        </a>
                        <Button v-if="item.teks_jawaban" type="button" color="outline-info" size="sm" @click="detail = item">
                            <i class="bi bi-text-left me-1" aria-hidden="true"></i>Jawaban
                        </Button>
                    </div>
                    <div class="mt-3">
                        <Button v-if="item.whatsapp_url" type="button" color="success" size="sm" class="mb-2" @click="prepareWhatsApp(item)"><i class="bi bi-whatsapp me-1" aria-hidden="true"></i>Kirim WhatsApp</Button>
                        <SubmissionGradeForm :item="item" />
                    </div>
                </div>
            </div>

            <EmptyState v-else :title="search || statusFilter !== 'semua' ? 'Pengumpulan tidak ditemukan' : 'Belum ada pengumpulan'" icon="bi-inbox" />
        </Card>

        <div v-if="detail" class="confirm-overlay" @click.self="detail = null">
            <div class="confirm-dialog" role="dialog" aria-modal="true" style="max-width:560px;text-align:left;">
                <h5 class="confirm-title">Detail Pengumpulan - {{ detail.siswa }}</h5>
                <div class="mb-3">
                    <p><strong>Status:</strong> <Badge :color="statusColor(detail.status)">{{ statusLabel(detail.status) }}</Badge></p>
                    <p><strong>Tanggal Kumpul:</strong> {{ detail.tanggal_kumpul ?? '-' }}</p>
                    <p v-if="detail.hari_terlambat"><strong>Keterlambatan:</strong> {{ detail.hari_terlambat }} hari, perkiraan potongan {{ detail.penalty_perkiraan }} poin</p>

                    <div v-if="detail.files.length" class="mb-3">
                        <strong>File:</strong>
                        <ul class="mb-0 mt-2">
                            <li v-for="file in detail.files" :key="file.id">
                                <a :href="file.url" target="_blank" rel="noopener noreferrer">{{ file.name }}</a>
                            </li>
                        </ul>
                    </div>
                    <p v-else-if="detail.legacy_file_url">
                        <strong>File:</strong> <a :href="detail.legacy_file_url" target="_blank" rel="noopener noreferrer">Download</a>
                    </p>

                    <div v-if="detail.teks_jawaban">
                        <strong>Jawaban Teks:</strong>
                        <div class="p-2 bg-light rounded mt-2">{{ detail.teks_jawaban }}</div>
                    </div>
                </div>

                <SubmissionGradeForm :item="detail" block />

                <div class="confirm-actions mt-3">
                    <Button type="button" color="outline-secondary" size="" @click="detail = null">Tutup</Button>
                </div>
            </div>
        </div>
    </AppShell>
</template>

<style scoped>
.assignment-review-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 8px;
}

.assignment-review-actions .form-select {
    width: 150px;
}

.assignment-search {
    position: relative;
    width: 180px;
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

@media (max-width: 991.98px) {
    .assignment-review-actions {
        display: grid;
        justify-content: stretch;
        width: 100%;
    }

    .assignment-search,
    .assignment-review-actions .form-select {
        width: 100%;
    }
}
</style>
