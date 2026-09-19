<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\KelasMapel;
use App\Models\MataPelajaran;
use App\Models\Role;
use App\Models\SikapSosial;
use App\Models\SikapSpiritual;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\SiswaImportService;
use App\Services\SiswaTemplateService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class P1DatabaseIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_name_must_be_unique(): void
    {
        Role::create(['nama_role' => 'admin']);

        $this->expectException(QueryException::class);

        Role::create(['nama_role' => 'admin']);
    }

    public function test_user_can_have_only_one_siswa_profile(): void
    {
        $role = Role::create(['nama_role' => 'siswa']);
        $kelas = Kelas::create(['tingkat' => 'VII', 'nama_kelas' => 'A']);
        $user = User::create([
            'username' => 'siswa-integrity',
            'password' => Hash::make('secret-password'),
            'role_id' => $role->id,
            'nama_lengkap' => 'Siswa Integrity',
            'is_active' => true,
        ]);

        Siswa::create([
            'user_id' => $user->id,
            'nis' => 'INT001',
            'kelas_id' => $kelas->id,
            'status' => 'aktif',
        ]);

        $this->expectException(QueryException::class);

        Siswa::create([
            'user_id' => $user->id,
            'nis' => 'INT002',
            'kelas_id' => $kelas->id,
            'status' => 'aktif',
        ]);
    }

    public function test_sikap_records_are_unique_per_student_class_year_and_semester(): void
    {
        [$student, $kelasMapel, $tahunAjaran] = $this->academicFixture();

        SikapSosial::create([
            'siswa_id' => $student->id,
            'kelas_mapel_id' => $kelasMapel->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'semester' => '1',
            'empati' => 4,
        ]);

        $this->expectException(QueryException::class);

        SikapSosial::create([
            'siswa_id' => $student->id,
            'kelas_mapel_id' => $kelasMapel->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'semester' => '1',
            'empati' => 5,
        ]);
    }

    public function test_sikap_spiritual_records_are_unique_per_student_class_year_and_semester(): void
    {
        [$student, $kelasMapel, $tahunAjaran] = $this->academicFixture();

        SikapSpiritual::create([
            'siswa_id' => $student->id,
            'kelas_mapel_id' => $kelasMapel->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'semester' => '1',
            'taqwa' => 4,
        ]);

        $this->expectException(QueryException::class);

        SikapSpiritual::create([
            'siswa_id' => $student->id,
            'kelas_mapel_id' => $kelasMapel->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'semester' => '1',
            'taqwa' => 5,
        ]);
    }

    public function test_siswa_import_normalizes_nis_and_creates_user_and_siswa_atomically(): void
    {
        Role::create(['nama_role' => 'siswa']);
        $kelas = Kelas::create(['tingkat' => 'VII', 'nama_kelas' => 'Import']);
        $filePath = $this->createSiswaImportFile([
            [
                'siswa-import-p1',
                'Siswa Import P1',
                'abc001',
                (string) $kelas->id,
                '',
                'L',
                '2026',
                'aktif',
                '1',
            ],
        ]);

        try {
            $result = app(SiswaImportService::class)->import($filePath);

            $this->assertSame(['imported' => 1, 'errors' => []], $result);
            $this->assertDatabaseHas('users', [
                'username' => 'siswa-import-p1',
                'nip_nis' => 'ABC001',
            ]);
            $this->assertDatabaseHas('siswa', [
                'nis' => 'ABC001',
                'kelas_id' => $kelas->id,
            ]);
            $this->assertSame(
                0,
                User::whereDoesntHave('siswa')
                    ->whereHas('role', fn ($query) => $query->where('nama_role', 'siswa'))
                    ->count()
            );
            $this->assertSame(0, Siswa::whereDoesntHave('user')->count());
        } finally {
            @unlink($filePath);
        }
    }

    private function academicFixture(): array
    {
        $guruRole = Role::create(['nama_role' => 'guru']);
        $siswaRole = Role::create(['nama_role' => 'siswa']);
        $guru = User::create([
            'username' => 'guru-integrity',
            'password' => Hash::make('secret-password'),
            'role_id' => $guruRole->id,
            'nama_lengkap' => 'Guru Integrity',
            'is_active' => true,
        ]);
        $studentUser = User::create([
            'username' => 'student-integrity',
            'password' => Hash::make('secret-password'),
            'role_id' => $siswaRole->id,
            'nama_lengkap' => 'Student Integrity',
            'is_active' => true,
        ]);
        $kelas = Kelas::create(['tingkat' => 'VII', 'nama_kelas' => 'A']);
        $student = Siswa::create([
            'user_id' => $studentUser->id,
            'nis' => 'INT003',
            'kelas_id' => $kelas->id,
            'status' => 'aktif',
        ]);
        $mapel = MataPelajaran::create(['kode' => 'INT', 'nama_mapel' => 'Integrity', 'urutan' => 1]);
        $tahunAjaran = TahunAjaran::create(['tahun' => '2026/2027', 'is_active' => true]);
        $kelasMapel = KelasMapel::create([
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapel->id,
            'guru_id' => $guru->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'semester' => '1',
            'pertemuan_per_minggu' => 2,
        ]);

        return [$student, $kelasMapel, $tahunAjaran];
    }

    private function createSiswaImportFile(array $rows): string
    {
        $filePath = tempnam(sys_get_temp_dir(), 'siswa_import_p1_');
        $writer = new Writer;
        $writer->openToFile($filePath);
        $writer->addRow(Row::fromValues(SiswaTemplateService::HEADERS));

        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }

        $writer->close();

        return $filePath;
    }
}
