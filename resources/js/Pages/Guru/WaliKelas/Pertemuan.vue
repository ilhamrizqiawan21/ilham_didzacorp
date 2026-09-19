<script setup lang="ts">
import type { PropType } from 'vue';
import type { LaravelPaginator } from '../../../types/pagination';
import { Head, router, useForm } from '@inertiajs/vue3';
import PageHeader from '../../../Components/AppShell/PageHeader.vue';
import { TextareaInput, TextInput } from '../../../Components/Form';
import AppShell from '../../../Layouts/AppShell.vue';
import { Badge, Button, Card, EmptyState, Pagination, TableWrapper } from '../../../Components/UI';

const props = defineProps({
    waliKelas: { type: Object as PropType<{ kelas: string; store_url: string }>, required: true },
    pertemuan: { type: Object as PropType<LaravelPaginator<{ id: number; tanggal: string; topik: string; hasil: string | null; delete_url: string }>>, required: true },
});

const form = useForm({
    tanggal: '',
    topik: '',
    hasil: '',
});

function submit() {
    if (form.processing) {
        return;
    }

    form.post(props.waliKelas.store_url, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

async function destroy(item: { delete_url: string }) {
    const confirmed = await window.confirmDialog?.('Hapus pertemuan ini?', {
        title: 'Hapus Pertemuan',
        confirmText: 'Ya, hapus',
        danger: true,
    });

    if (!confirmed) return;

    router.delete(item.delete_url, { preserveScroll: true });
}
</script>

<template>
    <Head title="Pertemuan Wali Kelas" />

    <AppShell title="Pertemuan Wali Kelas">
        <PageHeader title="Pertemuan Wali Kelas" icon="bi-calendar-event">
            <template #actions>
                <Badge color="primary">{{ waliKelas.kelas }}</Badge>
            </template>
        </PageHeader>

        <div class="row gy-4">
            <div class="col-lg-4">
                <Card title="Tambah Pertemuan" icon="bi-plus-circle">
                    <form @submit.prevent="submit">
                        <TextInput v-model="form.tanggal" type="date" name="tanggal" label="Hari/Tanggal" required :error="form.errors.tanggal" />
                        <TextInput v-model="form.topik" name="topik" label="Topik" maxlength="200" required :error="form.errors.topik" />
                        <TextareaInput v-model="form.hasil" name="hasil" label="Hasil" :rows="5" required :error="form.errors.hasil" />
                        <Button type="submit" color="success" icon="bi-save" class="w-100" :disabled="form.processing">
                            {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                        </Button>
                    </form>
                </Card>
            </div>

            <div class="col-lg-8">
                <Card title="Daftar Pertemuan" icon="bi-list-ul" body-class="p-0">
                    <TableWrapper v-if="pertemuan.data.length">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr><th>Tanggal</th><th>Topik</th><th>Hasil</th><th>Aksi</th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in pertemuan.data" :key="item.id">
                                    <td>{{ item.tanggal }}</td>
                                    <td><strong>{{ item.topik }}</strong></td>
                                    <td>{{ item.hasil }}</td>
                                    <td>
                                        <Button type="button" color="danger" icon="bi-trash" @click="destroy(item)" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </TableWrapper>
                    <EmptyState v-else title="Belum ada pertemuan." icon="bi-calendar-event" />
                    <template v-if="pertemuan.links?.length" #footer>
                        <Pagination :links="pertemuan.links" />
                    </template>
                </Card>
            </div>
        </div>
    </AppShell>
</template>
