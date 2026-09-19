<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SikapSpiritual extends Model
{
    protected $table = 'sikap_spiritual';
    public $timestamps = false;

    protected $fillable = [
        'siswa_id',
        'kelas_mapel_id',
        'tahun_ajaran_id',
        'semester',
        'taqwa',
        'kejujuran',
        'disiplin',
        'sabar',
        'syukur',
        'tawadhu',
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
