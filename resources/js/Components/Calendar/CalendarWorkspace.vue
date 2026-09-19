<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { SelectInput, TextareaInput, TextInput } from '../Form';
import { Badge, Button, Card, EmptyState } from '../UI';
import type { CalendarEvent, CalendarEventForm, CalendarPayload, SelectOption } from '../../types';

interface Props {
    calendar: CalendarPayload;
    monthEvents?: CalendarEvent[];
    storeUrl?: string;
    createTitle?: string;
    fixedScope?: string;
    scopeOptions?: SelectOption[];
    readOnly?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    monthEvents: () => [], storeUrl: '', createTitle: 'Tambah Event', fixedScope: '', scopeOptions: () => [], readOnly: false,
});

const selectedEvent = ref<CalendarEvent | null>(null);
const canCreate = computed(() => Boolean(props.storeUrl) && !props.readOnly);
const selectedTitle = computed(() => selectedEvent.value?.can_manage ? 'Edit Event' : 'Detail Event');

const createForm = useForm<CalendarEventForm>({
    title: '',
    event_date: props.calendar.today,
    description: '',
    is_holiday: false,
    is_done: false,
    scope: props.fixedScope || String(props.scopeOptions[0]?.value || 'user'),
});
const editForm = useForm<CalendarEventForm>(blankEventForm());

watch(selectedEvent, (event) => {
    if (!event) {
        return;
    }

    editForm.clearErrors();
    editForm.title = event.title ?? '';
    editForm.event_date = event.event_date ?? props.calendar.today;
    editForm.description = event.description ?? '';
    editForm.is_holiday = Boolean(event.is_holiday);
    editForm.is_done = Boolean(event.is_done);
    editForm.scope = event.scope ?? props.fixedScope ?? 'user';
});

function blankEventForm(): CalendarEventForm {
    return {
        title: '',
        event_date: '',
        description: '',
        is_holiday: false,
        is_done: false,
        scope: '',
    };
}

function submitCreate(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            createForm.event_date = props.calendar.today;
            createForm.scope = props.fixedScope || String(props.scopeOptions[0]?.value || 'user');
        },
    };

    createForm.transform((data) => ({
        ...data,
        scope: props.fixedScope || data.scope,
    })).post(props.storeUrl, options);
}

function submitEdit(): void {
    if (!selectedEvent.value?.update_url) {
        return;
    }

    editForm.put(selectedEvent.value.update_url, {
        preserveScroll: true,
        onSuccess: () => {
            selectedEvent.value = null;
        },
    });
}

async function destroySelected(): Promise<void> {
    if (!selectedEvent.value?.delete_url) {
        return;
    }

    const confirmed = await window.confirmDialog?.('Hapus event ini?', {
        title: 'Hapus Event',
        confirmText: 'Ya, hapus',
        danger: true,
    });

    if (!confirmed) {
        return;
    }

    router.delete(selectedEvent.value.delete_url, {
        preserveScroll: true,
        onSuccess: () => {
            selectedEvent.value = null;
        },
    });
}

function toggleDone(event: CalendarEvent): void {
    if (!event.toggle_done_url) {
        return;
    }

    router.patch(event.toggle_done_url, {}, {
        preserveScroll: true,
    });
}

function eventClass(event: CalendarEvent): Record<string, boolean> {
    return {
        'calendar-event-holiday': event.is_holiday,
        'calendar-event-normal': !event.is_holiday,
        'calendar-event-done': event.is_done,
    };
}

function openEvent(event: CalendarEvent): void {
    selectedEvent.value = event;
}
</script>

<template>
    <div class="row">
        <div class="col-lg-8 mb-4">
            <Card :title="calendar.title" icon="bi-calendar3" body-class="p-0">
                <template #actions>
                    <div class="calendar-actions">
                        <a :href="calendar.prev_url" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-chevron-left" aria-hidden="true"></i> {{ calendar.prev_label }}
                        </a>
                        <a :href="calendar.today_url" class="btn btn-sm btn-outline-primary">Hari Ini</a>
                        <a :href="calendar.next_url" class="btn btn-sm btn-outline-secondary">
                            {{ calendar.next_label }} <i class="bi bi-chevron-right" aria-hidden="true"></i>
                        </a>
                    </div>
                </template>

                <table class="table table-bordered mb-0 calendar-table">
                    <thead>
                        <tr class="text-center calendar-head">
                            <th v-for="day in calendar.weekdays" :key="day">{{ day }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(week, rowIndex) in calendar.weeks" :key="rowIndex">
                            <td
                                v-for="cell in week"
                                :key="cell.date || `${rowIndex}-${cell.day}`"
                                class="calendar-cell"
                                :class="{ 'calendar-cell-empty': !cell.date, 'calendar-cell-today': cell.is_today }"
                            >
                                <template v-if="cell.date">
                                    <div class="calendar-day" :class="{ 'calendar-day-today': cell.is_today }">{{ cell.day }}</div>
                                    <button
                                        v-for="event in cell.events"
                                        :key="event.id"
                                        type="button"
                                        class="calendar-event"
                                        :class="eventClass(event)"
                                        :title="event.title"
                                        @click="openEvent(event)"
                                    >
                                        <i v-if="event.is_holiday" class="bi bi-circle-fill me-1" aria-hidden="true"></i>{{ event.title_short }}
                                    </button>
                                </template>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </Card>
        </div>

        <div class="col-lg-4">
            <Card v-if="canCreate" :title="createTitle" icon="bi-plus-circle" class="mb-3">
                <form @submit.prevent="submitCreate">
                    <TextInput v-model="createForm.title" name="title" label="Judul" required :error="createForm.errors.title" wrapper-class="mb-2" />
                    <TextInput v-model="createForm.event_date" type="date" name="event_date" label="Tanggal" required :error="createForm.errors.event_date" wrapper-class="mb-2" />
                    <TextareaInput v-model="createForm.description" name="description" label="Deskripsi" :rows="2" :error="createForm.errors.description" wrapper-class="mb-2" />
                    <SelectInput
                        v-if="scopeOptions.length && !fixedScope"
                        v-model="createForm.scope"
                        name="scope"
                        label="Cakupan"
                        :options="scopeOptions"
                        required
                        :error="createForm.errors.scope"
                        wrapper-class="mb-2"
                    />
                    <div class="mb-2 d-flex gap-3">
                        <div class="form-check">
                            <input id="createHoliday" v-model="createForm.is_holiday" type="checkbox" class="form-check-input">
                            <label class="form-check-label text-sm" for="createHoliday">Libur</label>
                        </div>
                        <div class="form-check">
                            <input id="createDone" v-model="createForm.is_done" type="checkbox" class="form-check-input">
                            <label class="form-check-label text-sm" for="createDone">Selesai</label>
                        </div>
                    </div>
                    <Button type="submit" color="success" icon="bi-save" class="w-100" :disabled="createForm.processing">
                        {{ createForm.processing ? 'Menyimpan...' : 'Simpan' }}
                    </Button>
                </form>
            </Card>

            <Card :title="`Event ${calendar.month_label}`" icon="bi-list-ul" body-class="p-0">
                <div v-if="monthEvents.length">
                    <div
                        v-for="event in monthEvents"
                        :key="event.id"
                        class="calendar-list-item"
                    >
                        <button type="button" class="calendar-list-main" @click="openEvent(event)">
                            <strong class="text-sm" :class="{ 'calendar-list-done': event.is_done }">
                                <i v-if="event.is_holiday" class="bi bi-circle-fill me-1 text-danger" aria-hidden="true"></i>
                                <i v-else-if="event.is_done" class="bi bi-check-circle-fill me-1 text-success" aria-hidden="true"></i>
                                {{ event.title }}
                            </strong>
                            <span class="text-muted text-xs">
                                {{ event.event_date_label }}
                                <template v-if="event.description"> - {{ event.description }}</template>
                            </span>
                            <span v-if="event.created_by" class="text-muted text-xs">Dibuat oleh {{ event.created_by }}</span>
                        </button>
                        <div class="calendar-list-meta">
                            <Badge v-if="event.scope" :color="event.scope === 'school' ? 'info text-dark' : 'secondary'">{{ event.scope }}</Badge>
                            <Button
                                v-if="event.toggle_done_url && !event.is_done"
                                type="button"
                                color="outline-success"
                                size="sm"
                                icon="bi-check-lg"
                                title="Tandai selesai"
                                aria-label="Tandai selesai"
                                @click="toggleDone(event)"
                            />
                        </div>
                    </div>
                </div>
                <EmptyState v-else title="Tidak ada event." icon="bi-calendar3" />
            </Card>
        </div>
    </div>

    <div v-if="selectedEvent" class="confirm-overlay" @click.self="selectedEvent = null">
        <div class="confirm-dialog calendar-dialog" role="dialog" aria-modal="true">
            <h5 class="confirm-title">
                {{ selectedTitle }}
                <Badge v-if="selectedEvent.is_done" color="success" class="ms-2">Selesai</Badge>
            </h5>

            <form @submit.prevent="submitEdit">
                <TextInput v-model="editForm.title" name="edit_title" label="Judul" required :disabled="!selectedEvent.can_manage" :error="editForm.errors.title" wrapper-class="mb-2" />
                <TextInput v-model="editForm.event_date" type="date" name="edit_event_date" label="Tanggal" required :disabled="!selectedEvent.can_manage" :error="editForm.errors.event_date" wrapper-class="mb-2" />
                <TextareaInput v-model="editForm.description" name="edit_description" label="Deskripsi" :rows="2" :disabled="!selectedEvent.can_manage" :error="editForm.errors.description" wrapper-class="mb-2" />
                <div v-if="selectedEvent.created_by" class="mb-2">
                    <div class="form-label mb-1">Dibuat oleh</div>
                    <div class="text-muted text-sm">{{ selectedEvent.created_by }}</div>
                </div>
                <div class="d-flex gap-3 mb-2">
                    <div class="form-check">
                        <input :id="`eventHoliday${selectedEvent.id}`" v-model="editForm.is_holiday" type="checkbox" class="form-check-input" :disabled="!selectedEvent.can_manage">
                        <label class="form-check-label text-sm" :for="`eventHoliday${selectedEvent.id}`">Libur</label>
                    </div>
                    <div class="form-check">
                        <input :id="`eventDone${selectedEvent.id}`" v-model="editForm.is_done" type="checkbox" class="form-check-input" :disabled="!selectedEvent.can_manage">
                        <label class="form-check-label text-sm" :for="`eventDone${selectedEvent.id}`">Selesai</label>
                    </div>
                </div>

                <div v-if="!selectedEvent.can_manage" class="alert alert-info py-2 mb-0">
                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                    Event ini hanya dapat dilihat.
                </div>

                <div class="confirm-actions mt-3">
                    <Button type="button" color="outline-secondary" size="" @click="selectedEvent = null">Tutup</Button>
                    <Button v-if="selectedEvent.can_manage" type="submit" color="primary" size="" :disabled="editForm.processing">
                        {{ editForm.processing ? 'Menyimpan...' : 'Simpan' }}
                    </Button>
                </div>
            </form>

            <div v-if="selectedEvent.can_manage" class="pt-3 mt-3 border-top">
                <Button type="button" color="outline-danger" size="" icon="bi-trash" @click="destroySelected">Hapus</Button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.calendar-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.calendar-table {
    table-layout: fixed;
    overflow: hidden;
    border-color: var(--bs-border-color);
}

.calendar-head {
    background: var(--gray-100);
}

.calendar-head th {
    font-size: 0.75rem;
    padding: 8px 0;
    color: var(--text-muted);
    font-weight: 700;
}

.calendar-cell {
    height: 80px;
    vertical-align: top;
    padding: 4px;
    background: var(--surface-card);
}

.calendar-cell-empty {
    background: #f9fafb;
}

.calendar-cell-today {
    background: #dcfce7;
}

.calendar-day {
    font-size: 0.75rem;
    font-weight: 500;
    color: #6b7280;
    margin-bottom: 4px;
}

.calendar-day-today {
    font-weight: 800;
    color: #166534;
}

.calendar-event {
    display: block;
    width: 100%;
    border: 0;
    border-radius: 4px;
    margin-bottom: 4px;
    padding: 4px;
    text-align: left;
    font-size: 0.68rem;
    line-height: 1.2;
}

.calendar-event-normal {
    background: #dbeafe;
    color: #1e40af;
}

.calendar-event-holiday {
    background: #fee2e2;
    color: #991b1b;
}

.calendar-event-done {
    opacity: 0.55;
    text-decoration: line-through;
}

.calendar-list-item {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--bs-border-color);
    padding: 0.5rem;
}

.calendar-list-main {
    flex: 1;
    min-width: 0;
    border: 0;
    background: transparent;
    color: inherit;
    text-align: left;
    padding: 0;
}

.calendar-list-main span {
    display: block;
}

.calendar-list-done {
    opacity: 0.6;
    text-decoration: line-through;
}

.calendar-list-meta {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

.calendar-dialog {
    max-width: 560px;
    text-align: left;
}

:global([data-bs-theme="dark"]) .calendar-table {
    --calendar-border: rgba(148, 163, 184, 0.22);
    border-color: var(--calendar-border);
    background: #101827;
}

:global([data-bs-theme="dark"]) .calendar-table th,
:global([data-bs-theme="dark"]) .calendar-table td {
    border-color: var(--calendar-border);
}

:global([data-bs-theme="dark"]) .calendar-head {
    background: #243244;
}

:global([data-bs-theme="dark"]) .calendar-head th {
    color: #cbd5e1;
}

:global([data-bs-theme="dark"]) .calendar-cell {
    background: #111827;
}

:global([data-bs-theme="dark"]) .calendar-cell-empty {
    background: #0f172a;
}

:global([data-bs-theme="dark"]) .calendar-cell-today {
    background: #123626;
    box-shadow: inset 0 0 0 1px rgba(74, 222, 128, 0.42);
}

:global([data-bs-theme="dark"]) .calendar-day {
    color: #9ca3af;
}

:global([data-bs-theme="dark"]) .calendar-day-today {
    color: #86efac;
}

:global([data-bs-theme="dark"]) .calendar-event-normal {
    background: #1e3a5f;
    color: #bfdbfe;
    border: 1px solid rgba(147, 197, 253, 0.24);
}

:global([data-bs-theme="dark"]) .calendar-event-holiday {
    background: #4a1d24;
    color: #fecdd3;
    border: 1px solid rgba(251, 113, 133, 0.28);
}

:global([data-bs-theme="dark"]) .calendar-event-normal:hover,
:global([data-bs-theme="dark"]) .calendar-event-holiday:hover {
    filter: brightness(1.08);
}

:global([data-bs-theme="dark"]) .calendar-list-item {
    border-color: rgba(148, 163, 184, 0.2);
}

:global([data-bs-theme="dark"]) .calendar-list-main strong {
    color: #e5edf7;
}

:global([data-bs-theme="dark"]) .calendar-list-main .text-muted {
    color: #9fb0c5 !important;
}

@media (max-width: 576px) {
    .calendar-cell {
        height: 64px;
        padding: 2px;
    }

    .calendar-event {
        font-size: 0.58rem;
        padding: 3px;
    }

    .calendar-actions,
    .calendar-actions .btn {
        width: 100%;
    }
}
</style>
