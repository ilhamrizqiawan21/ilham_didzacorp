<script setup lang="ts">
import type { PropType } from 'vue';

import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { FileInput, TextareaInput, TextInput } from '../../../Components/Form';
import AppShell from '../../../Layouts/AppShell.vue';
import { Button, Card, DashboardHero, EmptyState, IconButton, QuickActionBar, TableWrapper } from '../../../Components/UI';

const props = defineProps({
    kelasMapel: { type: Object as PropType<{ id: number; kelas: string; mata_pelajaran: string; workspace_url: string; store_url: string }>, required: true },
    materi: { type: Array as PropType<{ id: number; judul: string; deskripsi_ringkas: string | null; tanggal: string; download_url: string | null; delete_url: string }[]>, default: () => [] },
});

const fileInputKey = ref(0);
const form = useForm({
    judul: '',
    deskripsi: '',
    file_materi: null as File | null,
});

const courseTabs = [
    { label: 'Ringkasan', href: props.kelasMapel.workspace_url, icon: 'bi-grid-1x2' },
    { label: 'Materi', href: '#', icon: 'bi-file-earmark-text', active: true },
    { label: 'Tugas', href: `/guru/tugas/${props.kelasMapel.id}/list`, icon: 'bi-journal-check' },
    { label: 'Nilai', href: `/guru/nilai/${props.kelasMapel.id}/input`, icon: 'bi-bar-chart' },
    { label: 'Absensi', href: `/guru/absensi/${props.kelasMapel.id}/create`, icon: 'bi-clipboard-check' },
    { label: 'Chat', href: `/guru/chat/${props.kelasMapel.id}`, icon: 'bi-chat-dots' },
];

function submit() {
    if (form.processing) {
        return;
    }

    form.post(props.kelasMapel.store_url, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            fileInputKey.value += 1;
        },
    });
}

async function destroy(item: { delete_url: string }) {
    const confirmed = await window.confirmDialog?.('Hapus materi ini?', {
        title: 'Hapus Materi',
        confirmText: 'Ya, hapus',
        danger: true,
    });

    if (!confirmed) {
        return;
    }

    router.delete(item.delete_url, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="`Materi: ${kelasMapel.mata_pelajaran} - ${kelasMapel.kelas}`" />

    <AppShell title="Materi">
        <DashboardHero
            eyebrow="Workspace Kelas/Mapel"
            :title="kelasMapel.mata_pelajaran"
            :subtitle="`${kelasMapel.kelas} - Kelola materi pembelajaran untuk kelas ini.`"
            icon="bi-file-earmark-text-fill"
            tone="teacher"
        >
            <template #actions>
                <QuickActionBar :actions="[{ label: 'Ringkasan', href: kelasMapel.workspace_url, icon: 'bi-grid-1x2', color: 'light' }]" />
            </template>
        </DashboardHero>

        <nav class="workspace-tabs" aria-label="Navigasi kelas dan mata pelajaran">
            <a v-for="tab in courseTabs" :key="tab.label" :href="tab.href" class="workspace-tab" :class="{ 'is-active': tab.active }">
                <i class="bi" :class="tab.icon" aria-hidden="true"></i>{{ tab.label }}
            </a>
        </nav>

        <div class="row">
            <div class="col-md-5 mb-4">
                <Card title="Upload Materi" icon="bi-cloud-upload-fill">
                    <form @submit.prevent="submit">
                        <TextInput
                            v-model="form.judul"
                            name="judul"
                            label="Judul"
                            required
                            :error="form.errors.judul"
                        />
                        <TextareaInput
                            v-model="form.deskripsi"
                            name="deskripsi"
                            label="Deskripsi"
                            :rows="3"
                            :error="form.errors.deskripsi"
                        />
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
                        <Button
                            type="submit"
                            color="success"
                            size=""
                            icon="bi-upload"
                            class="w-100 mt-2"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? 'Mengupload...' : 'Upload' }}
                        </Button>
                    </form>
                </Card>
            </div>

            <div class="col-md-7 mb-4">
                <Card title="Daftar Materi" icon="bi-list-ul" body-class="p-0">
                    <template #default>
                        <TableWrapper v-if="materi.length">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Judul</th>
                                        <th>Deskripsi</th>
                                        <th>Tanggal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in materi" :key="item.id">
                                        <td><strong>{{ item.judul }}</strong></td>
                                        <td style="font-size:0.82rem;">{{ item.deskripsi_ringkas }}</td>
                                        <td style="white-space:nowrap;font-size:0.82rem;">{{ item.tanggal }}</td>
                                        <td>
                                            <div class="d-inline-flex align-items-center gap-1 flex-wrap">
                                                <a
                                                    v-if="item.download_url"
                                                    :href="item.download_url"
                                                    class="btn btn-sm btn-outline-primary btn-icon"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    :title="`Download ${item.judul}`"
                                                    :aria-label="`Download ${item.judul}`"
                                                >
                                                    <i class="bi bi-download" aria-hidden="true"></i>
                                                </a>
                                                <IconButton
                                                    icon="bi-trash"
                                                    :label="`Hapus ${item.judul}`"
                                                    color="outline-danger"
                                                    @click="destroy(item)"
                                                />
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </TableWrapper>
                        <EmptyState v-else title="Belum ada materi" icon="bi-file-earmark-text" />
                    </template>
                </Card>
            </div>
        </div>
    </AppShell>
</template>
