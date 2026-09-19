<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensi', function (Blueprint $table) {
            $table->index(['kelas_mapel_id', 'tanggal'], 'absensi_kelas_mapel_tanggal_idx');
        });

        Schema::table('tugas', function (Blueprint $table) {
            $table->index(['kelas_mapel_id', 'batas_waktu'], 'tugas_kelas_mapel_batas_waktu_idx');
        });

        Schema::table('pengumpulan_tugas', function (Blueprint $table) {
            $table->index(['status', 'nilai', 'tugas_id'], 'pengumpulan_status_nilai_tugas_idx');
        });
    }

    public function down(): void
    {
        Schema::table('pengumpulan_tugas', function (Blueprint $table) {
            $table->dropIndex('pengumpulan_status_nilai_tugas_idx');
        });

        Schema::table('tugas', function (Blueprint $table) {
            $table->dropIndex('tugas_kelas_mapel_batas_waktu_idx');
        });

        Schema::table('absensi', function (Blueprint $table) {
            $table->dropIndex('absensi_kelas_mapel_tanggal_idx');
        });
    }
};
