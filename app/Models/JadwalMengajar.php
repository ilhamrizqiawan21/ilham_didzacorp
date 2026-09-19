<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JadwalMengajar extends Model
{
    protected $table = 'jadwal_mengajar';

    public const DAYS = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
    ];

    protected $fillable = [
        'guru_id',
        'kelas_id',
        'kelas_mapel_id',
        'hari',
        'pelajaran_ke',
    ];

    protected $casts = [
        'hari' => 'integer',
        'pelajaran_ke' => 'integer',
    ];

    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function kelasMapel(): BelongsTo
    {
        return $this->belongsTo(KelasMapel::class, 'kelas_mapel_id');
    }

    public function dayLabel(): string
    {
        return self::DAYS[$this->hari] ?? '-';
    }
}
