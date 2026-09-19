<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // absensi
        Schema::create('absensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('kelas_mapel_id')->constrained('kelas_mapel')->cascadeOnDelete();
            $table->date('tanggal');
            $table->enum('status', ['hadir', 'sakit', 'izin', 'alpha']);
            $table->text('keterangan')->nullable();
            $table->unique(['siswa_id', 'kelas_mapel_id', 'tanggal']);
        });

        // materi
        Schema::create('materi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_mapel_id')->constrained('kelas_mapel')->cascadeOnDelete();
            $table->string('judul', 200);
            $table->text('deskripsi')->nullable();
            $table->string('file_path')->nullable();
            $table->dateTime('created_at')->nullable();
        });

        // tugas
        Schema::create('tugas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_mapel_id')->constrained('kelas_mapel')->cascadeOnDelete();
            $table->string('judul', 200);
            $table->text('deskripsi')->nullable();
            $table->dateTime('batas_waktu')->nullable();
            $table->enum('kategori_nilai', ['NH', 'STS', 'SAS', 'SAT'])->default('NH');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        // pengumpulan_tugas
        Schema::create('pengumpulan_tugas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tugas_id')->constrained('tugas')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->enum('status', ['belum', 'sudah', 'terlambat', 'dinilai', 'perlu_perbaikan'])->default('belum');
            $table->decimal('nilai', 5, 2)->nullable();
            $table->string('file_upload', 255)->nullable();
            $table->text('teks_jawaban')->nullable();
            $table->text('catatan')->nullable();
            $table->dateTime('tanggal_kumpul')->nullable();
            $table->unique(['tugas_id', 'siswa_id']);
        });

        // pengumpulan_files (multi-file per pengumpulan)
        Schema::create('pengumpulan_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengumpulan_id')->constrained('pengumpulan_tugas')->cascadeOnDelete();
            $table->string('file_name', 255);
            $table->string('file_path', 255);
            $table->dateTime('uploaded_at')->nullable();
        });

        // nilai_akhir — Kurikulum Merdeka
        Schema::create('nilai_akhir', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('kelas_mapel_id')->constrained('kelas_mapel')->cascadeOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->cascadeOnDelete();
            $table->enum('semester', ['1', '2']);
            $table->decimal('sum1', 5, 2)->nullable();
            $table->decimal('sum2', 5, 2)->nullable();
            $table->decimal('sum3', 5, 2)->nullable();
            $table->decimal('sum4', 5, 2)->nullable();
            $table->decimal('nilai_harian', 5, 2)->nullable();
            $table->decimal('sts', 5, 2)->nullable();   // Sumatif Tengah Semester
            $table->decimal('sas', 5, 2)->nullable();   // Sumatif Akhir Semester
            $table->decimal('sat', 5, 2)->nullable();   // Sumatif Akhir Tahun
            // rata_akhir is Generated Column — defined via raw SQL below
            $table->unique(['siswa_id', 'kelas_mapel_id', 'tahun_ajaran_id', 'semester'], 'nilai_akhir_unique_scope');
        });

        // sikap_spiritual (KI-1)
        Schema::create('sikap_spiritual', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('kelas_mapel_id')->constrained('kelas_mapel')->cascadeOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->cascadeOnDelete();
            $table->enum('semester', ['1', '2']);
            $table->tinyInteger('taqwa')->nullable()->default(3);
            $table->tinyInteger('kejujuran')->nullable()->default(3);
            $table->tinyInteger('disiplin')->nullable()->default(3);
            $table->tinyInteger('sabar')->nullable()->default(3);
            $table->tinyInteger('syukur')->nullable()->default(3);
            $table->tinyInteger('tawadhu')->nullable()->default(3);
        });

        // sikap_sosial (KI-2)
        Schema::create('sikap_sosial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('kelas_mapel_id')->constrained('kelas_mapel')->cascadeOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->cascadeOnDelete();
            $table->enum('semester', ['1', '2']);
            $table->tinyInteger('empati')->nullable()->default(3);
            $table->tinyInteger('kerjasama')->nullable()->default(3);
            $table->tinyInteger('toleransi')->nullable()->default(3);
            $table->tinyInteger('percaya_diri')->nullable()->default(3);
            $table->tinyInteger('komunikasi')->nullable()->default(3);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sikap_sosial');
        Schema::dropIfExists('sikap_spiritual');
        Schema::dropIfExists('nilai_akhir');
        Schema::dropIfExists('pengumpulan_files');
        Schema::dropIfExists('pengumpulan_tugas');
        Schema::dropIfExists('tugas');
        Schema::dropIfExists('materi');
        Schema::dropIfExists('absensi');
    }
};
