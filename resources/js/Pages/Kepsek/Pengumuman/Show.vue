<script setup lang="ts">
import type { PropType } from 'vue';
import type { Announcement } from '../../../types/announcements';
import { Head, Link } from '@inertiajs/vue3';
import AppShell from '../../../Layouts/AppShell.vue';
import PageHeader from '../../../Components/AppShell/PageHeader.vue';

defineProps({
    pengumuman: { type: Object as PropType<Announcement>, required: true },
    targetKelasLabels: { type: Array as PropType<string[]>, default: () => [] },
    backUrl: { type: String, default: '/kepsek/pengumuman' },
});

function formatFileSize(bytes: number | null) {
    if (!bytes) return '';
    const kb = bytes / 1024;
    return kb >= 1024 ? `${(kb / 1024).toFixed(1)} MB` : `${Math.ceil(kb)} KB`;
}
</script>

<template>
    <Head title="Detail Pengumuman" />
    <AppShell title="Detail Pengumuman">
        <PageHeader title="Detail Pengumuman" icon="bi-megaphone" />
        <div class="mb-3"><Link :href="backUrl" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Kembali ke Pengumuman</Link></div>
        <article class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                    <div><h1 class="h3 mb-1">{{ pengumuman.judul }}</h1><div class="text-muted small">{{ pengumuman.creator?.nama_lengkap || '-' }} · {{ new Date(pengumuman.created_at).toLocaleString('id-ID') }}</div></div>
                    <span class="badge bg-success">{{ pengumuman.target }}</span>
                </div>
                <hr>
                <div class="text-secondary" style="white-space: pre-line">{{ pengumuman.isi }}</div>
                <div v-if="pengumuman.is_public_login" class="mt-4">
                    <span class="badge bg-success-subtle text-success-emphasis">Tampil di halaman login</span>
                </div>
                <div v-if="pengumuman.attachment" class="mt-3">
                    <a v-if="pengumuman.attachment.url" :href="pengumuman.attachment.url" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener noreferrer">
                        <i class="bi bi-paperclip me-1" aria-hidden="true"></i>{{ pengumuman.attachment.name }}
                        <span v-if="formatFileSize(pengumuman.attachment.size)" class="text-muted">({{ formatFileSize(pengumuman.attachment.size) }})</span>
                    </a>
                    <span v-else class="badge bg-secondary-subtle text-secondary-emphasis">
                        <i class="bi bi-paperclip me-1" aria-hidden="true"></i>{{ pengumuman.attachment.name }}
                    </span>
                </div>
                <div v-if="targetKelasLabels.length" class="mt-4"><strong>Kelas tujuan:</strong> {{ targetKelasLabels.join(', ') }}</div>
            </div>
        </article>
    </AppShell>
</template>
