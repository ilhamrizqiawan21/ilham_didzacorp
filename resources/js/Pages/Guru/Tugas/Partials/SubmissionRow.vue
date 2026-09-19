<script setup lang="ts">
import { Badge, IconButton } from '../../../../Components/UI';
import SubmissionGradeForm from './SubmissionGradeForm.vue';
import type { AssignmentSubmission, SubmissionStatus } from '../../../../types';

interface Props { item: AssignmentSubmission; statusColor: (status: SubmissionStatus) => string; statusLabel: (status: SubmissionStatus) => string }
defineProps<Props>();

defineEmits<{ detail: []; whatsapp: [] }>();
</script>

<template>
    <tr>
        <td class="text-center text-muted">{{ item.no }}</td>
        <td>
            <strong>{{ item.siswa }}</strong>
            <div class="text-muted small">{{ item.nis }}</div>
        </td>
        <td><Badge :color="statusColor(item.status)">{{ statusLabel(item.status) }}</Badge></td>
        <td>{{ item.tanggal_kumpul ?? '-' }}</td>
        <td>
            <span v-if="item.hari_terlambat" class="text-danger small fw-semibold">
                {{ item.hari_terlambat }} hari<br>
                <span v-if="item.penalty_perkiraan">-{{ item.penalty_perkiraan }} poin</span>
            </span>
            <span v-else class="text-muted">-</span>
        </td>
        <td>
            <template v-if="item.files.length">
                <a
                    v-for="file in item.files"
                    :key="file.id"
                    :href="file.url"
                    class="btn btn-sm btn-outline-primary mb-1 me-1"
                    target="_blank"
                    rel="noopener noreferrer"
                    :title="file.name"
                >
                    <i class="bi bi-paperclip" aria-hidden="true"></i>
                </a>
            </template>
            <a v-else-if="item.legacy_file_url" :href="item.legacy_file_url" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener noreferrer">
                <i class="bi bi-download" aria-hidden="true"></i>
            </a>
            <span v-else class="text-muted">-</span>
        </td>
        <td>
            <button v-if="item.teks_jawaban" class="btn btn-sm btn-outline-info" type="button" :title="item.teks_jawaban" @click="$emit('detail')">
                <i class="bi bi-text-left" aria-hidden="true"></i>
            </button>
            <span v-else class="text-muted">-</span>
        </td>
        <td>
            <SubmissionGradeForm :item="item" compact />
            <small v-if="Number(item.penalty_terlambat || 0) > 0" class="text-danger d-block mt-1">
                Akhir {{ item.nilai }} (-{{ item.penalty_terlambat }})
            </small>
        </td>
        <td>
            <span v-if="item.catatan" class="text-muted small">{{ item.catatan.length > 30 ? item.catatan.slice(0, 30) + '...' : item.catatan }}</span>
            <span v-else class="text-muted">-</span>
        </td>
        <td>
            <button v-if="item.whatsapp_url" type="button" class="btn btn-sm btn-success me-1" title="Buka WhatsApp" @click="$emit('whatsapp')"><i class="bi bi-whatsapp" aria-hidden="true"></i></button>
            <IconButton icon="bi-eye" label="Lihat detail" color="info" @click="$emit('detail')" />
            <small v-if="item.whatsapp_last_sent_at" class="d-block text-success mt-1">Diingatkan {{ item.whatsapp_last_sent_at }}</small>
            <small v-else-if="item.whatsapp_last_prepared_at" class="d-block text-muted mt-1">Disiapkan {{ item.whatsapp_last_prepared_at }}</small>
        </td>
    </tr>
</template>
