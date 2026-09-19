<script setup lang="ts">
import type { PropType } from 'vue';
interface OnlineSession  { id: number; judul: string; tanggal: string; pelajaran_ke: number; status: string; meeting_url: string; kelas_mapel: string; status_url: string; delete_url: string }
import { Head, router, useForm } from '@inertiajs/vue3';
import PageHeader from '../../../Components/AppShell/PageHeader.vue';
import { SearchableSelect, SelectInput, TextareaInput, TextInput } from '../../../Components/Form';
import AppShell from '../../../Layouts/AppShell.vue';
import { Badge, Button, Card, EmptyState, TableWrapper } from '../../../Components/UI';

const props = defineProps({
    kelasMapel: { type: Array as PropType<{ id: number; label: string }[]>, default: () => [] },
    lessonSlots: { type: Array as PropType<{ value: number; label: string }[]>, default: () => [] },
    sessions: { type: Array as PropType<OnlineSession[]>, default: () => [] },
    storeUrl: { type: String, required: true },
});

const form = useForm({
    kelas_mapel_id: '',
    judul: '',
    deskripsi: '',
    tanggal: '',
    pelajaran_ke: '',
    meeting_url: '',
    status: 'terjadwal',
});

const statusOptions = [
    { value: 'terjadwal', label: 'Terjadwal' },
    { value: 'selesai', label: 'Selesai' },
    { value: 'dibatalkan', label: 'Dibatalkan' },
];

function submit() {
    if (form.processing) {
        return;
    }

    form.post(props.storeUrl, {
        preserveScroll: true,
        onSuccess: () => form.reset('kelas_mapel_id', 'judul', 'deskripsi', 'tanggal', 'pelajaran_ke', 'meeting_url'),
    });
}

function statusColor(status: string) {
    if (status === 'selesai') return 'success';
    if (status === 'dibatalkan') return 'danger';
    return 'primary';
}

function updateStatus(session: OnlineSession, status: string | number | boolean | null) {
    router.patch(session.status_url, { status }, { preserveScroll: true });
}

async function destroySession(session: OnlineSession) {
    const confirmed = await window.confirmDialog?.(`Hapus kelas daring ${session.judul}?`, {
        title: 'Hapus Kelas Daring',
        confirmText: 'Ya, hapus',
        danger: true,
    });

    if (!confirmed) return;

    router.delete(session.delete_url, { preserveScroll: true });
}
</script>

<template>
    <Head title="Kelas Daring" />

    <AppShell title="Kelas Daring">
        <PageHeader
            title="Kelas Daring"
            subtitle="Simpan link Google Meet, Zoom, atau Jitsi tanpa integrasi API berbayar."
            icon="bi-camera-video-fill"
        />

        <div class="row g-4">
            <div class="col-lg-5">
                <Card title="Jadwalkan Kelas Daring" icon="bi-plus-circle">
                    <form @submit.prevent="submit">
                        <SearchableSelect
                            v-model="form.kelas_mapel_id"
                            name="kelas_mapel_id"
                            label="Kelas dan Mata Pelajaran"
                            placeholder="Pilih kelas/mapel"
                            :options="kelasMapel.map((item) => ({ value: item.id, label: item.label }))"
                            required
                            :error="form.errors.kelas_mapel_id"
                        />
                        <TextInput v-model="form.judul" name="judul" label="Judul" required :error="form.errors.judul" />
                        <div class="row">
                            <div class="col-md-6">
                                <TextInput v-model="form.tanggal" type="date" name="tanggal" label="Tanggal" required :error="form.errors.tanggal" />
                            </div>
                            <div class="col-md-6">
                                <SelectInput v-model="form.pelajaran_ke" name="pelajaran_ke" label="Jam Pelajaran" placeholder="Pilih" :options="lessonSlots" required :error="form.errors.pelajaran_ke" />
                            </div>
                        </div>
                        <TextInput v-model="form.meeting_url" type="url" name="meeting_url" label="Link Meeting" placeholder="https://meet.google.com/..." required :error="form.errors.meeting_url" />
                        <TextareaInput v-model="form.deskripsi" name="deskripsi" label="Catatan" :rows="3" :error="form.errors.deskripsi" />
                        <SelectInput v-model="form.status" name="status" label="Status" :options="statusOptions" required :error="form.errors.status" />
                        <Button type="submit" color="success" icon="bi-save" :disabled="form.processing">
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Kelas Daring' }}
                        </Button>
                    </form>
                </Card>
            </div>

            <div class="col-lg-7">
                <Card title="Histori Kelas Daring" icon="bi-camera-video" body-class="p-0">
                    <TableWrapper v-if="sessions.length">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Sesi</th>
                                    <th>Jadwal</th>
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="session in sessions" :key="session.id">
                                    <td>
                                        <strong>{{ session.judul }}</strong>
                                        <div class="text-muted small">{{ session.kelas_mapel }}</div>
                                        <a :href="session.meeting_url" target="_blank" rel="noopener noreferrer" class="small">Buka link</a>
                                    </td>
                                    <td>{{ session.tanggal }}<div class="text-muted small">Pelajaran ke-{{ session.pelajaran_ke }}</div></td>
                                    <td>
                                        <Badge :color="statusColor(session.status)">{{ session.status }}</Badge>
                                        <SelectInput
                                            :model-value="session.status"
                                            name="status_inline"
                                            wrapper-class="mt-2 mb-0"
                                            :options="statusOptions"
                                            @update:model-value="updateStatus(session, $event)"
                                        />
                                    </td>
                                    <td class="text-end">
                                        <Button type="button" color="outline-danger" icon="bi-trash" @click="destroySession(session)">Hapus</Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </TableWrapper>
                    <EmptyState v-else title="Belum ada kelas daring" message="Simpan link meeting dari layanan gratis atau akun sekolah yang sudah tersedia." icon="bi-camera-video" />
                </Card>
            </div>
        </div>
    </AppShell>
</template>
