<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // chat_messages
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('kelas_mapel_id')->constrained('kelas_mapel')->cascadeOnDelete();
            $table->text('message');
            $table->dateTime('created_at')->nullable();
            $table->boolean('is_read')->default(false);
        });

        // pengumuman
        Schema::create('pengumuman', function (Blueprint $table) {
            $table->id();
            $table->string('judul', 200);
            $table->text('isi');
            $table->enum('target', ['semua', 'guru', 'siswa', 'kelas_mapel'])->default('semua');
            $table->text('target_kelas')->nullable();
            $table->foreignId('kelas_mapel_id')->nullable()->constrained('kelas_mapel')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->dateTime('created_at')->nullable();
        });

        // notifikasi
        Schema::create('notifikasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipe', 50);
            $table->string('judul', 200);
            $table->text('pesan');
            $table->string('link', 255)->nullable();
            $table->boolean('is_read')->default(false);
            $table->dateTime('created_at')->nullable();
        });

        // log_login
        Schema::create('log_login', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('username', 50);
            $table->string('nama_lengkap', 100);
            $table->string('role', 20);
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->dateTime('login_time')->nullable();
        });

        // pengaturan (key-value settings)
        Schema::create('pengaturan', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->text('value')->nullable();
        });

        // dashboard_widgets
        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('widget_key', 50);
            $table->boolean('is_visible')->default(true);
            $table->integer('widget_order')->default(0);
            $table->boolean('is_pinned')->default(false);
            $table->unique(['user_id', 'widget_key']);
        });

        // calendar_events
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->date('event_date');
            $table->boolean('is_holiday')->default(false);
            $table->string('scope', 20)->default('user');
            $table->boolean('is_done')->default(false);
            $table->timestamps();
            $table->index('event_date');
        });

        // blocked_ips (rate limiting)
        Schema::create('blocked_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->unique();
            $table->dateTime('blocked_until');
            $table->string('reason', 255);
            $table->dateTime('created_at')->nullable();
        });

        // login_attempts (rate limiting)
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45);
            $table->string('username', 50)->nullable();
            $table->dateTime('attempt_time')->nullable();
            $table->boolean('success')->default(false);
            $table->index(['ip_address', 'attempt_time']);
        });

        // system_errors
        Schema::create('system_errors', function (Blueprint $table) {
            $table->id();
            $table->string('error_level', 20)->nullable();
            $table->text('error_code')->nullable();
            $table->text('message');
            $table->string('file', 255)->nullable();
            $table->integer('line')->nullable();
            $table->text('trace')->nullable();
            $table->string('url', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->integer('user_id')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->index('error_level');
        });

        // Add generated column for nilai_akhir.rata_akhir (MySQL only)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE nilai_akhir
                ADD COLUMN rata_akhir DECIMAL(5,2)
                GENERATED ALWAYS AS (
                    (
                        COALESCE(sum1, 0) +
                        COALESCE(sum2, 0) +
                        COALESCE(sum3, 0) +
                        COALESCE(sum4, 0) +
                        COALESCE(nilai_harian, 0) +
                        COALESCE(sts, 0) +
                        COALESCE(sas, 0) +
                        COALESCE(sat, 0)
                    ) /
                    NULLIF((
                        (CASE WHEN sum1 IS NOT NULL THEN 1 ELSE 0 END) +
                        (CASE WHEN sum2 IS NOT NULL THEN 1 ELSE 0 END) +
                        (CASE WHEN sum3 IS NOT NULL THEN 1 ELSE 0 END) +
                        (CASE WHEN sum4 IS NOT NULL THEN 1 ELSE 0 END) +
                        (CASE WHEN nilai_harian IS NOT NULL THEN 1 ELSE 0 END) +
                        (CASE WHEN sts IS NOT NULL THEN 1 ELSE 0 END) +
                        (CASE WHEN sas IS NOT NULL THEN 1 ELSE 0 END) +
                        (CASE WHEN sat IS NOT NULL THEN 1 ELSE 0 END)
                    ), 0)
                ) STORED
            ");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('system_errors');
        Schema::dropIfExists('login_attempts');
        Schema::dropIfExists('blocked_ips');
        Schema::dropIfExists('calendar_events');
        Schema::dropIfExists('dashboard_widgets');
        Schema::dropIfExists('pengaturan');
        Schema::dropIfExists('log_login');
        Schema::dropIfExists('notifikasi');
        Schema::dropIfExists('pengumuman');
        Schema::dropIfExists('chat_messages');
    }
};
