<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PageHeader from '../../../Components/AppShell/PageHeader.vue';
import { TextInput } from '../../../Components/Form';
import AppShell from '../../../Layouts/AppShell.vue';
import { Badge, Button, Card, EmptyState, IconButton, TableWrapper } from '../../../Components/UI';

interface TahunAjaran { id: number; tahun: string; is_active?: boolean }
interface TahunAjaranForm { tahun: string; is_active: boolean }
interface Props { tahunAjaran?: TahunAjaran[] }

const props = withDefaults(defineProps<Props>(), { tahunAjaran: () => [] });

const editing = ref<TahunAjaran | null>(null);
const createForm = useForm<TahunAjaranForm>(blankForm());
const editForm = useForm<TahunAjaranForm>(blankForm());
const formTitle = computed(() => editing.value ? 'Edit Tahun Ajaran' : 'Tambah Tahun Ajaran');
const formIcon = computed(() => editing.value ? 'bi-pencil-square' : 'bi-plus-circle');

function blankForm(): TahunAjaranForm {
    return {
        tahun: '',
        is_active: false,
    };
}

function startEdit(item: TahunAjaran): void {
    editing.value = item;
    editForm.clearErrors();
    editForm.defaults({
        tahun: item.tahun ?? '',
        is_active: Boolean(item.is_active),
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

    createForm.post('/admin/tahun-ajaran', {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
}

function submitEdit(): void {
    if (!editing.value || editForm.processing) {
        return;
    }

    editForm.put(`/admin/tahun-ajaran/${editing.value.id}`, {
        preserveScroll: true,
        onSuccess: cancelEdit,
    });
}

async function setAktif(item: TahunAjaran): Promise<void> {
    const confirmed = await window.confirmDialog?.('Aktifkan tahun ajaran ini? Semester aktif akan kembali ke Semester 1.', {
        title: 'Aktifkan Tahun Ajaran',
        confirmText: 'Ya, aktifkan',
    });

    if (!confirmed) {
        return;
    }

    router.post(`/admin/tahun-ajaran/${item.id}/set-aktif`, {}, {
        preserveScroll: true,
    });
}

async function destroy(item: TahunAjaran): Promise<void> {
    const confirmed = await window.confirmDialog?.(`Hapus tahun ajaran ${item.tahun}?`, {
        title: 'Hapus Tahun Ajaran',
        confirmText: 'Ya, hapus',
        danger: true,
    });

    if (!confirmed) {
        return;
    }

    router.delete(`/admin/tahun-ajaran/${item.id}`, {
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
    <Head title="Tahun Ajaran" />

    <AppShell title="Tahun Ajaran">
        <PageHeader
            title="Tahun Ajaran"
            subtitle="Kelola periode akademik dan tahun ajaran aktif."
            icon="bi-calendar-event-fill"
        />

        <div class="row">
            <div class="col-md-5 mb-4">
                <Card :title="formTitle" :icon="formIcon">
                    <form v-if="!editing" @submit.prevent="submitCreate">
                        <TextInput
                            v-model="createForm.tahun"
                            name="tahun"
                            label="Tahun Ajaran"
                            placeholder="Contoh: 2026/2027"
                            maxlength="9"
                            pattern="[0-9]{4}/[0-9]{4}"
                            required
                            :error="createForm.errors.tahun"
                        />
                        <div class="form-check mb-3">
                            <input
                                id="createIsActive"
                                v-model="createForm.is_active"
                                type="checkbox"
                                class="form-check-input"
                            />
                            <label class="form-check-label" for="createIsActive">Jadikan Aktif</label>
                        </div>
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
                            v-model="editForm.tahun"
                            name="tahun"
                            label="Tahun Ajaran"
                            placeholder="2026/2027"
                            maxlength="9"
                            pattern="[0-9]{4}/[0-9]{4}"
                            required
                            :error="editForm.errors.tahun"
                        />
                        <div class="form-check mb-3">
                            <input
                                id="editIsActive"
                                v-model="editForm.is_active"
                                type="checkbox"
                                class="form-check-input"
                            />
                            <label class="form-check-label" for="editIsActive">Jadikan aktif</label>
                        </div>
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
                <Card title="Daftar Tahun Ajaran" icon="bi-calendar-event-fill" body-class="p-0">
                    <TableWrapper v-if="tahunAjaran.length">
                        <table class="table table-hover app-table mb-0">
                            <thead>
                                <tr>
                                    <th>Tahun</th>
                                    <th>Status</th>
                                    <th class="table-action-column">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in tahunAjaran" :key="item.id">
                                    <td><strong>{{ item.tahun }}</strong></td>
                                    <td>
                                        <Badge :color="item.is_active ? 'success' : 'secondary'">
                                            {{ item.is_active ? 'Aktif' : 'Tidak Aktif' }}
                                        </Badge>
                                    </td>
                                    <td class="table-action-column">
                                        <div class="d-flex justify-content-end gap-1">
                                            <IconButton
                                                icon="bi-pencil"
                                                :label="`Edit tahun ajaran ${item.tahun}`"
                                                color="outline-primary"
                                                @click="startEdit(item)"
                                            />
                                            <Button
                                                v-if="!item.is_active"
                                                type="button"
                                                color="outline-primary"
                                                icon="bi-check-circle-fill"
                                                @click="setAktif(item)"
                                            >
                                                Aktifkan
                                            </Button>
                                            <IconButton
                                                icon="bi-trash"
                                                :label="`Hapus tahun ajaran ${item.tahun}`"
                                                color="outline-danger"
                                                @click="destroy(item)"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </TableWrapper>
                    <EmptyState v-else title="Belum ada tahun ajaran" icon="bi-calendar-event" />
                </Card>
            </div>
        </div>
    </AppShell>
</template>
