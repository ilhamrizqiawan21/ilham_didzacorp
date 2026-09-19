<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wali_kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->foreignId('guru_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['kelas_id', 'tahun_ajaran_id'], 'wali_kelas_unique_scope');
            $table->index(['guru_id', 'tahun_ajaran_id']);
        });

        Schema::create('absensi_wali_kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wali_kelas_id')->constrained('wali_kelas')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->date('tanggal');
            $table->enum('status', ['hadir', 'sakit', 'izin', 'alpha']);
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->unique(['wali_kelas_id', 'siswa_id', 'tanggal'], 'absensi_wali_kelas_unique_scope');
        });

        Schema::create('pertemuan_wali_kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wali_kelas_id')->constrained('wali_kelas')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('topik', 200);
            $table->text('hasil');
            $table->timestamps();
        });

        Schema::create('penanganan_siswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wali_kelas_id')->constrained('wali_kelas')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->string('kondisi', 200);
            $table->text('deskripsi')->nullable();
            $table->text('tindak_lanjut')->nullable();
            $table->text('hasil')->nullable();
            $table->enum('status', ['baru', 'proses', 'selesai'])->default('baru');
            $table->timestamps();
            $table->index(['wali_kelas_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penanganan_siswa');
        Schema::dropIfExists('pertemuan_wali_kelas');
        Schema::dropIfExists('absensi_wali_kelas');
        Schema::dropIfExists('wali_kelas');
    }
};
