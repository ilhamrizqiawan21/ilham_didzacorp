<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\KelasMapel;
use App\Models\MataPelajaran;
use App\Models\Pengaturan;
use App\Models\PengumpulanTugas;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use App\Models\User;
use App\Models\WhatsAppMessageLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class Phase10AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guru_can_open_task_menu_with_assigned_class_links(): void
    {
        [, $guruA, , $kelas, $tahunAjaran] = $this->fixture();
        $mapel = MataPelajaran::create([
            'kode' => 'TGS',
            'nama_mapel' => 'Tugas Regression',
            'urutan' => 1,
        ]);

        $kelasMapel = KelasMapel::create([
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapel->id,
            'guru_id' => $guruA->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'semester' => '1',
            'pertemuan_per_minggu' => 2,
        ]);

        $this->actingAs($guruA)
            ->get(route('guru.tugas.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Guru/Tugas/Index')
                ->has('kelasMapel', 1)
                ->where('kelasMapel.0.id', $kelasMapel->id)
                ->where('kelasMapel.0.href', route('guru.tugas.list', $kelasMapel))
            );
    }

    public function test_guru_task_submissions_include_student_name_and_submission_data(): void
    {
        [, $guruA, , $kelas, $tahunAjaran] = $this->fixture();
        $mapel = MataPelajaran::create(['kode' => 'SUB', 'nama_mapel' => 'Submission Test', 'urutan' => 1]);
        $kelasMapel = KelasMapel::create([
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapel->id,
            'guru_id' => $guruA->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'semester' => '1',
            'pertemuan_per_minggu' => 2,
        ]);
        $tugas = Tugas::create([
            'kelas_mapel_id' => $kelasMapel->id,
            'judul' => 'Tugas Submission Test',
            'batas_waktu' => now()->addDay(),
            'kategori_nilai' => 'NH',
        ]);
        $studentUser = $this->createUser('siswa-submission', 'Nama Siswa Submission', 'siswa');
        $student = Siswa::create([
            'user_id' => $studentUser->id,
            'nis' => '9101',
            'kelas_id' => $kelas->id,
            'status' => 'aktif',
        ]);
        PengumpulanTugas::create([
            'tugas_id' => $tugas->id,
            'siswa_id' => $student->id,
            'status' => 'sudah',
            'teks_jawaban' => 'Jawaban siswa',
            'tanggal_kumpul' => now(),
        ]);

        $this->actingAs($guruA)
            ->get(route('guru.tugas.pengumpulan', [$kelasMapel, $tugas]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Guru/Tugas/Pengumpulan')
                ->where('pengumpulan.0.siswa', 'Nama Siswa Submission')
                ->where('pengumpulan.0.status', 'sudah')
                ->where('pengumpulan.0.teks_jawaban', 'Jawaban siswa')
            );
    }

    public function test_guru_cannot_delete_another_gurus_task_by_id(): void
    {
        [, $guruA, $guruB, $kelas, $tahunAjaran] = $this->fixture();
        $mapel = MataPelajaran::create(['kode' => 'MTK', 'nama_mapel' => 'Matematika', 'urutan' => 1]);

        $kelasMapel = KelasMapel::create([
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapel->id,
            'guru_id' => $guruB->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'semester' => '1',
            'pertemuan_per_minggu' => 2,
        ]);

        $tugas = Tugas::create([
            'kelas_mapel_id' => $kelasMapel->id,
            'judul' => 'Tugas Guru B',
            'deskripsi' => 'Data yang harus tetap dimiliki Guru B.',
            'batas_waktu' => now()->addDay(),
            'kategori_nilai' => 'NH',
        ]);

        $this->actingAs($guruA)
            ->delete(route('guru.tugas.destroy', $tugas))
            ->assertForbidden();

        $this->assertDatabaseHas('tugas', ['id' => $tugas->id]);
    }

    public function test_owner_guru_can_delete_own_task(): void
    {
        [, $guruA, , $kelas, $tahunAjaran] = $this->fixture();
        $mapel = MataPelajaran::create(['kode' => 'IPA', 'nama_mapel' => 'IPA', 'urutan' => 2]);

        $kelasMapel = KelasMapel::create([
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapel->id,
            'guru_id' => $guruA->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'semester' => '1',
            'pertemuan_per_minggu' => 2,
        ]);

        $tugas = Tugas::create([
            'kelas_mapel_id' => $kelasMapel->id,
            'judul' => 'Tugas Guru A',
            'deskripsi' => 'Boleh dihapus pemiliknya.',
            'batas_waktu' => now()->addDay(),
            'kategori_nilai' => 'NH',
        ]);

        $this->actingAs($guruA)
            ->delete(route('guru.tugas.destroy', $tugas))
            ->assertRedirect();

        $this->assertDatabaseMissing('tugas', ['id' => $tugas->id]);
    }

    public function test_teacher_grading_updates_pending_count_applies_late_penalty_and_allows_comment_without_score(): void
    {
        [, $guruA, , $kelas, $tahunAjaran] = $this->fixture();
        Pengaturan::setValue('penalty_terlambat_poin', '1');
        $mapel = MataPelajaran::create(['kode' => 'PEN', 'nama_mapel' => 'Penalty Test', 'urutan' => 1]);
        $kelasMapel = KelasMapel::create([
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapel->id,
            'guru_id' => $guruA->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'semester' => '1',
            'pertemuan_per_minggu' => 2,
        ]);
        $tugas = Tugas::create([
            'kelas_mapel_id' => $kelasMapel->id,
            'judul' => 'Tugas Dengan Penalti',
            'batas_waktu' => now()->subDays(2),
            'kategori_nilai' => 'NH',
        ]);
        $studentUser = $this->createUser('siswa-penalty', 'Siswa Penalty', 'siswa');
        $student = Siswa::create([
            'user_id' => $studentUser->id,
            'nis' => '9201',
            'kelas_id' => $kelas->id,
            'status' => 'aktif',
        ]);
        $commentOnlyUser = $this->createUser('siswa-comment', 'Siswa Comment', 'siswa');
        $commentOnlyStudent = Siswa::create([
            'user_id' => $commentOnlyUser->id,
            'nis' => '9202',
            'nomor_whatsapp' => '6281234567890', 'whatsapp_opt_in' => true,
            'kelas_id' => $kelas->id,
            'status' => 'aktif',
        ]);
        // Siswa A kumpul telat 1 hari (deadline 2 hari lalu).
        PengumpulanTugas::create([
            'tugas_id' => $tugas->id,
            'siswa_id' => $student->id,
            'status' => 'sudah',
            'tanggal_kumpul' => now()->subDay(),
        ]);
        // Siswa B sudah kumpul dan menunggu komentar guru.
        PengumpulanTugas::create([
            'tugas_id' => $tugas->id,
            'siswa_id' => $commentOnlyStudent->id,
            'status' => 'sudah',
            'tanggal_kumpul' => now()->subDays(3),
        ]);

        // 1) Penilaian: telat 1 hari, penalti 1 poin per hari.
        $this->actingAs($guruA)
            ->post(route('guru.tugas.nilai', [$kelasMapel, $tugas, $student]), [
                'nilai' => 90,
                'catatan' => 'Bagus, tapi terlambat.',
            ])
            ->assertRedirect();

        $graded = PengumpulanTugas::where('tugas_id', $tugas->id)
            ->where('siswa_id', $student->id)
            ->firstOrFail();
        $this->assertSame('dinilai', $graded->status);
        $this->assertSame('89.00', $graded->nilai);
        $this->assertSame('90.00', $graded->nilai_sebelum_penalty);
        $this->assertSame('1.00', $graded->penalty_terlambat);

        // 2) Komentar tanpa nilai: status menjadi perlu_perbaikan.
        $this->actingAs($guruA)
            ->post(route('guru.tugas.nilai', [$kelasMapel, $tugas, $commentOnlyStudent]), [
                'nilai' => '',
                'catatan' => 'Jawaban salah, silakan diperbaiki.',
            ])
            ->assertRedirect();

        $commentOnly = PengumpulanTugas::where('tugas_id', $tugas->id)
            ->where('siswa_id', $commentOnlyStudent->id)
            ->firstOrFail();
        $this->assertSame('perlu_perbaikan', $commentOnly->status);
        $this->assertNull($commentOnly->nilai);
        $this->assertSame('Jawaban salah, silakan diperbaiki.', $commentOnly->catatan);

        // 3) Antrian perlu dinilai kosong: keduanya sudah ditindaklanjuti guru.
        $this->actingAs($guruA)
            ->get(route('guru.tugas.list', $kelasMapel))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('tugas.0.perlu_dinilai', 0)
            );

        // 4) Siswa dapat kumpul ulang saat perlu_perbaikan; deadline sudah lewat → terlambat.
        $this->actingAs($commentOnlyUser)
            ->post(route('siswa.tugas.kumpul', $tugas), [
                'teks_jawaban' => 'Jawaban yang sudah diperbaiki.',
            ])
            ->assertRedirect();

        $commentOnly->refresh();
        $this->assertSame('terlambat', $commentOnly->status);
        $this->assertSame('Jawaban yang sudah diperbaiki.', $commentOnly->teks_jawaban);

        // 5) Setelah kumpul ulang, kembali masuk antrian perlu dinilai.
        $this->actingAs($guruA)
            ->get(route('guru.tugas.list', $kelasMapel))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('tugas.0.perlu_dinilai', 1)
            );
    }

    public function test_teacher_direct_grade_on_unsubmitted_task_applies_late_penalty(): void
    {
        [, $guru, , $kelas, $tahunAjaran] = $this->fixture();
        Pengaturan::setValue('penalty_terlambat_poin', '2');
        $mapel = MataPelajaran::create(['kode' => 'DIR', 'nama_mapel' => 'Hafalan', 'urutan' => 1]);
        $kelasMapel = KelasMapel::create(['kelas_id' => $kelas->id, 'mapel_id' => $mapel->id, 'guru_id' => $guru->id, 'tahun_ajaran_id' => $tahunAjaran->id, 'semester' => '1', 'pertemuan_per_minggu' => 1]);
        $studentUser = $this->createUser('siswa-direct-grade', 'Siswa Direct Grade', 'siswa');
        $student = Siswa::create(['user_id' => $studentUser->id, 'nis' => '9299', 'kelas_id' => $kelas->id, 'status' => 'aktif']);
        $tugas = Tugas::create(['kelas_mapel_id' => $kelasMapel->id, 'judul' => 'Hafalan', 'batas_waktu' => now()->subDays(3), 'kategori_nilai' => 'NH']);

        $this->actingAs($guru)->postJson(route('guru.tugas.nilai', [$kelasMapel, $tugas, $student]), ['nilai' => 90])
            ->assertOk()
            ->assertJsonPath('penalty_terlambat', '6.00')
            ->assertJsonPath('nilai', '84.00');
        $this->assertDatabaseHas('pengumpulan_tugas', [
            'tugas_id' => $tugas->id, 'siswa_id' => $student->id,
            'status' => 'dinilai', 'nilai_sebelum_penalty' => 90, 'nilai' => 84,
        ]);
    }

    public function test_teacher_can_prepare_whatsapp_message_and_record_log(): void
    {
        [, $guru, , $kelas, $tahunAjaran] = $this->fixture();
        $mapel = MataPelajaran::create(['kode' => 'WA', 'nama_mapel' => 'WhatsApp', 'urutan' => 1]);
        $kelasMapel = KelasMapel::create([
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapel->id,
            'guru_id' => $guru->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'semester' => '1',
            'pertemuan_per_minggu' => 1,
        ]);
        $tugas = Tugas::create(['kelas_mapel_id' => $kelasMapel->id, 'judul' => 'Pengingat', 'batas_waktu' => now()->subDays(2), 'kategori_nilai' => 'NH']);
        $studentUser = $this->createUser('siswa-wa', 'Siswa WA', 'siswa');
        $student = Siswa::create([
            'user_id' => $studentUser->id,
            'nis' => '9301',
            'kelas_id' => $kelas->id,
            'status' => 'aktif',
            'nomor_whatsapp' => '081234567890',
            'whatsapp_opt_in' => true,
        ]);

        $response = $this->actingAs($guru)
            ->getJson(route('guru.tugas.whatsapp', [$kelasMapel, $tugas, $student]))
            ->assertOk();
        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $response->json('url'));

        $this->assertDatabaseHas('whatsapp_message_logs', [
            'siswa_id' => $student->id,
            'guru_id' => $guru->id,
            'jenis_template' => 'tugas_terlambat',
        ]);
        $this->assertSame('6281234567890', $student->fresh()->nomor_whatsapp);
        $this->assertInstanceOf(WhatsAppMessageLog::class, WhatsAppMessageLog::latest()->first());
    }

    public function test_admin_student_password_reset_uses_default_password(): void
    {
        [$admin, , , $kelas] = $this->fixture();
        $studentUser = $this->createUser('siswa-reset', 'Siswa Reset', 'siswa');
        $siswa = Siswa::create([
            'user_id' => $studentUser->id,
            'nis' => '9001',
            'kelas_id' => $kelas->id,
            'status' => 'aktif',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.kelas-siswa.reset-password', $siswa))
            ->assertRedirect()
            ->assertSessionHas('student_password');

        $payload = session('student_password');
        $studentUser->refresh();

        $this->assertIsArray($payload);
        $this->assertSame(User::DEFAULT_PASSWORD, $payload['password']);
        $this->assertTrue(Hash::check($payload['password'], $studentUser->password));
        $this->assertTrue((bool) $studentUser->is_password_default);
    }

    public function test_student_cannot_use_admin_student_reset_endpoint(): void
    {
        [, , , $kelas] = $this->fixture();
        $studentUser = $this->createUser('siswa-attacker', 'Siswa Attacker', 'siswa');
        $targetUser = $this->createUser('siswa-target', 'Siswa Target', 'siswa');
        $target = Siswa::create([
            'user_id' => $targetUser->id,
            'nis' => '9002',
            'kelas_id' => $kelas->id,
            'status' => 'aktif',
        ]);

        $this->actingAs($studentUser)
            ->post(route('admin.kelas-siswa.reset-password', $target))
            ->assertForbidden();
    }

    private function fixture(): array
    {
        Role::create(['nama_role' => 'admin']);
        Role::create(['nama_role' => 'guru']);
        Role::create(['nama_role' => 'siswa']);
        Role::create(['nama_role' => 'kepala_sekolah']);

        $admin = $this->createUser('admin-phase10', 'Admin Phase 10', 'admin');
        $guruA = $this->createUser('guru-a-phase10', 'Guru A', 'guru');
        $guruB = $this->createUser('guru-b-phase10', 'Guru B', 'guru');
        $kelas = Kelas::create(['tingkat' => 'VII', 'nama_kelas' => 'A']);
        $tahunAjaran = TahunAjaran::create(['tahun' => '2026/2027', 'is_active' => true]);

        return [$admin, $guruA, $guruB, $kelas, $tahunAjaran];
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
