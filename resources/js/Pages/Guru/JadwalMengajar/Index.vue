<script setup lang="ts">
import type { PropType } from 'vue';
interface Schedule { mapel: string; id: number; hari: number; hari_label: string; kelas: string; mata_pelajaran: string; pelajaran_ke: number; delete_url: string; workspace_url: string; kelas_mapel: string }
import { computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import PageHeader from '../../../Components/AppShell/PageHeader.vue';
import { SearchableSelect, SelectInput } from '../../../Components/Form';
import AppShell from '../../../Layouts/AppShell.vue';
import { Button, Card, EmptyState } from '../../../Components/UI';

const props = defineProps({
    days: { type: Array as PropType<{ value: number; label: string }[]>, default: () => [] },
    lessonSlots: { type: Array as PropType<{ value: number; label: string }[]>, default: () => [] },
    kelasMapel: { type: Array as PropType<{ id: number; label: string }[]>, default: () => [] },
    schedules: { type: Array as PropType<Schedule[]>, default: () => [] },
    storeUrl: { type: String, required: true },
});

const form = useForm({
    kelas_mapel_id: '',
    hari: '',
    pelajaran_ke: '',
});

const todayDayNumber = new Date().getDay();

const groupedSchedules = computed(() => props.days
    .map((day) => ({
        ...day,
        schedules: props.schedules.filter((schedule) => Number(schedule.hari) === Number(day.value)),
    }))
    .filter((day) => day.schedules.length > 0));

const todayScheduleCount = computed(() => props.schedules.filter(
    (schedule) => Number(schedule.hari) === todayDayNumber,
).length);

const uniqueClassCount = computed(() => new Set(
    props.schedules
        .map((schedule) => schedule.kelas)
        .filter((kelas) => kelas && kelas !== '-'),
).size);

function submit() {
    if (form.processing) {
        return;
    }

    form.post(props.storeUrl, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

async function destroySchedule(schedule: Schedule) {
    const confirmed = await window.confirmDialog?.(`Hapus jadwal ${schedule.hari_label} pelajaran ke-${schedule.pelajaran_ke}?`, {
        title: 'Hapus Jadwal',
        confirmText: 'Ya, hapus',
        danger: true,
    });

    if (!confirmed) return;

    router.delete(schedule.delete_url, { preserveScroll: true });
}
</script>

<template>
    <Head title="Jadwal Mengajar" />

    <AppShell title="Jadwal Mengajar">
        <PageHeader
            title="Jadwal Mengajar"
            subtitle="Atur jadwal mengajar untuk membantu sinkronisasi absensi kelas."
            icon="bi-calendar-week-fill"
        />

        <div class="schedule-summary" aria-label="Ringkasan jadwal mengajar">
            <div class="schedule-summary-card">
                <div class="schedule-summary-icon">
                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                </div>
                <div>
                    <div class="schedule-summary-label">Total Jadwal</div>
                    <div class="schedule-summary-value">{{ schedules.length }}</div>
                    <div class="schedule-summary-caption">Slot mengajar aktif</div>
                </div>
            </div>

            <div class="schedule-summary-card">
                <div class="schedule-summary-icon is-today">
                    <i class="bi bi-sun" aria-hidden="true"></i>
                </div>
                <div>
                    <div class="schedule-summary-label">Hari Ini</div>
                    <div class="schedule-summary-value">{{ todayScheduleCount }}</div>
                    <div class="schedule-summary-caption">Jadwal mengajar</div>
                </div>
            </div>

            <div class="schedule-summary-card">
                <div class="schedule-summary-icon is-class">
                    <i class="bi bi-mortarboard" aria-hidden="true"></i>
                </div>
                <div>
                    <div class="schedule-summary-label">Kelas Aktif</div>
                    <div class="schedule-summary-value">{{ uniqueClassCount }}</div>
                    <div class="schedule-summary-caption">Kelas dalam jadwal</div>
                </div>
            </div>
        </div>

        <Card title="Tambah Jadwal" icon="bi-calendar-plus" class="schedule-form-card">
            <form @submit.prevent="submit">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-6">
                        <SearchableSelect
                            v-model="form.kelas_mapel_id"
                            name="kelas_mapel_id"
                            label="Kelas dan Mata Pelajaran"
                            placeholder="Pilih kelas/mapel"
                            :options="kelasMapel.map((item) => ({ value: item.id, label: item.label }))"
                            required
                            :error="form.errors.kelas_mapel_id"
                        />
                    </div>
                    <div class="col-md-4 col-lg-2">
                        <SelectInput
                            v-model="form.hari"
                            name="hari"
                            label="Hari"
                            placeholder="Pilih hari"
                            :options="days"
                            required
                            :error="form.errors.hari"
                        />
                    </div>
                    <div class="col-md-4 col-lg-2">
                        <SelectInput
                            v-model="form.pelajaran_ke"
                            name="pelajaran_ke"
                            label="Jam Pelajaran"
                            placeholder="Pilih jam"
                            :options="lessonSlots"
                            required
                            :error="form.errors.pelajaran_ke"
                        />
                    </div>
                    <div class="col-md-4 col-lg-2 schedule-form-action">
                        <Button type="submit" color="success" icon="bi-plus-lg" :disabled="form.processing">
                            {{ form.processing ? 'Menyimpan...' : 'Tambah' }}
                        </Button>
                    </div>
                </div>
            </form>
        </Card>

        <section class="schedule-list-section" aria-labelledby="schedule-list-title">
            <div class="schedule-list-heading">
                <div>
                    <div class="schedule-eyebrow">JADWAL ANDA</div>
                    <h2 id="schedule-list-title" class="schedule-list-title">Daftar Jadwal Mengajar</h2>
                    <p class="schedule-list-caption mb-0">
                        Jadwal dikelompokkan berdasarkan hari agar lebih mudah dipindai.
                    </p>
                </div>
                <div v-if="schedules.length" class="schedule-count-badge">
                    {{ schedules.length }} jadwal
                </div>
            </div>

            <div v-if="schedules.length" class="schedule-day-stack">
                <section
                    v-for="day in groupedSchedules"
                    :key="day.value"
                    class="schedule-day-section"
                    :class="{ 'is-today': Number(day.value) === todayDayNumber }"
                >
                    <div class="schedule-day-header">
                        <div class="schedule-day-title-wrap">
                            <span class="schedule-day-dot" aria-hidden="true"></span>
                            <h3 class="schedule-day-title mb-0">{{ day.label }}</h3>
                            <span v-if="Number(day.value) === todayDayNumber" class="schedule-today-badge">
                                Hari ini
                            </span>
                        </div>
                        <span class="schedule-day-count">{{ day.schedules.length }} jadwal</span>
                    </div>

                    <div class="schedule-grid">
                        <article
                            v-for="schedule in day.schedules"
                            :key="schedule.id"
                            class="schedule-card"
                        >
                            <div class="schedule-card-top">
                                <div class="schedule-card-icon" aria-hidden="true">
                                    <i class="bi bi-book"></i>
                                </div>
                                <button
                                    type="button"
                                    class="schedule-delete-btn"
                                    :aria-label="`Hapus jadwal ${schedule.mapel} ${schedule.kelas}`"
                                    title="Hapus jadwal"
                                    @click="destroySchedule(schedule)"
                                >
                                    <i class="bi bi-trash3" aria-hidden="true"></i>
                                </button>
                            </div>

                            <div class="schedule-card-body">
                                <h4 class="schedule-card-title">{{ schedule.mapel || schedule.kelas_mapel }}</h4>
                                <p class="schedule-card-class mb-0">
                                    <i class="bi bi-people" aria-hidden="true"></i>
                                    {{ schedule.kelas || '-' }}
                                </p>
                            </div>

                            <div class="schedule-card-footer">
                                <span class="schedule-slot-badge">
                                    <i class="bi bi-clock" aria-hidden="true"></i>
                                    Pelajaran ke-{{ schedule.pelajaran_ke }}
                                </span>
                                <span class="schedule-day-mini">{{ schedule.hari_label }}</span>
                            </div>
                        </article>
                    </div>
                </section>
            </div>

            <div v-else class="schedule-empty-wrap">
                <EmptyState
                    title="Belum ada jadwal mengajar"
                    message="Tambahkan jadwal pertama Anda agar absensi dapat mengikuti hari dan slot pelajaran yang benar."
                    icon="bi-calendar-week"
                />
            </div>
        </section>
    </AppShell>
</template>

<style scoped>
.schedule-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.schedule-summary-card {
    display: flex;
    align-items: center;
    gap: 0.9rem;
    min-width: 0;
    padding: 1rem 1.05rem;
    border: 1px solid var(--bs-border-color-translucent);
    border-radius: 1rem;
    background: var(--surface-card, var(--bs-body-bg));
    box-shadow: var(--modern-shadow-sm, 0 8px 20px rgba(15, 23, 42, 0.04));
}

.schedule-summary-icon {
    display: grid;
    width: 46px;
    height: 46px;
    flex: 0 0 46px;
    place-items: center;
    border-radius: 0.85rem;
    background: var(--primary-50, var(--bs-primary-bg-subtle));
    color: var(--primary-600, var(--bs-primary));
    font-size: 1.2rem;
}

.schedule-summary-icon.is-today {
    background: var(--status-info-bg, var(--bs-info-bg-subtle));
    color: var(--status-info-text, var(--bs-info-text-emphasis));
}

.schedule-summary-icon.is-class {
    background: var(--status-success-bg, var(--bs-success-bg-subtle));
    color: var(--status-success-text, var(--bs-success-text-emphasis));
}

.schedule-summary-label {
    color: var(--bs-secondary-color);
    font-size: 0.78rem;
    font-weight: 600;
}

.schedule-summary-value {
    margin-top: 0.05rem;
    color: var(--bs-emphasis-color);
    font-size: 1.55rem;
    font-weight: 800;
    line-height: 1.05;
}

.schedule-summary-caption {
    margin-top: 0.18rem;
    color: var(--bs-secondary-color);
    font-size: 0.74rem;
}

.schedule-form-card {
    margin-bottom: 1.7rem;
}

.schedule-form-action :deep(.btn) {
    width: 100%;
    min-height: 38px;
}

.schedule-list-section {
    padding-bottom: 1rem;
}

.schedule-list-heading {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
}

.schedule-eyebrow {
    margin-bottom: 0.2rem;
    color: var(--primary-600, var(--bs-primary));
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.12em;
}

.schedule-list-title {
    margin: 0;
    color: var(--bs-emphasis-color);
    font-size: 1.18rem;
    font-weight: 800;
}

.schedule-list-caption {
    margin-top: 0.22rem;
    color: var(--bs-secondary-color);
    font-size: 0.82rem;
}

.schedule-count-badge,
.schedule-day-count {
    white-space: nowrap;
    color: var(--bs-secondary-color);
    font-size: 0.76rem;
    font-weight: 700;
}

.schedule-count-badge {
    padding: 0.42rem 0.65rem;
    border: 1px solid var(--bs-border-color-translucent);
    border-radius: 999px;
    background: var(--surface-card, var(--bs-body-bg));
}

.schedule-day-stack {
    display: grid;
    gap: 1.45rem;
}

.schedule-day-section {
    padding: 1rem;
    border: 1px solid var(--bs-border-color-translucent);
    border-radius: 1rem;
    background: var(--app-surface-subtle, var(--bs-tertiary-bg));
}

.schedule-day-section.is-today {
    border-color: color-mix(in srgb, var(--primary-500, var(--bs-primary)) 32%, transparent);
    background: color-mix(in srgb, var(--primary-500, var(--bs-primary)) 4%, var(--surface-card, var(--bs-body-bg)));
}

.schedule-day-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.85rem;
}

.schedule-day-title-wrap {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    min-width: 0;
}

.schedule-day-dot {
    width: 8px;
    height: 8px;
    flex: 0 0 8px;
    border-radius: 50%;
    background: var(--primary-500, var(--bs-primary));
}

.schedule-day-title {
    color: var(--bs-emphasis-color);
    font-size: 0.92rem;
    font-weight: 800;
}

.schedule-today-badge {
    padding: 0.22rem 0.48rem;
    border-radius: 999px;
    background: var(--status-success-bg, var(--bs-success-bg-subtle));
    color: var(--status-success-text, var(--bs-success-text-emphasis));
    font-size: 0.66rem;
    font-weight: 800;
}

.schedule-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.85rem;
}

.schedule-card {
    display: flex;
    min-width: 0;
    min-height: 178px;
    padding: 1rem;
    border: 1px solid var(--bs-border-color-translucent);
    border-radius: 0.9rem;
    background: var(--surface-card, var(--bs-body-bg));
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.035);
    flex-direction: column;
    transition: border-color 160ms ease, transform 160ms ease, box-shadow 160ms ease;
}

.schedule-card:hover {
    border-color: color-mix(in srgb, var(--primary-500, var(--bs-primary)) 28%, var(--bs-border-color));
    box-shadow: var(--modern-shadow-sm, 0 10px 24px rgba(15, 23, 42, 0.07));
    transform: translateY(-1px);
}

.schedule-card-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
}

.schedule-card-icon {
    display: grid;
    width: 38px;
    height: 38px;
    place-items: center;
    border-radius: 0.75rem;
    background: var(--primary-50, var(--bs-primary-bg-subtle));
    color: var(--primary-600, var(--bs-primary));
}

.schedule-delete-btn {
    display: grid;
    width: 34px;
    height: 34px;
    padding: 0;
    place-items: center;
    border: 0;
    border-radius: 0.65rem;
    background: transparent;
    color: var(--bs-secondary-color);
    transition: background-color 140ms ease, color 140ms ease;
}

.schedule-delete-btn:hover,
.schedule-delete-btn:focus-visible {
    outline: none;
    background: var(--bs-danger-bg-subtle);
    color: var(--bs-danger-text-emphasis);
}

.schedule-card-body {
    padding: 0.9rem 0 1rem;
}

.schedule-card-title {
    display: -webkit-box;
    margin: 0;
    overflow: hidden;
    color: var(--bs-emphasis-color);
    font-size: 1rem;
    font-weight: 800;
    line-height: 1.35;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.schedule-card-class {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    margin-top: 0.4rem;
    color: var(--bs-secondary-color);
    font-size: 0.8rem;
}

.schedule-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.65rem;
    padding-top: 0.75rem;
    margin-top: auto;
    border-top: 1px solid var(--bs-border-color-translucent);
}

.schedule-slot-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    min-width: 0;
    color: var(--bs-emphasis-color);
    font-size: 0.75rem;
    font-weight: 700;
}

.schedule-day-mini {
    color: var(--bs-secondary-color);
    font-size: 0.72rem;
}

.schedule-empty-wrap {
    overflow: hidden;
    border: 1px solid var(--bs-border-color-translucent);
    border-radius: 1rem;
    background: var(--surface-card, var(--bs-body-bg));
}

@media (max-width: 991.98px) {
    .schedule-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 767.98px) {
    .schedule-summary {
        grid-template-columns: 1fr;
        gap: 0.7rem;
    }

    .schedule-summary-card {
        padding: 0.85rem 0.9rem;
    }

    .schedule-list-heading {
        align-items: flex-start;
        flex-direction: column;
    }

    .schedule-grid {
        grid-template-columns: 1fr;
    }

    .schedule-day-section {
        padding: 0.8rem;
    }
}
</style>
