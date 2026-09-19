<script setup lang="ts">
import type { PropType } from 'vue';
import type { Chart } from 'chart.js';
import { Head, Link } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import PageHeader from '../../Components/AppShell/PageHeader.vue';
import AppShell from '../../Layouts/AppShell.vue';
import { Badge, Card, EmptyState } from '../../Components/UI';

const props = defineProps({
    header: { type: Object as PropType<{ nama: string; kelas: string; tahun_ajaran: string | null; semester_label: string }>, required: true },
    stats: { type: Object as PropType<{ trend_delta: number | null; trend_delta_label: string; rata_nilai_label: string; mapel_dinilai: number; total_mapel: number; persen_hadir: number; bulan_label: string; hadir: number; sakit: number; izin: number; alpha: number; persen_pengumpulan: number; tugas_dikumpulkan: number; total_tugas: number; tugas_belum: number; tugas_perlu_perbaikan: number; batas_ketuntasan: number }>, required: true },
    subjectScores: { type: Array as PropType<{ kelas_mapel_id: number; href: string; nama_mapel: string; rata: number | null; rata_label: string; status_tone: string; status_label: string }[]>, default: () => [] },
    focusItems: { type: Array as PropType<{ type: string; tone: string; icon: string; title: string; description: string; href: string | null; action_label: string }[]>, default: () => [] },
    scoreTrend: { type: Array as PropType<{ label: string; value: number }[]>, default: () => [] },
});

const trendCanvas = ref<HTMLCanvasElement | null>(null);
let trendChart: Chart<'line'> | null = null;
let themeObserver: MutationObserver | null = null;

const subtitle = computed(() => [
    props.header.nama,
    props.header.kelas,
    props.header.tahun_ajaran ? `TA ${props.header.tahun_ajaran}` : null,
    `Semester ${props.header.semester_label}`,
].filter(Boolean).join(' · '));

const hasTrend = computed(() => props.scoreTrend.length >= 2);
const trendDirection = computed(() => {
    const value = props.stats.trend_delta;
    if (value === null || value === undefined) return 'neutral';
    if (Number(value) > 0) return 'up';
    if (Number(value) < 0) return 'down';
    return 'neutral';
});

function scoreWidth(value: number | null) {
    if (value === null || value === undefined) return '0%';
    return `${Math.min(100, Math.max(0, Number(value)))}%`;
}

function cssVar(name: string, fallback: string) {
    const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    return value || fallback;
}

async function renderTrendChart() {
    if (!trendCanvas.value || !hasTrend.value) {
        trendChart?.destroy();
        trendChart = null;
        return;
    }

    const { Chart, registerables } = await import('chart.js');
    Chart.register(...registerables);
    if (!trendCanvas.value || !hasTrend.value) return;

    const primary = cssVar('--app-primary', '#198754');
    const muted = cssVar('--bs-secondary-color', '#6c757d');
    const border = cssVar('--bs-border-color', '#dee2e6');

    trendChart?.destroy();
    trendChart = new Chart(trendCanvas.value, {
        type: 'line',
        data: {
            labels: props.scoreTrend.map((item) => item.label),
            datasets: [{
                label: 'Rata-rata',
                data: props.scoreTrend.map((item) => item.value),
                borderColor: primary,
                backgroundColor: primary,
                pointBackgroundColor: primary,
                pointBorderColor: primary,
                pointRadius: 4,
                pointHoverRadius: 6,
                borderWidth: 3,
                tension: 0.32,
                fill: false,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: { color: muted, stepSize: 20 },
                    grid: { color: border },
                },
                x: {
                    ticks: { color: muted },
                    grid: { display: false },
                },
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (context) => `Rata-rata ${Number(context.parsed.y).toFixed(2)}`,
                    },
                },
            },
        },
    });
}

onMounted(() => {
    nextTick(renderTrendChart);

    themeObserver = new MutationObserver(() => nextTick(renderTrendChart));
    themeObserver.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['data-bs-theme', 'style'],
    });
});

watch(() => props.scoreTrend, () => nextTick(renderTrendChart), { deep: true });

onBeforeUnmount(() => {
    themeObserver?.disconnect();
    trendChart?.destroy();
});
</script>

<template>
    <Head title="Progress Saya" />

    <AppShell title="Progress Saya">
        <PageHeader
            title="Progress Belajar"
            icon="bi-graph-up-arrow"
            :subtitle="subtitle"
        />

        <div class="stats-grid progress-stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-star-fill" aria-hidden="true"></i></div>
                <div>
                    <div class="stat-number">{{ stats.rata_nilai_label }}</div>
                    <div class="stat-label">Rata-rata Nilai</div>
                    <small class="text-muted">
                        {{ stats.mapel_dinilai }} dari {{ stats.total_mapel }} mapel sudah memiliki nilai
                    </small>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-calendar-check-fill" aria-hidden="true"></i></div>
                <div class="w-100">
                    <div class="stat-number">{{ stats.persen_hadir }}%</div>
                    <div class="stat-label">Kehadiran {{ stats.bulan_label }}</div>
                    <div class="progress mt-2" role="progressbar" :aria-valuenow="stats.persen_hadir" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar bg-primary" :style="{ width: `${stats.persen_hadir}%` }"></div>
                    </div>
                    <small class="text-muted">H {{ stats.hadir }} · S {{ stats.sakit }} · I {{ stats.izin }} · A {{ stats.alpha }}</small>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-clipboard-check-fill" aria-hidden="true"></i></div>
                <div class="w-100">
                    <div class="stat-number">{{ stats.persen_pengumpulan }}%</div>
                    <div class="stat-label">Pengumpulan Tugas</div>
                    <div class="progress mt-2" role="progressbar" :aria-valuenow="stats.persen_pengumpulan" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar bg-primary" :style="{ width: `${stats.persen_pengumpulan}%` }"></div>
                    </div>
                    <small class="text-muted">
                        {{ stats.tugas_dikumpulkan }}/{{ stats.total_tugas }} dikumpulkan
                        <span v-if="stats.tugas_belum"> · {{ stats.tugas_belum }} belum</span>
                        <span v-if="stats.tugas_perlu_perbaikan"> · {{ stats.tugas_perlu_perbaikan }} perlu perbaikan</span>
                    </small>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-5">
                <Card title="Fokus Belajar" icon="bi-bullseye">
                    <div v-if="focusItems.length" class="focus-list">
                        <article
                            v-for="(item, index) in focusItems"
                            :key="`${item.type}-${index}`"
                            class="focus-item"
                            :class="`is-${item.tone}`"
                        >
                            <div class="focus-icon" aria-hidden="true">
                                <i class="bi" :class="item.icon"></i>
                            </div>
                            <div class="focus-body">
                                <strong>{{ item.title }}</strong>
                                <span>{{ item.description }}</span>
                            </div>
                            <Link v-if="item.href" :href="item.href" class="focus-link">
                                {{ item.action_label }}
                                <i class="bi bi-arrow-right" aria-hidden="true"></i>
                            </Link>
                        </article>
                    </div>
                    <EmptyState
                        v-else
                        title="Tidak ada prioritas mendesak"
                        message="Tugas, nilai, dan kehadiran Anda saat ini tidak membutuhkan tindakan khusus."
                        icon="bi-check-circle"
                    />
                </Card>
            </div>

            <div class="col-lg-7">
                <Card title="Perkembangan Komponen Nilai" icon="bi-activity">
                    <div v-if="hasTrend" class="trend-panel">
                        <div class="trend-summary">
                            <span>Rata-rata lintas mata pelajaran pada komponen yang sudah dinilai.</span>
                            <Badge
                                v-if="stats.trend_delta_label"
                                :color="trendDirection === 'up' ? 'success' : (trendDirection === 'down' ? 'warning' : 'secondary')"
                            >
                                <i
                                    class="bi me-1"
                                    :class="trendDirection === 'up' ? 'bi-arrow-up' : (trendDirection === 'down' ? 'bi-arrow-down' : 'bi-dash')"
                                    aria-hidden="true"
                                ></i>
                                {{ stats.trend_delta_label }}
                            </Badge>
                        </div>
                        <div class="trend-chart-wrap">
                            <canvas ref="trendCanvas"></canvas>
                        </div>
                    </div>
                    <EmptyState
                        v-else
                        title="Tren belum terbentuk"
                        message="Tren akan tampil setelah setidaknya dua komponen penilaian tersedia."
                        icon="bi-graph-up"
                    />
                </Card>
            </div>
        </div>

        <Card title="Performa Mata Pelajaran" icon="bi-journal-check">
            <div v-if="subjectScores.length" class="subject-list">
                <article v-for="item in subjectScores" :key="item.kelas_mapel_id" class="subject-item">
                    <div class="subject-heading">
                        <div>
                            <Link :href="item.href" class="subject-name">{{ item.nama_mapel }}</Link>
                            <div class="subject-meta">
                                <span v-if="item.rata !== null">Batas ketuntasan {{ Number(stats.batas_ketuntasan).toFixed(2) }}</span>
                                <span v-else>Nilai belum tersedia</span>
                            </div>
                        </div>
                        <div class="subject-score">
                            <strong>{{ item.rata_label }}</strong>
                            <Badge :color="item.status_tone">{{ item.status_label }}</Badge>
                        </div>
                    </div>

                    <div v-if="item.rata !== null" class="progress subject-progress" role="progressbar" :aria-valuenow="item.rata" aria-valuemin="0" aria-valuemax="100">
                        <div
                            class="progress-bar"
                            :class="item.status_tone === 'success' ? 'bg-primary' : 'subject-progress-warning'"
                            :style="{ width: scoreWidth(item.rata) }"
                        ></div>
                    </div>
                    <div v-else class="subject-empty-line" aria-hidden="true"></div>
                </article>
            </div>
            <EmptyState
                v-else
                title="Belum ada mata pelajaran aktif"
                message="Data performa akan muncul setelah mata pelajaran aktif tersedia untuk kelas Anda."
                icon="bi-journal"
            />
        </Card>
    </AppShell>
</template>

<style scoped>
.progress-stats-grid {
    margin-bottom: 1.5rem;
}

.stat-card .progress {
    height: 7px;
    background: var(--surface-muted, var(--bs-tertiary-bg));
}

.focus-list {
    display: flex;
    flex-direction: column;
    gap: 0.7rem;
}

.focus-item {
    display: grid;
    grid-template-columns: 40px minmax(0, 1fr) auto;
    gap: 0.75rem;
    align-items: center;
    padding: 0.8rem;
    border: 1px solid var(--modern-border, var(--bs-border-color));
    border-radius: 0.8rem;
    background: var(--surface-card, var(--bs-body-bg));
}

.focus-icon {
    display: grid;
    width: 40px;
    height: 40px;
    place-items: center;
    border-radius: 0.7rem;
    background: var(--surface-muted, var(--bs-tertiary-bg));
    color: var(--text-muted, var(--bs-secondary-color));
}

.focus-item.is-warning .focus-icon {
    background: var(--status-warning-bg, var(--bs-warning-bg-subtle));
    color: var(--status-warning-text, var(--bs-warning-text-emphasis));
}

.focus-item.is-danger .focus-icon {
    background: var(--status-danger-bg, var(--bs-danger-bg-subtle));
    color: var(--status-danger-text, var(--bs-danger-text-emphasis));
}

.focus-body {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: 0.18rem;
}

.focus-body strong {
    color: var(--text-strong, var(--bs-emphasis-color));
    font-size: 0.84rem;
}

.focus-body span {
    color: var(--text-muted, var(--bs-secondary-color));
    font-size: 0.74rem;
    line-height: 1.4;
}

.focus-link,
.subject-name {
    color: var(--primary-600, var(--bs-primary));
    font-weight: 700;
    text-decoration: none;
}

.focus-link {
    display: inline-flex;
    gap: 0.35rem;
    align-items: center;
    white-space: nowrap;
    font-size: 0.75rem;
}

.focus-link:hover,
.subject-name:hover {
    text-decoration: underline;
}

.trend-panel {
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
}

.trend-summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    color: var(--text-muted, var(--bs-secondary-color));
    font-size: 0.76rem;
}

.trend-chart-wrap {
    position: relative;
    min-height: 260px;
}

.subject-list {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.85rem;
}

.subject-item {
    padding: 0.95rem;
    border: 1px solid var(--modern-border, var(--bs-border-color));
    border-radius: 0.9rem;
    background: var(--surface-card, var(--bs-body-bg));
}

.subject-heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.9rem;
}

.subject-name {
    display: inline-block;
    color: var(--text-strong, var(--bs-emphasis-color));
    font-size: 0.86rem;
}

.subject-meta {
    margin-top: 0.15rem;
    color: var(--text-muted, var(--bs-secondary-color));
    font-size: 0.7rem;
}

.subject-score {
    display: flex;
    flex: 0 0 auto;
    align-items: center;
    gap: 0.5rem;
}

.subject-score strong {
    color: var(--text-strong, var(--bs-emphasis-color));
    font-size: 1rem;
}

.subject-progress {
    height: 8px;
    margin-top: 0.8rem;
    background: var(--surface-muted, var(--bs-tertiary-bg));
}

.subject-progress-warning {
    background: var(--status-warning-text, var(--bs-warning)) !important;
}

.subject-empty-line {
    height: 8px;
    margin-top: 0.8rem;
    border-radius: 99px;
    background: var(--surface-muted, var(--bs-tertiary-bg));
}

@media (max-width: 991.98px) {
    .subject-list {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 575.98px) {
    .focus-item {
        grid-template-columns: 40px minmax(0, 1fr);
    }

    .focus-link {
        grid-column: 2;
        justify-self: start;
    }

    .subject-heading,
    .trend-summary {
        align-items: flex-start;
        flex-direction: column;
    }

    .subject-score {
        width: 100%;
        justify-content: space-between;
    }
}
</style>
