export interface Announcement {
    id: number; judul: string; isi: string; target: string; created_at: string;
    creator: { nama_lengkap: string } | null;
    is_public_login: boolean;
    attachment: { url: string | null; name: string; size: number | null } | null;
    target_kelas_ids: number[];
    show_url: string; update_url: string; delete_url: string;
    can_edit: boolean; can_delete: boolean;
}
export interface StudentAnnouncement {
    id: number; judul: string; isi: string; creator: string; created_at: string;
    target_label: string; show_url: string;
}
