<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\BiodataSiswa;
use App\Models\Siswa;
use App\Models\WaliKelas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BiodataSiswaController extends Controller
{
    public function index(WaliKelas $waliKelas)
    {
        $this->authorize('kelola-wali-kelas', $waliKelas);
        $waliKelas->loadMissing(['kelas', 'tahunAjaran']);

        $students = Siswa::with(['user', 'biodata'])
            ->where('kelas_id', $waliKelas->kelas_id)
            ->where('status', 'aktif')
            ->orderBy('nis')
            ->get();

        return Inertia::render('Guru/WaliKelas/Biodata', [
            'waliKelas' => [
                'id' => $waliKelas->id,
                'kelas' => $waliKelas->kelas?->displayName() ?? '-',
                'tahun_ajaran' => $waliKelas->tahunAjaran?->tahun ?? '-',
                'back_url' => route('guru.wali-kelas.index'),
            ],
            'students' => $students
                ->map(fn (Siswa $siswa) => $this->studentProps($waliKelas, $siswa))
                ->values(),
        ]);
    }

    public function update(Request $request, WaliKelas $waliKelas, Siswa $siswa)
    {
        $this->authorize('kelola-wali-kelas', $waliKelas);

        abort_unless(
            (int) $siswa->kelas_id === (int) $waliKelas->kelas_id && $siswa->status === 'aktif',
            404
        );

        $validated = $request->validate([
            'nama_lengkap' => 'required|string|max:100',
            'nama_panggilan' => 'nullable|string|max:100',
            'alamat' => 'nullable|string|max:2000',
            'tempat_lahir' => 'nullable|string|max:100',
            'tanggal_lahir' => 'nullable|date|before_or_equal:today',
            'hobi' => 'nullable|string|max:255',
            'cita_cita' => 'nullable|string|max:255',
            'nama_ayah' => 'nullable|string|max:150',
            'pekerjaan_ayah' => 'nullable|string|max:150',
            'nama_ibu' => 'nullable|string|max:150',
            'pekerjaan_ibu' => 'nullable|string|max:150',
            'penghasilan_orangtua' => 'nullable|integer|min:0|max:999999999999',
            'nama_wali' => 'nullable|string|max:150',
            'pekerjaan_wali' => 'nullable|string|max:150',
            'penyakit_kronis' => 'nullable|string|max:2000',
            'teman_dekat_sekolah' => 'nullable|string|max:1000',
            'teman_dekat_luar_sekolah' => 'nullable|string|max:1000',
            'jarak_rumah_km' => 'nullable|numeric|min:0|max:9999.99',
            'transportasi' => 'nullable|string|max:150',
            'kegiatan_luar_sekolah' => 'nullable|string|max:2000',
        ]);

        $namaLengkap = $validated['nama_lengkap'];
        unset($validated['nama_lengkap']);

        DB::transaction(function () use ($siswa, $namaLengkap, $validated) {
            $siswa->loadMissing('user');
            abort_unless($siswa->user, 404);

            $siswa->user->update([
                'nama_lengkap' => $namaLengkap,
            ]);

            BiodataSiswa::updateOrCreate(
                ['siswa_id' => $siswa->id],
                $validated
            );
        });

        return back()->with('success', 'Biodata siswa berhasil disimpan.');
    }

    private function studentProps(WaliKelas $waliKelas, Siswa $siswa): array
    {
        $biodata = $siswa->biodata;

        $data = [
            'nama_lengkap' => $siswa->user?->nama_lengkap ?? '',
            'nama_panggilan' => $biodata?->nama_panggilan ?? '',
            'alamat' => $biodata?->alamat ?? '',
            'tempat_lahir' => $biodata?->tempat_lahir ?? '',
            'tanggal_lahir' => $biodata?->tanggal_lahir?->format('Y-m-d') ?? '',
            'hobi' => $biodata?->hobi ?? '',
            'cita_cita' => $biodata?->cita_cita ?? '',
            'nama_ayah' => $biodata?->nama_ayah ?? '',
            'pekerjaan_ayah' => $biodata?->pekerjaan_ayah ?? '',
            'nama_ibu' => $biodata?->nama_ibu ?? '',
            'pekerjaan_ibu' => $biodata?->pekerjaan_ibu ?? '',
            'penghasilan_orangtua' => $biodata?->penghasilan_orangtua ?? '',
            'nama_wali' => $biodata?->nama_wali ?? '',
            'pekerjaan_wali' => $biodata?->pekerjaan_wali ?? '',
            'penyakit_kronis' => $biodata?->penyakit_kronis ?? '',
            'teman_dekat_sekolah' => $biodata?->teman_dekat_sekolah ?? '',
            'teman_dekat_luar_sekolah' => $biodata?->teman_dekat_luar_sekolah ?? '',
            'jarak_rumah_km' => $biodata?->jarak_rumah_km ?? '',
            'transportasi' => $biodata?->transportasi ?? '',
            'kegiatan_luar_sekolah' => $biodata?->kegiatan_luar_sekolah ?? '',
        ];

        $completedFields = collect($data)
            ->filter(fn ($value) => $value !== null && trim((string) $value) !== '')
            ->count();

        return [
            'id' => $siswa->id,
            'nis' => $siswa->nis,
            ...$data,
            'completed_fields' => $completedFields,
            'total_fields' => count($data),
            'update_url' => route('guru.wali-kelas.biodata.update', [$waliKelas, $siswa]),
        ];
    }
}
