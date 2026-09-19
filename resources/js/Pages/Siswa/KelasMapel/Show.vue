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
    latestMessage: { type: Object as PropType<{ href: string; author: string; message: string } | null>, default: null },
    onlineClasses: { type: Array as PropType<{ id: number; meeting_url: string; judul: string; tanggal: string; pelajaran_ke: number }[]>, default: () => [] },
});

const quickActions = [
    { label: 'Buka Materi', href: props.tabs.find((tab) => tab.label === 'Materi')?.href, icon: 'bi-file-earmark-text', color: 'primary' },
    { label: 'Chat Kelas', href: props.tabs.find((tab) => tab.label === 'Chat')?.href, icon: 'bi-chat-dots', color: 'light' },
];
</script>

<template>
    <Head :title="`${course.title} - ${course.kelas}`" />

    <AppShell :title="course.title">
        <DashboardHero eyebrow="Ruang Belajar" :title="course.title" :subtitle="`${course.kelas} - Semester ${course.semester} - ${course.tahun_ajaran}`" icon="bi-mortarboard" tone="student">
            <template #actions><QuickActionBar :actions="quickActions" /></template>
        </DashboardHero>

        <nav class="workspace-tabs" aria-label="Navigasi mata pelajaran">
            <Link v-for="tab in tabs" :key="tab.label" :href="tab.href" class="workspace-tab" :class="{ 'is-active': tab.label === 'Ringkasan' }">
                <i class="bi" :class="tab.icon" aria-hidden="true"></i>{{ tab.label }}
            </Link>
        </nav>

        <MetricStrip :items="metrics" />

        <div class="dashboard-grid dashboard-grid-admin">
            <AgendaPanel id="tugas" title="Agenda Tugas" :items="tasks" empty-title="Belum ada tugas" />
            <section class="workspace-panel">
                <header class="workspace-panel-header"><span class="workspace-panel-title"><i class="bi bi-camera-video" aria-hidden="true"></i>Kelas Daring</span></header>
                <div class="workspace-panel-body workspace-summary-list">
                    <a v-for="session in onlineClasses" :key="session.id" :href="session.meeting_url" target="_blank" rel="noopener noreferrer" class="workspace-summary-item">
                        <span class="workspace-summary-icon text-primary"><i class="bi bi-camera-video" aria-hidden="true"></i></span>
                        <span><strong>{{ session.judul }}</strong><small>{{ session.tanggal }} · Pelajaran ke-{{ session.pelajaran_ke }}</small></span>
                    </a>
                    <div v-if="!onlineClasses.length" class="workspace-summary-item">
                        <span class="workspace-summary-icon text-muted"><i class="bi bi-camera-video" aria-hidden="true"></i></span>
                        <span><strong>Belum ada kelas daring</strong><small>Link meeting akan tampil saat guru menjadwalkan sesi.</small></span>
                    </div>
                </div>
            </section>
            <section class="workspace-panel">
                <header class="workspace-panel-header"><span class="workspace-panel-title"><i class="bi bi-chat-dots" aria-hidden="true"></i>Chat Kelas</span></header>
                <div class="workspace-panel-body workspace-summary-list">
                    <Link v-if="latestMessage" :href="latestMessage.href" class="workspace-summary-item">
                        <span class="workspace-summary-icon text-primary"><i class="bi bi-chat-dots" aria-hidden="true"></i></span>
                        <span><strong>{{ latestMessage.author }}</strong><small>{{ latestMessage.message }}</small></span>
                    </Link>
                    <Link v-else :href="tabs.find((tab) => tab.label === 'Chat')?.href" class="workspace-summary-item">
                        <span class="workspace-summary-icon text-muted"><i class="bi bi-chat-dots" aria-hidden="true"></i></span>
                        <span><strong>Belum ada pesan</strong><small>Buka chat untuk mulai percakapan dengan guru dan kelas.</small></span>
                    </Link>
                </div>
            </section>
        </div>

        <Link :href="course.back_url" class="app-card-action-link"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Kembali ke Mata Pelajaran</Link>
    </AppShell>
</template>
