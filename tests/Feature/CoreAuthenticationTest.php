<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\KelasMapel;
use App\Models\MataPelajaran;
use App\Models\Pengumuman;
use App\Models\Role;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CoreAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();

        Route::middleware(['auth', 'role:admin'])
            ->get('/__tests__/admin-only', fn () => response('ok'))
            ->name('tests.admin-only');
    }

    public function test_login_by_username_redirects_to_role_dashboard(): void
    {
        $user = $this->makeUser('admin', 'admin-test');

        $response = $this->post(route('login.post'), [
            'username' => $user->username,
            'password' => 'secret-password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_redirects_each_role_to_its_dashboard(): void
    {
        $cases = [
            'admin' => 'admin.dashboard',
            'guru' => 'guru.dashboard',
            'siswa' => 'siswa.dashboard',
            'kepala_sekolah' => 'kepsek.dashboard',
        ];

        foreach ($cases as $role => $routeName) {
            $user = $this->makeUser($role, "redirect-{$role}");

            $this->post(route('login.post'), [
                'username' => $user->username,
                'password' => 'secret-password',
            ])->assertRedirect(route($routeName));

            Auth::logout();
            session()->flush();
        }
    }

    public function test_inactive_user_cannot_remain_authenticated(): void
    {
        $user = $this->makeUser('admin', 'inactive-admin', false);

        $response = $this->post(route('login.post'), [
            'username' => $user->username,
            'password' => 'secret-password',
        ]);

        $response->assertSessionHas('error', 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator.');
        $this->assertGuest();
    }

    public function test_role_middleware_rejects_authenticated_user_with_wrong_role(): void
    {
        $user = $this->makeUser('guru', 'guru-test');

        $response = $this->actingAs($user)->get('/__tests__/admin-only');

        $response->assertForbidden();
    }

    public function test_role_middleware_accepts_authenticated_user_with_matching_role(): void
    {
        $user = $this->makeUser('admin', 'admin-access');

        $response = $this->actingAs($user)->get('/__tests__/admin-only');

        $response->assertOk()->assertSee('ok');
    }

    public function test_external_intended_url_is_not_used_after_login(): void
    {
        $user = $this->makeUser('admin', 'redirect-admin');

        $this->withSession(['url.intended' => 'https://attacker.example/admin/panel']);

        $response = $this->post(route('login.post'), [
            'username' => $user->username,
            'password' => 'secret-password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_cross_role_intended_url_is_not_used_after_login(): void
    {
        $user = $this->makeUser('guru', 'redirect-guru-cross-role');

        $this->withSession(['url.intended' => route('admin.dashboard')]);

        $response = $this->post(route('login.post'), [
            'username' => $user->username,
            'password' => 'secret-password',
        ]);

        $response->assertRedirect(route('guru.dashboard'));
    }

    public function test_login_shows_only_public_announcements_with_public_attachment(): void
    {
        Storage::fake('local');

        $teacher = $this->makeUser('guru', 'public-board-teacher');
        Storage::disk('local')->put('pengumuman-public/fixture/info.pdf', 'informasi publik');

        Pengumuman::create([
            'judul' => 'Daftar Hasil Ulangan',
            'isi' => 'Silakan lihat lampiran.',
            'target' => 'kelas_mapel',
            'target_kelas' => null,
            'kelas_mapel_id' => null,
            'is_public_login' => true,
            'public_file_name' => 'hasil-ulangan.pdf',
            'public_file_path' => 'pengumuman-public/fixture/info.pdf',
            'public_file_mime' => 'application/pdf',
            'public_file_size' => 18,
            'created_by' => $teacher->id,
            'created_at' => now(),
        ]);

        Pengumuman::create([
            'judul' => 'Pengumuman Internal',
            'isi' => 'Tidak untuk halaman login.',
            'target' => 'kelas_mapel',
            'is_public_login' => false,
            'created_by' => $teacher->id,
            'created_at' => now()->subMinute(),
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Auth/Login')
                ->has('publicAnnouncements', 1)
                ->where('publicAnnouncements.0.judul', 'Daftar Hasil Ulangan')
                ->where('publicAnnouncements.0.attachment.name', 'hasil-ulangan.pdf')
            );
    }

    public function test_public_attachment_download_requires_public_login_flag(): void
    {
        Storage::fake('local');

        $teacher = $this->makeUser('guru', 'public-download-teacher');
        Storage::disk('local')->put('pengumuman-public/fixture/info.pdf', 'informasi publik');
        Storage::disk('local')->put('pengumuman-public/fixture/private.pdf', 'informasi internal');

        $public = Pengumuman::create([
            'judul' => 'Lampiran Publik',
            'isi' => 'Bisa diunduh.',
            'target' => 'kelas_mapel',
            'is_public_login' => true,
            'public_file_name' => 'publik.pdf',
            'public_file_path' => 'pengumuman-public/fixture/info.pdf',
            'created_by' => $teacher->id,
            'created_at' => now(),
        ]);

        $private = Pengumuman::create([
            'judul' => 'Lampiran Internal',
            'isi' => 'Tidak bisa diunduh dari publik.',
            'target' => 'kelas_mapel',
            'is_public_login' => false,
            'public_file_name' => 'internal.pdf',
            'public_file_path' => 'pengumuman-public/fixture/private.pdf',
            'created_by' => $teacher->id,
            'created_at' => now(),
        ]);

        $this->get(route('public-pengumuman.attachment', $public))
            ->assertOk()
            ->assertDownload('publik.pdf');

        $this->get(route('public-pengumuman.attachment', $private))
            ->assertNotFound();
    }

    public function test_guru_can_publish_login_board_announcement_with_attachment(): void
    {
        Storage::fake('local');

        $teacher = $this->makeUser('guru', 'public-upload-teacher');
        $kelas = Kelas::create(['tingkat' => 'VII', 'nama_kelas' => 'A']);
        $mapel = MataPelajaran::create(['kode' => 'MTK', 'nama_mapel' => 'Matematika']);
        $tahunAjaran = TahunAjaran::create(['tahun' => '2026/2027', 'is_active' => true]);
        KelasMapel::create([
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapel->id,
            'guru_id' => $teacher->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'semester' => '1',
        ]);

        $this->actingAs($teacher)->post(route('guru.pengumuman.store'), [
            'judul' => 'Siswa Belum Mengumpulkan Tugas',
            'isi' => 'Daftar lengkap ada di lampiran.',
            'target' => 'kelas_mapel',
            'target_kelas_ids' => [$kelas->id],
            'is_public_login' => '1',
            'public_file' => UploadedFile::fake()->create('rekap-tugas.pdf', 80, 'application/pdf'),
        ])->assertRedirect(route('guru.pengumuman.index'));

        $pengumuman = Pengumuman::where('judul', 'Siswa Belum Mengumpulkan Tugas')->firstOrFail();

        $this->assertTrue($pengumuman->is_public_login);
        $this->assertSame('rekap-tugas.pdf', $pengumuman->public_file_name);
        $this->assertNotNull($pengumuman->public_file_path);
        Storage::disk('local')->assertExists($pengumuman->public_file_path);
    }

    private function seedRoles(): void
    {
        Role::create(['id' => 1, 'nama_role' => 'admin']);
        Role::create(['id' => 2, 'nama_role' => 'guru']);
        Role::create(['id' => 3, 'nama_role' => 'siswa']);
        Role::create(['id' => 4, 'nama_role' => 'kepala_sekolah']);
    }

    private function makeUser(string $role, string $username, bool $active = true): User
    {
        $roleId = Role::where('nama_role', $role)->value('id');

        return User::create([
            'username' => $username,
            'email' => $username.'@example.test',
            'password' => Hash::make('secret-password'),
            'is_password_default' => false,
            'nama_lengkap' => strtoupper($username),
            'nip_nis' => null,
            'jenis_kelamin' => null,
            'foto' => null,
            'role_id' => $roleId,
            'is_active' => $active,
        ]);
    }
}
