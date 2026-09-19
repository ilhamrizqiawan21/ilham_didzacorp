<?php

namespace Database\Seeders;

use App\Models\GuruMapel;
use App\Models\Kelas;
use App\Models\KelasMapel;
use App\Models\MataPelajaran;
use App\Models\Pengaturan;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoAcademicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TahunAjaran::where('is_active', true)->update(['is_active' => false]);

        $tahunAjaran = TahunAjaran::updateOrCreate(
            ['tahun' => '2026/2027'],
            ['is_active' => true]
        );

        Pengaturan::setValue('tahun_ajaran_aktif', (string) $tahunAjaran->id);
        Pengaturan::setValue('semester_aktif', '1');

        $kelasList = collect([
            ['tingkat' => '7', 'nama_kelas' => '7A'],
            ['tingkat' => '8', 'nama_kelas' => '8A'],
            ['tingkat' => '9', 'nama_kelas' => '9A'],
        ])->map(fn ($kelas) => Kelas::firstOrCreate($kelas));

        $mapelList = collect([
            ['kode' => 'BIN', 'nama_mapel' => 'Bahasa Indonesia', 'urutan' => 1],
            ['kode' => 'MAT', 'nama_mapel' => 'Matematika', 'urutan' => 2],
            ['kode' => 'IPA', 'nama_mapel' => 'Ilmu Pengetahuan Alam', 'urutan' => 3],
            ['kode' => 'IPS', 'nama_mapel' => 'Ilmu Pengetahuan Sosial', 'urutan' => 4],
            ['kode' => 'BIG', 'nama_mapel' => 'Bahasa Inggris', 'urutan' => 5],
        ])->map(fn ($mapel) => MataPelajaran::updateOrCreate(
            ['kode' => $mapel['kode']],
            $mapel
        ));

        $gurus = User::where('role_id', 2)->where('username', 'guru')->get()->values();

        foreach ($mapelList as $index => $mapel) {
            $guru = $gurus[$index % max($gurus->count(), 1)];

            GuruMapel::firstOrCreate([
                'guru_id' => $guru->id,
                'mapel_id' => $mapel->id,
            ]);

            foreach ($kelasList as $kelas) {
                KelasMapel::updateOrCreate(
                    [
                        'kelas_id' => $kelas->id,
                        'mapel_id' => $mapel->id,
                        'tahun_ajaran_id' => $tahunAjaran->id,
                        'semester' => '1',
                    ],
                    [
                        'guru_id' => $guru->id,
                        'pertemuan_per_minggu' => $mapel->kode === 'IPA' ? 2 : 1,
                    ]
                );
            }
        }

        User::where('role_id', 3)
            ->where(fn ($query) => $query->where('username', 'siswa')->orWhere('username', 'like', 'siswa__'))
            ->orderBy('username')
            ->get()
            ->values()
            ->each(function (User $user, int $index) use ($kelasList) {
                $number = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
                $kelas = $kelasList[$index % $kelasList->count()];

                Siswa::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'nis' => "2026{$number}",
                        'kelas_id' => $kelas->id,
                        'angkatan' => '2026',
                        'status' => 'aktif',
                        'tinggal_kelas' => false,
                    ]
                );
            });
    }
}
