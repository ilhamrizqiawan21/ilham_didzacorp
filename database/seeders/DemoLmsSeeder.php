<?php

namespace Database\Seeders;

use App\Models\Absensi;
use App\Models\KelasMapel;
use App\Models\Materi;
use App\Models\NilaiAkhir;
use App\Models\PengumpulanTugas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use Illuminate\Database\Seeder;

class DemoLmsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tahunAjaran = TahunAjaran::getAktif() ?? TahunAjaran::where('tahun', '2026/2027')->first();

        if (! $tahunAjaran) {
            return;
        }

        $kelasMapelList = KelasMapel::with(['kelas', 'mataPelajaran'])
            ->where('tahun_ajaran_id', $tahunAjaran->id)
            ->where('semester', '1')
            ->orderBy('kelas_id')
            ->orderBy('mapel_id')
            ->take(6)
            ->get();

        foreach ($kelasMapelList as $index => $kelasMapel) {
            $judulMateri = 'Materi Demo ' . $kelasMapel->mataPelajaran->nama_mapel;

            Materi::updateOrCreate(
                [
                    'kelas_mapel_id' => $kelasMapel->id,
                    'judul' => $judulMateri,
                ],
                [
                    'deskripsi' => 'Materi contoh untuk memperlihatkan alur belajar di kelas demo.',
                    'file_path' => null,
                ]
            );

            $tugas = Tugas::updateOrCreate(
                [
                    'kelas_mapel_id' => $kelasMapel->id,
                    'judul' => 'Tugas Demo ' . $kelasMapel->mataPelajaran->nama_mapel,
                ],
                [
                    'deskripsi' => 'Kerjakan latihan singkat berdasarkan materi demo yang tersedia.',
                    'batas_waktu' => now()->addDays(7 + $index),
                    'kategori_nilai' => 'NH',
                ]
            );

            $siswaList = Siswa::where('kelas_id', $kelasMapel->kelas_id)->orderBy('nis')->get();

            foreach ($siswaList as $studentIndex => $siswa) {
                Absensi::updateOrCreate(
                    [
                        'siswa_id' => $siswa->id,
                        'kelas_mapel_id' => $kelasMapel->id,
                        'tanggal' => now()->subDays($index + 1)->toDateString(),
                    ],
                    [
                        'status' => $studentIndex % 7 === 0 ? 'izin' : 'hadir',
                        'keterangan' => $studentIndex % 7 === 0 ? 'Data demo izin' : null,
                    ]
                );

                PengumpulanTugas::updateOrCreate(
                    [
                        'tugas_id' => $tugas->id,
                        'siswa_id' => $siswa->id,
                    ],
                    [
                        'status' => $studentIndex % 4 === 0 ? 'belum' : 'sudah',
                        'nilai' => $studentIndex % 4 === 0 ? null : 78 + (($studentIndex + $index) % 15),
                        'teks_jawaban' => $studentIndex % 4 === 0 ? null : 'Jawaban demo siswa.',
                        'catatan' => 'Data contoh, bukan data asli.',
                        'tanggal_kumpul' => $studentIndex % 4 === 0 ? null : now()->subDays($index),
                    ]
                );

                NilaiAkhir::updateOrCreate(
                    [
                        'siswa_id' => $siswa->id,
                        'kelas_mapel_id' => $kelasMapel->id,
                        'tahun_ajaran_id' => $tahunAjaran->id,
                        'semester' => '1',
                    ],
                    [
                        'sum1' => 76 + (($studentIndex + $index) % 12),
                        'sum2' => 78 + (($studentIndex + $index) % 10),
                        'sum3' => 80 + (($studentIndex + $index) % 9),
                        'sum4' => 79 + (($studentIndex + $index) % 8),
                        'nilai_harian' => 80 + (($studentIndex + $index) % 10),
                        'sts' => 77 + (($studentIndex + $index) % 12),
                        'sas' => 79 + (($studentIndex + $index) % 11),
                        'sat' => null,
                    ]
                );
            }
        }
    }
}
