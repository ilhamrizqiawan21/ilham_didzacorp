<script setup lang="ts">
import type { PropType } from 'vue';

import { Head } from '@inertiajs/vue3';
import PageHeader from '../../../Components/AppShell/PageHeader.vue';
import AppShell from '../../../Layouts/AppShell.vue';
import { Card, EmptyState } from '../../../Components/UI';

defineProps({
    kelasMapel: { type: Array as PropType<{ id: number; href: string; initials: string; mata_pelajaran: string; guru: string }[]>, default: () => [] },
});
</script>

<template>
    <Head title="Materi Saya" />

    <AppShell title="Materi Saya">
        <PageHeader title="Materi Saya" icon="bi-file-earmark-text-fill" />

        <Card v-if="kelasMapel.length" title="Pilih Mata Pelajaran" icon="bi-book">
            <div class="row">
                <div v-for="item in kelasMapel" :key="item.id" class="col-md-4 mb-3">
                    <a :href="item.href" class="text-decoration-none">
                        <div class="card border h-100 hover-shadow" style="transition:all 0.2s;">
                            <div class="card-body text-center">
                                <div style="font-size:2rem;color:var(--primary-500);">{{ item.initials }}</div>
                                <strong>{{ item.mata_pelajaran }}</strong>
                                <div class="text-muted" style="font-size:0.8rem;">{{ item.guru }}</div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </Card>

        <Card v-else>
            <EmptyState title="Belum ada mata pelajaran." icon="bi-book" />
        </Card>
    </AppShell>
</template>
