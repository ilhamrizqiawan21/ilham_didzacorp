<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '../../../Components/AppShell/PageHeader.vue';
import ChatRoom from '../../../Components/Chat/ChatRoom.vue';
import AppShell from '../../../Layouts/AppShell.vue';
import { Button } from '../../../Components/UI';
import type { ChatMessage, ChatRoomPayload } from '../../../types';

interface Room extends ChatRoomPayload { title: string; subtitle?: string }
interface Props { room: Room; messages?: ChatMessage[]; sendUrl: string; backUrl: string; emptyMessage?: string }
withDefaults(defineProps<Props>(), { messages: () => [], emptyMessage: 'Belum ada pesan. Mulai percakapan!' });
</script>

<template>
    <Head :title="`Chat: ${room.title}`" />

    <AppShell :title="`Chat: ${room.title}`">
        <PageHeader
            :title="room.title"
            :subtitle="room.subtitle"
            icon="bi-chat-dots-fill"
        >
            <template #actions>
                <Button :href="backUrl" color="outline-secondary" icon="bi-arrow-left">Kembali</Button>
            </template>
        </PageHeader>

        <ChatRoom
            :room="room"
            :messages="messages"
            :send-url="sendUrl"
            :empty-message="emptyMessage"
        />
    </AppShell>
</template>
