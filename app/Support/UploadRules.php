<?php

namespace App\Support;

final class UploadRules
{
    /**
     * File yang boleh dibagikan sebagai materi, tugas, atau lampiran.
     * extensions memeriksa nama file, sedangkan mimes memeriksa isi/MIME.
     */
    public static function document(int $maxKb = 5120, bool $nullable = false): array
    {
        return [
            $nullable ? 'nullable' : 'required',
            'file',
            'mimes:jpg,jpeg,png,pdf',
            'extensions:jpg,jpeg,png,pdf',
            'max:'.$maxKb,
        ];
    }

    public static function image(int $maxKb = 2048, bool $nullable = false): array
    {
        return [
            $nullable ? 'nullable' : 'required',
            'file',
            'mimes:jpg,jpeg,png',
            'extensions:jpg,jpeg,png',
            'max:'.$maxKb,
        ];
    }

    private function __construct() {}
}
