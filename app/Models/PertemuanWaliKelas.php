<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PertemuanWaliKelas extends Model
{
    protected $table = 'pertemuan_wali_kelas';

    protected $fillable = [
        'wali_kelas_id',
        'tanggal',
        'topik',
        'hasil',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function waliKelas(): BelongsTo
    {
        return $this->belongsTo(WaliKelas::class, 'wali_kelas_id');
    }
}
