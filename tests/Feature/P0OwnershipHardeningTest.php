<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\KelasDaring;
use App\Models\KelasMapel;
use App\Models\MataPelajaran;
use App\Models\Materi;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class P0OwnershipHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_guru_cannot_store_absensi_for_another_gurus_kelas_mapel(): void
    {
        $fixture = $this->fixture();

        $this->actingAs($fixture['guru_a'])
            ->post(route('guru.absensi.store', $fixture['kelas_mapel_b']), [
                'bulan' => now()->format('Y-m'),
                'absensi' => [],
            ])
            ->assertForbidden();
    }

    public function test_guru_cannot_update_or_delete_another_gurus_kelas_daring(): void
    {
        $fixture = $this->fixture();
        $kelasDaring = KelasDaring::create([
            'guru_id' => $fixture['guru_b']->id,
            'kelas_mapel_id' => $fixture['kelas_mapel_b']->id,
            'judul' => 'Kelas Milik Guru B',
            'tanggal' => now()->addDay()->toDateString(),
            'pelajaran_ke' => 1,
            'meeting_url' => 'https://meet.example.test/guru-b',
            'status' => KelasDaring::STATUS_TERJADWAL,
        ]);

        $this->actingAs($fixture['guru_a'])
            ->patch(route('guru.kelas-daring.status', $kelasDaring), [
                'status' => KelasDaring::STATUS_SELESAI,
            ])
            ->assertForbidden();

        $this->actingAs($fixture['guru_a'])
            ->delete(route('guru.kelas-daring.destroy', $kelasDaring))
            ->assertForbidden();

        $this->assertDatabaseHas('kelas_daring', [
            'id' => $kelasDaring->id,
            'status' => KelasDaring::STATUS_TERJADWAL,
        ]);
    }

    public function test_guru_cannot_delete_materi_from_same_url_when_materi_belongs_to_other_kelas_mapel(): void
    {
        $fixture = $this->fixture();
        $materi = Materi::create([
            'kelas_mapel_id' => $fixture['kelas_mapel_b']->id,
            'judul' => 'Materi Guru B',
            'deskripsi' => 'Harus tetap aman.',
        ]);

        $this->actingAs($fixture['guru_a'])
            ->delete(route('guru.materi.destroy', [$fixture['kelas_mapel_a'], $materi]))
            ->assertForbidden();

        $this->assertDatabaseHas('materi', ['id' => $materi->id]);
    }

    public function test_siswa_cannot_access_materi_chat_or_tugas_from_another_class(): void
    {
        $fixture = $this->fixture();
        $tugas = Tugas::create([
            'kelas_mapel_id' => $fixture['kelas_mapel_b']->id,
            'judul' => 'Tugas Kelas B',
            'batas_waktu' => now()->addDay(),
            'kategori_nilai' => 'NH',
        ]);

        $this->actingAs($fixture['siswa_user_a'])
            ->get(route('siswa.materi.list', $fixture['kelas_mapel_b']))
            ->assertForbidden();

        $this->actingAs($fixture['siswa_user_a'])
            ->post(route('siswa.chat.send', $fixture['kelas_mapel_b']), [
                'message' => 'Mencoba masuk kelas lain.',
            ])
            ->assertForbidden();

        $this->actingAs($fixture['siswa_user_a'])
            ->get(route('siswa.tugas.show', $tugas))
            ->assertForbidden();
    }

    private function fixture(): array
    {
        Role::create(['id' => 1, 'nama_role' => 'admin']);
        Role::create(['id' => 2, 'nama_role' => 'guru']);
        Role::create(['id' => 3, 'nama_role' => 'siswa']);
        Role::create(['id' => 4, 'nama_role' => 'kepala_sekolah']);

        $guruA = $this->makeUser('guru', 'guru-a-p0-ownership');
        $guruB = $this->makeUser('guru', 'guru-b-p0-ownership');
        $siswaUserA = $this->makeUser('siswa', 'siswa-a-p0-ownership');
        $siswaUserB = $this->makeUser('siswa', 'siswa-b-p0-ownership');

        $kelasA = Kelas::create(['tingkat' => 'VII', 'nama_kelas' => 'A']);
        $kelasB = Kelas::create(['tingkat' => 'VII', 'nama_kelas' => 'B']);
        $tahunAjaran = TahunAjaran::create(['tahun' => '2026/2027', 'is_active' => true]);
        $mapelA = MataPelajaran::create(['kode' => 'P0A', 'nama_mapel' => 'P0 A', 'urutan' => 1]);
        $mapelB = MataPelajaran::create(['kode' => 'P0B', 'nama_mapel' => 'P0 B', 'urutan' => 2]);

        $kelasMapelA = KelasMapel::create([
            'kelas_id' => $kelasA->id,
            'mapel_id' => $mapelA->id,
            'guru_id' => $guruA->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'semester' => '1',
            'pertemuan_per_minggu' => 2,
        ]);

        $kelasMapelB = KelasMapel::create([
            'kelas_id' => $kelasB->id,
            'mapel_id' => $mapelB->id,
            'guru_id' => $guruB->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'semester' => '1',
            'pertemuan_per_minggu' => 2,
        ]);

        Siswa::create([
            'user_id' => $siswaUserA->id,
            'nis' => 'P0A001',
            'nomor_whatsapp' => '6281234567890', 'whatsapp_opt_in' => true,
            'kelas_id' => $kelasA->id,
            'status' => 'aktif',
        ]);

        Siswa::create([
            'user_id' => $siswaUserB->id,
            'nis' => 'P0B001',
            'nomor_whatsapp' => '6281234567891', 'whatsapp_opt_in' => true,
            'kelas_id' => $kelasB->id,
            'status' => 'aktif',
        ]);

        return [
            'guru_a' => $guruA,
            'guru_b' => $guruB,
            'siswa_user_a' => $siswaUserA,
            'siswa_user_b' => $siswaUserB,
            'kelas_mapel_a' => $kelasMapelA,
            'kelas_mapel_b' => $kelasMapelB,
        ];
    }

    private function makeUser(string $roleName, string $username): User
    {
        return User::create([
            'username' => $username,
            'email' => "{$username}@example.test",
            'password' => Hash::make('secret-password'),
            'is_password_default' => false,
            'nama_lengkap' => strtoupper($username),
            'role_id' => Role::where('nama_role', $roleName)->value('id'),
            'is_active' => true,
        ]);
    }
}
