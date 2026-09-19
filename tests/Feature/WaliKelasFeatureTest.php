<?php

namespace Tests\Feature;

use App\Models\AbsensiWaliKelas;
use App\Models\Kelas;
use App\Models\PenangananSiswa;
use App\Models\PertemuanWaliKelas;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Models\WaliKelas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class WaliKelasFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_delete_wali_kelas_assignment(): void
    {
        [$admin, $guru, , $kelas, $tahunAjaran] = $this->fixture();
        $this->actingAs($admin)->post(route('admin.wali-kelas.store'), ['kelas_id' => $kelas->id, 'guru_id' => $guru->id, 'tahun_ajaran_id' => $tahunAjaran->id])->assertRedirect(route('admin.kelas-mapel.index'));
        $waliKelas = WaliKelas::firstOrFail();
        $this->assertSame($guru->id, $waliKelas->guru_id);
        $this->actingAs($admin)->delete(route('admin.wali-kelas.destroy', $waliKelas))->assertRedirect(route('admin.kelas-mapel.index'));
        $this->assertDatabaseMissing('wali_kelas', ['id' => $waliKelas->id]);
    }

    public function test_admin_cannot_duplicate_or_use_non_guru_assignment(): void
    {
        [$admin, $guru, $siswaUser, $kelas, $tahunAjaran] = $this->fixture();
        WaliKelas::create(['kelas_id' => $kelas->id, 'guru_id' => $guru->id, 'tahun_ajaran_id' => $tahunAjaran->id]);
        $this->actingAs($admin)->from(route('admin.kelas-mapel.index'))->post(route('admin.wali-kelas.store'), ['kelas_id' => $kelas->id, 'guru_id' => $guru->id, 'tahun_ajaran_id' => $tahunAjaran->id])->assertRedirect(route('admin.kelas-mapel.index'));
        $this->assertSame(1, WaliKelas::count());
        $kelasLain = Kelas::create(['tingkat' => 'VIII', 'nama_kelas' => 'B']);
        $this->actingAs($admin)->post(route('admin.wali-kelas.store'), ['kelas_id' => $kelasLain->id, 'guru_id' => $siswaUser->id, 'tahun_ajaran_id' => $tahunAjaran->id])->assertSessionHasErrors('guru_id');
    }

    public function test_admin_cannot_delete_assignment_with_wali_kelas_data(): void
    {
        [$admin, $guru, , $kelas, $tahunAjaran] = $this->fixture();
        $waliKelas = WaliKelas::create(['kelas_id' => $kelas->id, 'guru_id' => $guru->id, 'tahun_ajaran_id' => $tahunAjaran->id]);
        PertemuanWaliKelas::create(['wali_kelas_id' => $waliKelas->id, 'tanggal' => '2026-07-13', 'topik' => 'Pembinaan kelas', 'hasil' => 'Kelas siap mengikuti kegiatan.']);
        $this->actingAs($admin)->delete(route('admin.wali-kelas.destroy', $waliKelas))->assertRedirect(route('admin.kelas-mapel.index'));
        $this->assertDatabaseHas('wali_kelas', ['id' => $waliKelas->id]);
    }

    public function test_guru_wali_kelas_can_manage_attendance_meetings_and_student_cases(): void
    {
        [, $guru, , $kelas, $tahunAjaran] = $this->fixture();
        $waliKelas = WaliKelas::create(['kelas_id' => $kelas->id, 'guru_id' => $guru->id, 'tahun_ajaran_id' => $tahunAjaran->id]);
        $siswa = $this->createSiswa($kelas, '1001', 'Siswa Satu');
        $this->actingAs($guru)->post(route('guru.wali-kelas.absensi.store', $waliKelas), ['bulan' => '2026-07', 'absensi' => [$siswa->id => ['2026-07-13' => 'hadir']]])->assertRedirect();
        $this->assertDatabaseHas('absensi_wali_kelas', ['wali_kelas_id' => $waliKelas->id, 'siswa_id' => $siswa->id, 'tanggal' => '2026-07-13 00:00:00', 'status' => 'hadir']);
        $this->actingAs($guru)->post(route('guru.wali-kelas.pertemuan.store', $waliKelas), ['tanggal' => '2026-07-14', 'topik' => 'Kedisiplinan', 'hasil' => 'Siswa menyepakati aturan kelas.'])->assertRedirect();
        $this->assertDatabaseHas('pertemuan_wali_kelas', ['topik' => 'Kedisiplinan']);
        $this->actingAs($guru)->post(route('guru.wali-kelas.penanganan.store', $waliKelas), ['siswa_id' => $siswa->id, 'kondisi' => 'Sering terlambat', 'deskripsi' => 'Terlambat tiga kali.', 'tindak_lanjut' => 'Komunikasi dengan orang tua.', 'hasil' => null, 'status' => 'proses'])->assertRedirect();
        $this->assertDatabaseHas('penanganan_siswa', ['siswa_id' => $siswa->id, 'kondisi' => 'Sering terlambat', 'status' => 'proses']);
    }

    public function test_other_guru_cannot_access_wali_kelas_data_or_use_student_outside_class(): void
    {
        [, $guru, , $kelas, $tahunAjaran] = $this->fixture();
        $guruLain = $this->createUser('guru2', 'Guru Lain', 'guru');
        $kelasLain = Kelas::create(['tingkat' => 'VIII', 'nama_kelas' => 'B']);
        $waliKelas = WaliKelas::create(['kelas_id' => $kelas->id, 'guru_id' => $guru->id, 'tahun_ajaran_id' => $tahunAjaran->id]);
        $siswaLuar = $this->createSiswa($kelasLain, '2001', 'Siswa Luar');
        $this->actingAs($guruLain)->get(route('guru.wali-kelas.absensi', $waliKelas))->assertForbidden();
        $this->actingAs($guru)->post(route('guru.wali-kelas.penanganan.store', $waliKelas), ['siswa_id' => $siswaLuar->id, 'kondisi' => 'Perlu pendampingan', 'status' => 'baru'])->assertSessionHasErrors('siswa_id');
    }

    public function test_kepsek_can_view_wali_kelas_report_but_cannot_mutate_guru_data(): void
    {
        [, $guru, , $kelas, $tahunAjaran] = $this->fixture();
        $kepsek = $this->createUser('kepsek', 'Kepala Sekolah', 'kepala_sekolah');
        $waliKelas = WaliKelas::create(['kelas_id' => $kelas->id, 'guru_id' => $guru->id, 'tahun_ajaran_id' => $tahunAjaran->id]);
        $siswa = $this->createSiswa($kelas, '1002', 'Siswa Dua');
        AbsensiWaliKelas::create(['wali_kelas_id' => $waliKelas->id, 'siswa_id' => $siswa->id, 'tanggal' => '2026-07-13', 'status' => 'hadir']);
        $this->actingAs($kepsek)->get(route('kepsek.laporan.wali-kelas.show', $waliKelas))->assertOk()->assertInertia(fn ($page) => $page->component('Kepsek/Laporan/WaliKelas/Show')->where('waliKelas.title', 'VII A - Guru Wali')->where('siswaRows.0.nama', 'Siswa Dua'));
        $this->actingAs($kepsek)->post(route('guru.wali-kelas.pertemuan.store', $waliKelas), ['tanggal' => '2026-07-14', 'topik' => 'Tidak boleh', 'hasil' => 'Ditolak'])->assertForbidden();
    }

    private function fixture(): array
    {
        Role::create(['nama_role' => 'admin']); Role::create(['nama_role' => 'guru']); Role::create(['nama_role' => 'siswa']); Role::create(['nama_role' => 'kepala_sekolah']);
        $admin = $this->createUser('admin', 'Admin', 'admin'); $guru = $this->createUser('guru', 'Guru Wali', 'guru'); $siswaUser = $this->createUser('siswa-user', 'User Siswa', 'siswa');
        $kelas = Kelas::create(['tingkat' => 'VII', 'nama_kelas' => 'A']); $tahunAjaran = TahunAjaran::create(['tahun' => '2026/2027', 'is_active' => true]);
        return [$admin, $guru, $siswaUser, $kelas, $tahunAjaran];
    }

    private function createUser(string $username, string $namaLengkap, string $roleName): User
    {
        $role = Role::where('nama_role', $roleName)->firstOrFail();
        return User::create(['username' => $username, 'email' => "{$username}@test.local", 'password' => 'password', 'role_id' => $role->id, 'nama_lengkap' => $namaLengkap, 'is_active' => true]);
    }

    private function createSiswa(Kelas $kelas, string $nis, string $namaLengkap): Siswa
    {
        $user = $this->createUser("siswa-{$nis}", $namaLengkap, 'siswa');
        return Siswa::create(['user_id' => $user->id, 'nis' => $nis, 'kelas_id' => $kelas->id, 'status' => 'aktif']);
    }
}
