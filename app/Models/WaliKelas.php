<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WaliKelas extends Model
{
    protected $table = 'wali_kelas';

    protected $fillable = [
        'kelas_id',
        'guru_id',
        'tahun_ajaran_id',
    ];

    public function scopeAktif($query)
    {
        return $query->whereHas('tahunAjaran', fn($q) => $q->where('is_active', true));
    }

    public function isAktif(): bool
    {
        return $this->tahunAjaran()->where('is_active', true)->exists();
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }

    public function absensi(): HasMany
    {
        return $this->hasMany(AbsensiWaliKelas::class, 'wali_kelas_id');
    }

    public function pertemuan(): HasMany
    {
        return $this->hasMany(PertemuanWaliKelas::class, 'wali_kelas_id');
    }

    public function penangananSiswa(): HasMany
    {
        return $this->hasMany(PenangananSiswa::class, 'wali_kelas_id');
    }
}
