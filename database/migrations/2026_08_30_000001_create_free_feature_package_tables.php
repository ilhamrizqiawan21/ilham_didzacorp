<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_mengajar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->foreignId('kelas_mapel_id')->constrained('kelas_mapel')->cascadeOnDelete();
            $table->unsignedTinyInteger('hari');
            $table->unsignedTinyInteger('pelajaran_ke');
            $table->timestamps();

            $table->unique(['guru_id', 'hari', 'pelajaran_ke'], 'jadwal_guru_slot_unique');
            $table->unique(['kelas_id', 'hari', 'pelajaran_ke'], 'jadwal_kelas_slot_unique');
            $table->index(['kelas_mapel_id', 'hari']);
        });

        Schema::create('kelas_daring', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('kelas_mapel_id')->constrained('kelas_mapel')->cascadeOnDelete();
            $table->string('judul', 200);
            $table->text('deskripsi')->nullable();
            $table->date('tanggal');
            $table->unsignedTinyInteger('pelajaran_ke');
            $table->string('meeting_url', 500);
            $table->enum('status', ['terjadwal', 'selesai', 'dibatalkan'])->default('terjadwal');
            $table->timestamps();

            $table->index(['kelas_mapel_id', 'tanggal']);
            $table->index(['guru_id', 'tanggal']);
            $table->index('status');
        });

        Schema::table('pengumpulan_tugas', function (Blueprint $table) {
            if (! Schema::hasColumn('pengumpulan_tugas', 'graded_at')) {
                $table->dateTime('graded_at')->nullable()->after('tanggal_kumpul');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pengumpulan_tugas', function (Blueprint $table) {
            if (Schema::hasColumn('pengumpulan_tugas', 'graded_at')) {
                $table->dropColumn('graded_at');
            }
        });

        Schema::dropIfExists('kelas_daring');
        Schema::dropIfExists('jadwal_mengajar');
    }
};
