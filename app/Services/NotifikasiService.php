<?php

namespace App\Services;

use App\Jobs\CreateClassNotifications;
use App\Models\Notifikasi;

class NotifikasiService
{
    /**
     * Buat notifikasi untuk semua siswa dalam satu kelas_mapel.
     */
    public function notifikasiKelasMapel(int $kelasMapelId, string $tipe, string $judul, string $pesan, ?string $link = null): void
    {
        CreateClassNotifications::dispatch($kelasMapelId, $tipe, $judul, $pesan, $link)->afterResponse();
    }

    /**
     * Buat notifikasi untuk satu user.
     */
    public function notifikasiUser(int $userId, string $tipe, string $judul, string $pesan, ?string $link = null): Notifikasi
    {
        return Notifikasi::create([
            'user_id' => $userId,
            'tipe' => $tipe,
            'judul' => $judul,
            'pesan' => $pesan,
            'link' => $link,
        ]);
    }
}
