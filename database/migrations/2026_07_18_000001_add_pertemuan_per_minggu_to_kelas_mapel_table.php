<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('kelas_mapel', 'pertemuan_per_minggu')) {
            Schema::table('kelas_mapel', function (Blueprint $table) {
                $table->unsignedTinyInteger('pertemuan_per_minggu')->default(1)->after('semester');
            });
        }

        DB::table('kelas_mapel')
            ->whereIn('mapel_id', DB::table('mata_pelajaran')->select('id')->where('kode', 'IPA'))
            ->update(['pertemuan_per_minggu' => 2]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('kelas_mapel', 'pertemuan_per_minggu')) {
            Schema::table('kelas_mapel', function (Blueprint $table) {
                $table->dropColumn('pertemuan_per_minggu');
            });
        }
    }
};
