<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\KelasMapel;
use App\Models\MataPelajaran;
use App\Models\NilaiAkhir;
use App\Models\Pengaturan;
use App\Models\PengumpulanTugas;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class SiswaProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_progress_payload_is_actionable_scoped_and_keeps_missing_scores_as_null(): void
    {
        [$studentUser, $student, $teacher, $kelas, $tahunAjaran] = $this->fixture();
        Pengaturan::setValue('semester_aktif', '1');
        Pengaturan::setValue('kkm_default', '86');

        $matematika = MataPelajaran::create([
            'kode' => 'MTK',
            'nama_mapel' => 'Matematika',
            'urutan' => 1,
        ]);
        $ipa = MataPelajaran::create([
            'kode' => 'IPA',
            'nama_mapel' => 'IPA',
            'urutan' => 2,
        ]);

        $kelasMapelMtk = $this->kelasMapel($kelas, $matematika, $teacher, $tahunAjaran, '1');
        $kelasMapelIpa = $this->kelasMapel($kelas, $ipa, $teacher, $tahunAjaran, '1');

        NilaiAkhir::create([
            'siswa_id' => $student->id,
            'kelas_mapel_id' => $kelasMapelMtk->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'semester' => '1',
            'sum1' => 80,
            'sum2' => 90,
        ]);

        Absensi::create([
            'siswa_id' => $student->id,
            'kelas_mapel_id' => $kelasMapelMtk->id,
            'tanggal' => now()->copy()->startOfMonth()->addDay()->toDateString(),
            'status' => 'hadir',
        ]);
        Absensi::create([
            'siswa_id' => $student->id,
            'kelas_mapel_id' => $kelasMapelIpa->id,
            'tanggal' => now()->copy()->startOfMonth()->addDays(2)->toDateString(),
            'status' => 'alpha',
        ]);

        $tugasBelum = Tugas::create([
            'kelas_mapel_id' => $kelasMapelMtk->id,
            'judul' => 'Latihan Aljabar',
            'batas_waktu' => now()->addDays(2),
            'kategori_nilai' => 'NH',
        ]);
        $tugasPerbaikan = Tugas::create([
            'kelas_mapel_id' => $kelasMapelIpa->id,
            'judul' => 'Laporan Praktikum',
            'batas_waktu' => now()->addDays(3),
            'kategori_nilai' => 'NH',
        ]);
        PengumpulanTugas::create([
            'tugas_id' => $tugasPerbaikan->id,
            'siswa_id' => $student->id,
            'status' => PengumpulanTugas::STATUS_PERLU_PERBAIKAN,
            'tanggal_kumpul' => now(),
        ]);

        // Data semester lain tidak boleh masuk ke progress semester aktif.
        $mapelSemesterLain = MataPelajaran::create([
            'kode' => 'IPS',
            'nama_mapel' => 'IPS',
            'urutan' => 3,
        ]);
        $kelasMapelSemesterLain = $this->kelasMapel($kelas, $mapelSemesterLain, $teacher, $tahunAjaran, '2');
        Tugas::create([
            'kelas_mapel_id' => $kelasMapelSemesterLain->id,
            'judul' => 'Tugas Semester Lain',
            'batas_waktu' => now()->addDay(),
            'kategori_nilai' => 'NH',
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($studentUser)
            ->get(route('siswa.progress'));

        $response
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Siswa/Progress')
                ->where('header.kelas', 'VII-A')
                ->where('header.semester_label', 'Ganjil')
                ->where('stats.rata_nilai', 85)
                ->where('stats.rata_nilai_label', '85.00')
                ->where('stats.mapel_dinilai', 1)
                ->where('stats.total_mapel', 2)
                ->where('stats.persen_hadir', 50)
                ->where('stats.persen_pengumpulan', 50)
                ->where('stats.total_tugas', 2)
                ->where('stats.tugas_dikumpulkan', 1)
                ->where('stats.tugas_belum', 1)
                ->where('stats.tugas_perlu_perbaikan', 1)
                ->where('stats.batas_ketuntasan', 86)
                ->where('stats.bulan_label', now()->locale('id')->translatedFormat('F'))
                ->where('stats.trend_delta', 10)
                ->has('subjectScores', 2)
                ->where('subjectScores.0.nama_mapel', 'Matematika')
                ->where('subjectScores.0.rata', 85)
                ->where('subjectScores.0.status_label', 'Perlu ditingkatkan')
                ->where('subjectScores.1.nama_mapel', 'IPA')
                ->where('subjectScores.1.rata', null)
                ->where('subjectScores.1.status_label', 'Belum ada nilai')
                ->has('scoreTrend', 2)
                ->where('scoreTrend.0.label', 'Sumatif 1')
                ->where('scoreTrend.0.value', 80)
                ->where('scoreTrend.1.label', 'Sumatif 2')
                ->where('scoreTrend.1.value', 90)
                ->has('focusItems', 4)
                ->where('focusItems.0.title', 'Tugas belum dikumpulkan')
                ->where('focusItems.0.href', route('siswa.tugas.show', $tugasBelum))
                ->where('focusItems.1.title', 'Tugas perlu diperbaiki')
                ->where('focusItems.1.href', route('siswa.tugas.show', $tugasPerbaikan))
                ->where('focusItems.2.title', 'Matematika perlu perhatian')
                ->where('focusItems.3.title', 'Kehadiran perlu diperhatikan')
            );

        $nilaiQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query) => str_contains(strtolower($query['query']), 'nilai_akhir'));

        $this->assertLessThanOrEqual(
            1,
            $nilaiQueries->count(),
            'Progress siswa tidak boleh melakukan query nilai per mata pelajaran (N+1).'
        );
    }

    public function test_progress_only_uses_the_authenticated_students_scores(): void
    {
        [$studentUser, $student, $teacher, $kelas, $tahunAjaran] = $this->fixture();
        Pengaturan::setValue('semester_aktif', '1');

        $mapel = MataPelajaran::create([
            'kode' => 'QH',
            'nama_mapel' => 'Al-Quran Hadis',
            'urutan' => 1,
        ]);
        $kelasMapel = $this->kelasMapel($kelas, $mapel, $teacher, $tahunAjaran, '1');

        NilaiAkhir::create([
            'siswa_id' => $student->id,
            'kelas_mapel_id' => $kelasMapel->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'semester' => '1',
            'sum1' => 70,
        ]);

        $otherUser = $this->createUser('siswa-progress-lain', 'Siswa Lain', 'siswa');
        $otherStudent = Siswa::create([
            'user_id' => $otherUser->id,
            'nis' => '9302',
            'kelas_id' => $kelas->id,
            'status' => 'aktif',
        ]);
        NilaiAkhir::create([
            'siswa_id' => $otherStudent->id,
            'kelas_mapel_id' => $kelasMapel->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'semester' => '1',
            'sum1' => 100,
        ]);

        $this->actingAs($studentUser)
            ->get(route('siswa.progress'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stats.rata_nilai', 70)
                ->where('subjectScores.0.rata', 70)
            );
    }

    private function fixture(): array
    {
        Role::create(['nama_role' => 'admin']);
        Role::create(['nama_role' => 'guru']);
        Role::create(['nama_role' => 'siswa']);
        Role::create(['nama_role' => 'kepala_sekolah']);

        $teacher = $this->createUser('guru-progress', 'Guru Progress', 'guru');
        $studentUser = $this->createUser('siswa-progress', 'Siswa Progress', 'siswa');
        $kelas = Kelas::create([
            'tingkat' => 'VII',
            'nama_kelas' => 'VII-A',
        ]);
        $tahunAjaran = TahunAjaran::create([
            'tahun' => '2026/2027',
            'is_active' => true,
        ]);
        $student = Siswa::create([
            'user_id' => $studentUser->id,
            'nis' => '9301',
            'nomor_whatsapp' => '6281234567890', 'whatsapp_opt_in' => true,
            'kelas_id' => $kelas->id,
            'status' => 'aktif',
        ]);

        return [$studentUser, $student, $teacher, $kelas, $tahunAjaran];
    }

    private function kelasMapel(
        Kelas $kelas,
        MataPelajaran $mapel,
        User $teacher,
        TahunAjaran $tahunAjaran,
        string $semester
    ): KelasMapel {
        return KelasMapel::create([
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapel->id,
            'guru_id' => $teacher->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'semester' => $semester,
            'pertemuan_per_minggu' => 2,
        ]);
    }

    private function createUser(string $username, string $namaLengkap, string $roleName): User
    {
        $role = Role::where('nama_role', $roleName)->firstOrFail();

        return User::create([
            'username' => $username,
            'email' => "{$username}@test.local",
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'nama_lengkap' => $namaLengkap,
            'is_active' => true,
            'is_password_default' => false,
        ]);
    }
}
