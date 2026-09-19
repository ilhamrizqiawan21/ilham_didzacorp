<script setup lang="ts">
import type { PropType } from 'vue';
import type { StudentAnnouncement } from '../../../types/announcements';
import { Head } from '@inertiajs/vue3';
import PageHeader from '../../../Components/AppShell/PageHeader.vue';
import AppShell from '../../../Layouts/AppShell.vue';
import { Badge, Button, Card } from '../../../Components/UI';

defineProps({
    pengumuman: { type: Object as PropType<StudentAnnouncement>, required: true },
    backUrl: { type: String, required: true },
});
</script>

<template>
    <Head :title="pengumuman.judul" />

    <AppShell title="Detail Pengumuman">
        <PageHeader
            title="Detail Pengumuman"
            icon="bi-megaphone-fill"
        />

        <Card icon="bi-info-circle">
            <template #actions>
                <Button :href="backUrl" color="outline-secondary" icon="bi-arrow-left">
                    Kembali
                </Button>
            </template>

            <article>
                <h2 class="announcement-title">{{ pengumuman.judul }}</h2>
                <div class="announcement-meta">
                    <Badge color="info text-dark">{{ pengumuman.target_label }}</Badge>
                    <span>{{ pengumuman.creator }}</span>
                    <span>{{ pengumuman.created_at }}</span>
                </div>
                <div class="announcement-content">
                    {{ pengumuman.isi }}
                </div>
            </article>
        </Card>
    </AppShell>
</template>

<style scoped>
.announcement-title {
    color: var(--text-strong);
    font-size: 1.35rem;
    font-weight: 800;
    margin-bottom: 0.75rem;
}

.announcement-meta {
    align-items: center;
    color: var(--text-muted);
    display: flex;
    flex-wrap: wrap;
    font-size: 0.85rem;
    gap: 0.65rem;
    margin-bottom: 1.25rem;
}

.announcement-content {
    color: var(--text-body);
    line-height: 1.75;
    white-space: pre-line;
}
</style>
