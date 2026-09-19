<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\UserController;
use App\Models\Kelas;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use App\Services\SiswaExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class P0AdminHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();

        Route::middleware('web')
            ->post('/__tests__/admin-users-without-role-middleware', [UserController::class, 'store'])
            ->name('tests.admin-users.store-without-role-middleware');
    }

    public function test_json_request_to_admin_route_without_session_returns_401(): void
    {
        $this->getJson(route('admin.dashboard'))
            ->assertUnauthorized()
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_non_manager_json_request_to_admin_route_returns_403(): void
    {
        $siswa = $this->makeUser('siswa', 'siswa-p0');

        $this->actingAs($siswa)
            ->postJson(route('admin.users.store'), [
                'username' => 'staff-baru',
                'nama_lengkap' => 'Staff Baru',
                'role_id' => Role::where('nama_role', 'guru')->value('id'),
            ])
            ->assertForbidden()
            ->assertJson(['message' => 'Forbidden']);
    }

    public function test_controller_guard_blocks_non_manager_even_without_role_middleware(): void
    {
        $siswa = $this->makeUser('siswa', 'siswa-direct-p0');

        $this->actingAs($siswa)
            ->post('/__tests__/admin-users-without-role-middleware', [
                'username' => 'direct-staff',
                'nama_lengkap' => 'Direct Staff',
                'role_id' => Role::where('nama_role', 'guru')->value('id'),
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['username' => 'direct-staff']);
    }

    public function test_admin_can_create_staff_but_cannot_assign_siswa_role_from_staff_form(): void
    {
        $admin = $this->makeUser('admin', 'admin-p0');
        $guruRoleId = Role::where('nama_role', 'guru')->value('id');
        $siswaRoleId = Role::where('nama_role', 'siswa')->value('id');

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'username' => 'guru-baru-p0',
                'nama_lengkap' => 'Guru Baru P0',
                'email' => 'guru-baru-p0@example.test',
                'role_id' => $guruRoleId,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'username' => 'guru-baru-p0',
            'role_id' => $guruRoleId,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'username' => 'siswa-dari-staff-form',
                'nama_lengkap' => 'Siswa Dari Staff Form',
                'role_id' => $siswaRoleId,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.create'))
            ->assertSessionHasErrors('role_id');

        $this->assertDatabaseMissing('users', ['username' => 'siswa-dari-staff-form']);
    }

    public function test_staff_user_input_is_normalized_and_restricted(): void
    {
        $admin = $this->makeUser('admin', 'admin-normalize-p1');
        $guruRoleId = Role::where('nama_role', 'guru')->value('id');

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'username' => ' guru.normalized ',
                'nama_lengkap' => ' Guru Normalized ',
                'email' => ' GURU.NORMALIZED@EXAMPLE.TEST ',
                'nip_nis' => ' 1987-01 ',
                'role_id' => $guruRoleId,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'username' => 'guru.normalized',
            'nama_lengkap' => 'Guru Normalized',
            'email' => 'guru.normalized@example.test',
            'nip_nis' => '1987-01',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'username' => 'invalid username',
                'nama_lengkap' => 'Invalid Username',
                'role_id' => $guruRoleId,
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('username');
    }

    public function test_staff_password_export_does_not_contain_default_password_plaintext(): void
    {
        $admin = $this->makeUser('admin', 'admin-export-p0');
        $this->makeUser('guru', 'guru-default-export-p0', true, User::DEFAULT_PASSWORD, true);

        $response = $this->actingAs($admin)
            ->get(route('admin.users.export.excel'));

        $response->assertOk();
        $this->assertStringNotContainsString(User::DEFAULT_PASSWORD, $response->streamedContent());
    }

    #[RequiresPhpExtension('zip')]
    public function test_student_password_export_does_not_contain_default_password_plaintext(): void
    {
        $kelas = Kelas::create(['tingkat' => 'VII', 'nama_kelas' => 'Export']);
        $studentUser = $this->makeUser('siswa', 'siswa-default-export-p0', true, User::DEFAULT_PASSWORD, true);
        $siswa = Siswa::create([
            'user_id' => $studentUser->id,
            'nis' => 'EXP001',
            'kelas_id' => $kelas->id,
            'status' => 'aktif',
        ]);

        $filePath = app(SiswaExportService::class)->export(
            Siswa::with(['user', 'kelas'])->whereKey($siswa->id)
        );

        try {
            $content = $this->xlsxContent($filePath);

            $this->assertStringNotContainsString(User::DEFAULT_PASSWORD, $content);
            $this->assertStringNotContainsString('Password Default', $content);
        } finally {
            @unlink($filePath);
        }
    }

    public function test_staff_and_student_password_reset_stays_on_default_password(): void
    {
        $admin = $this->makeUser('admin', 'admin-reset-p0');
        $guru = $this->makeUser('guru', 'guru-reset-p0', true, 'old-password', false);
        $kelas = Kelas::create(['tingkat' => 'VII', 'nama_kelas' => 'P0']);
        $studentUser = $this->makeUser('siswa', 'siswa-reset-p0', true, 'old-password', false);
        $siswa = Siswa::create([
            'user_id' => $studentUser->id,
            'nis' => 'P0001',
            'kelas_id' => $kelas->id,
            'status' => 'aktif',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.reset-password', $guru))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->post(route('admin.kelas-siswa.reset-password', $siswa))
            ->assertRedirect()
            ->assertSessionHas('student_password.password', User::DEFAULT_PASSWORD);

        $guru->refresh();
        $studentUser->refresh();

        $this->assertTrue(Hash::check(User::DEFAULT_PASSWORD, $guru->password));
        $this->assertTrue(Hash::check(User::DEFAULT_PASSWORD, $studentUser->password));
        $this->assertTrue((bool) $guru->is_password_default);
        $this->assertTrue((bool) $studentUser->is_password_default);
    }

    private function seedRoles(): void
    {
        Role::create(['id' => 1, 'nama_role' => 'admin']);
        Role::create(['id' => 2, 'nama_role' => 'guru']);
        Role::create(['id' => 3, 'nama_role' => 'siswa']);
        Role::create(['id' => 4, 'nama_role' => 'kepala_sekolah']);
    }

    private function makeUser(
        string $roleName,
        string $username,
        bool $active = true,
        string $password = 'secret-password',
        bool $passwordIsDefault = false
    ): User {
        return User::create([
            'username' => $username,
            'email' => "{$username}@example.test",
            'password' => Hash::make($password),
            'is_password_default' => $passwordIsDefault,
            'nama_lengkap' => strtoupper($username),
            'nip_nis' => null,
            'jenis_kelamin' => null,
            'foto' => null,
            'role_id' => Role::where('nama_role', $roleName)->value('id'),
            'is_active' => $active,
        ]);
    }

    private function xlsxContent(string $filePath): string
    {
        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($filePath));

        $content = '';

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);

            if ($name && str_ends_with($name, '.xml')) {
                $content .= (string) $zip->getFromIndex($index);
            }
        }

        $zip->close();

        return $content;
    }
}
