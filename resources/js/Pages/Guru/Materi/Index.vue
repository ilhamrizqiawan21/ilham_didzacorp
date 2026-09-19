<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PageHeader from '../../../Components/AppShell/PageHeader.vue';
import { FileInput, TextareaInput, TextInput } from '../../../Components/Form';
import AppShell from '../../../Layouts/AppShell.vue';
import { Button, Card, EmptyState, IconButton, TableWrapper } from '../../../Components/UI';

interface KelasMapelOption { id: number; label: string }
interface MateriItem { id: number; judul: string; deskripsi_ringkas?: string | null; kelas: string; mata_pelajaran: string; tanggal: string; download_url?: string | null; delete_url?: string | null }
interface MateriForm { kelas_mapel_ids: number[]; judul: string; deskripsi: string; file_materi: File | null }
interface Props { kelasMapel?: KelasMapelOption[]; materi?: MateriItem[]; storeUrl: string }

const props = withDefaults(defineProps<Props>(), { kelasMapel: () => [], materi: () => [] });

const fileInputKey = ref(0);
const form = useForm<MateriForm>({
    kelas_mapel_ids: [],
    judul: '',
    deskripsi: '',
    file_materi: null,
});
const uploadPercentage = computed(() => form.progress?.percentage ?? 0);

function submit(): void {
    if (form.processing) {
        return;
    }

    form.post(props.storeUrl, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            fileInputKey.value += 1;
        },
    });
}

async function destroy(item: MateriItem): Promise<void> {
    const confirmed = await window.confirmDialog?.('Hapus materi ini?', {
        title: 'Hapus Materi',
        confirmText: 'Ya, hapus',
        danger: true,
    });

    if (!confirmed || !item.delete_url) return;

    router.delete(item.delete_url, { preserveScroll: true });
}
</script>

<template>
    <Head title="Materi" />

    <AppShell title="Materi">
        <PageHeader
            title="Materi Pembelajaran"
            subtitle="Upload materi dan pilih kelas tujuan sesuai penugasan."
            icon="bi-file-earmark-text-fill"
        />

        <div v-if="kelasMapel.length" class="row">
            <div class="col-md-5 mb-4">
                <Card title="Upload Materi" icon="bi-cloud-upload-fill">
                    <form @submit.prevent="submit">
                        <TextInput v-model="form.judul" name="judul" label="Judul" required :error="form.errors.judul" />
                        <TextareaInput v-model="form.deskripsi" name="deskripsi" label="Deskripsi" :rows="3" :error="form.errors.deskripsi" />

                        <div class="mb-3">
                            <label class="form-label">Kelas Tujuan <span class="text-danger">*</span></label>
                            <div class="assignment-list">
                                <label v-for="item in kelasMapel" :key="item.id" class="assignment-option">
                                    <input
                                        v-model="form.kelas_mapel_ids"
                                        class="form-check-input"
                                        type="checkbox"
                                        :value="item.id"
                                    >
                                    <span>{{ item.label }}</span>
                                </label>
                            </div>
                            <div v-if="form.errors.kelas_mapel_ids" class="text-danger small mt-1">
                                {{ form.errors.kelas_mapel_ids }}
                            </div>
                        </div>

                        <FileInput
                            :key="fileInputKey"
                            v-model="form.file_materi"
                            name="file_materi"
                            label="File Materi"
                            accept=".jpg,.jpeg,.pdf,image/jpeg,application/pdf"
                            accept-label="JPG, JPEG, PDF"
                            max-size="5MB"
                            required
                            :error="form.errors.file_materi"
                        />

                        <Button type="submit" color="success" size="" icon="bi-upload" class="w-100" :disabled="form.processing">
                            {{ form.processing ? 'Mengupload...' : 'Upload Materi' }}
                        </Button>
                        <div v-if="form.processing && form.progress" class="progress mt-2" role="progressbar" :aria-valuenow="uploadPercentage" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" :style="{ width: `${uploadPercentage}%` }">{{ uploadPercentage }}%</div>
                        </div>
                    </form>
                </Card>
            </div>

            <div class="col-md-7 mb-4">
                <Card title="Daftar Materi" icon="bi-list-ul" body-class="p-0">
                    <TableWrapper v-if="materi.length">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Judul</th>
                                    <th>Kelas</th>
                                    <th>Mapel</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in materi" :key="item.id">
                                    <td>
                                        <strong>{{ item.judul }}</strong>
                                        <div v-if="item.deskripsi_ringkas" class="text-muted small">{{ item.deskripsi_ringkas }}</div>
                                    </td>
                                    <td>{{ item.kelas }}</td>
                                    <td>{{ item.mata_pelajaran }}</td>
                                    <td class="text-nowrap small">{{ item.tanggal }}</td>
                                    <td>
                                        <div class="d-inline-flex align-items-center gap-1">
                                            <a v-if="item.download_url" :href="item.download_url" class="btn btn-sm btn-outline-primary btn-icon" target="_blank" rel="noopener noreferrer" :title="`Download ${item.judul}`">
                                                <i class="bi bi-download" aria-hidden="true"></i>
                                            </a>
                                            <IconButton icon="bi-trash" :label="`Hapus ${item.judul}`" color="outline-danger" @click="destroy(item)" />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </TableWrapper>
                    <EmptyState v-else title="Belum ada materi" icon="bi-file-earmark-text" />
                </Card>
            </div>
        </div>

        <Card v-else>
            <EmptyState title="Anda belum memiliki penugasan mengajar semester ini" icon="bi-book" />
        </Card>
    </AppShell>
</template>

<style scoped>
.assignment-list {
    display: grid;
    gap: 8px;
}

.assignment-option {
    display: flex;
    gap: 8px;
    align-items: flex-start;
    padding: 9px 10px;
    border: 1px solid var(--bs-border-color);
    border-radius: 8px;
    cursor: pointer;
}
</style>
