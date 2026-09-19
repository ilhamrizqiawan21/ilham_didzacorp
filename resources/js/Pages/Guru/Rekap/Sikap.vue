<script setup lang="ts">
import type { PropType } from 'vue';
interface AttitudeRow { siswa: { id: number; nama: string; kelas: string | null }; nilai: Record<string, number | null>; rata: number | null }
import { router } from '@inertiajs/vue3';
import AppShell from '../../../Layouts/AppShell.vue';
import PageHeader from '../../../Components/AppShell/PageHeader.vue';
import { Card, EmptyState, TableWrapper } from '../../../Components/UI';

const props = defineProps({
    title: { type: String, default: 'Rekap Sikap Spiritual & Sosial' },
    semester: { type: String, default: '1' },
    kelasMapel: { type: Array as PropType<{ id: number; label: string }[]>, default: () => [] },
    sikapSpiritual: { type: Array as PropType<AttitudeRow[]>, default: () => [] },
    sikapSosial: { type: Array as PropType<AttitudeRow[]>, default: () => [] },
});

function filter() {
    const kelas = document.querySelector<HTMLSelectElement>('#kelas-mapel')?.value || undefined;
    const semester = document.querySelector<HTMLSelectElement>('#semester')?.value || '1';
    router.get('/guru/rekap-sikap', { kelas_mapel_id: kelas, semester }, { preserveState: true, replace: true });
}

function reset() {
    router.get('/guru/rekap-sikap', {}, { preserveState: true, replace: true });
}

const spiritualFields = [
    ['taqwa', 'Taqwa'], ['kejujuran', 'Kejujuran'], ['disiplin', 'Disiplin'], ['sabar', 'Sabar'], ['syukur', 'Syukur'], ['tawadhu', 'Tawadhu'],
];
const sosialFields = [
    ['empati', 'Empati'], ['kerjasama', 'Kerja Sama'], ['toleransi', 'Toleransi'], ['percaya_diri', 'Percaya Diri'], ['komunikasi', 'Komunikasi'],
];

function badgeClass(value: number | null) {
    return Number(value) >= 4 ? 'text-bg-success' : Number(value) >= 3 ? 'text-bg-warning' : 'text-bg-danger';
}
</script>

<template>
    <AppShell title="Rekap Sikap">
        <PageHeader :title="title" subtitle="Rekap sikap spiritual dan sosial dari kelas yang Anda ampu." icon="bi-file-earmark-text-fill" />

        <Card class="mb-4">
            <div class="row g-3 align-items-end app-table-filter">
                <div class="col-12 col-md-5">
                    <label class="form-label" for="kelas-mapel">Kelas & Mata Pelajaran</label>
                    <select id="kelas-mapel" class="form-select">
                        <option value="">Semua Kelas & Mapel</option>
                        <option v-for="item in kelasMapel" :key="item.id" :value="item.id">{{ item.label }}</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label" for="semester">Semester</label>
                    <select id="semester" class="form-select">
                        <option value="1" :selected="semester === '1'">Semester 1</option>
                        <option value="2" :selected="semester === '2'">Semester 2</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-2 d-grid">
                    <button class="btn btn-primary" @click="filter"><i class="bi bi-search me-1" aria-hidden="true"></i>Tampilkan</button>
                </div>
                <div class="col-12 col-md-2 d-grid">
                    <button class="btn btn-outline-secondary" @click="reset">Reset</button>
                </div>
            </div>
        </Card>

        <Card class="mb-4" body-class="p-0">
            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap px-3 py-3 border-bottom">
                <strong><i class="bi bi-star-fill me-2" aria-hidden="true"></i>Sikap Spiritual (KI-1)</strong>
                <span class="badge text-bg-secondary">{{ sikapSpiritual.length }} siswa</span>
            </div>

            <div v-if="!sikapSpiritual.length" class="p-5">
                <EmptyState title="Belum ada data spiritual." icon="bi-inbox" />
            </div>

            <TableWrapper v-else :min-width="1120">
                <table class="table table-hover align-middle mb-0 app-table rekap-table">
                    <colgroup>
                        <col class="col-no">
                        <col class="col-name">
                        <col class="col-class">
                        <col v-for="index in spiritualFields.length" :key="`spiritual-${index}`" class="col-score">
                        <col class="col-average">
                    </colgroup>
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="text-center">#</th>
                            <th scope="col">Nama Siswa</th>
                            <th scope="col">Kelas</th>
                            <th v-for="field in spiritualFields" :key="field[0]" scope="col" class="text-center">{{ field[1] }}</th>
                            <th scope="col" class="text-center">Rata²</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, index) in sikapSpiritual" :key="row.siswa.id">
                            <td class="text-center text-muted">{{ index + 1 }}</td>
                            <td class="rekap-name-cell">{{ row.siswa.nama }}</td>
                            <td>{{ row.siswa.kelas || '-' }}</td>
                            <td v-for="field in spiritualFields" :key="field[0]" class="text-center">
                                <span class="badge" :class="badgeClass(row.nilai[field[0]])">{{ row.nilai[field[0]] ?? '-' }}</span>
                            </td>
                            <td class="text-center"><strong>{{ row.rata ?? '-' }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </TableWrapper>
        </Card>

        <Card body-class="p-0">
            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap px-3 py-3 border-bottom">
                <strong><i class="bi bi-people-fill me-2" aria-hidden="true"></i>Sikap Sosial (KI-2)</strong>
                <span class="badge text-bg-secondary">{{ sikapSosial.length }} siswa</span>
            </div>

            <div v-if="!sikapSosial.length" class="p-5">
                <EmptyState title="Belum ada data sosial." icon="bi-inbox" />
            </div>

            <TableWrapper v-else :min-width="1040">
                <table class="table table-hover align-middle mb-0 app-table rekap-table">
                    <colgroup>
                        <col class="col-no">
                        <col class="col-name">
                        <col class="col-class">
                        <col v-for="index in sosialFields.length" :key="`social-${index}`" class="col-score">
                        <col class="col-average">
                    </colgroup>
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="text-center">#</th>
                            <th scope="col">Nama Siswa</th>
                            <th scope="col">Kelas</th>
                            <th v-for="field in sosialFields" :key="field[0]" scope="col" class="text-center">{{ field[1] }}</th>
                            <th scope="col" class="text-center">Rata²</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, index) in sikapSosial" :key="row.siswa.id">
                            <td class="text-center text-muted">{{ index + 1 }}</td>
                            <td class="rekap-name-cell">{{ row.siswa.nama }}</td>
                            <td>{{ row.siswa.kelas || '-' }}</td>
                            <td v-for="field in sosialFields" :key="field[0]" class="text-center">
                                <span class="badge" :class="badgeClass(row.nilai[field[0]])">{{ row.nilai[field[0]] ?? '-' }}</span>
                            </td>
                            <td class="text-center"><strong>{{ row.rata ?? '-' }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </TableWrapper>
        </Card>
    </AppShell>
</template>

<style scoped>
.rekap-table {
    table-layout: fixed;
}

.rekap-table .col-no { width: 56px; }
.rekap-table .col-name { width: 250px; }
.rekap-table .col-class { width: 110px; }
.rekap-table .col-score { width: 92px; }
.rekap-table .col-average { width: 90px; }

.rekap-name-cell {
    white-space: normal !important;
    overflow-wrap: anywhere;
    line-height: 1.35;
    font-weight: 600;
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
