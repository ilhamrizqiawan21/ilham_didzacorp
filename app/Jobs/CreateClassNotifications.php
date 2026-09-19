<?php

namespace App\Jobs;

use App\Models\Notifikasi;
use App\Models\Siswa;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CreateClassNotifications implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $kelasMapelId,
        private readonly string $tipe,
        private readonly string $judul,
        private readonly string $pesan,
        private readonly ?string $link = null,
    ) {}

    public function handle(): void
    {
        Siswa::whereHas('kelas.kelasMapel', function ($query) {
            $query->where('kelas_mapel.id', $this->kelasMapelId);
        })
            ->where('status', 'aktif')
            ->whereNotNull('user_id')
            ->select('user_id')
            ->orderBy('id')
            ->chunk(500, function ($students): void {
                $now = now();
                $rows = $students->map(fn (Siswa $student) => [
                    'user_id' => $student->user_id,
                    'tipe' => $this->tipe,
                    'judul' => $this->judul,
                    'pesan' => $this->pesan,
                    'link' => $this->link,
                    'is_read' => false,
                    'created_at' => $now,
                ])->all();

                if ($rows !== []) {
                    Notifikasi::insert($rows);
                }
            });
    }
}
