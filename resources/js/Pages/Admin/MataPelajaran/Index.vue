<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PageHeader from '../../../Components/AppShell/PageHeader.vue';
import { TextInput } from '../../../Components/Form';
import AppShell from '../../../Layouts/AppShell.vue';
import { Badge, Button, Card, EmptyState, IconButton, TableWrapper } from '../../../Components/UI';

interface MataPelajaran { id: number; kode: string; nama_mapel: string; urutan?: number }
interface MataPelajaranForm { kode: string; nama_mapel: string; urutan: number }
interface Props { mapel?: MataPelajaran[] }

const props = withDefaults(defineProps<Props>(), { mapel: () => [] });

const editing = ref<MataPelajaran | null>(null);
const createForm = useForm<MataPelajaranForm>({
    kode: '',
    nama_mapel: '',
    urutan: 0,
});
const editForm = useForm<MataPelajaranForm>({
    kode: '',
    nama_mapel: '',
    urutan: 0,
});
const formTitle = computed(() => editing.value ? 'Edit Mata Pelajaran' : 'Tambah Mata Pelajaran');
const formIcon = computed(() => editing.value ? 'bi-pencil-square' : 'bi-plus-circle');

function startEdit(item: MataPelajaran): void {
    editing.value = item;
    editForm.clearErrors();
    editForm.defaults({
        kode: item.kode ?? '',
        nama_mapel: item.nama_mapel ?? '',
        urutan: item.urutan ?? 0,
    });
    editForm.reset();
}

function cancelEdit(): void {
    editing.value = null;
    editForm.clearErrors();
    editForm.reset();
}

function submitCreate(): void {
    if (createForm.processing) {
        return;
    }

    createForm.post('/admin/mata-pelajaran', {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
}

function submitEdit(): void {
    if (!editing.value || editForm.processing) {
        return;
    }

    editForm.put(`/admin/mata-pelajaran/${editing.value.id}`, {
        preserveScroll: true,
        onSuccess: cancelEdit,
    });
}

async function destroy(item: MataPelajaran): Promise<void> {
    const confirmed = await window.confirmDialog?.(`Hapus ${item.nama_mapel}?`, {
        title: 'Hapus Mata Pelajaran',
        confirmText: 'Ya, hapus',
        danger: true,
    });

    if (!confirmed) {
        return;
    }

    router.delete(`/admin/mata-pelajaran/${item.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            if (editing.value?.id === item.id) {
                cancelEdit();
            }
        },
    });
}
</script>

<template>
    <Head title="Pengaturan Mata Pelajaran" />

    <AppShell title="Pengaturan Mata Pelajaran">
        <PageHeader
            title="Pengaturan Mata Pelajaran"
            subtitle="Tentukan mata pelajaran yang diajar dan urutan tampilannya di LMS."
            icon="bi-book-fill"
        />

        <div class="row">
            <div class="col-md-5 mb-4">
                <Card :title="formTitle" :icon="formIcon">
                    <form v-if="!editing" @submit.prevent="submitCreate">
                        <TextInput
                            v-model="createForm.kode"
                            name="kode"
                            label="Kode"
                            placeholder="Contoh: MTK"
                            maxlength="10"
                            required
                            :error="createForm.errors.kode"
                        />
                        <TextInput
                            v-model="createForm.nama_mapel"
                            name="nama_mapel"
                            label="Nama Mata Pelajaran"
                            placeholder="Contoh: Matematika"
                            maxlength="100"
                            required
                            :error="createForm.errors.nama_mapel"
                        />
                        <TextInput
                            v-model="createForm.urutan"
                            name="urutan"
                            type="number"
                            label="Urutan"
                            min="0"
                            :error="createForm.errors.urutan"
                        />
                        <Button
                            type="submit"
                            color="success"
                            size=""
                            icon="bi-save"
                            class="w-100"
                            :disabled="createForm.processing"
                        >
                            {{ createForm.processing ? 'Menyimpan...' : 'Simpan' }}
                        </Button>
                    </form>

                    <form v-else @submit.prevent="submitEdit">
                        <TextInput
                            v-model="editForm.kode"
                            name="kode"
                            label="Kode"
                            maxlength="10"
                            required
                            :error="editForm.errors.kode"
                        />
                        <TextInput
                            v-model="editForm.nama_mapel"
                            name="nama_mapel"
                            label="Nama Mata Pelajaran"
                            maxlength="100"
                            required
                            :error="editForm.errors.nama_mapel"
                        />
                        <TextInput
                            v-model="editForm.urutan"
                            name="urutan"
                            type="number"
                            label="Urutan"
                            min="0"
                            :error="editForm.errors.urutan"
                        />
                        <div class="d-flex gap-2">
                            <Button type="button" color="light" size="" class="flex-fill" @click="cancelEdit">
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                color="primary"
                                size=""
                                icon="bi-save"
                                class="flex-fill"
                                :disabled="editForm.processing"
                            >
                                {{ editForm.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                            </Button>
                        </div>
                    </form>
                </Card>
            </div>

            <div class="col-md-7 mb-4">
                <Card title="Daftar Mata Pelajaran" icon="bi-book-fill" body-class="p-0">
                    <TableWrapper v-if="mapel.length">
                        <table class="table table-hover app-table mb-0">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama Mapel</th>
                                    <th>Urutan</th>
                                    <th class="table-action-column">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in mapel" :key="item.id">
                                    <td><Badge color="secondary">{{ item.kode }}</Badge></td>
                                    <td>{{ item.nama_mapel }}</td>
                                    <td>{{ item.urutan }}</td>
                                    <td class="table-action-column">
                                        <div class="d-flex justify-content-end gap-1">
                                            <IconButton
                                                icon="bi-pencil"
                                                :label="`Edit ${item.nama_mapel}`"
                                                color="outline-primary"
                                                @click="startEdit(item)"
                                            />
                                            <IconButton
                                                icon="bi-trash"
                                                :label="`Hapus ${item.nama_mapel}`"
                                                color="outline-danger"
                                                @click="destroy(item)"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </TableWrapper>
                    <EmptyState v-else title="Belum ada mata pelajaran" icon="bi-book" />
                </Card>
            </div>
        </div>
    </AppShell>
</template>
