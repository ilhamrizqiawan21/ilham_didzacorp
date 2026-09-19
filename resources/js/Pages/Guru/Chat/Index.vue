<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '../../../Components/AppShell/PageHeader.vue';
import RoomGrid from '../../../Components/Chat/RoomGrid.vue';
import AppShell from '../../../Layouts/AppShell.vue';
import type { ChatRoomSummary } from '../../../types';

interface Props { rooms?: ChatRoomSummary[]; emptyMessage?: string }
withDefaults(defineProps<Props>(), { rooms: () => [], emptyMessage: 'Anda belum memiliki penugasan.' });
</script>

<template>
    <Head title="Chat Kelas" />

    <AppShell title="Chat Kelas">
        <PageHeader title="Chat Kelas" icon="bi-chat-dots-fill" />
        <RoomGrid :rooms="rooms" :empty-message="emptyMessage" />
    </AppShell>
</template>
