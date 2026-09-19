<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengumpulan_tugas', function (Blueprint $table) {
            $table->decimal('nilai_sebelum_penalty', 5, 2)->nullable()->after('nilai');
            $table->decimal('penalty_terlambat', 5, 2)->default(0)->after('nilai_sebelum_penalty');
        });
    }

    public function down(): void
    {
        Schema::table('pengumpulan_tugas', function (Blueprint $table) {
            $table->dropColumn(['nilai_sebelum_penalty', 'penalty_terlambat']);
        });
    }
};
