<script setup lang="ts">
import type { PropType } from 'vue';

import { Head, router, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import PageHeader from '../../../Components/AppShell/PageHeader.vue';
import { SelectInput } from '../../../Components/Form';
import AppShell from '../../../Layouts/AppShell.vue';
import { Badge, Button, Card, EmptyState, TableWrapper } from '../../../Components/UI';

const props = defineProps({
    waliKelas: { type: Object as PropType<{ kelas: string; store_url: string; back_url: string }>, required: true },
    bulan: { type: String, required: true },
    bulanLabel: { type: String, default: '' },
    bulanOptions: { type: Object as PropType<Record<string, string>>, default: () => ({}) },
    tanggalList: { type: Array as PropType<{ key: string; day: string; label: string }[]>, default: () => [] },
    students: { type: Array as PropType<{ id: number; no: number; nis: string; nama: string; absensi: Record<string, string> }[]>, default: () => [] },
});

const filter = useForm({ bulan: props.bulan });
const form = useForm({
    bulan: props.bulan,
    absensi: buildAbsensi(),
});

watch(() => [props.students, props.bulan], () => {
    form.bulan = props.bulan;
    form.absensi = buildAbsensi();
    filter.bulan = props.bulan;
}, { deep: true });

function buildAbsensi() {
    return Object.fromEntries(props.students.map((student) => [
        String(student.id),
        Object.fromEntries(props.tanggalList.map((tanggal) => [
            tanggal.key,
            student.absensi?.[tanggal.key] ?? '',
        ])),
    ]));
}

function filterMonth() {
    router.get(window.location.pathname, { bulan: filter.bulan }, {
        preserveScroll: true,
        preserveState: false,
    });
}

function fillColumn(tanggalKey: string, event: Event) {
    if (!(event.target instanceof HTMLSelectElement)) return;
    const status = event.target.value;
    event.target.value = '';
    if (!status) return;

    props.students.forEach((student) => {
        form.absensi[String(student.id)][tanggalKey] = status;
    });
}

function statusClass(status: string) {
    return status ? `status-${status}` : '';
}

function submit() {
    if (form.processing) {
        return;
    }

    form.post(props.waliKelas.store_url, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Absensi Wali Kelas" />

    <AppShell title="Absensi Wali Kelas">
        <PageHeader title="Absensi Wali Kelas" icon="bi-clipboard-check-fill">
            <template #actions>
                <Badge color="primary">{{ waliKelas.kelas }}</Badge>
            </template>
        </PageHeader>

        <div class="row gy-4">
            <div class="col-12">
                <Card title="Filter Bulan" icon="bi-funnel-fill">
                    <form class="row g-3 align-items-end" @submit.prevent="filterMonth">
                        <div class="col-md-4">
                            <SelectInput
                                v-model="filter.bulan"
                                name="bulan"
                                label="Bulan"
                                wrapper-class="mb-0"
                                :options="bulanOptions"
                            />
                        </div>
                        <div class="col-md-3 d-grid">
                            <Button type="submit" color="primary" icon="bi-search">Tampilkan</Button>
                        </div>
                    </form>
                </Card>
            </div>

            <div class="col-12">
                <form @submit.prevent="submit">
                    <input v-model="form.bulan" type="hidden" name="bulan">
                    <Card :title="`Absensi Harian ${bulanLabel}`" icon="bi-table" body-class="p-0">
                        <div class="p-3 d-flex flex-wrap gap-2">
                            <Badge color="success">H=Hadir</Badge>
                            <Badge color="warning text-dark">S=Sakit</Badge>
                            <Badge color="info text-dark">I=Izin</Badge>
                            <Badge color="danger">A=Alpha</Badge>
                        </div>

                        <TableWrapper>
                            <table class="table table-bordered table-hover mb-0 wali-attendance-table">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width:44px;">No</th>
                                        <th style="min-width:90px;">NIS</th>
                                        <th style="min-width:180px;">Nama</th>
                                        <th
                                            v-for="tanggal in tanggalList"
                                            :key="tanggal.key"
                                            class="text-center"
                                            style="min-width:62px;"
                                        >
                                            {{ tanggal.day }}<br><small class="text-muted">{{ tanggal.label }}</small>
                                        </th>
                                    </tr>
                                    <tr>
                                        <td colspan="3"></td>
                                        <td v-for="tanggal in tanggalList" :key="`fill-${tanggal.key}`" class="text-center p-1">
                                            <select
                                                class="form-select form-select-sm wali-attendance-select"
                                                @change="fillColumn(tanggal.key, $event)"
                                            >
                                                <option value="">-</option>
                                                <option value="hadir">H</option>
                                                <option value="sakit">S</option>
                                                <option value="izin">I</option>
                                                <option value="alpha">A</option>
                                            </select>
                                        </td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="student in students" :key="student.id">
                                        <td class="text-center text-muted align-middle">{{ student.no }}</td>
                                        <td class="align-middle">{{ student.nis }}</td>
                                        <td class="align-middle"><strong>{{ student.nama }}</strong></td>
                                        <td v-for="tanggal in tanggalList" :key="`${student.id}-${tanggal.key}`" class="p-1 text-center align-middle">
                                            <select
                                                v-model="form.absensi[String(student.id)][tanggal.key]"
                                                class="form-select form-select-sm wali-attendance-select"
                                                :class="statusClass(form.absensi[String(student.id)][tanggal.key])"
                                            >
                                                <option value="">-</option>
                                                <option value="hadir">H</option>
                                                <option value="sakit">S</option>
                                                <option value="izin">I</option>
                                                <option value="alpha">A</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr v-if="!students.length">
                                        <td :colspan="3 + tanggalList.length">
                                            <EmptyState title="Tidak ada siswa aktif di kelas ini." icon="bi-people" />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </TableWrapper>

                        <template #footer>
                            <div class="d-flex flex-column flex-sm-row justify-content-between gap-2">
                                <a :href="waliKelas.back_url" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Kembali
                                </a>
                                <Button type="submit" color="success" icon="bi-save" :disabled="form.processing">
                                    {{ form.processing ? 'Menyimpan...' : 'Simpan Absensi' }}
                                </Button>
                            </div>
                        </template>
                    </Card>
                </form>
            </div>
        </div>
    </AppShell>
</template>

<style scoped>
.wali-attendance-select { font-size:0.72rem; min-width:54px; padding:0.25rem 0.35rem; text-align:center; }
.wali-attendance-select.status-hadir { background:var(--status-success-bg); color:var(--status-success-text); }
.wali-attendance-select.status-sakit { background:var(--status-warning-bg); color:var(--status-warning-text); }
.wali-attendance-select.status-izin { background:var(--status-info-bg); color:var(--status-info-text); }
.wali-attendance-select.status-alpha { background:var(--status-danger-bg); color:var(--status-danger-text); }
.wali-attendance-table th { vertical-align:middle; }
</style>
