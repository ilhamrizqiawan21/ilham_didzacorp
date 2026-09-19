<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MataPelajaran extends Model
{
    protected $table = 'mata_pelajaran';

    public $timestamps = false;

    protected $fillable = [
        'kode',
        'nama_mapel',
        'urutan',
    ];

    // Relasi: mapel memiliki banyak kelas_mapel
    public function kelasMapel(): HasMany
    {
        return $this->hasMany(KelasMapel::class, 'mapel_id');
    }

    // Relasi: mapel memiliki banyak guru melalui pivot guru_mapel
    public function guru()
    {
        return $this->belongsToMany(User::class, 'guru_mapel', 'mapel_id', 'guru_id');
    }
}
