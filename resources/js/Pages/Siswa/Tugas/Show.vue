<script setup lang="ts">
import type { PropType } from 'vue';
import type { SubmissionFile } from '../../../types/tasks';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PageHeader from '../../../Components/AppShell/PageHeader.vue';
import { FileInput, TextareaInput } from '../../../Components/Form';
import AppShell from '../../../Layouts/AppShell.vue';
import { Badge, Button, Card } from '../../../Components/UI';

const props = defineProps({
    tugas: { type: Object as PropType<{ judul: string; kategori_nilai: string; mata_pelajaran: string; is_late: boolean; batas_waktu: string; deskripsi: string | null; store_url: string; back_url: string; guru: string; kelas: string }>, required: true },
    pengumpulan: { type: Object as PropType<{ status: string; tanggal_kumpul: string | null; nilai: number | string | null; files: SubmissionFile[]; legacy_file_url: string | null; teks_jawaban: string | null; catatan: string | null } | null>, default: null },
    canSubmit: { type: Boolean, default: false },
});

const form = useForm({
    files: [] as File[],
    teks_jawaban: '',
});
const allowedUploadExtensions = ['jpg', 'jpeg', 'pdf'];
const maxUploadSizeInBytes = 5 * 1024 * 1024;
const fileInputKey = ref(0);

const uploadFileError = computed(() => {
    const errors = [
        (form.errors as Record<string, string | undefined>).file_upload,
        form.errors.files,
        ...Object.entries(form.errors)
            .filter(([key]) => key.startsWith('files.'))
            .map(([, message]) => message),
    ].filter((message): message is string => Boolean(message));

    return errors.length > 1 ? errors : errors[0] ?? '';
});

const statusMap: Record<string, { color: string; label: string }> = {
    belum: { color: 'secondary', label: 'Belum' },
    sudah: { color: 'success', label: 'Sudah' },
    terlambat: { color: 'danger', label: 'Terlambat' },
    dinilai: { color: 'primary', label: 'Dinilai' },
    perlu_perbaikan: { color: 'warning', label: 'Perlu Perbaikan' },
};

function statusColor(status: string) {
    return statusMap[status]?.color ?? 'secondary';
}

function statusLabel(status: string) {
    return statusMap[status]?.label ?? (status ? status.replace(/\b\w/g, (char) => char.toUpperCase()) : '-');
}

function selectedFiles(files: File[] | File | null) {
    return Array.isArray(files) ? files.filter(Boolean) : (files ? [files] : []);
}

function clearUploadErrors() {
    delete (form.errors as Record<string, string | undefined>).file_upload;
    form.clearErrors('files');

    Object.keys(form.errors)
        .filter((key) => key.startsWith('files.'))
        .forEach((key) => form.clearErrors(key as `files.${number}`));
}

function validateSelectedFiles(files: File[]) {
    if (files.length > 5) {
        return 'Maksimal 5 file untuk satu pengumpulan tugas.';
    }

    const invalidFile = files.find((file) => {
        const extension = file.name.split('.').pop()?.toLowerCase();

        return !allowedUploadExtensions.includes(extension ?? '');
    });

    if (invalidFile) {
        return `Format file "${invalidFile.name}" tidak didukung. Gunakan JPG, JPEG, atau PDF.`;
    }

    const oversizedFile = files.find((file) => file.size > maxUploadSizeInBytes);

    return oversizedFile ? `Ukuran file "${oversizedFile.name}" melebihi 5MB.` : '';
}

function submit() {
    if (form.processing) {
        return;
    }

    const files = selectedFiles(form.files);
    const uploadError = validateSelectedFiles(files);

    if (uploadError) {
        form.setError('files', uploadError);
        return;
    }

    form.transform((data) => ({
        teks_jawaban: data.teks_jawaban,
        ...(files.length ? { files } : {}),
    }));

    form.post(props.tugas.store_url, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            form.reset('files');
            fileInputKey.value += 1;
        },
        onFinish: () => form.transform((data) => data),
    });
}
</script>

<template>
    <Head title="Detail Tugas" />

    <AppShell title="Detail Tugas">
        <PageHeader title="Detail Tugas" icon="bi-journal-fill" />

        <div class="row">
            <div class="col-md-8">
                <Card :title="tugas.judul" icon="bi-journal-fill" class="mb-3">
                    <template #actions>
                        <Badge color="secondary">{{ tugas.kategori_nilai }}</Badge>
                    </template>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <small class="text-muted">Mata Pelajaran</small>
                            <p class="fw-bold mb-0">{{ tugas.mata_pelajaran }}</p>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">Batas Waktu</small>
                            <p class="fw-bold mb-0" :class="{ 'text-danger': tugas.is_late }">
                                {{ tugas.batas_waktu }}
                                <Badge v-if="tugas.is_late" color="danger" class="ms-1">Terlambat</Badge>
                            </p>
                        </div>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted">Deskripsi</small>
                        <p class="mb-0">{{ tugas.deskripsi }}</p>
                    </div>
                </Card>

                <Card v-if="pengumpulan" title="Riwayat Pengumpulan" icon="bi-clock-history" class="mb-3">
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <small class="text-muted">Status</small>
                            <p class="fw-bold"><Badge :color="statusColor(pengumpulan.status)">{{ statusLabel(pengumpulan.status) }}</Badge></p>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Tanggal Kumpul</small>
                            <p class="fw-bold">{{ pengumpulan.tanggal_kumpul }}</p>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Nilai</small>
                            <p class="fw-bold" :class="pengumpulan.nilai ? 'text-success' : 'text-muted'">
                                {{ pengumpulan.nilai ?? 'Belum dinilai' }}
                            </p>
                        </div>
                    </div>

                    <div v-if="pengumpulan.files.length" class="mb-2">
                        <small class="text-muted">File yang diupload:</small>
                        <ul class="list-unstyled mb-0">
                            <li v-for="file in pengumpulan.files" :key="file.id">
                                <a :href="file.url" target="_blank" rel="noopener noreferrer" class="text-decoration-none">
                                    <i class="bi bi-paperclip me-1" aria-hidden="true"></i> {{ file.name }}
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div v-else-if="pengumpulan.legacy_file_url" class="mb-2">
                        <small class="text-muted">File yang diupload:</small><br>
                        <a :href="pengumpulan.legacy_file_url" target="_blank" rel="noopener noreferrer" class="text-decoration-none">
                            <i class="bi bi-paperclip me-1" aria-hidden="true"></i> Download File
                        </a>
                    </div>

                    <div v-if="pengumpulan.teks_jawaban" class="mb-2">
                        <small class="text-muted">Jawaban Teks:</small>
                        <p class="mb-0 p-2 bg-light rounded">{{ pengumpulan.teks_jawaban }}</p>
                    </div>

                    <div v-if="pengumpulan.catatan" class="mb-0">
                        <small class="text-muted">Catatan Guru:</small>
                        <p class="mb-0 p-2 bg-warning-subtle rounded">{{ pengumpulan.catatan }}</p>
                    </div>
                </Card>

                <Card v-if="canSubmit" title="Kumpulkan Tugas" icon="bi-upload">
                    <form @submit.prevent="submit">
                        <FileInput
                            :key="fileInputKey"
                            v-model="form.files"
                            name="files[]"
                            label="Upload File"
                            accept=".jpg,.jpeg,.pdf,image/jpeg,application/pdf"
                            accept-label="JPG, JPEG, PDF"
                            max-size="5MB"
                            multiple
                            help="Opsional jika jawaban dikirim lewat teks."
                            :error="uploadFileError"
                            @update:model-value="clearUploadErrors"
                        />
                        <TextareaInput
                            v-model="form.teks_jawaban"
                            name="teks_jawaban"
                            label="Jawaban Teks"
                            :rows="4"
                            placeholder="Tulis jawaban di sini jika tidak upload file..."
                            help="Opsional jika jawaban dikirim lewat file."
                            :error="form.errors.teks_jawaban"
                        />
                        <div class="d-flex justify-content-end gap-2">
                            <a :href="tugas.back_url" class="btn btn-secondary">Kembali</a>
                            <Button type="submit" color="primary" size="" icon="bi-send" :disabled="form.processing">
                                {{ form.processing ? 'Mengumpulkan...' : 'Kumpulkan' }}
                            </Button>
                        </div>
                    </form>
                </Card>

                <div v-else class="d-flex justify-content-between mt-3">
                    <a :href="tugas.back_url" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Kembali
                    </a>
                </div>
            </div>

            <div class="col-md-4">
                <Card title="Info" icon="bi-info-circle" class="mb-3">
                    <small class="text-muted d-block">Guru Pengampu</small>
                    <p class="fw-bold">{{ tugas.guru }}</p>

                    <small class="text-muted d-block">Kelas</small>
                    <p class="fw-bold">{{ tugas.kelas || '-' }}</p>

                    <small class="text-muted d-block">Kategori Nilai</small>
                    <p class="fw-bold">{{ tugas.kategori_nilai }}</p>

                    <hr>

                    <template v-if="pengumpulan">
                        <small class="text-muted d-block">Status Pengumpulan</small>
                        <p class="fw-bold"><Badge :color="statusColor(pengumpulan.status)">{{ statusLabel(pengumpulan.status) }}</Badge></p>
                    </template>
                    <div v-else class="alert alert-info py-2 mb-0">
                        <i class="bi bi-info-circle me-1" aria-hidden="true"></i> Anda belum mengumpulkan tugas ini.
                    </div>
                </Card>
            </div>
        </div>
    </AppShell>
</template>
