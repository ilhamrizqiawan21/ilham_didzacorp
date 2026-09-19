<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengumpulanFile extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'pengumpulan_id',
        'file_name',
        'file_path',
    ];

    public function pengumpulan(): BelongsTo
    {
        return $this->belongsTo(PengumpulanTugas::class, 'pengumpulan_id');
    }
}
