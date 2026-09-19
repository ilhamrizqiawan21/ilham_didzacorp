<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KelasDaring extends Model
{
    protected $table = 'kelas_daring';

    public const STATUS_TERJADWAL = 'terjadwal';
    public const STATUS_SELESAI = 'selesai';
    public const STATUS_DIBATALKAN = 'dibatalkan';

    protected $fillable = [
        'guru_id',
        'kelas_mapel_id',
        'judul',
        'deskripsi',
        'tanggal',
        'pelajaran_ke',
        'meeting_url',
        'status',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'pelajaran_ke' => 'integer',
    ];

    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function kelasMapel(): BelongsTo
    {
        return $this->belongsTo(KelasMapel::class, 'kelas_mapel_id');
    }
}
