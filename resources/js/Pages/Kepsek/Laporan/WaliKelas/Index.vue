<script setup lang="ts">
import type { PropType } from 'vue';
import type { LaravelPaginator } from '../../../../types/pagination';

import type { HomeroomReportRow } from '../../../../types/reports';
import { Head } from '@inertiajs/vue3';
import PageHeader from '../../../../Components/AppShell/PageHeader.vue';
import AppShell from '../../../../Layouts/AppShell.vue';
import { Badge, Button, Card, EmptyState, Pagination, TableWrapper } from '../../../../Components/UI';

defineProps({
    waliKelas: { type: Object as PropType<LaravelPaginator<HomeroomReportRow>>, required: true },
});
</script>

<template>
    <Head title="Laporan Wali Kelas" />

    <AppShell title="Laporan Wali Kelas">
        <PageHeader title="Laporan Wali Kelas" icon="bi-person-badge-fill" />

        <Card title="Daftar Wali Kelas Aktif" icon="bi-list-ul" body-class="p-0">
            <TableWrapper v-if="waliKelas.data.length">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Kelas</th>
                            <th>Wali Kelas</th>
                            <th>Tahun Ajaran</th>
                            <th>Absensi</th>
                            <th>Pertemuan</th>
                            <th>Penanganan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in waliKelas.data" :key="item.id">
                            <td><strong>{{ item.kelas }}</strong></td>
                            <td>{{ item.guru }}</td>
                            <td>{{ item.tahun_ajaran }}</td>
                            <td>{{ item.absensi_count }}</td>
                            <td>{{ item.pertemuan_count }}</td>
                            <td>
                                <Badge color="warning text-dark">{{ item.penanganan_aktif_count }} aktif</Badge>
                                <span class="text-muted small"> / {{ item.penanganan_siswa_count }} total</span>
                            </td>
                            <td>
                                <Button :href="item.show_url" color="outline-primary" icon="bi-eye">Detail</Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </TableWrapper>

            <EmptyState v-else title="Belum ada wali kelas aktif." icon="bi-person-badge" />

            <template v-if="waliKelas.links?.length" #footer>
                <Pagination :links="waliKelas.links" />
            </template>
        </Card>
    </AppShell>
</template>
