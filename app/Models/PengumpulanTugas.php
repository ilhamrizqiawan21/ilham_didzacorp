<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PengumpulanTugas extends Model
{
    public const STATUS_BELUM = 'belum';
    public const STATUS_SUDAH = 'sudah';
    public const STATUS_TERLAMBAT = 'terlambat';
    public const STATUS_DINILAI = 'dinilai';
    public const STATUS_PERLU_PERBAIKAN = 'perlu_perbaikan';

    /** Status yang berarti siswa sudah mengumpulkan (termasuk menunggu perbaikan). */
    public const STATUS_SUBMITTED = [
        self::STATUS_SUDAH,
        self::STATUS_TERLAMBAT,
        self::STATUS_DINILAI,
        self::STATUS_PERLU_PERBAIKAN,
    ];

    /** Status yang masuk antrian penilaian guru (sudah kumpul, belum dinilai). */
    public const STATUS_PERLU_DINILAI = [
        self::STATUS_SUDAH,
        self::STATUS_TERLAMBAT,
    ];

    public $timestamps = false;

    protected $fillable = [
        'tugas_id',
        'siswa_id',
        'status',
        'nilai',
        'nilai_sebelum_penalty',
        'penalty_terlambat',
        'file_upload',
        'teks_jawaban',
        'catatan',
        'tanggal_kumpul',
        'graded_at',
    ];

    protected $casts = [
        'tanggal_kumpul' => 'datetime',
        'graded_at' => 'datetime',
        'nilai' => 'decimal:2',
        'nilai_sebelum_penalty' => 'decimal:2',
        'penalty_terlambat' => 'decimal:2',
    ];

    public function tugas(): BelongsTo
    {
        return $this->belongsTo(Tugas::class, 'tugas_id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(PengumpulanFile::class, 'pengumpulan_id');
    }
}
