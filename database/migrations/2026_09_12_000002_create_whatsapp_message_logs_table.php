<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_message_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('guru_id')->constrained('users')->cascadeOnDelete();
            $table->string('jenis_template', 50);
            $table->json('tugas_ids');
            $table->unsignedInteger('total_hari_terlambat')->default(0);
            $table->timestamp('prepared_at');
            $table->timestamp('sent_marked_at')->nullable();
            $table->timestamps();
            $table->index(['siswa_id', 'guru_id', 'prepared_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_message_logs');
    }
};
