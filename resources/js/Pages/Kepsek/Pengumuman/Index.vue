<script setup lang="ts">
import type { PropType } from 'vue';
import type { Announcement } from '../../../types/announcements';
import type { PaginationLink } from '../../../types/pagination';
import { Head, Link } from '@inertiajs/vue3';
import AppShell from '../../../Layouts/AppShell.vue';
import PageHeader from '../../../Components/AppShell/PageHeader.vue';
import { EmptyState } from '../../../Components/UI';

const props = defineProps({
    pengumuman: { type: Object as PropType<{ data: Announcement[]; links?: PaginationLink[] }>, default: () => ({ data: [] }) },
});
</script>

<template>
    <Head title="Pengumuman" />

    <AppShell title="Pengumuman">
        <PageHeader
            title="Pengumuman"
            icon="bi-megaphone"
            subtitle="Pantau informasi resmi sekolah tanpa mengubah atau mengelola pengumuman."
        />

        <div v-if="!pengumuman.data?.length" class="card border-0 shadow-sm">
            <div class="card-body py-5">
                <EmptyState title="Belum ada pengumuman yang dapat dipantau." icon="bi-megaphone" />
            </div>
        </div>

        <div v-else class="d-grid gap-3">
            <article v-for="item in pengumuman.data" :key="item.id" class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                        <div>
                            <h2 class="h5 mb-1">{{ item.judul }}</h2>
                            <div class="small text-muted">
                                {{ item.creator?.nama_lengkap || '-' }}
                                ·
                                {{ new Date(item.created_at).toLocaleDateString('id-ID') }}
                            </div>
                        </div>
                        <span class="badge bg-light text-dark">{{ item.target }}</span>
                    </div>

                    <p class="mt-3 mb-3 text-secondary" style="white-space: pre-line">{{ item.isi }}</p>

                    <Link
                        :href="`/kepsek/pengumuman/${item.id}`"
                        class="btn btn-sm btn-outline-primary"
                    >
                        <i class="bi bi-eye me-1" aria-hidden="true"></i> Lihat Detail
                    </Link>
                </div>
            </article>
        </div>

        <div v-if="(pengumuman.links?.length ?? 0) > 3" class="d-flex flex-wrap gap-1 mt-4">
            <Link
                v-for="(link, index) in pengumuman.links"
                :key="index"
                :href="link.url || '#'"
                class="btn btn-sm"
                :class="link.active ? 'btn-primary' : 'btn-outline-secondary'"
                :aria-disabled="!link.url"
            >
                <span v-html="link.label"></span>
            </Link>
        </div>
    </AppShell>
</template>
