<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SikapSosial extends Model
{
    protected $table = 'sikap_sosial';
    public $timestamps = false;

    protected $fillable = [
        'siswa_id',
        'kelas_mapel_id',
        'tahun_ajaran_id',
        'semester',
        'empati',
        'kerjasama',
        'toleransi',
        'percaya_diri',
        'komunikasi',
    ];

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
