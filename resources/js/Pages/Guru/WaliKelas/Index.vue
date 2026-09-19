<script setup lang="ts">
import type { PropType } from 'vue';

import { Head } from '@inertiajs/vue3';
import PageHeader from '../../../Components/AppShell/PageHeader.vue';
import AppShell from '../../../Layouts/AppShell.vue';
import { Badge, Button, Card, EmptyState } from '../../../Components/UI';

defineProps({
    waliKelas: { type: Object as PropType<{ id: number; kelas: string; tahun_ajaran: string; absensi_count: number; pertemuan_count: number; penanganan_aktif_count: number; absensi_url: string; pertemuan_url: string; penanganan_url: string } | null>, default: null },
});
</script>

<template>
    <Head title="Wali Kelas" />

    <AppShell title="Wali Kelas">
        <PageHeader title="Wali Kelas" icon="bi-person-badge-fill" />

        <Card v-if="!waliKelas">
            <EmptyState
                title="Belum ada penugasan wali kelas"
                message="Anda belum ditugaskan sebagai wali kelas pada tahun ajaran aktif."
                icon="bi-person-badge"
            />
        </Card>

        <div v-else class="row gy-4">
            <div class="col-12 col-xl-10">
                <Card :title="`Kelas Wali ${waliKelas.kelas}`" icon="bi-building">
                    <div class="d-flex flex-column gap-3">
                        <div>
                            <div class="text-muted small">Tahun Ajaran</div>
                            <div class="fw-semibold">{{ waliKelas.tahun_ajaran }}</div>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <Badge color="primary">{{ waliKelas.absensi_count }} absensi</Badge>
                            <Badge color="info text-dark">{{ waliKelas.pertemuan_count }} pertemuan</Badge>
                            <Badge color="warning text-dark">{{ waliKelas.penanganan_aktif_count }} penanganan aktif</Badge>
                        </div>

                        <div class="row g-2">
                            <div class="col-sm-6 col-xl-3 d-grid">
                                <Button :href="`/guru/wali-kelas/${waliKelas.id}/biodata`" color="outline-primary" icon="bi-person-vcard">Biodata Siswa</Button>
                            </div>
                            <div class="col-sm-6 col-xl-3 d-grid">
                                <Button :href="waliKelas.absensi_url" color="outline-success" icon="bi-clipboard-check">Absensi Harian</Button>
                            </div>
                            <div class="col-sm-6 col-xl-3 d-grid">
                                <Button :href="waliKelas.pertemuan_url" color="outline-secondary" icon="bi-calendar-event">Pertemuan</Button>
                            </div>
                            <div class="col-sm-6 col-xl-3 d-grid">
                                <Button :href="waliKelas.penanganan_url" color="outline-danger" icon="bi-heart-pulse">Penanganan Siswa</Button>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>
        </div>
    </AppShell>
</template>
