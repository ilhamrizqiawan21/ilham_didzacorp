<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import PageHeader from '../../../Components/AppShell/PageHeader.vue';
import AppShell from '../../../Layouts/AppShell.vue';
import { Card, EmptyState, StatCard, TableWrapper } from '../../../Components/UI';
interface ClassStudents { label: string; jumlah: number; }
interface MonthlyAttendance { bulan: string; bulan_label?: string; hadir: number; total: number; persentase: number; }
interface MonthlySubmission { bulan: string; bulan_label?: string; total: number; tepat_waktu: number; terlambat: number; dinilai: number; }
interface GradeDistribution { label: string; value: number; color: string; }
interface LearningStats { total_tugas: number; total_pengumpulan: number; total_dinilai: number; persentase_dinilai: number; rata_nilai_tugas: number | null; }

interface Props { siswaPerKelas?: ClassStudents[]; totalGuru?: number; totalSiswa?: number; totalKelas?: number; absensiBulanan?: MonthlyAttendance[]; pengumpulanBulanan?: MonthlySubmission[]; distribusiNilai?: GradeDistribution[]; pembelajaran?: LearningStats; }
const props = withDefaults(defineProps<Props>(), {
    siswaPerKelas: () => [], totalGuru: 0, totalSiswa: 0, totalKelas: 0, absensiBulanan: () => [], pengumpulanBulanan: () => [], distribusiNilai: () => [],
    pembelajaran: () => ({ total_tugas: 0, total_pengumpulan: 0, total_dinilai: 0, persentase_dinilai: 0, rata_nilai_tugas: null }),
});

const siswaCanvas = ref<HTMLCanvasElement | null>(null);
const nilaiCanvas = ref<HTMLCanvasElement | null>(null);
const absensiCanvas = ref<HTMLCanvasElement | null>(null);
const pengumpulanCanvas = ref<HTMLCanvasElement | null>(null);
type ChartHandle = { destroy: () => void };
let siswaChart: ChartHandle | null = null;
let nilaiChart: ChartHandle | null = null;
let absensiChart: ChartHandle | null = null;
let pengumpulanChart: ChartHandle | null = null;

async function chartJs() {
    const { Chart, registerables } = await import('chart.js');
    Chart.register(...registerables);
    return Chart;
}

async function renderCharts() {
    const Chart = await chartJs();

    if (siswaCanvas.value && props.siswaPerKelas.length) {
        siswaChart?.destroy();
        siswaChart = new Chart(siswaCanvas.value, {
            type: 'bar',
            data: {
                labels: props.siswaPerKelas.map((item) => item.label),
                datasets: [{
                    label: 'Jumlah Siswa',
                    data: props.siswaPerKelas.map((item) => item.jumlah),
                    backgroundColor: '#198754',
                    borderRadius: 8,
                }],
            },
            options: { responsive: true, plugins: { legend: { display: false } } },
        });
    }

    if (nilaiCanvas.value && props.distribusiNilai.length) {
        nilaiChart?.destroy();
        nilaiChart = new Chart(nilaiCanvas.value, {
            type: 'doughnut',
            data: {
                labels: props.distribusiNilai.map((item) => item.label),
                datasets: [{
                    data: props.distribusiNilai.map((item) => item.value),
                    backgroundColor: props.distribusiNilai.map((item) => item.color),
                }],
            },
        });
    }

    if (absensiCanvas.value && props.absensiBulanan.length) {
        absensiChart?.destroy();
        absensiChart = new Chart(absensiCanvas.value, {
            type: 'line',
            data: {
                labels: props.absensiBulanan.map((item) => item.bulan_label || item.bulan),
                datasets: [{
                    label: 'Persentase Hadir',
                    data: props.absensiBulanan.map((item) => item.persentase),
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25,135,84,0.16)',
                    tension: 0.35,
                    fill: true,
                }],
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true, max: 100 } },
            },
        });
    }

    if (pengumpulanCanvas.value && props.pengumpulanBulanan.length) {
        pengumpulanChart?.destroy();
        pengumpulanChart = new Chart(pengumpulanCanvas.value, {
            type: 'bar',
            data: {
                labels: props.pengumpulanBulanan.map((item) => item.bulan_label || item.bulan),
                datasets: [
                    { label: 'Tepat Waktu', data: props.pengumpulanBulanan.map((item) => item.tepat_waktu), backgroundColor: '#198754', borderRadius: 8 },
                    { label: 'Terlambat', data: props.pengumpulanBulanan.map((item) => item.terlambat), backgroundColor: '#dc3545', borderRadius: 8 },
                    { label: 'Sudah Dinilai', data: props.pengumpulanBulanan.map((item) => item.dinilai), backgroundColor: '#0d6efd', borderRadius: 8 },
                ],
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true } },
            },
        });
    }
}

onMounted(() => nextTick(renderCharts));
watch(() => [props.siswaPerKelas, props.distribusiNilai, props.absensiBulanan, props.pengumpulanBulanan], () => nextTick(renderCharts), { deep: true });
onBeforeUnmount(() => {
    siswaChart?.destroy();
    nilaiChart?.destroy();
    absensiChart?.destroy();
    pengumpulanChart?.destroy();
});
</script>

<template>
    <Head title="Statistik" />

    <AppShell title="Statistik">
        <PageHeader
            title="Statistik Sekolah"
            icon="bi-graph-up-arrow"
            subtitle="Pantau kondisi siswa, guru, kelas, pembelajaran, absensi, dan hasil belajar sekolah."
        />

        <div class="stats-grid">
            <StatCard label="Total Guru" :value="totalGuru" icon="bi-person-workspace" />
            <StatCard label="Total Kelas" :value="totalKelas" icon="bi-building" />
            <StatCard label="Total Siswa Aktif" :value="totalSiswa" icon="bi-people-fill" />
            <StatCard label="Data Nilai" :value="distribusiNilai.reduce((total, item) => total + item.value, 0)" icon="bi-bar-chart-fill" />
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <Card title="Statistik Pembelajaran" icon="bi-journal-check">
                    <div class="row g-3">
                        <div class="col-6 col-xl-3">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">Total Tugas Aktif</div>
                                <div class="fs-4 fw-bold">{{ pembelajaran.total_tugas }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-xl-3">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">Total Pengumpulan</div>
                                <div class="fs-4 fw-bold">{{ pembelajaran.total_pengumpulan }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-xl-3">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">Sudah Dinilai</div>
                                <div class="fs-4 fw-bold">{{ pembelajaran.total_dinilai }}</div>
                                <div class="text-muted small">{{ pembelajaran.persentase_dinilai }}% dari pengumpulan</div>
                            </div>
                        </div>
                        <div class="col-6 col-xl-3">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">Rata-rata Nilai Tugas</div>
                                <div class="fs-4 fw-bold">{{ pembelajaran.rata_nilai_tugas ?? '-' }}</div>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6 mb-4">
                <Card title="Jumlah Siswa Per Kelas" icon="bi-people-fill">
                    <canvas v-if="siswaPerKelas.length" ref="siswaCanvas" height="250"></canvas>
                    <EmptyState v-else title="Belum ada data siswa." icon="bi-people" />
                </Card>
            </div>

            <div class="col-md-6 mb-4">
                <Card title="Distribusi Nilai" icon="bi-bar-chart-fill">
                    <canvas v-if="distribusiNilai.some((item) => item.value > 0)" ref="nilaiCanvas" height="250"></canvas>
                    <EmptyState v-else title="Belum ada data nilai." icon="bi-bar-chart" />
                </Card>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-7 mb-4">
                <Card title="Tren Kehadiran 6 Bulan Terakhir" icon="bi-clipboard-check-fill">
                    <canvas v-if="absensiBulanan.length" ref="absensiCanvas" height="220"></canvas>
                    <EmptyState v-else title="Belum ada data absensi." icon="bi-clipboard-check" />
                </Card>
            </div>

            <div class="col-lg-5 mb-4">
                <Card title="Statistik Absensi Bulanan" icon="bi-list-ul" body-class="p-0">
                    <TableWrapper v-if="absensiBulanan.length">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Bulan</th>
                                    <th>Hadir</th>
                                    <th>Total</th>
                                    <th>Kehadiran</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in absensiBulanan" :key="item.bulan">
                                    <td><strong>{{ item.bulan_label || item.bulan }}</strong></td>
                                    <td>{{ item.hadir }}</td>
                                    <td>{{ item.total }}</td>
                                    <td>{{ item.persentase }}%</td>
                                </tr>
                            </tbody>
                        </table>
                    </TableWrapper>
                    <EmptyState v-else title="Tidak ada data" icon="bi-list-ul" />
                </Card>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-7 mb-4">
                <Card title="Tren Pengumpulan Tugas Bulanan" icon="bi-journal-check">
                    <canvas v-if="pengumpulanBulanan.length" ref="pengumpulanCanvas" height="220"></canvas>
                    <EmptyState v-else title="Belum ada data pengumpulan tugas." icon="bi-journal-check" />
                </Card>
            </div>

            <div class="col-lg-5 mb-4">
                <Card title="Rekap Pengumpulan Bulanan" icon="bi-list-check" body-class="p-0">
                    <TableWrapper v-if="pengumpulanBulanan.length">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Bulan</th>
                                    <th>Total</th>
                                    <th>Tepat Waktu</th>
                                    <th>Terlambat</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in pengumpulanBulanan" :key="item.bulan">
                                    <td><strong>{{ item.bulan_label || item.bulan }}</strong></td>
                                    <td>{{ item.total }}</td>
                                    <td>{{ item.tepat_waktu }}</td>
                                    <td>{{ item.terlambat }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </TableWrapper>
                    <EmptyState v-else title="Tidak ada data" icon="bi-list-check" />
                </Card>
            </div>
        </div>
    </AppShell>
</template>
