<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('singleton_key')->default(1)->unique();
            $table->string('school_name')->default('Nama Sekolah');
            $table->string('school_short_name')->default('LMS Sekolah');
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->string('address')->default('Alamat sekolah belum diatur');
            $table->string('village')->nullable();
            $table->string('district')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('whatsapp', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('npsn', 50)->nullable();
            $table->string('nsm', 50)->nullable();
            $table->string('accreditation', 50)->nullable();
            $table->string('school_status', 100)->nullable();
            $table->string('principal_name')->default('Nama Kepala Sekolah');
            $table->string('principal_nip', 50)->nullable();
            $table->string('principal_nuptk', 50)->nullable();
            $table->string('foundation_name')->nullable();
            $table->string('school_year', 20)->default('2026/2027');
            $table->string('semester', 20)->default('Ganjil');
            $table->text('vision')->nullable();
            $table->text('mission')->nullable();
            $table->string('motto')->nullable();
            $table->string('primary_color', 20)->default('#198754');
            $table->string('secondary_color', 20)->default('#0d6efd');
            $table->string('sidebar_color', 20)->nullable();
            $table->string('navbar_color', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_settings');
    }
};
