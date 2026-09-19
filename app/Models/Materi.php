<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Materi extends Model
{
    protected $table = 'materi';

    public const UPDATED_AT = null;

    protected $fillable = [
        'kelas_mapel_id',
        'judul',
        'deskripsi',
        'file_path',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function kelasMapel(): BelongsTo
    {
        return $this->belongsTo(KelasMapel::class, 'kelas_mapel_id');
    }
}
