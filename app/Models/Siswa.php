<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Siswa extends Model
{
    protected $table = 'siswa';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'nis',
        'kelas_id',
        'angkatan',
        'status',
        'tinggal_kelas',
        'nomor_whatsapp',
        'whatsapp_opt_in',
        'whatsapp_opted_in_at',
    ];

    protected $casts = [
        'tinggal_kelas' => 'boolean',
        'whatsapp_opt_in' => 'boolean',
        'whatsapp_opted_in_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function biodata(): HasOne
    {
        return $this->hasOne(BiodataSiswa::class, 'siswa_id');
    }

    public function absensi(): HasMany
    {
        return $this->hasMany(Absensi::class, 'siswa_id');
    }

    public function pengumpulanTugas(): HasMany
    {
        return $this->hasMany(PengumpulanTugas::class, 'siswa_id');
    }

    public function nilaiAkhir(): HasMany
    {
        return $this->hasMany(NilaiAkhir::class, 'siswa_id');
    }

    public function sikapSosial(): HasMany
    {
        return $this->hasMany(SikapSosial::class, 'siswa_id');
    }

    public function sikapSpiritual(): HasMany
    {
        return $this->hasMany(SikapSpiritual::class, 'siswa_id');
    }
}
