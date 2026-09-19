import type { Method } from '@inertiajs/core';

export interface QueueItem {
    id?: string | number; title: string; meta?: string; detail?: string;
    href?: string | null; accent?: string; icon?: string; badge?: string | number; badgeColor?: string;
}
export interface MetricItem {
    label: string; value: string | number; href?: string; tone?: string; icon?: string;
}
export interface QuickAction {
    label: string; href?: string; method?: Method; color?: string; icon?: string;
}
export interface SelectOption { value: string | number | boolean; label: string }
