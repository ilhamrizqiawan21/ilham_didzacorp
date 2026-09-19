<?php

namespace App\Http\Controllers\Guru;

use App\Models\KelasMapel;
use App\Models\Pengaturan;
use App\Models\SikapSosial;
use App\Models\SikapSpiritual;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SikapRekapController extends SikapController
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
        $kmId = $request->input('kelas_mapel_id');

        $sosialQuery = SikapSosial::with(['siswa.user', 'siswa.kelas', 'kelasMapel.mataPelajaran'])
            ->where('tahun_ajaran_id', $tahunAjaran?->id)
            ->where('semester', $semester)
            ->whereHas('kelasMapel', fn ($q) => $q->where('guru_id', Auth::id())->aktif($semester));
        if ($kmId) $sosialQuery->where('kelas_mapel_id', $kmId);

        $sosialFields = ['empati', 'kerjasama', 'toleransi', 'percaya_diri', 'komunikasi'];
        $sikapSosial = $sosialQuery->get()->groupBy('siswa_id')->map(function ($records) use ($sosialFields) {
            $first = $records->first();
            $values = collect($sosialFields)->mapWithKeys(fn ($field) => [$field => round((float) $records->avg($field), 1)]);
            return [
                'siswa' => ['id' => $first->siswa?->id, 'nis' => $first->siswa?->nis, 'nama' => $first->siswa?->user?->nama_lengkap ?? $first->siswa?->nis, 'kelas' => $first->siswa?->kelas?->displayName()],
                'nilai' => $values->all(),
                'rata' => round((float) $values->avg(), 1),
            ];
        })->values();

        $spiritualQuery = SikapSpiritual::with(['siswa.user', 'siswa.kelas', 'kelasMapel.mataPelajaran'])
            ->where('tahun_ajaran_id', $tahunAjaran?->id)
            ->where('semester', $semester)
            ->whereHas('kelasMapel', fn ($q) => $q->where('guru_id', Auth::id())->aktif($semester));
        if ($kmId) $spiritualQuery->where('kelas_mapel_id', $kmId);

        $spiritualFields = ['taqwa', 'kejujuran', 'disiplin', 'sabar', 'syukur', 'tawadhu'];
        $sikapSpiritual = $spiritualQuery->get()->groupBy('siswa_id')->map(function ($records) use ($spiritualFields) {
            $first = $records->first();
            $values = collect($spiritualFields)->mapWithKeys(fn ($field) => [$field => round((float) $records->avg($field), 1)]);
            return [
                'siswa' => ['id' => $first->siswa?->id, 'nis' => $first->siswa?->nis, 'nama' => $first->siswa?->user?->nama_lengkap ?? $first->siswa?->nis, 'kelas' => $first->siswa?->kelas?->displayName()],
                'nilai' => $values->all(),
                'rata' => round((float) $values->avg(), 1),
            ];
        })->values();

        return Inertia::render('Guru/Rekap/Sikap', [
            'title' => 'Rekap Sikap Spiritual & Sosial',
            'semester' => $semester,
            'kelasMapel' => $kelasMapel->map(fn (KelasMapel $item) => [
                'id' => $item->id,
                'label' => ($item->kelas?->displayName() ?? '-').' — '.($item->mataPelajaran?->nama_mapel ?? '-'),
            ])->values(),
            'sikapSpiritual' => $sikapSpiritual,
            'sikapSosial' => $sikapSosial,
        ]);
    }
}
