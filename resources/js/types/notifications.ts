export interface NotificationItem {
    id: number;
    judul: string;
    pesan: string;
    is_read: boolean;
    created_at: string | null;
    mark_read_route: string | null;
}

export interface NotificationsShare {
    route: string | null;
    mark_all_route: string | null;
    unread_count: number;
    latest: NotificationItem[];
}

export interface NotificationRow {
    id: number;
    judul: string;
    pesan_ringkas: string;
    created_at: string;
    tipe: string;
    is_read: boolean;
    link: string | null;
    mark_read_url: string;
}
