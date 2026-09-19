<script setup lang="ts">
import type { PropType } from 'vue';

import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppShell from '../../Layouts/AppShell.vue';

const props = defineProps({
    type: { type: String, required: true }, title: { type: String, required: true },
    kelasList: { type: Array as PropType<{ id: number; tingkat: string | number; nama_kelas: string }[]>, default: () => [] }, kelasId: { type: [Number, String], default: null },
    semester: { type: [Number, String], default: 1 }, bulan: { type: String, default: '' }, kelasNama: { type: String, default: '' },
    rekap: { type: Array as PropType<{ nis: string; nama: string; absensi?: Record<string, string>; hadir?: number; sakit?: number; izin?: number; alpha?: number; nilai?: Record<string, number | string | null>; rata?: number | null; spiritual?: Record<string, number | null>; sosial?: Record<string, number | null> }[]>, default: () => [] }, tanggalList: { type: Array as PropType<string[]>, default: () => [] }, mapelList: { type: Array as PropType<{ id: number; kelas_mapel_id: number; nama_mapel: string }[]>, default: () => [] }, tugasList: { type: Array as PropType<{ id: number; kelasMapel?: { mataPelajaran?: { nama_mapel: string }; guru?: { nama_lengkap: string } }; sudah_kumpul: number; total_siswa: number }[]>, default: () => [] },
});
const kelasId = ref(props.kelasId);
const semester = ref(String(props.semester ?? 1));
const bulan = ref(props.bulan || '');

function reload() {
    const params: Record<string, string | number | undefined> = { kelas_id: kelasId.value || undefined, semester: semester.value || undefined };
    if (props.type === 'absensi') params.bulan = bulan.value || undefined;
    router.get(window.location.pathname, params, { preserveState: true, replace: true });
}

function exportUrl(format: 'excel' | 'pdf') {
    const base = `/admin/export/${props.type}/${format}`;
    const params = new URLSearchParams();
    if (kelasId.value) params.set('kelas_id', String(kelasId.value));
    if (semester.value) params.set('semester', semester.value);
    if (props.type === 'absensi' && bulan.value) params.set('bulan', bulan.value);
    const query = params.toString();
    return query ? `${base}?${query}` : base;
}

const empty = computed(() => props.rekap.length === 0 && props.tugasList.length === 0);
</script>

<template>
    <AppShell :title="title">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div><h1 class="h3 mb-1">{{ title }}</h1><p class="text-muted mb-0">Rekap akademik terintegrasi untuk administrasi sekolah.</p></div>
            <div v-if="kelasId" class="d-flex gap-2"><a :href="exportUrl('excel')" class="btn btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a><a :href="exportUrl('pdf')" class="btn btn-outline-danger"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a></div>
        </div>
        <div class="card border-0 shadow-sm mb-4"><div class="card-body"><div class="row g-3 align-items-end">
            <div class="col-md-5"><label class="form-label">Kelas</label><select v-model="kelasId" class="form-select" @change="reload"><option :value="null">Pilih kelas</option><option v-for="k in kelasList" :key="k.id" :value="k.id">{{ k.tingkat }} {{ k.nama_kelas }}</option></select></div>
            <div class="col-md-3"><label class="form-label">Semester</label><select v-model="semester" class="form-select" @change="reload"><option value="1">Semester 1</option><option value="2">Semester 2</option></select></div>
            <div v-if="type === 'absensi'" class="col-md-3"><label class="form-label">Bulan</label><input v-model="bulan" type="month" class="form-control" @change="reload"></div>
        </div></div></div>
        <div v-if="kelasNama" class="alert alert-light border mb-3"><strong>{{ kelasNama }}</strong> · Semester {{ semester }}</div>
        <div v-if="empty" class="card border-0 shadow-sm"><div class="card-body text-center py-5 text-muted"><i class="bi bi-inbox fs-1 d-block mb-3"></i>Belum ada data untuk filter yang dipilih.</div></div>
        <div v-else class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light">
            <tr v-if="type === 'absensi'"><th>NIS</th><th>Nama</th><th v-for="t in tanggalList" :key="t" class="text-center">{{ t.slice(8) }}</th><th>H</th><th>S</th><th>I</th><th>A</th></tr>
            <tr v-else-if="type === 'nilai'"><th>NIS</th><th>Nama</th><th v-for="m in mapelList" :key="m.kelas_mapel_id">{{ m.nama_mapel }}</th><th>Rata-rata</th></tr>
            <tr v-else-if="type === 'sikap'"><th>NIS</th><th>Nama</th><th>Taqwa</th><th>Jujur</th><th>Disiplin</th><th>Sabar</th><th>Syukur</th><th>Tawadhu</th><th>Empati</th><th>Kerja Sama</th><th>Toleransi</th><th>Percaya Diri</th><th>Komunikasi</th></tr>
            <tr v-else><th>Mata Pelajaran</th><th>Guru</th><th>Terkumpul</th><th>Total Siswa</th></tr>
        </thead><tbody>
            <template v-if="type === 'tugas'"><tr v-for="t in tugasList" :key="t.id"><td>{{ t.kelasMapel?.mataPelajaran?.nama_mapel || '-' }}</td><td>{{ t.kelasMapel?.guru?.nama_lengkap || '-' }}</td><td>{{ t.sudah_kumpul }}</td><td>{{ t.total_siswa }}</td></tr></template>
            <template v-else-if="type === 'absensi'"><tr v-for="r in rekap" :key="r.nis"><td>{{ r.nis }}</td><td>{{ r.nama }}</td><td v-for="t in tanggalList" :key="t">{{ r.absensi?.[t] ? r.absensi[t].charAt(0).toUpperCase() : '-' }}</td><td>{{ r.hadir }}</td><td>{{ r.sakit }}</td><td>{{ r.izin }}</td><td>{{ r.alpha }}</td></tr></template>
            <template v-else-if="type === 'nilai'"><tr v-for="r in rekap" :key="r.nis"><td>{{ r.nis }}</td><td>{{ r.nama }}</td><td v-for="m in mapelList" :key="m.kelas_mapel_id">{{ r.nilai?.[m.id] ?? '-' }}</td><td>{{ r.rata ?? '-' }}</td></tr></template>
            <template v-else><tr v-for="r in rekap" :key="r.nis"><td>{{ r.nis }}</td><td>{{ r.nama }}</td><td>{{ r.spiritual?.taqwa || '-' }}</td><td>{{ r.spiritual?.kejujuran || '-' }}</td><td>{{ r.spiritual?.disiplin || '-' }}</td><td>{{ r.spiritual?.sabar || '-' }}</td><td>{{ r.spiritual?.syukur || '-' }}</td><td>{{ r.spiritual?.tawadhu || '-' }}</td><td>{{ r.sosial?.empati || '-' }}</td><td>{{ r.sosial?.kerjasama || '-' }}</td><td>{{ r.sosial?.toleransi || '-' }}</td><td>{{ r.sosial?.percaya_diri || '-' }}</td><td>{{ r.sosial?.komunikasi || '-' }}</td></tr></template>
        </tbody></table></div></div>
    </AppShell>
</template>
