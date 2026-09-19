export type SubmissionStatus = 'belum' | 'sudah' | 'terlambat' | 'dinilai' | 'perlu_perbaikan' | string;

export interface SubmissionFile {
    id: number | string;
    name: string;
    url: string;
}

export interface AssignmentSubmission {
    key: number | string;
    id?: number | string;
    no: number | string;
    siswa: string;
    nis?: string | null;
    status: SubmissionStatus;
    tanggal_kumpul?: string | null;
    hari_terlambat?: number;
    penalty_perkiraan?: number;
    files: SubmissionFile[];
    legacy_file_url?: string | null;
    teks_jawaban?: string | null;
    nilai?: number | string | null;
    nilai_input?: number | string | null;
    catatan?: string | null;
    nilai_url: string;
    whatsapp_url?: string | null;
    whatsapp_last_prepared_at?: string | null;
    whatsapp_last_sent_at?: string | null;
    penalty_terlambat?: number | string | null;
}

export interface AssignmentContext {
    mata_pelajaran: string;
    kelas: string;
    back_url: string;
    workspace_url: string;
    export_excel_url: string;
    export_pdf_url: string;
}

export interface AssignmentSummary {
    judul: string;
    batas_waktu?: string | null;
    deskripsi?: string | null;
}

export interface TeacherTask {
    id: number; kelas_mapel_id: number; judul: string; deskripsi: string | null;
    batas_waktu: string | null; sudah_mengumpulkan: number; perlu_dinilai: number;
    is_overdue: boolean; progress_percent: number; pengumpulan_url: string; delete_url: string;
}
