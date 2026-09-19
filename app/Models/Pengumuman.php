<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pengumuman extends Model
{
    protected $table = 'pengumuman';

    public $timestamps = false;

    const CREATED_AT = 'created_at';

    protected $fillable = [
        'judul',
        'isi',
        'target',
        'target_kelas',
        'kelas_mapel_id',
        'is_public_login',
        'public_file_name',
        'public_file_path',
        'public_file_mime',
        'public_file_size',
        'created_by',
    ];

    protected $casts = [
        'is_public_login' => 'boolean',
        'public_file_size' => 'integer',
        'created_at' => 'datetime',
    ];

    protected $hidden = [
        'public_file_path',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function kelasMapel(): BelongsTo
    {
        return $this->belongsTo(KelasMapel::class, 'kelas_mapel_id');
    }

    public function targetKelasIds(): array
    {
        if (blank($this->target_kelas)) {
            return [];
        }

        $decoded = json_decode($this->target_kelas, true);

        if (is_array($decoded)) {
            return array_values(array_filter(array_map('intval', $decoded)));
        }

        return array_values(array_filter(array_map('intval', explode(',', $this->target_kelas))));
    }
}
