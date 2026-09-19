<?php

namespace App\Http\Controllers\Guru;

use App\Models\KelasMapel;
use App\Models\NilaiAkhir;
use App\Models\Pengaturan;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NilaiRekapController extends NilaiController
{
    public function rekap(Request $request)
    {
        $request->validate([
            'semester' => 'nullable|in:1,2',
            'kelas_mapel_id' => 'nullable|integer',
        ]);

        $kelasMapel = KelasMapel::with(['kelas', 'mataPelajaran'])
            ->where('guru_id', Auth::id())
            ->aktif()
            ->get();

        $tahunAjaran = TahunAjaran::getAktif();
        $semester = (string) $request->input('semester', Pengaturan::getValue('semester_aktif', '1'));

        $query = NilaiAkhir::with(['siswa.user', 'siswa.kelas', 'kelasMapel.mataPelajaran'])
            ->where('tahun_ajaran_id', $tahunAjaran?->id)
            ->where('semester', $semester)
            ->whereHas('kelasMapel', fn ($q) => $q->where('guru_id', Auth::id())->aktif($semester));

        if ($request->filled('kelas_mapel_id')) {
            $query->where('kelas_mapel_id', $request->integer('kelas_mapel_id'));
        }

        $nilai = $query->orderByDesc('rata_akhir')->paginate(30)->withQueryString();

        return Inertia::render('Guru/Rekap/Nilai', [
            'title' => 'Rekap Nilai Siswa',
            'semester' => $semester,
            'kelasMapel' => $kelasMapel->map(fn (KelasMapel $item) => [
                'id' => $item->id,
                'kelas' => $item->kelas?->displayName() ?? '-',
                'tingkat' => $item->kelas?->tingkat,
                'mata_pelajaran' => $item->mataPelajaran?->nama_mapel ?? '-',
                'label' => ($item->kelas?->displayName() ?? '-').' — '.($item->mataPelajaran?->nama_mapel ?? '-'),
            ])->values(),
            'nilai' => $nilai,
        ]);
    }
}
