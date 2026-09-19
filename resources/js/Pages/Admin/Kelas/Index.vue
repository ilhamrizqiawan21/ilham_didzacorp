<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { SelectInput, TextInput } from '../../../Components/Form';
import AppShell from '../../../Layouts/AppShell.vue';
import { Badge, Button, Card, DashboardHero, EmptyState, IconButton, MetricStrip, TableWrapper } from '../../../Components/UI';

interface Kelas { id: number; tingkat: string; nama_kelas: string; siswa_count?: number; kelas_mapel_count?: number; wali_kelas_count?: number; siswa_url?: string }
interface KelasMetrics { total_kelas?: number; total_siswa?: number }
interface KelasForm { tingkat: string; nama_kelas: string }
interface Props { kelas?: Kelas[]; metrics?: KelasMetrics }

const props = withDefaults(defineProps<Props>(), { kelas: () => [], metrics: () => ({}) });

const metrics = computed(() => [
    { label: 'Kelas', value: props.metrics.total_kelas ?? 0, icon: 'bi-building', tone: 'primary' },
    { label: 'Siswa aktif', value: props.metrics.total_siswa ?? 0, icon: 'bi-people-fill', tone: 'success', href: '/admin/kelas-siswa' },
]);

const tingkatOptions = [
    { value: '1', label: 'Kelas 1 SD' },
    { value: '2', label: 'Kelas 2 SD' },
    { value: '3', label: 'Kelas 3 SD' },
    { value: '4', label: 'Kelas 4 SD' },
    { value: '5', label: 'Kelas 5 SD' },
    { value: '6', label: 'Kelas 6 SD' },
    { value: '7', label: 'Kelas 7 SMP' },
    { value: '8', label: 'Kelas 8 SMP' },
    { value: '9', label: 'Kelas 9 SMP' },
    { value: '10', label: 'Kelas 10 SMA' },
    { value: '11', label: 'Kelas 11 SMA' },
    { value: '12', label: 'Kelas 12 SMA' },
    { value: 'Mahasiswa', label: 'Mahasiswa' },
];
const tingkatLabels = Object.fromEntries(tingkatOptions.map((item) => [item.value, item.label])) as Record<string, string>;
const legacyTingkatLabels: Record<string, string> = { VII: 'Kelas 7 SMP', VIII: 'Kelas 8 SMP', IX: 'Kelas 9 SMP' };
const legacyTingkatValues: Record<string, string> = { VII: '7', VIII: '8', IX: '9' };

function tingkatLabel(value: string): string {
    return tingkatLabels[value] ?? legacyTingkatLabels[value] ?? value;
}

const editing = ref<Kelas | null>(null);
const createForm = useForm<KelasForm>(blankForm());
const editForm = useForm<KelasForm>(blankForm());
const formTitle = computed(() => editing.value ? 'Edit Kelas' : 'Tambah Kelas');
const formIcon = computed(() => editing.value ? 'bi-pencil-square' : 'bi-plus-circle');

function blankForm(): KelasForm {
    return {
        tingkat: '',
        nama_kelas: '',
    };
}

function startEdit(item: Kelas): void {
    editing.value = item;
    editForm.clearErrors();
    editForm.defaults({
        tingkat: legacyTingkatValues[item.tingkat] ?? item.tingkat ?? '',
        nama_kelas: item.nama_kelas ?? '',
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

    createForm.post('/admin/kelas', {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
}

function submitEdit(): void {
    if (!editing.value || editForm.processing) {
        return;
    }

    editForm.put(`/admin/kelas/${editing.value.id}`, {
        preserveScroll: true,
        onSuccess: cancelEdit,
    });
}

async function destroy(item: Kelas): Promise<void> {
    const confirmed = await window.confirmDialog?.(`Hapus kelas ${item.nama_kelas}?`, {
        title: 'Hapus Kelas',
        confirmText: 'Ya, hapus',
        danger: true,
    });

    if (!confirmed) {
        return;
    }

    router.delete(`/admin/kelas/${item.id}`, {
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
    <Head title="Data Kelas" />

    <AppShell title="Data Kelas">
        <DashboardHero
            eyebrow="Master Data"
            title="Data Kelas"
            subtitle="Kelola rombongan belajar dan siswa aktif. Mata pelajaran mengikuti pengaturan pembelajaran guru."
            icon="bi-building-fill"
            tone="admin"
        />

        <MetricStrip :items="metrics" />

        <div class="row">
            <div class="col-md-5 mb-4">
                <Card :title="formTitle" :icon="formIcon">
                    <form v-if="!editing" @submit.prevent="submitCreate">
                        <SelectInput
                            v-model="createForm.tingkat"
                            name="tingkat"
                            label="Tingkat"
                            placeholder="-- Pilih --"
                            required
                            :options="tingkatOptions"
                            :error="createForm.errors.tingkat"
                        />
                        <TextInput
                            v-model="createForm.nama_kelas"
                            name="nama_kelas"
                            label="Nama Kelas"
                            placeholder="Contoh: A"
                            help="Tingkat dipilih terpisah. Nama kelas dapat dipakai kembali pada tingkat lain."
                            maxlength="20"
                            required
                            :error="createForm.errors.nama_kelas"
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
                        <SelectInput
                            v-model="editForm.tingkat"
                            name="tingkat"
                            label="Tingkat"
                            required
                            :options="tingkatOptions"
                            :error="editForm.errors.tingkat"
                        />
                        <TextInput
                            v-model="editForm.nama_kelas"
                            name="nama_kelas"
                            label="Nama Kelas"
                            maxlength="20"
                            required
                            :error="editForm.errors.nama_kelas"
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
                <Card title="Daftar Kelas" icon="bi-building" body-class="p-0">
                    <TableWrapper v-if="kelas.length">
                        <table class="table table-hover app-table mb-0">
                            <thead>
                                <tr>
                                    <th>Tingkat</th>
                                    <th>Nama Kelas</th>
                                    <th>Siswa Aktif</th>
                                    <th>Wali Kelas</th>
                                    <th class="table-action-column">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in kelas" :key="item.id">
                            <td><Badge color="secondary">{{ tingkatLabel(item.tingkat) }}</Badge></td>
                                    <td><strong>{{ item.nama_kelas }}</strong></td>
                                    <td>{{ item.siswa_count ?? 0 }} siswa</td>
                                    <td>{{ item.wali_kelas_count ? 'Sudah ada' : 'Belum ada' }}</td>
                                    <td class="table-action-column kelas-action-column">
                                        <div class="kelas-action-group">
                                            <Button :href="item.siswa_url" color="outline-secondary" icon="bi-people" aria-label="Lihat siswa" class="kelas-student-action">Siswa</Button>
                                            <IconButton
                                                icon="bi-pencil"
                                                :label="`Edit kelas ${item.nama_kelas}`"
                                                color="outline-primary"
                                                @click="startEdit(item)"
                                            />
                                            <IconButton
                                                icon="bi-trash"
                                                :label="`Hapus kelas ${item.nama_kelas}`"
                                                color="outline-danger"
                                                @click="destroy(item)"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </TableWrapper>
                    <EmptyState v-else title="Belum ada kelas" icon="bi-building" />
                </Card>
            </div>
        </div>
    </AppShell>
</template>

<style scoped>
.kelas-action-column {
    width: 166px;
    min-width: 166px;
}

.kelas-action-group {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 34px 34px;
    align-items: center;
    gap: 0.35rem;
}

.kelas-action-group :deep(.kelas-student-action) {
    width: 100%;
    min-width: 0;
    white-space: nowrap;
}

.kelas-action-group :deep(.btn-icon) {
    width: 34px;
    height: 34px;
    padding: 0;
}

@media (max-width: 767.98px) {
    .kelas-action-column {
        width: 156px;
        min-width: 156px;
    }
}
</style>
