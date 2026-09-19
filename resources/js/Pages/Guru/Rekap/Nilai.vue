<script setup lang="ts">
import type { PropType } from 'vue';
import type { Scores, Score } from '../../../types/assessment';
interface GradeRow extends Scores { id: number; rata_akhir: Score; siswa: { user: { nama_lengkap: string } | null; nis: string; kelas: { nama_kelas: string } | null } | null; kelas_mapel?: { mata_pelajaran: { nama_mapel: string } | null } | null; kelasMapel?: { mataPelajaran: { nama_mapel: string } | null } | null }
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppShell from '../../../Layouts/AppShell.vue';
import PageHeader from '../../../Components/AppShell/PageHeader.vue';
import { Card, EmptyState, TableWrapper } from '../../../Components/UI';

const props = defineProps({
    title: { type: String, default: 'Rekap Nilai Siswa' },
    semester: { type: String, default: '1' },
    kelasMapel: { type: Array as PropType<{ id: number; label: string }[]>, default: () => [] },
    nilai: { type: Object as PropType<{ data: GradeRow[]; current_page: number; last_page: number; from: number | null; total: number }>, default: () => ({ data: [], current_page: 1, last_page: 1, from: 0, total: 0 }) },
});

const selectedKelas = ref(new URLSearchParams(window.location.search).get('kelas_mapel_id') || '');
const selectedSemester = ref(props.semester);

function filter() {
    router.get('/guru/rekap-nilai', { kelas_mapel_id: selectedKelas.value || undefined, semester: selectedSemester.value }, { preserveState: true, replace: true });
}

function reset() {
    selectedKelas.value = '';
    router.get('/guru/rekap-nilai', { semester: selectedSemester.value }, { preserveState: true, replace: true });
}

function page(pageNumber: number) {
    router.get('/guru/rekap-nilai', {
        kelas_mapel_id: selectedKelas.value || undefined,
        semester: selectedSemester.value,
        page: pageNumber,
    }, { preserveState: true, replace: true });
}
</script>

<template>
    <AppShell title="Rekap Nilai">
        <PageHeader title="Rekap Nilai Siswa" subtitle="Rekap nilai dari kelas dan mata pelajaran yang Anda ampu." icon="bi-file-earmark-bar-graph-fill" />

        <Card class="mb-4">
            <form class="row g-3 align-items-end app-table-filter" @submit.prevent="filter">
                <div class="col-12 col-md-5">
                    <label class="form-label" for="rekap-nilai-kelas">Kelas & Mata Pelajaran</label>
                    <select id="rekap-nilai-kelas" v-model="selectedKelas" class="form-select">
                        <option value="">Semua Kelas & Mapel</option>
                        <option v-for="item in kelasMapel" :key="item.id" :value="String(item.id)">{{ item.label }}</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label" for="rekap-nilai-semester">Semester</label>
                    <select id="rekap-nilai-semester" v-model="selectedSemester" class="form-select">
                        <option value="1">Semester 1</option>
                        <option value="2">Semester 2</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-2 d-grid">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1" aria-hidden="true"></i>Tampilkan</button>
                </div>
                <div class="col-12 col-md-2 d-grid">
                    <button class="btn btn-outline-secondary" type="button" @click="reset">Reset</button>
                </div>
            </form>
        </Card>

        <Card body-class="p-0">
            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap px-3 py-3 border-bottom">
                <strong>Data Nilai</strong>
                <span class="badge text-bg-secondary">{{ nilai.total ?? 0 }} siswa</span>
            </div>

            <div v-if="!nilai.data?.length" class="p-5">
                <EmptyState title="Tidak ada data nilai." icon="bi-inbox" />
            </div>

            <TableWrapper v-else :min-width="1320">
                <table class="table table-hover align-middle mb-0 app-table rekap-table">
                    <colgroup>
                        <col class="col-no">
                        <col class="col-name">
                        <col class="col-class">
                        <col class="col-subject">
                        <col v-for="index in 9" :key="`score-${index}`" class="col-score">
                        <col class="col-predicate">
                    </colgroup>
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="text-center">#</th>
                            <th scope="col">Nama Siswa</th>
                            <th scope="col">Kelas</th>
                            <th scope="col">Mapel</th>
                            <th scope="col" class="text-center">SUM1</th>
                            <th scope="col" class="text-center">SUM2</th>
                            <th scope="col" class="text-center">SUM3</th>
                            <th scope="col" class="text-center">SUM4</th>
                            <th scope="col" class="text-center">Harian</th>
                            <th scope="col" class="text-center">STS</th>
                            <th scope="col" class="text-center">SAS</th>
                            <th scope="col" class="text-center">SAT</th>
                            <th scope="col" class="text-center">Rata²</th>
                            <th scope="col" class="text-center">Predikat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, index) in nilai.data" :key="row.id">
                            <td class="text-center text-muted">{{ (nilai.from || 1) + index }}</td>
                            <td class="rekap-name-cell">{{ row.siswa?.user?.nama_lengkap || row.siswa?.nis || '-' }}</td>
                            <td>{{ row.siswa?.kelas?.nama_kelas || '-' }}</td>
                            <td class="rekap-subject-cell">{{ row.kelas_mapel?.mata_pelajaran?.nama_mapel || row.kelasMapel?.mataPelajaran?.nama_mapel || '-' }}</td>
                            <td class="text-center">{{ row.sum1 ?? '-' }}</td>
                            <td class="text-center">{{ row.sum2 ?? '-' }}</td>
                            <td class="text-center">{{ row.sum3 ?? '-' }}</td>
                            <td class="text-center">{{ row.sum4 ?? '-' }}</td>
                            <td class="text-center">{{ row.nilai_harian ?? '-' }}</td>
                            <td class="text-center">{{ row.sts ?? '-' }}</td>
                            <td class="text-center">{{ row.sas ?? '-' }}</td>
                            <td class="text-center">{{ row.sat ?? '-' }}</td>
                            <td class="text-center">
                                <strong>{{ row.rata_akhir != null ? Number(row.rata_akhir).toFixed(1) : '-' }}</strong>
                            </td>
                            <td class="text-center">
                                <span
                                    v-if="row.rata_akhir != null"
                                    class="badge"
                                    :class="Number(row.rata_akhir) >= 92 ? 'text-bg-success' : Number(row.rata_akhir) >= 83 ? 'text-bg-primary' : Number(row.rata_akhir) >= 75 ? 'text-bg-warning' : 'text-bg-danger'"
                                >
                                    {{ Number(row.rata_akhir) >= 92 ? 'A' : Number(row.rata_akhir) >= 83 ? 'B' : Number(row.rata_akhir) >= 75 ? 'C' : 'D' }}
                                </span>
                                <span v-else>-</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </TableWrapper>

            <div v-if="nilai.last_page > 1" class="p-3 border-top d-flex justify-content-between align-items-center gap-2 flex-wrap">
                <span class="text-muted small">Halaman {{ nilai.current_page }} dari {{ nilai.last_page }}</span>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary" :disabled="nilai.current_page <= 1" @click="page(nilai.current_page - 1)">Sebelumnya</button>
                    <button class="btn btn-sm btn-outline-secondary" :disabled="nilai.current_page >= nilai.last_page" @click="page(nilai.current_page + 1)">Berikutnya</button>
                </div>
            </div>
        </Card>
    </AppShell>
</template>

<style scoped>
.rekap-table {
    table-layout: fixed;
}

.rekap-table .col-no { width: 56px; }
.rekap-table .col-name { width: 250px; }
.rekap-table .col-class { width: 100px; }
.rekap-table .col-subject { width: 190px; }
.rekap-table .col-score { width: 70px; }
.rekap-table .col-predicate { width: 94px; }

.rekap-name-cell,
.rekap-subject-cell {
    white-space: normal !important;
    overflow-wrap: anywhere;
    line-height: 1.35;
}

@media (max-width: 767.98px) {
    .app-table th,
    .app-table td {
        font-size: 0.8rem;
    }

    .rekap-table .col-name {
        width: 220px;
    }
}
</style>
