<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenangananSiswa extends Model
{
    protected $table = 'penanganan_siswa';

    protected $fillable = [
        'wali_kelas_id',
        'siswa_id',
        'kondisi',
        'deskripsi',
        'tindak_lanjut',
        'hasil',
        'status',
    ];

    public function waliKelas(): BelongsTo
    {
        return $this->belongsTo(WaliKelas::class, 'wali_kelas_id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }
}
