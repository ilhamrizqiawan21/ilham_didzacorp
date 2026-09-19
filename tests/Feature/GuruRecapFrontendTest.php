<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GuruRecapFrontendTest extends TestCase
{
    use RefreshDatabase;

    public function test_guru_recaps_use_inertia_pages(): void
    {
        $role = Role::create(['id' => 2, 'nama_role' => 'guru']);
        $user = User::create([
            'username' => 'guru-rekap-test',
            'email' => 'guru-rekap@example.test',
            'password' => Hash::make('secret'),
            'is_password_default' => false,
            'nama_lengkap' => 'Guru Rekap Test',
            'nip_nis' => null,
            'jenis_kelamin' => null,
            'foto' => null,
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->actingAs($user)->get('/guru/rekap-nilai')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Guru/Rekap/Nilai'));

        $this->actingAs($user)->get('/guru/rekap-sikap')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Guru/Rekap/Sikap'));
    }
}
