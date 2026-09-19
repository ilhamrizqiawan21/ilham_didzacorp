<script setup lang="ts">
import type { PropType } from 'vue';
import type { LaravelPaginator } from '../../../types/pagination';
interface StudentCase { id: number; siswa_id: number | null; siswa: string; nis: string; kondisi: string; deskripsi: string | null; tindak_lanjut: string | null; hasil: string | null; status: string; update_url: string; delete_url: string }
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import PageHeader from '../../../Components/AppShell/PageHeader.vue';
import { SearchableSelect, SelectInput, TextareaInput, TextInput } from '../../../Components/Form';
import AppShell from '../../../Layouts/AppShell.vue';
import { Badge, Button, Card, EmptyState, IconButton, Pagination, TableWrapper } from '../../../Components/UI';

const props = defineProps({
    waliKelas: { type: Object as PropType<{ kelas: string; store_url: string }>, required: true },
    siswaOptions: { type: Array as PropType<{ value: number; label: string }[]>, default: () => [] },
    penanganan: { type: Object as PropType<LaravelPaginator<StudentCase>>, required: true },
});

const statusOptions = [
    { value: 'baru', label: 'Baru' },
    { value: 'proses', label: 'Proses' },
    { value: 'selesai', label: 'Selesai' },
];

const expandedId = ref<number | null>(null);
const createForm = useForm(blankForm('baru'));
const editForm = useForm(blankForm('baru'));

function blankForm(status = '') {
    return {
        siswa_id: '' as string | number,
        kondisi: '',
        deskripsi: '',
        tindak_lanjut: '',
        hasil: '',
        status,
    };
}

function submitCreate() {
    if (createForm.processing) {
        return;
    }

    createForm.post(props.waliKelas.store_url, {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
}

function startEdit(item: StudentCase) {
    expandedId.value = expandedId.value === item.id ? null : item.id;
    editForm.clearErrors();
    editForm.siswa_id = item.siswa_id ?? '';
    editForm.kondisi = item.kondisi ?? '';
    editForm.deskripsi = item.deskripsi ?? '';
    editForm.tindak_lanjut = item.tindak_lanjut ?? '';
    editForm.hasil = item.hasil ?? '';
    editForm.status = item.status ?? 'baru';
}

function submitEdit(item: StudentCase) {
    if (editForm.processing) {
        return;
    }

    editForm.put(item.update_url, {
        preserveScroll: true,
        onSuccess: () => {
            expandedId.value = null;
        },
    });
}

async function destroy(item: StudentCase) {
    const confirmed = await window.confirmDialog?.('Hapus penanganan siswa ini?', {
        title: 'Hapus Penanganan',
        confirmText: 'Ya, hapus',
        danger: true,
    });

    if (!confirmed) return;

    router.delete(item.delete_url, { preserveScroll: true });
}

function statusColor(status: string) {
    const colors: Record<string, string> = {
        selesai: 'success',
        proses: 'warning text-dark',
        baru: 'danger',
    };
    return colors[status] ?? 'secondary';
}

function statusLabel(status: string) {
    return status ? status.charAt(0).toUpperCase() + status.slice(1) : '-';
}
</script>

<template>
    <Head title="Penanganan Siswa" />

    <AppShell title="Penanganan Siswa">
        <PageHeader title="Penanganan Siswa" icon="bi-heart-pulse">
            <template #actions>
                <Badge color="primary">{{ waliKelas.kelas }}</Badge>
            </template>
        </PageHeader>

        <div class="row gy-4">
            <div class="col-lg-4">
                <Card title="Tambah Penanganan" icon="bi-plus-circle">
                    <form @submit.prevent="submitCreate">
                        <SearchableSelect v-model="createForm.siswa_id" name="siswa_id" label="Siswa" placeholder="-- Pilih Siswa --" search-placeholder="Cari siswa..." required :options="siswaOptions" :error="createForm.errors.siswa_id" />
                        <TextInput v-model="createForm.kondisi" name="kondisi" label="Kondisi" maxlength="200" required :error="createForm.errors.kondisi" />
                        <TextareaInput v-model="createForm.deskripsi" name="deskripsi" label="Deskripsi" :rows="3" :error="createForm.errors.deskripsi" />
                        <TextareaInput v-model="createForm.tindak_lanjut" name="tindak_lanjut" label="Tindak Lanjut" :rows="3" :error="createForm.errors.tindak_lanjut" />
                        <TextareaInput v-model="createForm.hasil" name="hasil" label="Hasil" :rows="3" :error="createForm.errors.hasil" />
                        <SelectInput v-model="createForm.status" name="status" label="Status" required :options="statusOptions" :error="createForm.errors.status" />
                        <Button type="submit" color="success" icon="bi-save" class="w-100" :disabled="createForm.processing">
                            {{ createForm.processing ? 'Menyimpan...' : 'Simpan' }}
                        </Button>
                    </form>
                </Card>
            </div>

            <div class="col-lg-8">
                <Card title="Daftar Penanganan" icon="bi-list-ul" body-class="p-0">
                    <TableWrapper v-if="penanganan.data.length">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr><th>Siswa</th><th>Kondisi</th><th>Tindak Lanjut</th><th>Status</th><th>Aksi</th></tr>
                            </thead>
                            <tbody>
                                <template v-for="item in penanganan.data" :key="item.id">
                                    <tr>
                                        <td>
                                            <strong>{{ item.siswa }}</strong>
                                            <div class="small text-muted">{{ item.nis }}</div>
                                        </td>
                                        <td>
                                            {{ item.kondisi }}
                                            <div class="small text-muted">{{ item.deskripsi }}</div>
                                        </td>
                                        <td>
                                            {{ item.tindak_lanjut }}
                                            <div class="small text-muted">{{ item.hasil }}</div>
                                        </td>
                                        <td><Badge :color="statusColor(item.status)">{{ statusLabel(item.status) }}</Badge></td>
                                        <td>
                                            <div class="d-inline-flex gap-1">
                                                <IconButton icon="bi-pencil" :label="`Edit ${item.kondisi}`" color="outline-primary" @click="startEdit(item)" />
                                                <IconButton icon="bi-trash" :label="`Hapus ${item.kondisi}`" color="danger" @click="destroy(item)" />
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="expandedId === item.id">
                                        <td colspan="5">
                                            <form class="row g-3" @submit.prevent="submitEdit(item)">
                                                <div class="col-md-6">
                                                    <SearchableSelect v-model="editForm.siswa_id" name="edit_siswa_id" label="Siswa" placeholder="-- Pilih Siswa --" search-placeholder="Cari siswa..." required :options="siswaOptions" :error="editForm.errors.siswa_id" />
                                                </div>
                                                <div class="col-md-6">
                                                    <TextInput v-model="editForm.kondisi" name="edit_kondisi" label="Kondisi" maxlength="200" required :error="editForm.errors.kondisi" />
                                                </div>
                                                <div class="col-md-6">
                                                    <TextareaInput v-model="editForm.deskripsi" name="edit_deskripsi" label="Deskripsi" :rows="3" :error="editForm.errors.deskripsi" />
                                                </div>
                                                <div class="col-md-6">
                                                    <TextareaInput v-model="editForm.tindak_lanjut" name="edit_tindak_lanjut" label="Tindak Lanjut" :rows="3" :error="editForm.errors.tindak_lanjut" />
                                                </div>
                                                <div class="col-md-6">
                                                    <TextareaInput v-model="editForm.hasil" name="edit_hasil" label="Hasil" :rows="3" :error="editForm.errors.hasil" />
                                                </div>
                                                <div class="col-md-3">
                                                    <SelectInput v-model="editForm.status" name="edit_status" label="Status" required :options="statusOptions" :error="editForm.errors.status" />
                                                </div>
                                                <div class="col-md-3 d-grid align-self-end">
                                                    <Button type="submit" color="success" size="" icon="bi-save" :disabled="editForm.processing">
                                                        {{ editForm.processing ? 'Menyimpan...' : 'Simpan' }}
                                                    </Button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </TableWrapper>
                    <EmptyState v-else title="Belum ada penanganan siswa." icon="bi-heart-pulse" />
                    <template v-if="penanganan.links?.length" #footer>
                        <Pagination :links="penanganan.links" />
                    </template>
                </Card>
            </div>
        </div>
    </AppShell>
</template>
