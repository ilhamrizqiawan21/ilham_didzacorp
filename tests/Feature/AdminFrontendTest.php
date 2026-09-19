<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminFrontendTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_announcement_and_recap_menus_use_inertia_pages(): void
    {
        $role = Role::create(['id' => 1, 'nama_role' => 'admin']);
        $user = User::create([
            'username' => 'admin-test',
            'email' => 'admin-test@example.test',
            'password' => Hash::make('secret'),
            'is_password_default' => false,
            'nama_lengkap' => 'Admin Test',
            'nip_nis' => null,
            'jenis_kelamin' => null,
            'foto' => null,
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->actingAs($user)->get('/admin/pengumuman')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Pengumuman/Index'));

        foreach (['absensi', 'nilai', 'sikap', 'tugas'] as $report) {
            $this->actingAs($user)->get('/admin/rekap/' . $report)
                ->assertOk()
                ->assertInertia(fn ($page) => $page->component('Admin/Rekap')->where('type', $report));
        }
    }
}
