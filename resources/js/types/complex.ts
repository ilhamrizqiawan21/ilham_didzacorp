export type CalendarScope = 'user' | 'school' | string;

export interface SelectOption {
    value: string | number;
    label: string;
}

export interface CalendarEvent {
    id: number | string;
    title: string;
    title_short?: string;
    event_date: string;
    event_date_label?: string;
    description?: string | null;
    created_by?: string | null;
    scope?: CalendarScope;
    is_holiday: boolean;
    is_done: boolean;
    can_manage?: boolean;
    update_url?: string | null;
    delete_url?: string | null;
    toggle_done_url?: string | null;
}

export interface CalendarCell {
    date?: string | null;
    day?: number | string;
    is_today?: boolean;
    events: CalendarEvent[];
}

export interface CalendarPayload {
    title: string;
    today: string;
    month_label: string;
    prev_url: string;
    prev_label: string;
    today_url: string;
    next_url: string;
    next_label: string;
    weekdays: string[];
    weeks: CalendarCell[][];
}

export interface CalendarEventForm {
    title: string;
    event_date: string;
    description: string;
    is_holiday: boolean;
    is_done: boolean;
    scope: CalendarScope;
}

export type TimelineEventType = 'calendar' | 'task' | 'announcement';

export interface TimelineDetailLink {
    label: string;
    url: string;
}

export interface TimelineEvent {
    id: number | string;
    type: TimelineEventType;
    type_label: string;
    title: string;
    date_label: string;
    time_label?: string | null;
    meta?: string | null;
    description?: string | null;
    detail_url?: string | null;
    detail_links?: TimelineDetailLink[];
    is_holiday?: boolean;
    is_done?: boolean;
}

export interface ChatRoomSummary {
    id: number | string;
    title: string;
    subtitle?: string | null;
    url: string;
}

export interface ChatRoomPayload {
    id?: number | string;
    title?: string;
    subtitle?: string | null;
    [key: string]: unknown;
}

export interface ChatMessage {
    id: number | string;
    author: string;
    message: string;
    time: string;
    is_mine: boolean;
    avatar_url?: string | null;
}

export interface UploadProgress {
    percentage: number | null;
    bytesSent?: number;
    bytesTotal?: number;
}
