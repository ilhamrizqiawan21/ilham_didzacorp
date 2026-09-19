<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Card, EmptyState } from '../UI';
import type { ChatRoomSummary } from '../../types';

interface Props { rooms?: ChatRoomSummary[]; emptyMessage?: string }
const props = withDefaults(defineProps<Props>(), { rooms: () => [], emptyMessage: 'Belum ada data.' });

const selectedRoomUrl = ref('');

function roomLabel(room: ChatRoomSummary): string {
    return [room.title, room.subtitle].filter(Boolean).join(' - ');
}

function openRoom(): void {
    if (!selectedRoomUrl.value) {
        return;
    }

    router.visit(selectedRoomUrl.value);
}
</script>

<template>
    <EmptyState
        v-if="rooms.length === 0"
        :title="emptyMessage"
        icon="bi-chat-dots"
    />

    <Card v-else title="Pilih Kelas" icon="bi-book">
        <div class="chat-room-picker">
            <label for="chat-room-select" class="form-label">Kelas Chat</label>
            <select
                id="chat-room-select"
                v-model="selectedRoomUrl"
                class="form-select"
                @change="openRoom"
            >
                <option value="" disabled>-- Pilih Kelas --</option>
                <option v-for="room in props.rooms" :key="room.id" :value="room.url">
                    {{ roomLabel(room) }}
                </option>
            </select>
        </div>
    </Card>
</template>

<style scoped>
.chat-room-picker {
    max-width: 560px;
}
</style>
