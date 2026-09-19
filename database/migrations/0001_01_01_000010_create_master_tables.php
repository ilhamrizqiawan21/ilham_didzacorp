<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // tahun_ajaran
        Schema::create('tahun_ajaran', function (Blueprint $table) {
            $table->id();
            $table->string('tahun', 9);
            $table->boolean('is_active')->default(false);
            $table->unique('tahun');
        });

        // kelas
        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->string('tingkat', 10);
            $table->string('nama_kelas', 10);
        });

        // mata_pelajaran
        Schema::create('mata_pelajaran', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 10)->nullable();
            $table->string('nama_mapel', 100);
            $table->integer('urutan')->nullable();
        });

        // guru_mapel (pivot guru ↔ mapel)
        Schema::create('guru_mapel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('mapel_id')->constrained('mata_pelajaran')->cascadeOnDelete();
            $table->unique(['guru_id', 'mapel_id']);
        });

        // kelas_mapel (pivot kelas ↔ mapel dengan guru + tahun_ajaran + semester)
        Schema::create('kelas_mapel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->foreignId('mapel_id')->constrained('mata_pelajaran')->cascadeOnDelete();
            $table->foreignId('guru_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->cascadeOnDelete();
            $table->enum('semester', ['1', '2']);
            $table->unsignedTinyInteger('pertemuan_per_minggu')->default(1);
            $table->timestamps();
        });

        // siswa
        Schema::create('siswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nis', 20)->unique();
            $table->foreignId('kelas_id')->nullable()->constrained('kelas')->nullOnDelete();
            $table->string('angkatan', 9)->nullable();
            $table->enum('status', ['aktif', 'lulus', 'keluar'])->default('aktif');
            $table->boolean('tinggal_kelas')->default(false);
            $table->timestamps();
            $table->index('kelas_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siswa');
        Schema::dropIfExists('kelas_mapel');
        Schema::dropIfExists('guru_mapel');
        Schema::dropIfExists('mata_pelajaran');
        Schema::dropIfExists('kelas');
        Schema::dropIfExists('tahun_ajaran');
    }
};
