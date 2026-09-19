<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE pengumpulan_tugas MODIFY status ENUM('belum','sudah','terlambat','dinilai','perlu_perbaikan') NOT NULL DEFAULT 'belum'");

        // Backfill data penalti dari kolom legacy `nilai_asli` (nilai sebelum penalti).
        // Hanya berlaku untuk instalasi lama yang masih memiliki kolom tersebut.
        if (Schema::hasColumn('pengumpulan_tugas', 'nilai_asli')) {
            DB::table('pengumpulan_tugas')
                ->whereNull('nilai_sebelum_penalty')
                ->whereNotNull('nilai_asli')
                ->update(['nilai_sebelum_penalty' => DB::raw('CAST(nilai_asli AS DECIMAL(5,2))')]);

            DB::table('pengumpulan_tugas')
                ->where('penalty_terlambat', 0)
                ->whereNotNull('nilai')
                ->whereNotNull('nilai_sebelum_penalty')
                ->whereColumn('nilai', '<', 'nilai_sebelum_penalty')
                ->update([
                    'penalty_terlambat' => DB::raw('GREATEST(0, CAST(nilai_sebelum_penalty AS DECIMAL(5,2)) - CAST(nilai AS DECIMAL(5,2)))'),
                ]);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('pengumpulan_tugas')
            ->where('status', 'perlu_perbaikan')
            ->update(['status' => 'sudah']);

        DB::statement("ALTER TABLE pengumpulan_tugas MODIFY status ENUM('belum','sudah','terlambat','dinilai') NOT NULL DEFAULT 'belum'");
    }
};
