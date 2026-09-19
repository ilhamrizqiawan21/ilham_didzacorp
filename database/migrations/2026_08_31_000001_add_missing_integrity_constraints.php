<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureNoDuplicate('roles', ['nama_role']);
        $this->ensureNoDuplicate('siswa', ['user_id']);
        $this->ensureNoDuplicate('sikap_spiritual', ['siswa_id', 'kelas_mapel_id', 'tahun_ajaran_id', 'semester']);
        $this->ensureNoDuplicate('sikap_sosial', ['siswa_id', 'kelas_mapel_id', 'tahun_ajaran_id', 'semester']);

        Schema::table('roles', function (Blueprint $table) {
            $table->unique('nama_role', 'roles_nama_role_unique');
        });

        Schema::table('siswa', function (Blueprint $table) {
            $table->unique('user_id', 'siswa_user_id_unique');
        });

        Schema::table('sikap_spiritual', function (Blueprint $table) {
            $table->unique(
                ['siswa_id', 'kelas_mapel_id', 'tahun_ajaran_id', 'semester'],
                'sikap_spiritual_unique_scope'
            );
        });

        Schema::table('sikap_sosial', function (Blueprint $table) {
            $table->unique(
                ['siswa_id', 'kelas_mapel_id', 'tahun_ajaran_id', 'semester'],
                'sikap_sosial_unique_scope'
            );
        });
    }

    public function down(): void
    {
        Schema::table('sikap_sosial', function (Blueprint $table) {
            $table->dropUnique('sikap_sosial_unique_scope');
        });

        Schema::table('sikap_spiritual', function (Blueprint $table) {
            $table->dropUnique('sikap_spiritual_unique_scope');
        });

        Schema::table('siswa', function (Blueprint $table) {
            $table->dropUnique('siswa_user_id_unique');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique('roles_nama_role_unique');
        });
    }

    private function ensureNoDuplicate(string $table, array $columns): void
    {
        $query = DB::table($table)
            ->select($columns)
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy($columns)
            ->havingRaw('COUNT(*) > 1');

        if ($query->exists()) {
            throw new RuntimeException(
                'Duplicate data found in '.$table.' for unique scope: '.implode(', ', $columns)
            );
        }
    }
};
