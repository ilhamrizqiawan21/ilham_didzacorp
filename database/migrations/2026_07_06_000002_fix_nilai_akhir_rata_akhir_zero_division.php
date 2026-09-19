<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            DB::getDriverName() !== 'mysql' ||
            !Schema::hasColumn('nilai_akhir', 'rata_akhir')
        ) {
            return;
        }

        DB::statement('ALTER TABLE nilai_akhir DROP COLUMN rata_akhir');
        DB::statement($this->generatedColumnSql());
    }

    public function down(): void
    {
        if (
            DB::getDriverName() !== 'mysql' ||
            !Schema::hasColumn('nilai_akhir', 'rata_akhir')
        ) {
            return;
        }

        DB::statement('ALTER TABLE nilai_akhir DROP COLUMN rata_akhir');
        DB::statement($this->generatedColumnSql(false));
    }

    private function generatedColumnSql(bool $safeDivision = true): string
    {
        $denominator = "
            (CASE WHEN sum1 IS NOT NULL THEN 1 ELSE 0 END) +
            (CASE WHEN sum2 IS NOT NULL THEN 1 ELSE 0 END) +
            (CASE WHEN sum3 IS NOT NULL THEN 1 ELSE 0 END) +
            (CASE WHEN sum4 IS NOT NULL THEN 1 ELSE 0 END) +
            (CASE WHEN nilai_harian IS NOT NULL THEN 1 ELSE 0 END) +
            (CASE WHEN sts IS NOT NULL THEN 1 ELSE 0 END) +
            (CASE WHEN sas IS NOT NULL THEN 1 ELSE 0 END) +
            (CASE WHEN sat IS NOT NULL THEN 1 ELSE 0 END)
        ";

        $denominator = $safeDivision
            ? "NULLIF(($denominator), 0)"
            : "($denominator)";

        return "
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
                ) / $denominator
            ) STORED
        ";
    }
};