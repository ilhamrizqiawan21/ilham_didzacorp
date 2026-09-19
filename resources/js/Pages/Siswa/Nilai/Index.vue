<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppShell from '../../../Layouts/AppShell.vue';
import { Card, EmptyState, TableWrapper } from '../../../Components/UI';

interface NilaiItem { id: number; mata_pelajaran: string; rata_akhir?: number | string | null; [key: string]: unknown }
interface NilaiGroup { periode: string; nilai: NilaiItem[] }
interface Props { nilaiGroups?: NilaiGroup[] }

const props = withDefaults(defineProps<Props>(), { nilaiGroups: () => [] });

const fields = [
    { key: 'sum1', label: 'SUM1' },
    { key: 'sum2', label: 'SUM2' },
    { key: 'sum3', label: 'SUM3' },
    { key: 'sum4', label: 'SUM4' },
    { key: 'nilai_harian', label: 'Harian' },
    { key: 'sts', label: 'STS' },
    { key: 'sas', label: 'SAS' },
    { key: 'sat', label: 'SAT' },
];

function display(value: unknown): string | number {
    return typeof value === 'string' || typeof value === 'number' ? value : '-';
}

function averageStyle(value: unknown): { color: string } {
    if (value === null || value === undefined || value === '') {
        return { color: '#ef4444' };
    }

    return { color: Number(value) >= 75 ? '#16a34a' : '#ef4444' };
}
</script>

<template>
    <Head title="Nilai Saya" />

    <AppShell title="Nilai Saya">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <span class="badge bg-soft-primary">Ringkasan nilai per periode</span>
            <span class="text-muted small">Nilai yang terlihat di sini mengikuti data terakhir yang tersedia.</span>
        </div>

        <template v-if="props.nilaiGroups.length">
            <Card
                v-for="group in props.nilaiGroups"
                :key="group.periode"
                :title="group.periode"
                icon="bi-calendar3"
                body-class="p-0"
                class="mb-3"
            >
                <TableWrapper>
                    <div class="p-3 border-bottom bg-light-subtle">
                        <span class="text-muted small">Periode {{ group.periode }}</span>
                    </div>
                    <table class="table table-hover mb-0" style="font-size:0.82rem;">
                        <thead style="background:var(--primary-100);">
                            <tr>
                                <th>Mata Pelajaran</th>
                                <th v-for="field in fields" :key="field.key" class="text-center">{{ field.label }}</th>
                                <th class="text-center average-head">Rata-rata</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in group.nilai" :key="item.id">
                                <td><strong>{{ item.mata_pelajaran }}</strong></td>
                                <td v-for="field in fields" :key="`${item.id}-${field.key}`" class="text-center">
                                    {{ display(item[field.key]) }}
                                </td>
                                <td class="text-center fw-bold" :style="averageStyle(item.rata_akhir)">
                                    {{ display(item.rata_akhir) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </TableWrapper>
            </Card>
        </template>

        <Card v-else>
            <EmptyState title="Belum ada data nilai." icon="bi-bar-chart" />
        </Card>
    </AppShell>
</template>

<style scoped>
.average-head {
    background: var(--primary-500);
    color: white;
}
</style>
