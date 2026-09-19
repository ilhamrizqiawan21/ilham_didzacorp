<?php

namespace App\Services;

use App\Models\GuruMapel;
use App\Models\Kelas;
use App\Models\KelasMapel;
use App\Models\MataPelajaran;
use App\Models\TahunAjaran;
use App\Models\User;

/**
 * Keeps the single-teacher product model simple: the active teacher teaches
 * every configured subject in every configured class. No assignment screen is
 * required, while the existing class-subject relation remains available to
 * scope materials, tasks, attendance, grades, and chat.
 */
class SingleTeacherTeachingService
{
    public function syncClass(Kelas $kelas): void
    {
        $this->sync($kelas, MataPelajaran::orderBy('id')->get(), TahunAjaran::getAktif());
    }

    public function syncSubject(MataPelajaran $mataPelajaran): void
    {
        $this->sync(Kelas::orderBy('id')->get(), collect([$mataPelajaran]), TahunAjaran::getAktif());
    }

    public function syncYear(TahunAjaran $tahunAjaran): void
    {
        $this->sync(Kelas::orderBy('id')->get(), MataPelajaran::orderBy('id')->get(), $tahunAjaran);
    }

    private function sync($kelasList, $mapelList, ?TahunAjaran $tahunAjaran): void
    {
        $guru = User::whereHas('role', fn ($query) => $query->where('nama_role', 'guru'))
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if (! $guru || ! $tahunAjaran) {
            return;
        }

        foreach ($mapelList as $mapel) {
            GuruMapel::firstOrCreate([
                'guru_id' => $guru->id,
                'mapel_id' => $mapel->id,
            ]);

            foreach ($kelasList as $kelas) {
                foreach (['1', '2'] as $semester) {
                    KelasMapel::firstOrCreate(
                        [
                            'kelas_id' => $kelas->id,
                            'mapel_id' => $mapel->id,
                            'tahun_ajaran_id' => $tahunAjaran->id,
                            'semester' => $semester,
                        ],
                        [
                            'guru_id' => $guru->id,
                            'pertemuan_per_minggu' => 1,
                        ]
                    );
                }
            }
        }
    }
}
