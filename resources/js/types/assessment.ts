export type Score = number | string | null;
export type ScoreField = 'sum1' | 'sum2' | 'sum3' | 'sum4' | 'nilai_harian' | 'sts' | 'sas' | 'sat';
export type Scores = Record<ScoreField, Score>;
export interface GradeStudent {
    id: number;
    no: number;
    nis: string;
    nama: string;
    scores: Scores;
    rata_akhir: Score;
}
export interface GradeCourse {
    id: number;
    kelas: string;
    mata_pelajaran: string;
    workspace_url: string;
    store_url: string;
    export_excel_url: string;
    export_pdf_url: string;
}
export interface GradeGroup {
    kelas_mapel_id: number;
    kelas: string;
    mata_pelajaran: string;
    label: string;
    export_excel_url: string;
    export_pdf_url: string;
    students: GradeStudent[];
}
export type AttendanceStatus = '' | 'hadir' | 'sakit' | 'izin' | 'alpha';
export interface AttendanceStudent {
    id: number;
    no: number;
    nis: string;
    nama: string;
    absensi: Record<string, AttendanceStatus>;
}
export interface AttendanceMeeting {
    key: string | number;
    title: string;
    label: string;
    date: string | null;
    lesson_title?: string | null;
}
