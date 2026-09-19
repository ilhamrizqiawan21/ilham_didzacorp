<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NilaiAkhir extends Model
{
    protected $table = 'nilai_akhir';

    private const NILAI_COMPONENTS = [
        'sum1',
        'sum2',
        'sum3',
        'sum4',
        'nilai_harian',
        'sts',
        'sas',
        'sat',
    ];

    protected $fillable = [
        'siswa_id',
        'kelas_mapel_id',
        'tahun_ajaran_id',
        'semester',
        'sum1',
        'sum2',
        'sum3',
        'sum4',
        'nilai_harian',
        'sts',
        'sas',
        'sat',
    ];

    public $timestamps = false;

    // rata_akhir is a MySQL GENERATED ALWAYS AS column — excluded from fillable

    public static function rataAkhirExpression(string $table = 'nilai_akhir'): string
    {
        $qualifiedColumns = array_map(
            fn (string $column): string => "{$table}.{$column}",
            self::NILAI_COMPONENTS
        );

        $total = implode(' + ', array_map(
            fn (string $column): string => "COALESCE({$column}, 0)",
            $qualifiedColumns
        ));

        $count = implode(' + ', array_map(
            fn (string $column): string => "(CASE WHEN {$column} IS NOT NULL THEN 1 ELSE 0 END)",
            $qualifiedColumns
        ));

        return "(($total) / NULLIF(($count), 0))";
    }

    public function getRataAkhirAttribute(mixed $value): ?float
    {
        if ($value !== null) {
            return round((float) $value, 2);
        }

        $values = array_filter(
            array_map(fn (string $column) => $this->attributes[$column] ?? null, self::NILAI_COMPONENTS),
            fn ($value) => $value !== null
        );

        if ($values === []) {
            return null;
        }

        return round(array_sum(array_map('floatval', $values)) / count($values), 2);
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function kelasMapel(): BelongsTo
    {
        return $this->belongsTo(KelasMapel::class, 'kelas_mapel_id');
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }
}
