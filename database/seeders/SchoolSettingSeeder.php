<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SchoolSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaults = [
            'school_name' => 'Nama Sekolah',
            'school_short_name' => 'LMS',
            'address' => 'Alamat sekolah belum diatur',
            'principal_name' => 'Nama Kepala Sekolah',
            'school_year' => '2026/2027',
            'semester' => 'Ganjil',
            'primary_color' => '#198754',
            'secondary_color' => '#0d6efd',
            'updated_at' => now(),
        ];

        $exists = DB::table('school_settings')
            ->where('singleton_key', 1)
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('school_settings')->insert(array_merge($defaults, [
            'singleton_key' => 1,
            'created_at' => now(),
        ]));
    }
}
