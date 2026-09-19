<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('nilai_komponen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nilai_akhir_id')->constrained('nilai_akhir')->cascadeOnDelete();
            $table->string('nama_komponen', 100);
            $table->enum('jenis', ['harian', 'sts', 'sas', 'sat', 'lainnya'])->default('lainnya');
            $table->decimal('bobot', 5, 2)->nullable();
            $table->decimal('nilai', 5, 2)->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['nilai_akhir_id', 'nama_komponen']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nilai_komponen');
    }
};
