<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\CalendarEvent;
use App\Models\ChatMessage;
use App\Models\JadwalMengajar;
use App\Models\Kelas;
use App\Models\KelasDaring;
use App\Models\KelasMapel;
use App\Models\MataPelajaran;
use App\Models\PengumpulanTugas;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use App\Models\User;
use App\Models\WhatsAppMessageLog;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class FreeFeaturePackageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_kepsek_can_open_teacher_performance_dashboard(): void
    {
        [$admin, $guru, , $kelas, $tahunAjaran, $kepsek] = $this->fixture();
        $kelasMapel = $this->course($guru, $kelas, $tahunAjaran, 'KPI');
        $siswaUser = $this->createUser('siswa-kpi', 'Siswa KPI', 'siswa');
        $siswa = Siswa::create(['user_id' => $siswaUser->id, 'nis' => '8001', 'kelas_id' => $kelas->id, 'status' => 'aktif']);
        $tugas = Tugas::create(['kelas_mapel_id' => $kelasMapel->id, 'judul' => 'Tugas KPI', 'batas_waktu' => now()->addDay(), 'kategori_nilai' => 'NH']);
        PengumpulanTugas::create([
            'tugas_id' => $tugas->id,
            'siswa_id' => $siswa->id,
            'status' => 'dinilai',
            'nilai' => 88,
            'catatan' => 'Bagus.',
            'tanggal_kumpul' => now()->subDay(),
            'graded_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.performa-guru'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('PerformaGuru/Index')
                ->where('teachers.0.nama', 'Guru A')
                ->has('exportUrls.excel')
            );

        $this->actingAs($kepsek)
            ->get(route('kepsek.performa-guru'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('PerformaGuru/Index'));
    }

    public function test_whatsapp_late_task_reminder_excludes_submitted_tasks(): void
    {
        [, $guru, , $kelas, $tahunAjaran] = $this->fixture();
        $kelasMapel = $this->course($guru, $kelas, $tahunAjaran, 'WAT');
        $siswaUser = $this->createUser('siswa-wa-filter', 'Siswa WA Filter', 'siswa');
        $siswa = Siswa::create([
            'user_id' => $siswaUser->id,
            'nis' => '8401',
            'kelas_id' => $kelas->id,
            'status' => 'aktif',
            'nomor_whatsapp' => '6281234567890',
            'whatsapp_opt_in' => true,
        ]);
        $submitted = Tugas::create([
            'kelas_mapel_id' => $kelasMapel->id,
            'judul' => 'Tugas Sudah Terkirim',
            'batas_waktu' => now()->subDays(3),
            'kategori_nilai' => 'NH',
        ]);
        $pending = Tugas::create([
            'kelas_mapel_id' => $kelasMapel->id,
            'judul' => 'Tugas Belum Terkirim',
            'batas_waktu' => now()->subDays(2),
            'kategori_nilai' => 'NH',
        ]);
        PengumpulanTugas::create([
            'tugas_id' => $submitted->id,
            'siswa_id' => $siswa->id,
            'status' => PengumpulanTugas::STATUS_TERLAMBAT,
            'tanggal_kumpul' => now()->subDay(),
        ]);

        $result = app(WhatsAppService::class)->prepareLateTaskMessage($siswa, $pending, $guru->id);

        $this->assertStringNotContainsString($submitted->judul, $result['message']);
        $this->assertStringContainsString($pending->judul, $result['message']);
        $this->assertDatabaseHas('whatsapp_message_logs', [
            'id' => $result['log_id'],
            'siswa_id' => $siswa->id,
            'guru_id' => $guru->id,
        ]);
        $this->assertSame([$pending->id], WhatsAppMessageLog::findOrFail($result['log_id'])->tugas_ids);

        $submittedResult = app(WhatsAppService::class)->prepareLateTaskMessage($siswa, $submitted, $guru->id);

        $this->assertStringNotContainsString($submitted->judul, $submittedResult['message']);
        $this->assertSame([$pending->id], WhatsAppMessageLog::findOrFail($submittedResult['log_id'])->tugas_ids);
    }

    public function test_guru_schedule_rejects_teacher_and_class_slot_conflicts(): void
    {
        [, $guruA, $guruB, $kelas, $tahunAjaran] = $this->fixture();
        $courseA = $this->course($guruA, $kelas, $tahunAjaran, 'JDA');
        $courseB = $this->course($guruB, $kelas, $tahunAjaran, 'JDB');

        $this->actingAs($guruA)
            ->post(route('guru.jadwal-mengajar.store'), [
                'kelas_mapel_id' => $courseA->id,
                'hari' => 1,
                'pelajaran_ke' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('jadwal_mengajar', [
            'guru_id' => $guruA->id,
            'kelas_id' => $kelas->id,
            'kelas_mapel_id' => $courseA->id,
            'hari' => 1,
            'pelajaran_ke' => 1,
        ]);

        $this->actingAs($guruA)
            ->post(route('guru.jadwal-mengajar.store'), [
                'kelas_mapel_id' => $courseA->id,
                'hari' => 1,
                'pelajaran_ke' => 1,
            ])
            ->assertSessionHasErrors('pelajaran_ke');

        $this->actingAs($guruB)
            ->post(route('guru.jadwal-mengajar.store'), [
                'kelas_mapel_id' => $courseB->id,
                'hari' => 1,
                'pelajaran_ke' => 1,
            ])
            ->assertSessionHasErrors('pelajaran_ke');
    }

    public function test_attendance_dates_follow_teacher_schedule_and_skip_holidays(): void
    {
        [, $guru, , $kelas, $tahunAjaran] = $this->fixture();
        $kelasMapel = $this->course($guru, $kelas, $tahunAjaran, 'ABS');
        $siswaUser = $this->createUser('siswa-absensi', 'Siswa Absensi', 'siswa');
        Siswa::create(['user_id' => $siswaUser->id, 'nis' => '8101', 'kelas_id' => $kelas->id, 'status' => 'aktif']);
        JadwalMengajar::create([
            'guru_id' => $guru->id,
            'kelas_id' => $kelas->id,
            'kelas_mapel_id' => $kelasMapel->id,
            'hari' => 1,
            'pelajaran_ke' => 1,
        ]);
        CalendarEvent::create([
            'title' => 'Libur',
            'event_date' => '2026-09-07',
            'is_holiday' => true,
            'scope' => 'school',
        ]);

        $this->actingAs($guru)
            ->get(route('guru.absensi.index', ['kelas_mapel_id' => $kelasMapel->id, 'bulan' => '2026-09']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Guru/Absensi/Index')
                ->where('selected.has_schedule', true)
                ->where('weeks.0.date', '2026-09-14')
                ->where('weeks.0.title', 'Senin P1')
                ->where('weeks.1.date', '2026-09-21')
                ->where('weeks.1.title', 'Senin P2')
                ->where('weeks.2.date', '2026-09-28')
                ->where('weeks.2.title', 'Senin P3')
            );
    }

    public function test_attendance_date_scope_updates_existing_record(): void
    {
        [, $guru, , $kelas, $tahunAjaran] = $this->fixture();
        $course = $this->course($guru, $kelas, $tahunAjaran, 'DATE');
        $user = $this->createUser('siswa-date', 'Siswa Date', 'siswa');
        $student = Siswa::create(['user_id' => $user->id, 'nis' => '8199', 'kelas_id' => $kelas->id, 'status' => 'aktif']);
        $scope = ['siswa_id' => $student->id, 'kelas_mapel_id' => $course->id, 'tanggal' => '2026-09-07'];

        $attendance = Absensi::create([...$scope, 'status' => 'hadir']);
        $updated = Absensi::updateOrCreate($scope, ['status' => 'izin']);

        $this->assertSame($attendance->id, $updated->id);
        $this->assertDatabaseCount('absensi', 1);
        $this->assertDatabaseHas('absensi', [...$scope, 'status' => 'izin']);
    }

    public function test_guru_dashboard_trends_are_aggregated_by_month(): void
    {
        [, $guru, , $kelas, $tahunAjaran] = $this->fixture();
        $kelasMapel = $this->course($guru, $kelas, $tahunAjaran, 'TRG');
        $siswaUser = $this->createUser('siswa-tren-guru', 'Siswa Tren Guru', 'siswa');
        $siswa = Siswa::create([
            'user_id' => $siswaUser->id,
            'nis' => '8151',
            'kelas_id' => $kelas->id,
            'status' => 'aktif',
        ]);
        $bulanLalu = Carbon::create(
            now()->month >= 7 ? now()->year : now()->year - 1,
            7,
            1
        );

        Absensi::create([
            'siswa_id' => $siswa->id,
            'kelas_mapel_id' => $kelasMapel->id,
            'tanggal' => $bulanLalu->copy()->day(3),
            'status' => 'hadir',
        ]);
        Absensi::create([
            'siswa_id' => $siswa->id,
            'kelas_mapel_id' => $kelasMapel->id,
            'tanggal' => $bulanLalu->copy()->day(20),
            'status' => 'alpha',
        ]);

        $tugasA = Tugas::create([
            'kelas_mapel_id' => $kelasMapel->id,
            'judul' => 'Tugas Tren A',
            'batas_waktu' => $bulanLalu->copy()->day(10),
            'kategori_nilai' => 'NH',
        ]);
        $tugasB = Tugas::create([
            'kelas_mapel_id' => $kelasMapel->id,
            'judul' => 'Tugas Tren B',
            'batas_waktu' => $bulanLalu->copy()->day(24),
            'kategori_nilai' => 'NH',
        ]);
        PengumpulanTugas::create([
            'tugas_id' => $tugasA->id,
            'siswa_id' => $siswa->id,
            'status' => 'dinilai',
            'nilai' => 85,
            'tanggal_kumpul' => $bulanLalu->copy()->day(8),
        ]);
        PengumpulanTugas::create([
            'tugas_id' => $tugasB->id,
            'siswa_id' => $siswa->id,
            'status' => 'terlambat',
            'tanggal_kumpul' => $bulanLalu->copy()->day(26),
        ]);

        $this->actingAs($guru)
            ->get(route('guru.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('kehadiranChart', 12)
                ->where('kehadiranChart.0.bulan', '2026-07')
                ->where('kehadiranChart.0.total', 2)
                ->where('kehadiranChart.11.bulan', '2027-06')
                ->where('pengumpulanTugasChart.0.bulan', '2026-07')
                ->where('pengumpulanTugasChart.0.total', 2)
                ->where('pengumpulanTugasChart.0.collected', 2)
            );
    }

    public function test_online_class_requires_schedule_and_is_visible_to_students(): void
    {
        [, $guru, , $kelas, $tahunAjaran] = $this->fixture();
        $tanggalKelasDaring = now()->next(Carbon::MONDAY)->toDateString();
        $kelasMapel = $this->course($guru, $kelas, $tahunAjaran, 'DRG');
        $siswaUser = $this->createUser('siswa-daring', 'Siswa Daring', 'siswa');
        Siswa::create(['user_id' => $siswaUser->id, 'nis' => '8201', 'kelas_id' => $kelas->id, 'status' => 'aktif', 'nomor_whatsapp' => '6281234567890', 'whatsapp_opt_in' => true]);
        JadwalMengajar::create([
            'guru_id' => $guru->id,
            'kelas_id' => $kelas->id,
            'kelas_mapel_id' => $kelasMapel->id,
            'hari' => 1,
            'pelajaran_ke' => 2,
        ]);

        $this->actingAs($guru)
            ->post(route('guru.kelas-daring.store'), [
                'kelas_mapel_id' => $kelasMapel->id,
                'judul' => 'Diskusi Daring',
                'tanggal' => $tanggalKelasDaring,
                'pelajaran_ke' => 2,
                'meeting_url' => 'https://meet.google.com/abc-defg-hij',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('kelas_daring', [
            'kelas_mapel_id' => $kelasMapel->id,
            'judul' => 'Diskusi Daring',
            'status' => 'terjadwal',
        ]);

        $this->actingAs($siswaUser)
            ->get(route('siswa.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('kelasDaring.0.judul', 'Diskusi Daring')
                ->where('kelasDaring.0.meeting_url', 'https://meet.google.com/abc-defg-hij')
            );
    }

    public function test_student_dashboard_task_status_matches_submission_status(): void
    {
        [, $guru, , $kelas, $tahunAjaran] = $this->fixture();
        $course = $this->course($guru, $kelas, $tahunAjaran, 'STATUS');
        $user = $this->createUser('siswa-status', 'Siswa Status', 'siswa');
        $student = Siswa::create(['user_id' => $user->id, 'nis' => '8299', 'kelas_id' => $kelas->id, 'status' => 'aktif', 'nomor_whatsapp' => '6281234567890', 'whatsapp_opt_in' => true]);
        $task = Tugas::create(['kelas_mapel_id' => $course->id, 'judul' => 'Status tugas', 'batas_waktu' => now()->addDay(), 'kategori_nilai' => 'NH']);
        $submission = PengumpulanTugas::create(['tugas_id' => $task->id, 'siswa_id' => $student->id, 'status' => 'belum']);

        foreach (['belum', ...PengumpulanTugas::STATUS_SUBMITTED] as $status) {
            $submission->update(['status' => $status]);
            $completed = $status !== 'belum';
            $this->actingAs($user)->get(route('siswa.dashboard'))
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->where('stats.tugas_selesai', (int) $completed)
                    ->where('tugasTerbaru.0.selesai', $completed)
                    ->where('tugasTerbaru.0.show_url', route('siswa.tugas.show', $task))
                );
        }
    }

    public function test_student_can_open_schedule_and_online_class_menus(): void
    {
        [, $guru, , $kelas, $tahunAjaran] = $this->fixture();
        $kelasMapel = $this->course($guru, $kelas, $tahunAjaran, 'MNS');
        $siswaUser = $this->createUser('siswa-menu-baru', 'Siswa Menu Baru', 'siswa');
        Siswa::create(['user_id' => $siswaUser->id, 'nis' => '8251', 'kelas_id' => $kelas->id, 'status' => 'aktif', 'nomor_whatsapp' => '6281234567890', 'whatsapp_opt_in' => true]);
        JadwalMengajar::create([
            'guru_id' => $guru->id,
            'kelas_id' => $kelas->id,
            'kelas_mapel_id' => $kelasMapel->id,
            'hari' => 1,
            'pelajaran_ke' => 2,
        ]);
        KelasDaring::create([
            'guru_id' => $guru->id,
            'kelas_mapel_id' => $kelasMapel->id,
            'judul' => 'Sesi dari Menu',
            'tanggal' => now()->addWeek()->toDateString(),
            'pelajaran_ke' => 2,
            'meeting_url' => 'https://meet.google.com/menu-baru',
            'status' => 'terjadwal',
        ]);

        $this->actingAs($siswaUser)
            ->get(route('siswa.jadwal-pelajaran'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Siswa/Jadwal/Index')
                ->where('summary.total_jadwal', 1)
                ->where('days.0.slots.1.course.mata_pelajaran', 'Mapel MNS')
            );

        $this->actingAs($siswaUser)
            ->get(route('siswa.kelas-daring'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Siswa/KelasDaring/Index')
                ->where('sessions.0.judul', 'Sesi dari Menu')
                ->where('sessions.0.meeting_url', 'https://meet.google.com/menu-baru')
            );
    }

    public function test_student_can_upload_and_delete_profile_photo(): void
    {
        Storage::fake('public');
        $this->seedRoles();
        $user = $this->createUser('siswa-foto', 'Siswa Foto', 'siswa');

        $this->actingAs($user)
            ->post(route('siswa.pengaturan.foto'), [
                'foto' => UploadedFile::fake()->image('avatar.jpg', 320, 320),
            ])
            ->assertRedirect();

        $user->refresh();
        $this->assertNotNull($user->foto);
        Storage::disk('public')->assertExists($user->foto);

        $oldPath = $user->foto;

        $this->actingAs($user)
            ->delete(route('siswa.pengaturan.foto.delete'))
            ->assertRedirect();

        $user->refresh();
        $this->assertNull($user->foto);
        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_profile_photo_is_shared_to_layout_and_chat_messages(): void
    {
        Storage::fake('public');
        [, $guru, , $kelas, $tahunAjaran] = $this->fixture();
        $kelasMapel = $this->course($guru, $kelas, $tahunAjaran, 'CHT');
        $siswaUser = $this->createUser('siswa-chat-foto', 'Siswa Chat Foto', 'siswa');
        Siswa::create(['user_id' => $siswaUser->id, 'nis' => '8301', 'kelas_id' => $kelas->id, 'status' => 'aktif', 'nomor_whatsapp' => '6281234567890', 'whatsapp_opt_in' => true]);

        $guru->update(['foto' => 'avatars/guru-chat.jpg']);
        $siswaUser->update(['foto' => 'avatars/siswa-chat.jpg']);

        ChatMessage::create([
            'user_id' => $guru->id,
            'kelas_mapel_id' => $kelasMapel->id,
            'message' => 'Selamat pagi.',
        ]);

        $this->actingAs($siswaUser)
            ->get(route('siswa.chat.show', $kelasMapel))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Siswa/Chat/Show')
                ->where('auth.user.foto_url', Storage::disk('public')->url('avatars/siswa-chat.jpg'))
                ->where('messages.0.avatar_url', Storage::disk('public')->url('avatars/guru-chat.jpg'))
            );
    }

    private function fixture(): array
    {
        $this->seedRoles();

        $admin = $this->createUser('admin-free', 'Admin Free', 'admin');
        $guruA = $this->createUser('guru-a-free', 'Guru A', 'guru');
        $guruB = $this->createUser('guru-b-free', 'Guru B', 'guru');
        $kepsek = $this->createUser('kepsek-free', 'Kepala Sekolah', 'kepala_sekolah');
        $kelas = Kelas::create(['tingkat' => 'VII', 'nama_kelas' => 'A']);
        $tahunAjaran = TahunAjaran::create(['tahun' => '2026/2027', 'is_active' => true]);

        return [$admin, $guruA, $guruB, $kelas, $tahunAjaran, $kepsek];
    }

    private function seedRoles(): void
    {
        foreach (['admin', 'guru', 'siswa', 'kepala_sekolah'] as $role) {
            Role::firstOrCreate(['nama_role' => $role]);
        }
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

    private function course(User $guru, Kelas $kelas, TahunAjaran $tahunAjaran, string $kode): KelasMapel
    {
        $mapel = MataPelajaran::create([
            'kode' => $kode,
            'nama_mapel' => 'Mapel '.$kode,
            'urutan' => 1,
        ]);

        return KelasMapel::create([
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapel->id,
            'guru_id' => $guru->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'semester' => '1',
            'pertemuan_per_minggu' => 1,
        ]);
    }
}
