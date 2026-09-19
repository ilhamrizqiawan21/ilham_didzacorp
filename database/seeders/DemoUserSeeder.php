<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'username' => 'guru',
                'email' => 'guru@demo.test',
                'nama_lengkap' => 'Guru Utama Demo',
                'nip_nis' => 'GR-DEMO-001',
                'jenis_kelamin' => 'L',
                'role_id' => 2,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['username' => $user['username']],
                array_merge($user, [
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ])
            );
        }

        for ($i = 1; $i <= 10; $i++) {
            $number = str_pad((string) $i, 2, '0', STR_PAD_LEFT);

            User::updateOrCreate(
                ['username' => $i === 1 ? 'siswa' : "siswa{$number}"],
                [
                    'email' => $i === 1 ? 'siswa@demo.test' : "siswa{$number}@demo.test",
                    'nama_lengkap' => "Siswa Demo {$number}",
                    'nip_nis' => "SD-DEMO-{$number}",
                    'jenis_kelamin' => $i % 2 === 0 ? 'P' : 'L',
                    'password' => Hash::make('password'),
                    'role_id' => 3,
                    'is_active' => true,
                ]
            );
        }
    }
}
