<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\KelasMapel;
use App\Models\MataPelajaran;
use App\Models\PengumpulanTugas;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class KepsekPhase5Test extends TestCase
{
    use RefreshDatabase;

    private function kepsek(): User
    {
        $role = Role::create(['id' => 1, 'nama_role' => 'kepala_sekolah']);

        return User::create([
            'username' => 'kepsek-phase5',
            'email' => 'kepsek-phase5@example.test',
            'password' => Hash::make('secret'),
            'is_password_default' => false,
            'nama_lengkap' => 'Kepala Sekolah Phase 5',
            'nip_nis' => null,
            'jenis_kelamin' => null,
            'foto' => null,
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }

    public function test_kepsek_phase5_monitoring_pages_are_available(): void
    {
        $user = $this->kepsek();

        $pages = [
            '/kepsek/dashboard' => 'Kepsek/Dashboard',
            '/kepsek/statistik' => 'Kepsek/Statistik/Index',
            '/kepsek/kalender' => 'Kepsek/Kalender/Index',
            '/kepsek/pengumuman' => 'Kepsek/Pengumuman/Index',
            '/kepsek/laporan/absensi' => 'Kepsek/Laporan/Absensi',
            '/kepsek/laporan/nilai' => 'Kepsek/Laporan/Nilai',
            '/kepsek/laporan/rekap-absensi' => 'Kepsek/Laporan/RekapAbsensi',
            '/kepsek/laporan/rekap-tugas' => 'Kepsek/Laporan/RekapTugas',
            '/kepsek/laporan/rekap-sikap' => 'Kepsek/Laporan/RekapSikap',
            '/kepsek/laporan/wali-kelas' => 'Kepsek/Laporan/WaliKelas/Index',
        ];

        foreach ($pages as $url => $component) {
            $this->actingAs($user)
                ->get($url)
                ->assertOk()
                ->assertInertia(fn ($page) => $page->component($component));
        }
    }

    public function test_kepsek_cannot_mutate_calendar_or_announcements(): void
    {
        $user = $this->kepsek();

        $this->actingAs($user)
            ->post('/kepsek/kalender', [
                'title' => 'Tidak boleh',
                'event_date' => '2026-08-19',
                'scope' => 'school',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->post('/kepsek/pengumuman', [
                'judul' => 'Tidak boleh',
                'isi' => 'Tidak boleh membuat pengumuman.',
                'target' => 'semua',
            ])
            ->assertForbidden();
    }

    public function test_kepsek_attendance_and_submission_trends_are_monthly(): void
    {
        $kepsek = $this->kepsek();
        $guru = $this->userWithRole('guru-trend', 'Guru Trend', 'guru');
        $siswaUser = $this->userWithRole('siswa-trend', 'Siswa Trend', 'siswa');
        $kelas = Kelas::create(['tingkat' => 'VII', 'nama_kelas' => 'Trend']);
        $tahunAjaran = TahunAjaran::create(['tahun' => '2026/2027', 'is_active' => true]);
        $mapel = MataPelajaran::create(['kode' => 'TRD', 'nama_mapel' => 'Mapel Trend', 'urutan' => 1]);
        $kelasMapel = KelasMapel::create([
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapel->id,
            'guru_id' => $guru->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'semester' => '1',
            'pertemuan_per_minggu' => 1,
        ]);
        $siswa = Siswa::create(['user_id' => $siswaUser->id, 'nis' => '9001', 'kelas_id' => $kelas->id, 'status' => 'aktif']);
        $bulanLalu = now()->subMonth()->startOfMonth();

        Absensi::create(['siswa_id' => $siswa->id, 'kelas_mapel_id' => $kelasMapel->id, 'tanggal' => $bulanLalu->copy()->day(3), 'status' => 'hadir']);
        Absensi::create(['siswa_id' => $siswa->id, 'kelas_mapel_id' => $kelasMapel->id, 'tanggal' => $bulanLalu->copy()->day(20), 'status' => 'alpha']);

        $tugasA = Tugas::create(['kelas_mapel_id' => $kelasMapel->id, 'judul' => 'Tugas Trend A', 'batas_waktu' => now()->addDay(), 'kategori_nilai' => 'NH']);
        $tugasB = Tugas::create(['kelas_mapel_id' => $kelasMapel->id, 'judul' => 'Tugas Trend B', 'batas_waktu' => now()->addDay(), 'kategori_nilai' => 'NH']);
        PengumpulanTugas::create(['tugas_id' => $tugasA->id, 'siswa_id' => $siswa->id, 'status' => 'dinilai', 'nilai' => 80, 'tanggal_kumpul' => $bulanLalu->copy()->day(5)]);
        PengumpulanTugas::create(['tugas_id' => $tugasB->id, 'siswa_id' => $siswa->id, 'status' => 'terlambat', 'tanggal_kumpul' => $bulanLalu->copy()->day(25)]);

        $this->actingAs($kepsek)
            ->get(route('kepsek.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Kepsek/Dashboard')
                ->where('absensiBulanan.0.bulan', $bulanLalu->format('Y-m'))
                ->where('absensiBulanan.0.hadir', 1)
                ->where('absensiBulanan.0.alpha', 1)
            );

        $this->actingAs($kepsek)
            ->get(route('kepsek.statistik'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Kepsek/Statistik/Index')
                ->where('absensiBulanan.0.bulan', $bulanLalu->format('Y-m'))
                ->where('absensiBulanan.0.total', 2)
                ->where('pengumpulanBulanan.0.bulan', $bulanLalu->format('Y-m'))
                ->where('pengumpulanBulanan.0.total', 2)
                ->where('pengumpulanBulanan.0.terlambat', 1)
            );
    }

    private function userWithRole(string $username, string $namaLengkap, string $roleName): User
    {
        $role = Role::firstOrCreate(['nama_role' => $roleName]);

        return User::create([
            'username' => $username,
            'email' => "{$username}@example.test",
            'password' => Hash::make('secret'),
            'is_password_default' => false,
            'nama_lengkap' => $namaLengkap,
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }
}
