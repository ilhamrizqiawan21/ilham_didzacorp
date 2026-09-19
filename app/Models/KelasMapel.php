<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KelasMapel extends Model
{
    protected $table = 'kelas_mapel';
    public $timestamps = false;

    protected $fillable = [
        'kelas_id',
        'mapel_id',
        'guru_id',
        'tahun_ajaran_id',
        'semester',
        'pertemuan_per_minggu',
    ];

    protected $casts = [
        'pertemuan_per_minggu' => 'integer',
    ];

    public function scopeAktif($query, ?string $semester = null)
    {
        $semester ??= \App\Models\Pengaturan::getValue('semester_aktif', '1');

        return $query
            ->where('semester', $semester)
            ->whereHas('tahunAjaran', fn($q) => $q->where('is_active', true));
    }

    public function isAktif(): bool
    {
        $semesterAktif = \App\Models\Pengaturan::getValue('semester_aktif', '1');

        return (string) $this->semester === (string) $semesterAktif
            && $this->tahunAjaran()->where('is_active', true)->exists();
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class, 'mapel_id');
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }

    // Relasi turunan
    public function absensi(): HasMany
    {
        return $this->hasMany(Absensi::class, 'kelas_mapel_id');
    }

    public function materi(): HasMany
    {
        return $this->hasMany(Materi::class, 'kelas_mapel_id');
    }

    public function tugas(): HasMany
    {
        return $this->hasMany(Tugas::class, 'kelas_mapel_id');
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'kelas_mapel_id');
    }

    public function jadwalMengajar(): HasMany
    {
        return $this->hasMany(JadwalMengajar::class, 'kelas_mapel_id');
    }

    public function kelasDaring(): HasMany
    {
        return $this->hasMany(KelasDaring::class, 'kelas_mapel_id');
    }

    public function nilaiAkhir(): HasMany
    {
        return $this->hasMany(NilaiAkhir::class, 'kelas_mapel_id');
    }

    public function sikapSosial(): HasMany
    {
        return $this->hasMany(SikapSosial::class, 'kelas_mapel_id');
    }

    public function sikapSpiritual(): HasMany
    {
        return $this->hasMany(SikapSpiritual::class, 'kelas_mapel_id');
    }
}
