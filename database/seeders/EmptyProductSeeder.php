<?php

namespace Database\Seeders;

use App\Models\Pengaturan;
use App\Models\Role;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmptyProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedRoles();
        $this->call(SchoolSettingSeeder::class);
        $this->seedGuru();
        $this->seedAcademicDefaults();
    }

    private function seedRoles(): void
    {
        $roles = [
            ['id' => 2, 'nama_role' => 'guru'],
            ['id' => 3, 'nama_role' => 'siswa'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['id' => $role['id']],
                ['nama_role' => $role['nama_role']]
            );
        }
    }

    private function seedGuru(): void
    {
        $username = env('DEFAULT_GURU_USERNAME') ?: 'guru';
        $email = env('DEFAULT_GURU_EMAIL') ?: 'guru@example.test';
        $password = (string) env('DEFAULT_GURU_PASSWORD', '');
        if (strlen($password) < 12) {
            throw new \RuntimeException('DEFAULT_GURU_PASSWORD wajib diisi dan minimal 12 karakter.');
        }

        User::updateOrCreate(
            ['username' => $username],
            [
                'email' => $email,
                'nama_lengkap' => env('DEFAULT_GURU_NAME') ?: 'Guru Utama',
                'nip_nis' => 'GURU-DEFAULT-001',
                'jenis_kelamin' => null,
                'password' => Hash::make($password),
                'role_id' => 2,
                'is_active' => true,
            ]
        );

    }

    private function seedAcademicDefaults(): void
    {
        TahunAjaran::where('is_active', true)->update(['is_active' => false]);

        $tahunAjaran = TahunAjaran::updateOrCreate(
            ['tahun' => '2026/2027'],
            ['is_active' => true]
        );

        Pengaturan::setValue('tahun_ajaran_aktif', (string) $tahunAjaran->id);
        Pengaturan::setValue('semester_aktif', '1');
        Pengaturan::setValue('penalty_terlambat_poin', '1');
    }
}
