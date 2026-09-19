export type UserRole = 'admin' | 'guru' | 'siswa' | 'kepala_sekolah';

export interface AuthUser {
    id: number;
    username: string;
    nama_lengkap: string;
    email: string | null;
    foto_url: string | null;
    role: UserRole | null;
    role_label: string | null;
}

export interface AuthShare {
    user: AuthUser | null;
}
