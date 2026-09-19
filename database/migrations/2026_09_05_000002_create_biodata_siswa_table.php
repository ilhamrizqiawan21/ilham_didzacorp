<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biodata_siswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->unique()->constrained('siswa')->cascadeOnDelete();
            $table->string('nama_panggilan', 100)->nullable();
            $table->text('alamat')->nullable();
            $table->string('tempat_lahir', 100)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('hobi', 255)->nullable();
            $table->string('cita_cita', 255)->nullable();
            $table->string('nama_ayah', 150)->nullable();
            $table->string('pekerjaan_ayah', 150)->nullable();
            $table->string('nama_ibu', 150)->nullable();
            $table->string('pekerjaan_ibu', 150)->nullable();
            $table->unsignedBigInteger('penghasilan_orangtua')->nullable();
            $table->string('nama_wali', 150)->nullable();
            $table->string('pekerjaan_wali', 150)->nullable();
            $table->text('penyakit_kronis')->nullable();
            $table->text('teman_dekat_sekolah')->nullable();
            $table->text('teman_dekat_luar_sekolah')->nullable();
            $table->decimal('jarak_rumah_km', 6, 2)->nullable();
            $table->string('transportasi', 150)->nullable();
            $table->text('kegiatan_luar_sekolah')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biodata_siswa');
    }
};
