<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Convert notifikasi.tipe to VARCHAR(50) so new notification types
     * (absensi, etc.) can be added without future ALTER TABLE.
     */
    public function up(): void
    {
        // Only run if the column is still an ENUM (from SQL dump import).
        // If already VARCHAR (from clean migration), this is a no-op.
        try {
            $column = DB::selectOne("
                SELECT DATA_TYPE, COLUMN_TYPE
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'notifikasi'
                  AND COLUMN_NAME = 'tipe'
            ");

            if ($column && strtolower($column->DATA_TYPE) === 'enum') {
                // Preserve existing values, add new types
                DB::statement("ALTER TABLE notifikasi MODIFY COLUMN tipe VARCHAR(50) NOT NULL DEFAULT 'tugas_baru'");
            }
        } catch (\Exception $e) {
            // Table may not exist yet — that's fine, the original migration handles creation.
        }
    }

    public function down(): void
    {
        // Reverting to enum is destructive and unnecessary.
        // No down migration needed — VARCHAR is a superset.
    }
};
