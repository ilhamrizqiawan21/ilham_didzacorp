<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use App\Support\WhatsAppPhone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPhoneSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['security.require_student_phone' => true]);
    }

    public function test_student_can_access_pages_without_phone_or_password_change_when_requirements_are_disabled(): void
    {
        config(['security.require_student_phone' => false, 'security.force_password_change' => false]);
        $user = $this->student();
        $user->update(['is_password_default' => true]);

        $this->actingAs($user)->get(route('siswa.dashboard'))->assertOk();
        $this->get(route('siswa.nilai.index'))->assertOk();
        $this->get(route('siswa.pengaturan'))->assertOk()->assertInertia(fn ($page) => $page
            ->component('Account/Pengaturan')
            ->where('profile.siswa.phone_required', false)
            ->where('profile.is_password_default', true));
    }

    private function student(): User
    {
        $role = Role::firstOrCreate(['nama_role' => 'siswa']);
        $user = User::create([
            'username' => 'phone-student', 'nama_lengkap' => 'Siswa Telepon',
            'password' => bcrypt('Student#12345'), 'role_id' => $role->id,
            'is_active' => true, 'is_password_default' => false,
        ]);
        Siswa::create(['user_id' => $user->id, 'nis' => 'PHONE1', 'status' => 'aktif']);

        return $user;
    }

    public function test_student_must_complete_phone_without_redirect_loop(): void
    {
        $user = $this->student();
        $this->actingAs($user)->get(route('siswa.dashboard'))->assertRedirect(route('siswa.pengaturan'));
        $this->get(route('siswa.pengaturan'))->assertOk()->assertInertia(fn ($page) => $page
            ->component('Account/Pengaturan')
            ->where('profile.siswa.phone_required', true)
            ->where('profile.siswa.whatsapp_opt_in', false));
        $this->get(route('siswa.profil'))->assertOk();
        $this->getJson(route('siswa.nilai.index'))->assertForbidden()->assertJsonPath('code', 'student_phone_required');
        $this->post(route('siswa.notifikasi.mark-all-read'))->assertRedirect(route('siswa.pengaturan'));
        $this->post(route('logout'))->assertRedirect();
        $this->assertGuest();
    }

    public function test_phone_is_required_and_invalid_input_does_not_change_contact(): void
    {
        $user = $this->student();
        $this->actingAs($user);
        foreach (['', '02112345678', '0812', 'abc081234567890', '+6512345678', '081234567890ext1', str_repeat('1', 33)] as $number) {
            $this->put(route('siswa.pengaturan.telepon'), ['nomor_whatsapp' => $number, 'whatsapp_opt_in' => true])
                ->assertSessionHasErrors('nomor_whatsapp');
            $this->assertNull($user->siswa()->first()->nomor_whatsapp);
        }
    }

    public function test_normalized_phone_and_explicit_consent_unlock_student_routes(): void
    {
        $user = $this->student();
        foreach (['0812 3456-7890', '+62 (812) 3456-7890', '6281234567890'] as $number) {
            $this->actingAs($user)->put(route('siswa.pengaturan.telepon'), [
                'nomor_whatsapp' => $number, 'whatsapp_opt_in' => true, 'user_id' => 999,
            ])->assertSessionHasNoErrors()->assertRedirect(route('siswa.pengaturan'));
            $student = $user->siswa()->first();
            $this->assertSame('6281234567890', $student->nomor_whatsapp);
            $this->assertTrue($student->whatsapp_opt_in);
            $this->assertNotNull($student->whatsapp_opted_in_at);
            $this->actingAs($user->fresh())->get(route('siswa.nilai.index'))->assertOk();
        }
    }

    public function test_consent_is_required_and_timestamp_is_preserved(): void
    {
        $user = $this->student();
        $this->actingAs($user)->put(route('siswa.pengaturan.telepon'), [
            'nomor_whatsapp' => '081234567890', 'whatsapp_opt_in' => true,
        ])->assertSessionHasNoErrors();
        $timestamp = $user->siswa()->first()->whatsapp_opted_in_at;
        $this->assertNotNull($timestamp);
        $this->travel(1)->hours();
        $this->actingAs($user->fresh())->put(route('siswa.pengaturan.telepon'), [
            'nomor_whatsapp' => '081234567890', 'whatsapp_opt_in' => true,
        ])->assertSessionHasNoErrors();
        $this->assertTrue($timestamp->equalTo($user->siswa()->first()->whatsapp_opted_in_at));
        $this->actingAs($user->fresh())->put(route('siswa.pengaturan.telepon'), [
            'nomor_whatsapp' => '081234567890', 'whatsapp_opt_in' => false,
        ])->assertSessionHasErrors('whatsapp_opt_in');
        $this->assertTrue($user->siswa()->first()->whatsapp_opt_in);
        $this->assertTrue($timestamp->equalTo($user->siswa()->first()->whatsapp_opted_in_at));
    }

    public function test_existing_phone_without_consent_does_not_unlock_access(): void
    {
        $user = $this->student();
        $user->siswa()->update(['nomor_whatsapp' => '6281234567890']);
        $this->actingAs($user)->get(route('siswa.dashboard'))->assertRedirect(route('siswa.pengaturan'));
        foreach ([[], ['whatsapp_opt_in' => false], ['whatsapp_opt_in' => 'no']] as $consent) {
            $this->put(route('siswa.pengaturan.telepon'), ['nomor_whatsapp' => '081234567890', ...$consent])
                ->assertSessionHasErrors('whatsapp_opt_in');
        }
        $student = $user->siswa()->first();
        $this->assertFalse($student->whatsapp_opt_in);
        $this->assertNull($student->whatsapp_opted_in_at);
    }

    public function test_contact_endpoint_rejects_other_roles_and_missing_student_record(): void
    {
        $user = $this->student();
        $user->siswa()->delete();
        $this->actingAs($user->fresh())->get(route('siswa.pengaturan'))->assertOk();
        $payload = ['nomor_whatsapp' => '081234567890', 'whatsapp_opt_in' => true];
        $this->put(route('siswa.pengaturan.telepon'), $payload)->assertForbidden();
        $role = Role::create(['nama_role' => 'guru']);
        $user->update(['role_id' => $role->id]);
        $this->actingAs($user->fresh())->put(route('siswa.pengaturan.telepon'), $payload)->assertForbidden();
        $this->get(route('guru.pengaturan'))->assertOk();
    }

    public function test_number_validation_checks_format_not_whatsapp_registration(): void
    {
        $this->assertSame('6281234567890', WhatsAppPhone::normalize('081234567890'));
        $this->assertFalse(WhatsAppPhone::isValid('081234567890'));
        $this->assertFalse(WhatsAppPhone::isValid("6281234567890\n"));
        $this->assertNull(WhatsAppPhone::normalize('6281234567890123'));
    }
}
