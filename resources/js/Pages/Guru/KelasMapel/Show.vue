<script setup lang="ts">
import type { PropType } from 'vue';
import type { MetricItem, QueueItem } from '../../../types/ui';
import { Head, Link } from '@inertiajs/vue3';
import AppShell from '../../../Layouts/AppShell.vue';
import { AgendaPanel, DashboardHero, MetricStrip, QuickActionBar } from '../../../Components/UI';

const props = defineProps({
    course: { type: Object as PropType<{ title: string; kelas: string; semester: string; tahun_ajaran: string; back_url: string }>, required: true },
    tabs: { type: Array as PropType<{ label: string; href: string; icon: string; active?: boolean }[]>, default: () => [] },
    metrics: { type: Array as PropType<MetricItem[]>, default: () => [] },
    tasks: { type: Array as PropType<QueueItem[]>, default: () => [] },
    attendance: { type: Object as PropType<{ href: string; recorded_today: number; total_students: number }>, required: true },
    latestMessage: { type: Object as PropType<{ href: string; author: string; message: string } | null>, default: null },
});

const quickActions = [
    { label: 'Tambah Materi', href: props.tabs.find((tab) => tab.label === 'Materi')?.href, icon: 'bi-file-earmark-plus', color: 'primary' },
    { label: 'Buat Tugas', href: props.tabs.find((tab) => tab.label === 'Tugas')?.href, icon: 'bi-journal-plus', color: 'light' },
    { label: 'Isi Absensi', href: props.attendance.href, icon: 'bi-clipboard-check', color: 'light' },
];
</script>

<template>
    <Head :title="`${course.title} - ${course.kelas}`" />

    <AppShell :title="course.title">
        <DashboardHero
            eyebrow="Workspace Kelas/Mapel"
            :title="course.title"
            :subtitle="`${course.kelas} - Semester ${course.semester} - ${course.tahun_ajaran}`"
            icon="bi-book"
            tone="teacher"
        >
            <template #actions>
                <QuickActionBar :actions="quickActions" />
            </template>
        </DashboardHero>

        <nav class="workspace-tabs" aria-label="Navigasi kelas dan mata pelajaran">
            <Link
                v-for="tab in tabs"
                :key="tab.label"
                :href="tab.href"
                class="workspace-tab"
                :class="{ 'is-active': tab.label === 'Ringkasan' }"
            >
                <i class="bi" :class="tab.icon" aria-hidden="true"></i>
                {{ tab.label }}
            </Link>
        </nav>

        <MetricStrip :items="metrics" />

        <div class="dashboard-grid dashboard-grid-admin">
            <AgendaPanel
                title="Agenda Tugas"
                :items="tasks"
                empty-title="Belum ada tugas"
            />

            <section class="workspace-panel">
                <header class="workspace-panel-header">
                    <span class="workspace-panel-title">
                        <i class="bi bi-activity" aria-hidden="true"></i>
                        Aktivitas Kelas
                    </span>
                </header>
                <div class="workspace-panel-body workspace-summary-list">
                    <Link :href="attendance.href" class="workspace-summary-item">
                        <span class="workspace-summary-icon text-success"><i class="bi bi-clipboard-check" aria-hidden="true"></i></span>
                        <span>
                            <strong>Absensi hari ini</strong>
                            <small>{{ attendance.recorded_today }} dari {{ attendance.total_students }} siswa tercatat</small>
                        </span>
                    </Link>
                    <Link v-if="latestMessage" :href="latestMessage.href" class="workspace-summary-item">
                        <span class="workspace-summary-icon text-primary"><i class="bi bi-chat-dots" aria-hidden="true"></i></span>
                        <span>
                            <strong>{{ latestMessage.author }}</strong>
                            <small>{{ latestMessage.message }}</small>
                        </span>
                    </Link>
                    <Link v-else :href="tabs.find((tab) => tab.label === 'Chat')?.href" class="workspace-summary-item">
                        <span class="workspace-summary-icon text-muted"><i class="bi bi-chat-dots" aria-hidden="true"></i></span>
                        <span>
                            <strong>Chat kelas</strong>
                            <small>Belum ada pesan. Mulai percakapan dengan siswa.</small>
                        </span>
                    </Link>
                </div>
            </section>
        </div>

        <Link :href="course.back_url" class="app-card-action-link">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
            Kembali ke Dashboard
        </Link>
    </AppShell>
</template>
