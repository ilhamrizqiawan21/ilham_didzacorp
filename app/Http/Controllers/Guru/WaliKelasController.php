<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\AbsensiWaliKelas;
use App\Models\PenangananSiswa;
use App\Models\PertemuanWaliKelas;
use App\Models\Siswa;
use App\Models\WaliKelas;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class WaliKelasController extends Controller
{
    public function index()
    {
        $waliKelas = WaliKelas::with(['kelas', 'tahunAjaran'])
            ->where('guru_id', Auth::id())
            ->aktif()
            ->withCount([
                'absensi',
                'pertemuan',
                'penangananSiswa as penanganan_aktif_count' => fn($q) => $q->whereIn('status', ['baru', 'proses']),
            ])
            ->first();

        return Inertia::render('Guru/WaliKelas/Index', [
            'waliKelas' => $waliKelas ? [
                ...$this->waliKelasProps($waliKelas),
                'absensi_count' => $waliKelas->absensi_count ?? 0,
                'pertemuan_count' => $waliKelas->pertemuan_count ?? 0,
                'penanganan_aktif_count' => $waliKelas->penanganan_aktif_count ?? 0,
                'absensi_url' => route('guru.wali-kelas.absensi', $waliKelas),
                'pertemuan_url' => route('guru.wali-kelas.pertemuan', $waliKelas),
                'penanganan_url' => route('guru.wali-kelas.penanganan', $waliKelas),
            ] : null,
        ]);
    }

    public function absensi(Request $request, WaliKelas $waliKelas)
    {
        $this->authorize('kelola-wali-kelas', $waliKelas);

        $request->validate([
            'bulan' => 'nullable|date_format:Y-m',
        ]);

        $bulan = $request->input('bulan', date('Y-m'));
        $bulanOptions = $this->monthOptions($waliKelas, $bulan);
        $tanggalList = $this->schoolDays($bulan);
        $siswaList = $this->siswaAktif($waliKelas);
        $absensiData = $this->absensiData($waliKelas, $siswaList->pluck('id'), $bulan);

        return Inertia::render('Guru/WaliKelas/Absensi', [
            'waliKelas' => [
                ...$this->waliKelasProps($waliKelas),
                'store_url' => route('guru.wali-kelas.absensi.store', $waliKelas),
                'back_url' => route('guru.wali-kelas.index'),
            ],
            'bulan' => $bulan,
            'bulanLabel' => $bulanOptions[$bulan] ?? $bulan,
            'bulanOptions' => $bulanOptions,
            'tanggalList' => collect($tanggalList)->map(fn (Carbon $tanggal) => [
                'key' => $tanggal->format('Y-m-d'),
                'day' => $tanggal->format('d'),
                'label' => $tanggal->translatedFormat('D'),
            ])->values(),
            'students' => $siswaList->map(fn (Siswa $siswa, int $index) => [
                'id' => $siswa->id,
                'no' => $index + 1,
                'nis' => $siswa->nis,
                'nama' => $siswa->user?->nama_lengkap ?? '-',
                'absensi' => $absensiData[$siswa->id] ?? [],
            ])->values(),
        ]);
    }

    public function storeAbsensi(Request $request, WaliKelas $waliKelas)
    {
        $this->authorize('kelola-wali-kelas', $waliKelas);

        $validated = $request->validate([
            'bulan' => 'required|date_format:Y-m',
            'absensi' => 'nullable|array',
            'absensi.*' => 'array',
            'absensi.*.*' => 'nullable|in:hadir,sakit,izin,alpha',
        ]);

        $validTanggal = collect($this->schoolDays($validated['bulan']))
            ->map(fn(Carbon $date) => $date->format('Y-m-d'));
        $validSiswaIds = $this->siswaAktif($waliKelas)->pluck('id')->map(fn($id) => (string) $id);
        $absensiInput = $validated['absensi'] ?? [];

        if (collect(array_keys($absensiInput))->diff($validSiswaIds)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'absensi' => 'Data absensi berisi siswa yang tidak termasuk kelas wali ini.',
            ]);
        }

        foreach ($absensiInput as $siswaId => $tanggalData) {
            foreach ($tanggalData as $tanggal => $status) {
                if (!$validTanggal->contains($tanggal)) {
                    throw ValidationException::withMessages([
                        'absensi' => 'Data absensi berisi tanggal di luar hari sekolah bulan ini.',
                    ]);
                }

                $scope = [
                    'wali_kelas_id' => $waliKelas->id,
                    'siswa_id' => (int) $siswaId,
                    'tanggal' => $tanggal,
                ];

                if (!$status) {
                    AbsensiWaliKelas::where($scope)->delete();
                    continue;
                }

                AbsensiWaliKelas::updateOrCreate($scope, [
                    'status' => $status,
                ]);
            }
        }

        return back()->with('success', 'Absensi wali kelas berhasil disimpan.');
    }

    public function pertemuan(WaliKelas $waliKelas)
    {
        $this->authorize('kelola-wali-kelas', $waliKelas);

        $pertemuan = $waliKelas->pertemuan()
            ->orderBy('tanggal', 'desc')
            ->paginate(15);

        return Inertia::render('Guru/WaliKelas/Pertemuan', [
            'waliKelas' => [
                ...$this->waliKelasProps($waliKelas),
                'store_url' => route('guru.wali-kelas.pertemuan.store', $waliKelas),
                'back_url' => route('guru.wali-kelas.index'),
            ],
            'pertemuan' => $pertemuan->through(fn (PertemuanWaliKelas $item) => [
                'id' => $item->id,
                'tanggal' => $item->tanggal?->format('d/m/Y') ?? '-',
                'topik' => $item->topik,
                'hasil' => $item->hasil,
                'delete_url' => route('guru.wali-kelas.pertemuan.destroy', [$waliKelas, $item]),
            ]),
        ]);
    }

    public function storePertemuan(Request $request, WaliKelas $waliKelas)
    {
        $this->authorize('kelola-wali-kelas', $waliKelas);

        $validated = $request->validate([
            'tanggal' => 'required|date',
            'topik' => 'required|string|max:200',
            'hasil' => 'required|string',
        ]);

        $waliKelas->pertemuan()->create($validated);

        return back()->with('success', 'Pertemuan wali kelas berhasil dicatat.');
    }

    public function destroyPertemuan(WaliKelas $waliKelas, PertemuanWaliKelas $pertemuan)
    {
        $this->authorize('kelola-wali-kelas', $waliKelas);
        $this->ensureOwned($waliKelas, $pertemuan->wali_kelas_id);

        $pertemuan->delete();

        return back()->with('success', 'Pertemuan wali kelas berhasil dihapus.');
    }

    public function penanganan(WaliKelas $waliKelas)
    {
        $this->authorize('kelola-wali-kelas', $waliKelas);

        $siswaList = $this->siswaAktif($waliKelas);
        $penanganan = $waliKelas->penangananSiswa()
            ->with('siswa.user')
            ->orderByRaw("case status when 'baru' then 1 when 'proses' then 2 else 3 end")
            ->orderBy('updated_at', 'desc')
            ->paginate(15);

        return Inertia::render('Guru/WaliKelas/Penanganan', [
            'waliKelas' => [
                ...$this->waliKelasProps($waliKelas),
                'store_url' => route('guru.wali-kelas.penanganan.store', $waliKelas),
                'back_url' => route('guru.wali-kelas.index'),
            ],
            'siswaOptions' => $siswaList->map(fn (Siswa $siswa) => [
                'value' => $siswa->id,
                'label' => "{$siswa->nis} - " . ($siswa->user?->nama_lengkap ?? '-'),
            ])->values(),
            'penanganan' => $penanganan->through(fn (PenangananSiswa $item) => [
                'id' => $item->id,
                'siswa_id' => $item->siswa_id,
                'siswa' => $item->siswa?->user?->nama_lengkap ?? '-',
                'nis' => $item->siswa?->nis,
                'kondisi' => $item->kondisi,
                'deskripsi' => $item->deskripsi,
                'tindak_lanjut' => $item->tindak_lanjut,
                'hasil' => $item->hasil,
                'status' => $item->status,
                'update_url' => route('guru.wali-kelas.penanganan.update', [$waliKelas, $item]),
                'delete_url' => route('guru.wali-kelas.penanganan.destroy', [$waliKelas, $item]),
            ]),
        ]);
    }

    public function storePenanganan(Request $request, WaliKelas $waliKelas)
    {
        $this->authorize('kelola-wali-kelas', $waliKelas);

        $validated = $this->validatePenanganan($request, $waliKelas);
        $waliKelas->penangananSiswa()->create($validated);

        return back()->with('success', 'Penanganan siswa berhasil dicatat.');
    }

    public function updatePenanganan(Request $request, WaliKelas $waliKelas, PenangananSiswa $penanganan)
    {
        $this->authorize('kelola-wali-kelas', $waliKelas);
        $this->ensureOwned($waliKelas, $penanganan->wali_kelas_id);

        $penanganan->update($this->validatePenanganan($request, $waliKelas));

        return back()->with('success', 'Penanganan siswa berhasil diperbarui.');
    }

    public function destroyPenanganan(WaliKelas $waliKelas, PenangananSiswa $penanganan)
    {
        $this->authorize('kelola-wali-kelas', $waliKelas);
        $this->ensureOwned($waliKelas, $penanganan->wali_kelas_id);

        $penanganan->delete();

        return back()->with('success', 'Penanganan siswa berhasil dihapus.');
    }

    private function siswaAktif(WaliKelas $waliKelas)
    {
        return Siswa::with('user')
            ->where('kelas_id', $waliKelas->kelas_id)
            ->where('status', 'aktif')
            ->orderBy('nis')
            ->get();
    }

    private function schoolDays(string $bulan): array
    {
        $start = Carbon::createFromFormat('Y-m-d', "{$bulan}-01")->startOfDay();
        $end = $start->copy()->endOfMonth();
        $days = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if ($date->isWeekday()) {
                $days[] = $date->copy();
            }
        }

        return $days;
    }

    private function monthOptions(WaliKelas $waliKelas, string $bulan): array
    {
        $year = (int) substr($bulan, 0, 4);
        $startYear = (int) substr((string) $waliKelas->tahunAjaran?->tahun, 0, 4);
        if (!$startYear) {
            $monthNumber = (int) substr($bulan, 5, 2);
            $startYear = $monthNumber >= 7 ? $year : $year - 1;
        }

        $labels = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $months = [];
        foreach ([7, 8, 9, 10, 11, 12, 1, 2, 3, 4, 5, 6] as $month) {
            $optionYear = $month >= 7 ? $startYear : $startYear + 1;
            $months[sprintf('%04d-%02d', $optionYear, $month)] = "{$labels[$month]} {$optionYear}";
        }

        return $months;
    }

    private function absensiData(WaliKelas $waliKelas, $siswaIds, string $bulan): array
    {
        $raw = AbsensiWaliKelas::where('wali_kelas_id', $waliKelas->id)
            ->whereIn('siswa_id', $siswaIds)
            ->whereBetween('tanggal', ["{$bulan}-01", Carbon::createFromFormat('Y-m-d', "{$bulan}-01")->endOfMonth()->format('Y-m-d')])
            ->get();

        $data = [];
        foreach ($raw as $row) {
            $data[$row->siswa_id][$row->tanggal->format('Y-m-d')] = $row->status;
        }

        return $data;
    }

    private function validatePenanganan(Request $request, WaliKelas $waliKelas): array
    {
        $validated = $request->validate([
            'siswa_id' => 'required|exists:siswa,id',
            'kondisi' => 'required|string|max:200',
            'deskripsi' => 'nullable|string',
            'tindak_lanjut' => 'nullable|string',
            'hasil' => 'nullable|string',
            'status' => 'required|in:baru,proses,selesai',
        ]);

        $isSiswaKelas = Siswa::whereKey($validated['siswa_id'])
            ->where('kelas_id', $waliKelas->kelas_id)
            ->where('status', 'aktif')
            ->exists();

        if (!$isSiswaKelas) {
            throw ValidationException::withMessages([
                'siswa_id' => 'Siswa tidak termasuk kelas wali ini.',
            ]);
        }

        return $validated;
    }

    private function ensureOwned(WaliKelas $waliKelas, int $waliKelasId): void
    {
        if ((int) $waliKelas->id !== (int) $waliKelasId) {
            abort(404);
        }
    }

    private function waliKelasProps(WaliKelas $waliKelas): array
    {
        return [
            'id' => $waliKelas->id,
            'kelas' => $waliKelas->kelas?->displayName() ?? '-',
            'tahun_ajaran' => $waliKelas->tahunAjaran?->tahun ?? '-',
        ];
    }
}
