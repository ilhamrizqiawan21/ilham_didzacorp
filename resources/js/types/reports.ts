import type { Score, Scores } from './assessment';

export interface ReportOption { value: string | number; label: string }
export type ExportUrls = Partial<Record<'excel' | 'pdf', string>>;
export interface AttendanceReportRow {
    id: number; nomor: number | null; nama_siswa: string; kelas: string;
    mapel: string; tanggal: string; status: string; keterangan: string;
}
export interface GradeReportRow extends Scores {
    id: number; siswa: string; kelas: string; mapel: string; rata_akhir: Score;
}
export interface AttendanceSummary {
    no: number; nis: string; nama: string; hadir: number; sakit: number;
    izin: number; alpha: number; total: number; persen_hadir: number;
}
export interface ClassAttendanceSummary {
    kelas_id: number; kelas: string; jumlah_siswa: number;
    total_hadir: number; total_absensi: number; persen: number;
}
export type SocialAspect = 'empati' | 'kerjasama' | 'toleransi' | 'percaya_diri' | 'komunikasi';
export type SpiritualAspect = 'taqwa' | 'kejujuran' | 'disiplin' | 'sabar' | 'syukur' | 'tawadhu';
export type AttitudeReportRow<K extends string> = {
    nomor: number; nama_siswa: string; kelas: string; mapel_count: number;
} & Record<K, number>;
export interface HomeroomReportRow {
    id: number; kelas: string; guru: string; tahun_ajaran: string;
    absensi_count: number; pertemuan_count: number; penanganan_aktif_count: number;
    penanganan_siswa_count: number; show_url: string;
}
export interface HomeroomAttendanceRow {
    id: number; nis: string; nama: string;
    statuses: { date: string; status: string | null; label: string }[];
    counts: { hadir: number; sakit: number; izin: number; alpha: number };
}
