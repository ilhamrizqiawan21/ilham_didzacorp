<script setup lang="ts">
import type { PropType } from 'vue';
import type { LaravelPaginator } from '../../../types/pagination';
import type { NotificationRow } from '../../../types/notifications';
import { Head, router } from '@inertiajs/vue3';
import PageHeader from '../../../Components/AppShell/PageHeader.vue';
import AppShell from '../../../Layouts/AppShell.vue';
import { Button, Card, EmptyState, IconButton, InfoListItem, Pagination } from '../../../Components/UI';

const props = defineProps({
    notifikasi: { type: Object as PropType<LaravelPaginator<NotificationRow>>, required: true },
    unreadCount: { type: Number, default: 0 },
    markAllReadUrl: { type: String, required: true },
});

const iconMap: Record<string, { icon: string; color: string }> = {
    tugas_baru: { icon: 'bi-journal-plus', color: '#3b82f6' },
    nilai_baru: { icon: 'bi-bar-chart-fill', color: '#22c55e' },
    chat_baru: { icon: 'bi-chat-dots-fill', color: '#8b5cf6' },
    komentar_tugas: { icon: 'bi-chat-square-text-fill', color: '#f59e0b' },
    kumpul_tugas: { icon: 'bi-check-circle-fill', color: '#06b6d4' },
    absensi: { icon: 'bi-clipboard-check-fill', color: '#ef4444' },
    pengumuman_baru: { icon: 'bi-megaphone-fill', color: '#f97316' },
};

function iconFor(type: string) {
    return iconMap[type] ?? { icon: 'bi-bell-fill', color: '#6b7280' };
}

function markRead(item: NotificationRow) {
    router.post(item.mark_read_url, {}, { preserveScroll: true });
}

function markAllRead() {
    router.post(props.markAllReadUrl, {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Notifikasi" />
    <AppShell title="Notifikasi">
        <PageHeader title="Notifikasi" subtitle="Daftar notifikasi Anda" icon="bi-bell-fill">
            <template v-if="unreadCount > 0" #actions>
                <Button type="button" color="outline-primary" icon="bi-check-all" @click="markAllRead">Tandai Semua Sudah Dibaca</Button>
            </template>
        </PageHeader>
        <Card body-class="p-0">
            <div v-if="notifikasi.data.length" class="app-list">
                <InfoListItem v-for="item in notifikasi.data" :key="item.id" :title="item.judul" :message="item.pesan_ringkas" :meta="item.created_at" :icon="iconFor(item.tipe).icon" :accent="iconFor(item.tipe).color" :unread="!item.is_read">
                    <template #action>
                        <IconButton :icon="item.link ? 'bi-arrow-right' : 'bi-check2'" :label="item.link ? `Lihat notifikasi ${item.judul}` : `Tandai dibaca ${item.judul}`" color="outline-primary" @click="markRead(item)" />
                    </template>
                </InfoListItem>
            </div>
            <EmptyState v-else title="Belum ada notifikasi." icon="bi-bell-slash" />
            <template v-if="notifikasi.links?.length" #footer><Pagination :links="notifikasi.links" /></template>
        </Card>
    </AppShell>
</template>
