<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengumuman', function (Blueprint $table) {
            $table->boolean('is_public_login')->default(false)->index()->after('kelas_mapel_id');
            $table->string('public_file_name')->nullable()->after('is_public_login');
            $table->string('public_file_path')->nullable()->after('public_file_name');
            $table->string('public_file_mime', 120)->nullable()->after('public_file_path');
            $table->unsignedBigInteger('public_file_size')->nullable()->after('public_file_mime');
        });
    }

    public function down(): void
    {
        Schema::table('pengumuman', function (Blueprint $table) {
            $table->dropIndex(['is_public_login']);
            $table->dropColumn([
                'is_public_login',
                'public_file_name',
                'public_file_path',
                'public_file_mime',
                'public_file_size',
            ]);
        });
    }
};
