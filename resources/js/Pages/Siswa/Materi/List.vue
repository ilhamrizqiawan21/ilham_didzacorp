<script setup lang="ts">
import type { PropType } from 'vue';

import { Head } from '@inertiajs/vue3';
import AppShell from '../../../Layouts/AppShell.vue';
import { Card, DashboardHero, EmptyState, QuickActionBar } from '../../../Components/UI';

const props = defineProps({
    kelasMapel: { type: Object as PropType<{ mata_pelajaran: string; guru: string; workspace_url: string; tugas_url: string; chat_url: string }>, required: true },
    materi: { type: Array as PropType<{ id: number; judul: string; deskripsi: string | null; tanggal: string; download_url: string | null }[]>, default: () => [] },
});

const courseTabs = [
    { label: 'Ringkasan', href: props.kelasMapel.workspace_url, icon: 'bi-grid-1x2' },
    { label: 'Materi', href: '#', icon: 'bi-file-earmark-text', active: true },
    { label: 'Tugas', href: props.kelasMapel.tugas_url, icon: 'bi-journal-check' },
    { label: 'Chat', href: props.kelasMapel.chat_url, icon: 'bi-chat-dots' },
];
</script>

<template>
    <Head :title="`Materi: ${kelasMapel.mata_pelajaran}`" />

    <AppShell title="Materi">
        <DashboardHero
            eyebrow="Workspace Kelas/Mapel"
            :title="kelasMapel.mata_pelajaran"
            :subtitle="`Guru: ${kelasMapel.guru} - Materi pembelajaran untuk kelas Anda.`"
            icon="bi-file-earmark-text-fill"
            tone="student"
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

        <div v-if="materi.length" class="row">
            <div v-for="item in materi" :key="item.id" class="col-md-6 mb-4">
                <Card :title="item.judul" class="h-100">
                    <p class="text-muted" style="font-size:0.85rem;">{{ item.deskripsi }}</p>
                    <small class="text-muted">{{ item.tanggal }}</small>

                    <template v-if="item.download_url" #footer>
                        <a :href="item.download_url" class="btn btn-sm btn-success" target="_blank" rel="noopener noreferrer">
                            <i class="bi bi-download me-1" aria-hidden="true"></i> Download
                        </a>
                    </template>
                </Card>
            </div>
        </div>

        <Card v-else>
            <EmptyState title="Belum ada materi." icon="bi-file-earmark-text" />
        </Card>
    </AppShell>
</template>
