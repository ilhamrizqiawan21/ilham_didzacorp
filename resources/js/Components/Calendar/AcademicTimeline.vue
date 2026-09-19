<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Badge, Card, EmptyState } from '../UI';
import type { TimelineEvent, TimelineEventType } from '../../types';

interface Props { events?: TimelineEvent[] }
const props = withDefaults(defineProps<Props>(), { events: () => [] });

const filter = ref<'all' | TimelineEventType>('all');
const filters: Array<{ value: 'all' | TimelineEventType; label: string }> = [
    { value: 'all', label: 'Semua' },
    { value: 'calendar', label: 'Event' },
    { value: 'task', label: 'Deadline' },
    { value: 'announcement', label: 'Pengumuman' },
];

const visibleEvents = computed(() => props.events.filter((event) => filter.value === 'all' || event.type === filter.value));
const typeColor = (type: TimelineEventType): string => ({ calendar: 'secondary', task: 'warning text-dark', announcement: 'info text-dark' }[type]);
const typeIcon = (type: TimelineEventType): string => ({ calendar: 'bi-calendar-event', task: 'bi-hourglass-split', announcement: 'bi-megaphone' }[type]);
</script>

<template>
    <Card title="Academic Timeline" icon="bi-clock-history">
        <template #actions>
            <div class="timeline-filters">
                <button
                    v-for="item in filters"
                    :key="item.value"
                    type="button"
                    class="btn btn-sm"
                    :class="filter === item.value ? 'btn-primary' : 'btn-outline-secondary'"
                    @click="filter = item.value"
                >{{ item.label }}</button>
            </div>
        </template>

        <div v-if="visibleEvents.length" class="academic-timeline">
            <div v-for="event in visibleEvents" :key="event.id" class="timeline-item">
                <div class="timeline-marker"><i :class="`bi ${typeIcon(event.type)}`"></i></div>
                <div class="timeline-content">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <div>
                            <div class="small text-muted">{{ event.date_label }}<span v-if="event.time_label"> · {{ event.time_label }}</span></div>
                            <h6 class="mb-1 mt-1">{{ event.title }}</h6>
                        </div>
                        <Badge :color="typeColor(event.type)">{{ event.type_label }}</Badge>
                    </div>
                    <div v-if="event.meta" class="small text-muted mb-1">{{ event.meta }}</div>
                    <p v-if="event.description" class="small text-secondary mb-2 timeline-description">{{ event.description }}</p>

                    <div v-if="event.detail_links?.length" class="timeline-targets mb-2" aria-label="Kelas tujuan tugas">
                        <Link
                            v-for="link in event.detail_links"
                            :key="`${event.id}-${link.label}`"
                            :href="link.url"
                            class="timeline-target-link"
                            :title="`Buka pengumpulan ${link.label}`"
                        >
                            <i class="bi bi-people me-1"></i>{{ link.label }}
                        </Link>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <Link v-if="event.detail_url" :href="event.detail_url" class="btn btn-sm btn-outline-primary">Detail</Link>
                        <span v-if="event.is_holiday" class="badge bg-danger-subtle text-danger-emphasis">Hari libur</span>
                        <span v-if="event.is_done" class="badge bg-success-subtle text-success-emphasis">Selesai</span>
                    </div>
                </div>
            </div>
        </div>
        <EmptyState v-else title="Tidak ada agenda pada filter ini." icon="bi-calendar-x" />
    </Card>
</template>

<style scoped>
.timeline-filters { display: flex; flex-wrap: wrap; gap: 0.35rem; }
.academic-timeline { position: relative; display: grid; gap: 0.75rem; }
.timeline-item { display: grid; grid-template-columns: 32px 1fr; gap: 0.75rem; position: relative; }
.timeline-item:not(:last-child)::before { content: ''; position: absolute; left: 15px; top: 32px; bottom: -12px; width: 1px; background: var(--bs-border-color); }
.timeline-marker { width: 32px; height: 32px; border-radius: 50%; display: grid; place-items: center; background: var(--bs-light); border: 1px solid var(--bs-border-color); z-index: 1; }
.timeline-content { min-width: 0; padding-bottom: 0.5rem; }
.timeline-description { white-space: pre-line; overflow-wrap: anywhere; }
.timeline-targets { display: flex; flex-wrap: wrap; gap: 0.4rem; }
.timeline-target-link {
    display: inline-flex;
    align-items: center;
    min-height: 30px;
    padding: 0.28rem 0.6rem;
    border: 1px solid var(--bs-border-color);
    border-radius: 999px;
    background: var(--bs-body-bg);
    color: var(--bs-body-color);
    font-size: 0.78rem;
    font-weight: 600;
    text-decoration: none;
    transition: border-color 0.15s ease, background-color 0.15s ease, color 0.15s ease;
}
.timeline-target-link:hover,
.timeline-target-link:focus-visible {
    border-color: var(--bs-primary);
    background: color-mix(in srgb, var(--bs-primary) 8%, var(--bs-body-bg));
    color: var(--bs-primary);
}
:global([data-bs-theme="dark"]) .timeline-item:not(:last-child)::before { background: rgba(148, 163, 184, 0.24); }
:global([data-bs-theme="dark"]) .timeline-marker { background: #1e293b; border-color: rgba(148, 163, 184, 0.28); color: #86efac; }
:global([data-bs-theme="dark"]) .timeline-content h6 { color: #e5edf7; }
:global([data-bs-theme="dark"]) .timeline-description,
:global([data-bs-theme="dark"]) .timeline-content .text-secondary { color: #9fb0c5 !important; }
@media (max-width: 576px) {
    .timeline-filters .btn { flex: 1 1 auto; }
    .timeline-target-link { flex: 1 1 auto; justify-content: center; }
}
</style>
