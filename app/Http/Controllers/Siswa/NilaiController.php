<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\NilaiAkhir;
use App\Models\Siswa;
use App\Services\NilaiService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NilaiController extends Controller
{
    protected NilaiService $nilaiService;

    public function __construct(NilaiService $nilaiService)
    {
        $this->nilaiService = $nilaiService;
    }

    public function index()
    {
        $user = Auth::user();
        $siswa = $user->siswa;

        if (!$siswa) {
            return redirect()->route('login')->with('error', 'Data siswa tidak ditemukan.');
        }

        $nilaiList = NilaiAkhir::with(['kelasMapel.mataPelajaran', 'tahunAjaran'])
            ->where('siswa_id', $siswa->id)
            ->orderBy('tahun_ajaran_id', 'desc')
            ->orderBy('semester', 'desc')
            ->get()
            ->groupBy(function ($item) {
                return $item->tahunAjaran?->tahun . ' - Semester ' . $item->semester;
            });

        return Inertia::render('Siswa/Nilai/Index', [
            'nilaiGroups' => $nilaiList->map(fn ($items, string $periode) => [
                'periode' => $periode,
                'nilai' => $items->map(fn (NilaiAkhir $item) => [
                    'id' => $item->id,
                    'mata_pelajaran' => $item->kelasMapel?->mataPelajaran?->nama_mapel ?? '-',
                    'sum1' => $item->sum1,
                    'sum2' => $item->sum2,
                    'sum3' => $item->sum3,
                    'sum4' => $item->sum4,
                    'nilai_harian' => $item->nilai_harian,
                    'sts' => $item->sts,
                    'sas' => $item->sas,
                    'sat' => $item->sat,
                    'rata_akhir' => $item->rata_akhir,
                ])->values(),
            ])->values(),
        ]);
    }
}
