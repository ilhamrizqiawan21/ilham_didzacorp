<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE pengumpulan_tugas MODIFY status ENUM('belum','sudah','terlambat','dinilai') NOT NULL DEFAULT 'belum'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('pengumpulan_tugas')
            ->where('status', 'dinilai')
            ->update(['status' => 'sudah']);

        DB::statement("ALTER TABLE pengumpulan_tugas MODIFY status ENUM('belum','sudah','terlambat') NOT NULL DEFAULT 'belum'");
    }
};
