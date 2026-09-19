<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Materi;
use App\Models\NilaiAkhir;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StatistikService
{
    /**
     * Statistik dashboard admin.
     */
    public function dashboardAdmin(): array
    {
        return [
            'total_siswa' => $this->countUsersByRole('siswa'),
            'total_guru' => $this->countUsersByRole('guru'),
            'total_kelas' => Kelas::count(),
            'total_mapel' => MataPelajaran::count(),
            'total_materi' => Materi::count(),
            'siswa_aktif' => Siswa::where('status', 'aktif')->count(),
        ];
    }

    /**
     * Statistik dashboard guru.
     */
    public function dashboardGuru(int $guruId): array
    {
        $kelasMapels = \App\Models\KelasMapel::where('guru_id', $guruId)->get();
        $kelasMapelIds = $kelasMapels->pluck('id');

        return [
            'total_kelas_mapel' => $kelasMapels->count(),
            'total_siswa' => Siswa::whereIn('kelas_id', $kelasMapels->pluck('kelas_id'))->where('status', 'aktif')->count(),
            'total_materi' => \App\Models\Materi::whereIn('kelas_mapel_id', $kelasMapelIds)->count(),
            'total_tugas' => \App\Models\Tugas::whereIn('kelas_mapel_id', $kelasMapelIds)->count(),
            'total_pengumuman' => \App\Models\Pengumuman::where('created_by', $guruId)->count(),
        ];
    }

    /**
     * Statistik dashboard siswa.
     */
    public function dashboardSiswa(int $siswaId): array
    {
        $siswa = Siswa::findOrFail($siswaId);

        return [
            'total_absensi' => Absensi::where('siswa_id', $siswaId)->count(),
            'total_tugas' => \App\Models\PengumpulanTugas::where('siswa_id', $siswaId)->count(),
            'tugas_belum' => \App\Models\PengumpulanTugas::where('siswa_id', $siswaId)->where('status', 'belum')->count(),
            'rata_rata_nilai' => NilaiAkhir::where('siswa_id', $siswaId)
                ->selectRaw('AVG('.NilaiAkhir::rataAkhirExpression().') as rata_rata')
                ->value('rata_rata'),
        ];
    }

    /**
     * Statistik dashboard kepala sekolah.
     */
    public function dashboardKepsek(): array
    {
        $totalSiswa = Siswa::where('status', 'aktif')->count();
        $totalGuru = $this->countUsersByRole('guru');
        $totalKelas = Kelas::count();

        // Rata-rata nilai seluruh siswa
        $rataNilai = NilaiAkhir::selectRaw('AVG('.NilaiAkhir::rataAkhirExpression().') as rata_rata')
            ->value('rata_rata');

        // Persentase kehadiran
        $totalAbsensi = Absensi::count();
        $totalHadir = Absensi::where('status', 'hadir')->count();
        $persenHadir = $totalAbsensi > 0 ? round(($totalHadir / $totalAbsensi) * 100, 2) : 0;

        return [
            'total_siswa' => $totalSiswa,
            'total_guru' => $totalGuru,
            'total_kelas' => $totalKelas,
            'rata_nilai' => round($rataNilai ?? 0, 2),
            'persen_hadir' => $persenHadir,
            'total_mapel' => MataPelajaran::count(),
        ];
    }

    private function countUsersByRole(string $role): int
    {
        return User::whereHas('role', fn($query) => $query->where('nama_role', $role))->count();
    }
}
