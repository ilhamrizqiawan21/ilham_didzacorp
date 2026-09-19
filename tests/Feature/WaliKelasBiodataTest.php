<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Models\WaliKelas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class WaliKelasBiodataTest extends TestCase
{
    use RefreshDatabase;

    public function test_wali_kelas_can_open_biodata_students_page(): void
    {
        $fixture = $this->fixture();

        $this->actingAs($fixture['guru_a'])
            ->get(route('guru.wali-kelas.biodata', $fixture['wali_a']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Guru/WaliKelas/Biodata')
                ->has('students', 1)
                ->where('students.0.nis', 'BIO001')
                ->where('students.0.nama_lengkap', 'SISWA BIODATA A'));
    }

    public function test_wali_kelas_can_update_biodata_for_student_in_own_class(): void
    {
        $fixture = $this->fixture();

        $this->actingAs($fixture['guru_a'])
            ->put(route('guru.wali-kelas.biodata.update', [$fixture['wali_a'], $fixture['siswa_a']]), [
                'nama_lengkap' => 'Nama Siswa Diperbarui',
                'nama_panggilan' => 'Budi',
                'alamat' => 'Jl. Pendidikan No. 1',
                'tempat_lahir' => 'Bandung',
                'tanggal_lahir' => '2013-05-10',
                'hobi' => 'Membaca',
                'cita_cita' => 'Dokter',
                'nama_ayah' => 'Ayah Budi',
                'pekerjaan_ayah' => 'Wiraswasta',
                'nama_ibu' => 'Ibu Budi',
                'pekerjaan_ibu' => 'Guru',
                'penghasilan_orangtua' => 5000000,
                'nama_wali' => null,
                'pekerjaan_wali' => null,
                'penyakit_kronis' => 'Asma',
                'teman_dekat_sekolah' => 'Ahmad',
                'teman_dekat_luar_sekolah' => 'Rafi',
                'jarak_rumah_km' => 3.5,
                'transportasi' => 'Sepeda',
                'kegiatan_luar_sekolah' => 'Mengaji dan futsal',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $fixture['siswa_user_a']->id,
            'nama_lengkap' => 'Nama Siswa Diperbarui',
        ]);

        $this->assertDatabaseHas('biodata_siswa', [
            'siswa_id' => $fixture['siswa_a']->id,
            'nama_panggilan' => 'Budi',
            'penghasilan_orangtua' => 5000000,
            'transportasi' => 'Sepeda',
        ]);
    }

    public function test_wali_kelas_cannot_update_student_from_another_class(): void
    {
        $fixture = $this->fixture();

        $this->actingAs($fixture['guru_a'])
            ->put(route('guru.wali-kelas.biodata.update', [$fixture['wali_a'], $fixture['siswa_b']]), [
                'nama_lengkap' => 'Tidak Boleh Diubah',
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('biodata_siswa', [
            'siswa_id' => $fixture['siswa_b']->id,
        ]);
    }

    public function test_other_teacher_cannot_access_or_update_wali_kelas_biodata(): void
    {
        $fixture = $this->fixture();

        $this->actingAs($fixture['guru_b'])
            ->get(route('guru.wali-kelas.biodata', $fixture['wali_a']))
            ->assertForbidden();

        $this->actingAs($fixture['guru_b'])
            ->put(route('guru.wali-kelas.biodata.update', [$fixture['wali_a'], $fixture['siswa_a']]), [
                'nama_lengkap' => 'Tidak Boleh Diubah',
            ])
            ->assertForbidden();
    }

    private function fixture(): array
    {
        Role::create(['id' => 1, 'nama_role' => 'admin']);
        Role::create(['id' => 2, 'nama_role' => 'guru']);
        Role::create(['id' => 3, 'nama_role' => 'siswa']);
        Role::create(['id' => 4, 'nama_role' => 'kepala_sekolah']);

        $guruA = $this->makeUser('guru', 'guru-biodata-a', 'GURU BIODATA A');
        $guruB = $this->makeUser('guru', 'guru-biodata-b', 'GURU BIODATA B');
        $siswaUserA = $this->makeUser('siswa', 'siswa-biodata-a', 'SISWA BIODATA A');
        $siswaUserB = $this->makeUser('siswa', 'siswa-biodata-b', 'SISWA BIODATA B');

        $kelasA = Kelas::create(['tingkat' => 'VII', 'nama_kelas' => 'VII-A']);
        $kelasB = Kelas::create(['tingkat' => 'VII', 'nama_kelas' => 'VII-B']);
        $tahunAjaran = TahunAjaran::create(['tahun' => '2026/2027', 'is_active' => true]);

        $waliA = WaliKelas::create([
            'kelas_id' => $kelasA->id,
            'guru_id' => $guruA->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
        ]);

        WaliKelas::create([
            'kelas_id' => $kelasB->id,
            'guru_id' => $guruB->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
        ]);

        $siswaA = Siswa::create([
            'user_id' => $siswaUserA->id,
            'nis' => 'BIO001',
            'kelas_id' => $kelasA->id,
            'status' => 'aktif',
        ]);

        $siswaB = Siswa::create([
            'user_id' => $siswaUserB->id,
            'nis' => 'BIO002',
            'kelas_id' => $kelasB->id,
            'status' => 'aktif',
        ]);

        return [
            'guru_a' => $guruA,
            'guru_b' => $guruB,
            'siswa_user_a' => $siswaUserA,
            'siswa_user_b' => $siswaUserB,
            'wali_a' => $waliA,
            'siswa_a' => $siswaA,
            'siswa_b' => $siswaB,
        ];
    }

    private function makeUser(string $roleName, string $username, string $name): User
    {
        return User::create([
            'username' => $username,
            'email' => "{$username}@example.test",
            'password' => Hash::make('secret-password'),
            'is_password_default' => false,
            'nama_lengkap' => $name,
            'role_id' => Role::where('nama_role', $roleName)->value('id'),
            'is_active' => true,
        ]);
    }
}
