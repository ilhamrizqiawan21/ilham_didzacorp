<script setup lang="ts">
import type { PropType } from 'vue';
import type { PaginationLink } from '../../../types/pagination';
interface Student { id: number; nis: string; nama_lengkap: string; kelas_id: number | null; jenis_kelamin: string | null; tinggal_kelas: boolean; kelas: string; status: string; password_is_default: boolean; password_status: string; is_active: boolean }
import { Head, router, useForm } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import { FileInput, SelectInput, TextInput } from '../../../Components/Form';
import AppShell from '../../../Layouts/AppShell.vue';
import { Badge, Button, Card, DashboardHero, EmptyState, IconButton, MetricStrip, Pagination, TableWrapper } from '../../../Components/UI';

const props = defineProps({
    kelasList: { type: Array as PropType<{ id: number; label: string; tingkat: string; nama_kelas: string; siswa_count: number }[]>, default: () => [] },
    siswa: { type: Object as PropType<{ data: Student[]; links: PaginationLink[] }>, default: () => ({ data: [], links: [] }) },
    filters: { type: Object as PropType<{ kelas_id?: string; search?: string; status?: string }>, default: () => ({}) },
    metrics: { type: Object as PropType<{ total_siswa_aktif?: number; total_lulus?: number; total_keluar?: number }>, default: () => ({}) },
    importErrors: { type: Array as PropType<string[]>, default: () => [] },
    studentPassword: { type: Object as PropType<{ title: string; name: string; username: string; password: string } | null>, default: null },
    templateUrl: { type: String, required: true },
    exportUrl: { type: String, required: true },
});

const genderOptions = [
    { value: 'L', label: 'Laki-laki' },
    { value: 'P', label: 'Perempuan' },
];

const filterForm = reactive({
    kelas_id: props.filters.kelas_id ?? '',
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
});

const statusOptions = [
    { value: 'aktif', label: 'Aktif' },
    { value: 'lulus', label: 'Lulus' },
    { value: 'keluar', label: 'Keluar' },
];

const metrics = () => [
    { label: 'Siswa aktif', value: props.metrics.total_siswa_aktif ?? 0, icon: 'bi-people-fill', tone: 'success' },
    { label: 'Lulus', value: props.metrics.total_lulus ?? 0, icon: 'bi-mortarboard-fill', tone: 'primary' },
    { label: 'Keluar', value: props.metrics.total_keluar ?? 0, icon: 'bi-person-dash-fill', tone: 'warning' },
];

const editing = ref<Student | null>(null);
const importForm = useForm({ file_siswa: null as File | null });
const createForm = useForm(blankStudent());
const editForm = useForm({ ...blankStudent(), tinggal_kelas: false });

function blankStudent() {
    return {
        nis: '',
        nama_lengkap: '',
        kelas_id: '' as string | number,
        jenis_kelamin: '',
    };
}

function kelasOptions() {
    return props.kelasList.map((kelas) => ({ value: kelas.id, label: kelas.label }));
}

function cleanFilters() {
    return Object.fromEntries(Object.entries(filterForm).filter(([, value]) => value !== '' && value !== null));
}

function exportExcelUrl() {
    const params = new URLSearchParams(cleanFilters()).toString();
    return params ? `${props.exportUrl}?${params}` : props.exportUrl;
}

function applyFilters() {
    router.get('/admin/kelas-siswa', cleanFilters(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function resetFilters() {
    filterForm.kelas_id = '';
    filterForm.search = '';
    filterForm.status = '';
    router.get('/admin/kelas-siswa', {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function submitImport() {
    if (importForm.processing) {
        return;
    }

    importForm.post('/admin/kelas-siswa/import', {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => importForm.reset('file_siswa'),
    });
}

function submitCreate() {
    if (createForm.processing) {
        return;
    }

    createForm.post('/admin/kelas-siswa/siswa', {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
}

function startEdit(item: Student) {
    editing.value = item;
    editForm.clearErrors();
    editForm.defaults({
        nis: item.nis ?? '',
        nama_lengkap: item.nama_lengkap ?? '',
        kelas_id: item.kelas_id ?? '',
        jenis_kelamin: item.jenis_kelamin ?? '',
        tinggal_kelas: Boolean(item.tinggal_kelas),
    });
    editForm.reset();
}

function cancelEdit() {
    editing.value = null;
    editForm.clearErrors();
    editForm.reset();
}

function submitEdit(item: Student) {
    if (editForm.processing) {
        return;
    }

    editForm.put(`/admin/kelas-siswa/siswa/${item.id}`, {
        preserveScroll: true,
        onSuccess: cancelEdit,
    });
}

async function resetPassword(item: Student) {
    const confirmed = await window.confirmDialog?.('Reset password siswa ke password default 123456?', {
        title: 'Reset Password',
        confirmText: 'Ya, reset',
    });

    if (!confirmed) return;

    router.post(`/admin/kelas-siswa/siswa/${item.id}/reset-password`, {}, {
        preserveScroll: true,
    });
}

async function destroyStudent(item: Student) {
    const confirmed = await window.confirmDialog?.(`Hapus siswa ${item.nama_lengkap ?? item.nis}?`, {
        title: 'Hapus Siswa',
        confirmText: 'Ya, hapus',
        danger: true,
    });

    if (!confirmed) return;

    router.delete(`/admin/kelas-siswa/siswa/${item.id}`, {
        preserveScroll: true,
    });
}

async function graduateClass(kelas: { id: number; nama_kelas: string }) {
    const confirmed = await window.confirmDialog?.(`Luluskan semua siswa kelas ${kelas.nama_kelas}?`, {
        title: 'Luluskan Kelas',
        confirmText: 'Ya, luluskan',
    });

    if (!confirmed) return;

    router.post(`/admin/kelas-siswa/kelas/${kelas.id}/luluskan`, {}, {
        preserveScroll: true,
    });
}

function passwordStatusColor(isDefault: boolean) {
    return isDefault ? 'warning text-dark' : 'success';
}
</script>

<template>
    <Head title="Kelas dan Siswa" />

    <AppShell title="Kelas dan Siswa">
        <DashboardHero
            eyebrow="Administrasi Akademik"
            title="Kelas dan Siswa"
            subtitle="Import, tambah, perbarui, dan pantau status siswa per kelas."
            icon="bi-mortarboard-fill"
            tone="admin"
        />

        <MetricStrip :items="metrics()" />

        <div v-if="importErrors.length" class="alert alert-danger" role="alert">
            <div class="fw-semibold mb-2"><i class="bi bi-exclamation-triangle-fill me-1" aria-hidden="true"></i> Import siswa gagal</div>
            <ul class="mb-0 ps-3">
                <li v-for="error in importErrors" :key="error">{{ error }}</li>
            </ul>
        </div>

        <div v-if="studentPassword" class="alert alert-warning" role="alert">
            <div class="fw-semibold mb-1"><i class="bi bi-key-fill me-1" aria-hidden="true"></i> {{ studentPassword.title }}</div>
            <div>Nama: <strong>{{ studentPassword.name }}</strong></div>
            <div>Username: <code>{{ studentPassword.username }}</code></div>
            <div>Password: <code>{{ studentPassword.password }}</code></div>
            <div class="small mt-2">Catat dan serahkan password ini secara langsung. Password hanya ditampilkan setelah proses berhasil.</div>
        </div>

        <Card title="Import Siswa dari Excel" icon="bi-file-earmark-spreadsheet-fill" class="mb-3">
            <template #actions>
                <a :href="templateUrl" class="btn btn-outline-success btn-sm">
                    <i class="bi bi-download me-1" aria-hidden="true"></i> Download Template
                </a>
            </template>

            <form class="row g-3 align-items-end" @submit.prevent="submitImport">
                <div class="col-md-8">
                    <FileInput
                        v-model="importForm.file_siswa"
                        name="file_siswa"
                        label="File Excel"
                        accept=".xlsx"
                        accept-label="Format .xlsx"
                        max-size="5MB"
                        required
                        wrapper-class="mb-0"
                        help="Gunakan template yang disediakan. Maksimal 500 siswa per file."
                        :error="importForm.errors.file_siswa"
                    />
                </div>
                <div class="col-md-4">
                    <Button type="submit" color="primary" size="" icon="bi-upload" class="w-100" :disabled="importForm.processing">
                        {{ importForm.processing ? 'Mengimport...' : 'Import Siswa' }}
                    </Button>
                </div>
            </form>
        </Card>

        <Card title="Tambah Siswa Baru" icon="bi-person-plus-fill" class="mb-3">
            <form class="row g-3 align-items-end" @submit.prevent="submitCreate">
                <div class="col-md-3">
                    <TextInput v-model="createForm.nis" name="nis" label="NIS" placeholder="NIS" required wrapper-class="mb-0" :error="createForm.errors.nis" />
                </div>
                <div class="col-md-3">
                    <TextInput v-model="createForm.nama_lengkap" name="nama_lengkap" label="Nama Lengkap" placeholder="Nama" required wrapper-class="mb-0" :error="createForm.errors.nama_lengkap" />
                </div>
                <div class="col-md-3">
                    <SelectInput v-model="createForm.kelas_id" name="kelas_id" label="Kelas" placeholder="-- Pilih Kelas --" required wrapper-class="mb-0" :options="kelasOptions()" :error="createForm.errors.kelas_id" />
                </div>
                <div class="col-md-2">
                    <SelectInput v-model="createForm.jenis_kelamin" name="jenis_kelamin" label="Jenis Kelamin" placeholder="--" required wrapper-class="mb-0" :options="genderOptions" :error="createForm.errors.jenis_kelamin" />
                </div>
                <div class="col-md-1 d-grid">
                    <Button type="submit" color="success" size="" icon="bi-plus-lg" :disabled="createForm.processing" aria-label="Tambah siswa" />
                </div>
            </form>
        </Card>

        <Card title="Kelulusan Kelas IX" icon="bi-mortarboard-fill" class="mb-3" body-class="p-0">
            <TableWrapper v-if="kelasList.length">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>Tingkat</th><th>Kelas</th><th>Siswa Aktif</th><th class="table-action-column">Aksi</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="kelas in kelasList" :key="kelas.id">
                            <td><Badge color="secondary">{{ kelas.tingkat }}</Badge></td>
                            <td><strong>{{ kelas.nama_kelas }}</strong></td>
                            <td>{{ kelas.siswa_count ?? 0 }} siswa</td>
                            <td class="table-action-column">
                                <div class="d-flex justify-content-end gap-1">
                                    <Button v-if="kelas.tingkat === 'IX'" type="button" color="outline-success" icon="bi-check-circle" @click="graduateClass(kelas)">
                                        Luluskan
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </TableWrapper>
            <EmptyState v-else title="Belum ada kelas" icon="bi-building" />
        </Card>

        <Card title="Daftar Siswa" icon="bi-people-fill" body-class="p-0">
            <template #actions>
                <form class="d-flex flex-wrap gap-2" @submit.prevent="applyFilters">
                    <SelectInput v-model="filterForm.kelas_id" name="kelas_id" wrapper-class="mb-0 filter-control" placeholder="Semua Kelas" :options="kelasOptions()" />
                    <SelectInput v-model="filterForm.status" name="status" wrapper-class="mb-0 filter-control" placeholder="Semua Status" :options="statusOptions" />
                    <TextInput v-model="filterForm.search" name="search" wrapper-class="mb-0 filter-control" placeholder="Cari NIS/Nama..." />
                    <Button type="submit" color="primary" icon="bi-search" aria-label="Cari siswa" />
                    <Button type="button" color="outline-secondary" icon="bi-arrow-clockwise" aria-label="Reset filter" @click="resetFilters" />
                    <a :href="exportExcelUrl()" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i> Excel
                    </a>
                </form>
            </template>

            <TableWrapper v-if="siswa.data?.length">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>NIS</th><th>Nama</th><th>JK</th><th>Kelas</th><th>Status Siswa</th><th>Status Password</th><th class="table-action-column">Aksi</th></tr>
                    </thead>
                    <tbody>
                        <template v-for="item in siswa.data" :key="item.id">
                            <tr>
                                <td>{{ item.nis }}</td>
                                <td><strong>{{ item.nama_lengkap ?? '-' }}</strong></td>
                                <td>{{ item.jenis_kelamin ?? '-' }}</td>
                                <td>{{ item.kelas || '-' }}</td>
                                <td><Badge :color="item.status === 'aktif' ? 'success' : 'secondary'">{{ item.status }}</Badge></td>
                                <td>
                                    <Badge :color="passwordStatusColor(item.password_is_default)">
                                        {{ item.password_status }}
                                    </Badge>
                                    <Badge v-if="item.tinggal_kelas" color="warning text-dark" class="ms-1">Tinggal Kelas</Badge>
                                    <Badge v-if="!item.is_active" color="secondary" class="ms-1">Akun nonaktif</Badge>
                                </td>
                                <td class="table-action-column">
                                    <div class="d-inline-flex gap-1">
                                        <IconButton icon="bi-pencil" :label="`Edit ${item.nama_lengkap ?? item.nis}`" color="outline-primary" @click="startEdit(item)" />
                                        <IconButton icon="bi-key" :label="`Reset password ${item.nama_lengkap ?? item.nis}`" color="outline-warning" @click="resetPassword(item)" />
                                        <IconButton icon="bi-trash" :label="`Hapus ${item.nama_lengkap ?? item.nis}`" color="outline-danger" @click="destroyStudent(item)" />
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="editing?.id === item.id">
                                <td colspan="7">
                                    <form class="row g-3 align-items-end" @submit.prevent="submitEdit(item)">
                                        <div class="col-md-3">
                                            <TextInput v-model="editForm.nis" name="edit_nis" label="NIS" required wrapper-class="mb-0" :error="editForm.errors.nis" />
                                        </div>
                                        <div class="col-md-3">
                                            <TextInput v-model="editForm.nama_lengkap" name="edit_nama_lengkap" label="Nama Lengkap" required wrapper-class="mb-0" :error="editForm.errors.nama_lengkap" />
                                        </div>
                                        <div class="col-md-2">
                                            <SelectInput v-model="editForm.kelas_id" name="edit_kelas_id" label="Kelas" required wrapper-class="mb-0" :options="kelasOptions()" :error="editForm.errors.kelas_id" />
                                        </div>
                                        <div class="col-md-2">
                                            <SelectInput v-model="editForm.jenis_kelamin" name="edit_jenis_kelamin" label="Jenis Kelamin" required wrapper-class="mb-0" :options="genderOptions" :error="editForm.errors.jenis_kelamin" />
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-check mb-2">
                                                <input :id="`tinggalKelas${item.id}`" v-model="editForm.tinggal_kelas" type="checkbox" class="form-check-input" />
                                                <label class="form-check-label" :for="`tinggalKelas${item.id}`">Tinggal Kelas</label>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <Button type="button" color="light" class="flex-fill" @click="cancelEdit">Batal</Button>
                                                <Button type="submit" color="primary" icon="bi-save" class="flex-fill" :disabled="editForm.processing" />
                                            </div>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </TableWrapper>
            <EmptyState v-else title="Tidak ada data siswa" icon="bi-people" />

            <template v-if="siswa.links?.length" #footer>
                <Pagination :links="siswa.links" />
            </template>
        </Card>
    </AppShell>
</template>

<style scoped>
.filter-control {
    min-width: 180px;
}
</style>
