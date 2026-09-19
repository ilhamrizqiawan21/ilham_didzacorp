<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolSetting extends Model
{
    public const SINGLETON_ID = 1;

    protected $table = 'school_settings';

    protected $fillable = [
        'singleton_key',
        'school_name',
        'school_short_name',
        'logo_path',
        'favicon_path',
        'address',
        'village',
        'district',
        'city',
        'province',
        'postal_code',
        'phone',
        'whatsapp',
        'email',
        'website',
        'npsn',
        'nsm',
        'accreditation',
        'school_status',
        'principal_name',
        'principal_nip',
        'principal_nuptk',
        'foundation_name',
        'school_year',
        'semester',
        'vision',
        'mission',
        'motto',
        'primary_color',
        'secondary_color',
        'sidebar_color',
        'navbar_color',
    ];

    public static function fallback(): array
    {
        return [
            'school_name' => 'Nama Sekolah',
            'school_short_name' => 'LMS Sekolah',
            'address' => 'Alamat sekolah belum diatur',
            'principal_name' => 'Nama Kepala Sekolah',
            'school_year' => '2026/2027',
            'semester' => 'Ganjil',
            'primary_color' => '#198754',
            'secondary_color' => '#0d6efd',
        ];
    }

    public static function current(): self
    {
        $setting = self::query()->first();

        return $setting ?: new self(self::fallback());
    }
}
