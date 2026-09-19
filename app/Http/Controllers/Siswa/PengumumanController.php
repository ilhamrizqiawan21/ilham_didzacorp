<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\KelasMapel;
use App\Models\Pengumuman;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PengumumanController extends Controller
{
    public function index()
    {
        $siswa = Auth::user()->siswa;

        if (! $siswa) {
            return redirect()->route('login')->with('error', 'Data siswa tidak ditemukan.');
        }

        $pengumuman = $this->pengumumanQuery($siswa->kelas_id)
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Pengumuman $item) => $this->formatPengumuman($item));

        return Inertia::render('Siswa/Pengumuman/Index', [
            'pengumuman' => $pengumuman,
        ]);
    }

    public function show(Pengumuman $pengumuman)
    {
        $siswa = Auth::user()->siswa;

        if (! $siswa) {
            return redirect()->route('login')->with('error', 'Data siswa tidak ditemukan.');
        }

        abort_unless($this->pengumumanQuery($siswa->kelas_id)->whereKey($pengumuman->id)->exists(), 403);

        $pengumuman->loadMissing(['creator', 'kelasMapel.kelas', 'kelasMapel.mataPelajaran']);

        return Inertia::render('Siswa/Pengumuman/Show', [
            'pengumuman' => $this->formatPengumuman($pengumuman, true),
            'backUrl' => route('siswa.pengumuman.index'),
        ]);
    }

    private function pengumumanQuery(?int $kelasId)
    {
        $kelasMapelIds = KelasMapel::where('kelas_id', $kelasId)
            ->aktif()
            ->pluck('id');

        return Pengumuman::with(['creator', 'kelasMapel.kelas', 'kelasMapel.mataPelajaran'])
            ->where(function ($query) use ($kelasMapelIds, $kelasId) {
                $query->where('target', 'semua')
                    ->orWhere('target', 'siswa')
                    ->orWhere(function ($query) use ($kelasMapelIds, $kelasId) {
                        $query->where('target', 'kelas_mapel')
                            ->where(function ($query) use ($kelasMapelIds, $kelasId) {
                                $query->whereIn('kelas_mapel_id', $kelasMapelIds)
                                    ->orWhere('target_kelas', 'like', '%"' . $kelasId . '"%');
                            });
                    });
            })
            ->orderBy('created_at', 'desc');
    }

    private function formatPengumuman(Pengumuman $pengumuman, bool $full = false): array
    {
        return [
            'id' => $pengumuman->id,
            'judul' => $pengumuman->judul,
            'isi' => $full ? $pengumuman->isi : str($pengumuman->isi)->limit(120)->toString(),
            'target' => $pengumuman->target,
            'target_label' => $this->targetLabel($pengumuman),
            'creator' => $pengumuman->creator?->nama_lengkap ?? '-',
            'created_at' => $pengumuman->created_at ? Carbon::parse($pengumuman->created_at)->format('d M Y H:i') : '-',
            'show_url' => route('siswa.pengumuman.show', $pengumuman),
        ];
    }

    private function targetLabel(Pengumuman $pengumuman): string
    {
        if ($pengumuman->target === 'kelas_mapel') {
            $targetKelasIds = $pengumuman->targetKelasIds();
            if ($targetKelasIds !== []) {
                $labels = Kelas::whereIn('id', $targetKelasIds)
                    ->orderBy('tingkat')
                    ->orderBy('nama_kelas')
                    ->get()
                    ->map(fn (Kelas $kelas) => $kelas->displayName());

                if ($labels->isNotEmpty()) {
                    if ($labels->count() === 1) {
                        return (string) $labels->first();
                    }

                    if ($labels->count() === 2) {
                        return $labels->join(', ');
                    }

                    return $labels->count() . ' Kelas';
                }
            }

            $kelasLabel = $pengumuman->kelasMapel?->kelas?->displayName() ?? '-';
            $mapelLabel = $pengumuman->kelasMapel?->mataPelajaran?->nama_mapel ?? '-';

            return trim($kelasLabel . ' - ' . $mapelLabel);
        }

        return match ($pengumuman->target) {
            'semua' => 'Semua',
            'guru' => 'Guru',
            'siswa' => 'Siswa',
            default => $pengumuman->target,
        };
    }
}
