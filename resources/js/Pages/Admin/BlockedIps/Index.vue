<script setup lang="ts">
import type { PropType } from 'vue';
import type { LaravelPaginator } from '../../../types/pagination';
interface BlockedIp { id: number; ip_address: string; is_expired: boolean; blocked_until: string | null; reason: string | null; created_at: string | null; unblock_url: string }
import { Head, router } from '@inertiajs/vue3';
import PageHeader from '../../../Components/AppShell/PageHeader.vue';
import AppShell from '../../../Layouts/AppShell.vue';
import { Badge, Button, EmptyState, MetricStrip, Pagination, TableWrapper } from '../../../Components/UI';

const props = defineProps({
    ips: { type: Object as PropType<LaravelPaginator<BlockedIp>>, required: true },
});

const metrics = [
    { label: 'Total Diblokir', value: props.ips.total ?? 0, icon: 'bi-shield-fill-x', tone: 'danger' },
    { label: 'Halaman', value: `${props.ips.current_page ?? 1}/${props.ips.last_page ?? 1}`, icon: 'bi-files', tone: 'info' },
    { label: 'Per Halaman', value: props.ips.per_page ?? 25, icon: 'bi-list-ol', tone: 'primary' },
];

async function unblock(item: BlockedIp) {
    const confirmed = await window.confirmDialog?.(`Unblock IP ${item.ip_address}?`, {
        title: 'Unblock IP',
        confirmText: 'Ya, unblock',
    });

    if (!confirmed) {
        return;
    }

    router.delete(item.unblock_url, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="IP Diblokir" />

    <AppShell title="IP Diblokir">
        <PageHeader
            title="IP Diblokir"
            subtitle="Pantau dan buka blokir IP yang masuk daftar pembatasan akses."
            icon="bi-shield-fill-x"
        />

        <MetricStrip :items="metrics" />

        <section class="workspace-panel">
            <header class="workspace-panel-header">
                <span class="workspace-panel-title">
                    <i class="bi bi-list-ul" aria-hidden="true"></i>
                    Daftar IP yang Diblokir
                </span>
                <Badge color="danger">{{ ips.total ?? 0 }} IP</Badge>
            </header>

            <TableWrapper v-if="ips.data?.length">
                <table class="table table-hover app-table mb-0">
                    <thead>
                        <tr>
                            <th>IP Address</th>
                            <th>Diblokir Sampai</th>
                            <th>Alasan</th>
                            <th>Waktu Blokir</th>
                            <th class="table-action-column">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in ips.data" :key="item.id">
                            <td><code class="blocked-ip-code">{{ item.ip_address }}</code></td>
                            <td>
                                <Badge v-if="item.is_expired" color="secondary">Kedaluwarsa</Badge>
                                <Badge v-else color="danger">{{ item.blocked_until || '-' }}</Badge>
                            </td>
                            <td>{{ item.reason || '-' }}</td>
                            <td class="text-muted">{{ item.created_at || '-' }}</td>
                            <td class="table-action-column">
                                <Button type="button" color="outline-success" icon="bi-unlock-fill" @click="unblock(item)">
                                    Unblock
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </TableWrapper>

            <EmptyState
                v-else
                title="Tidak ada IP yang diblokir"
                message="Daftar pembatasan akses sedang kosong."
                icon="bi-shield-check"
            />

            <div v-if="ips.links?.length > 3" class="d-flex justify-content-end p-3 border-top">
                <Pagination :links="ips.links" />
            </div>
        </section>
    </AppShell>
</template>

<style scoped>
.blocked-ip-code {
    display: inline-flex;
    padding: 0.25rem 0.45rem;
    border-radius: 0.45rem;
    background: var(--surface-muted);
    color: var(--text-strong);
    font-weight: 800;
}
</style>
