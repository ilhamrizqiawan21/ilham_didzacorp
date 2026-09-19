<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiodataSiswa extends Model
{
    protected $table = 'biodata_siswa';

    protected $fillable = [
        'siswa_id',
        'nama_panggilan',
        'alamat',
        'tempat_lahir',
        'tanggal_lahir',
        'hobi',
        'cita_cita',
        'nama_ayah',
        'pekerjaan_ayah',
        'nama_ibu',
        'pekerjaan_ibu',
        'penghasilan_orangtua',
        'nama_wali',
        'pekerjaan_wali',
        'penyakit_kronis',
        'teman_dekat_sekolah',
        'teman_dekat_luar_sekolah',
        'jarak_rumah_km',
        'transportasi',
        'kegiatan_luar_sekolah',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'penghasilan_orangtua' => 'integer',
            'jarak_rumah_km' => 'decimal:2',
        ];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }
}
