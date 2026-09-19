<?php

namespace App\Http\Controllers\Kepsek;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\KelasMapel;
use App\Models\MataPelajaran;
use App\Models\NilaiAkhir;
use App\Models\Pengaturan;
use App\Models\PengumpulanTugas;
use App\Models\SikapSosial;
use App\Models\SikapSpiritual;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use App\Models\WaliKelas;
use App\Services\AbsensiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
// Laporan Nilai, Sikap, Tugas, dan Absensi untuk Kepala Sekolah
class LaporanController extends Controller
{
    protected AbsensiService $absensiService;

    public function __construct(AbsensiService $absensiService)
    {
        $this->absensiService = $absensiService;
    }

    public function absensi(Request $request)
    {
        $kelasMapel = KelasMapel::with(['kelas', 'mataPelajaran', 'guru'])
            ->aktif()
            ->get();

        $query = Absensi::with(['siswa.user', 'kelasMapel.kelas', 'kelasMapel.mataPelajaran'])
            ->whereHas('kelasMapel', fn($q) => $q->aktif());

        if ($request->filled('kelas_mapel_id')) {
            $query->where('kelas_mapel_id', $request->kelas_mapel_id);
        }
        if ($request->filled('tanggal_awal')) {
            $query->where('tanggal', '>=', $request->tanggal_awal);
        }
        if ($request->filled('tanggal_akhir')) {
            $query->where('tanggal', '<=', $request->tanggal_akhir);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $absensi = $query->orderBy('tanggal', 'desc')->paginate(30)->withQueryString();

        return Inertia::render('Kepsek/Laporan/Absensi', [
            'absensi' => $absensi->through(fn (Absensi $item) => [
                'id' => $item->id,
                'nomor' => $absensi->firstItem() ? $absensi->firstItem() + $absensi->getCollection()->search($item) : null,
                'nama_siswa' => $item->siswa?->user?->nama_lengkap ?? $item->siswa?->nis ?? '-',
                'kelas' => $item->kelasMapel?->kelas?->nama_kelas ?? '-',
                'mapel' => $item->kelasMapel?->mataPelajaran?->nama_mapel ?? '-',
                'tanggal' => $item->tanggal ? Carbon::parse($item->tanggal)->format('d M Y') : '-',
                'status' => $item->status,
                'keterangan' => $item->keterangan ?? '-',
            ]),
            'kelasMapelOptions' => $kelasMapel->map(fn (KelasMapel $item) => [
                'value' => $item->id,
                'label' => trim(($item->kelas?->nama_kelas ?? '-') . ' - ' . ($item->mataPelajaran?->nama_mapel ?? '-')),
            ]),
            'filters' => [
                'kelas_mapel_id' => $request->input('kelas_mapel_id', ''),
                'tanggal_awal' => $request->input('tanggal_awal', ''),
                'tanggal_akhir' => $request->input('tanggal_akhir', ''),
                'status' => $request->input('status', ''),
            ],
            'resetUrl' => route('kepsek.laporan.absensi'),
            'exportUrls' => [
                'excel' => route('kepsek.export.laporan.absensi.excel'),
                'pdf' => route('kepsek.export.laporan.absensi.pdf'),
            ],
        ]);
    }

    public function nilai(Request $request)
    {
        $kelas = Kelas::orderBy('tingkat')->orderBy('nama_kelas')->get();
        $mapel = MataPelajaran::orderBy('urutan')->get();
        $taAktif = TahunAjaran::getAktif();
        $semester = $request->input('semester', Pengaturan::getValue('semester_aktif', '1'));

        $query = NilaiAkhir::with(['siswa.user', 'siswa.kelas', 'kelasMapel.kelas', 'kelasMapel.mataPelajaran', 'tahunAjaran'])
            ->where('tahun_ajaran_id', $taAktif?->id)
            ->where('semester', $semester);

        if ($request->filled('kelas_id')) {
            $query->whereHas('kelasMapel', fn($q) => $q->where('kelas_id', $request->kelas_id));
        }
        if ($request->filled('mapel_id')) {
            $query->whereHas('kelasMapel', fn($q) => $q->where('mapel_id', $request->mapel_id));
        }
        $nilai = $query->orderByRaw(NilaiAkhir::rataAkhirExpression().' desc')->paginate(30)->withQueryString();

        return Inertia::render('Kepsek/Laporan/Nilai', [
            'nilai' => $nilai->through(fn (NilaiAkhir $item) => [
                'id' => $item->id,
                'siswa' => $item->siswa?->user?->nama_lengkap ?? '-',
                'kelas' => $item->siswa?->kelas?->nama_kelas ?? $item->kelasMapel?->kelas?->nama_kelas ?? '-',
                'mapel' => $item->kelasMapel?->mataPelajaran?->nama_mapel ?? '-',
                'sum1' => $item->sum1,
                'sum2' => $item->sum2,
                'sum3' => $item->sum3,
                'sum4' => $item->sum4,
                'nilai_harian' => $item->nilai_harian,
                'sts' => $item->sts,
                'sas' => $item->sas,
                'sat' => $item->sat,
                'rata_akhir' => $item->rata_akhir,
            ]),
            'kelasOptions' => $kelas->map(fn (Kelas $item) => [
                'value' => $item->id,
                'label' => $item->nama_kelas,
            ]),
            'mapelOptions' => $mapel->map(fn (MataPelajaran $item) => [
                'value' => $item->id,
                'label' => $item->nama_mapel,
            ]),
            'filters' => [
                'kelas_id' => $request->input('kelas_id', ''),
                'mapel_id' => $request->input('mapel_id', ''),
                'semester' => $semester,
            ],
            'taAktif' => $taAktif ? [
                'id' => $taAktif->id,
                'tahun' => $taAktif->tahun,
            ] : null,
            'resetUrl' => route('kepsek.laporan.nilai'),
            'exportUrls' => [
                'excel' => route('kepsek.export.laporan.nilai.excel'),
                'pdf' => route('kepsek.export.laporan.nilai.pdf'),
            ],
        ]);
    }

    public function rekapAbsensi()
    {
        $kelas = Kelas::withCount(['siswa' => fn($q) => $q->where('status', 'aktif')])->get();
        $rekap = [];

        foreach ($kelas as $k) {
            $total = Absensi::whereHas('kelasMapel', fn($q) => $q->where('kelas_id', $k->id)->aktif())
                ->count();
            $hadir = Absensi::whereHas('kelasMapel', fn($q) => $q->where('kelas_id', $k->id)->aktif())
                ->where('status', 'hadir')
                ->count();

            $rekap[] = [
                'kelas' => $k,
                'total_absensi' => $total,
                'total_hadir' => $hadir,
                'persen' => $total > 0 ? round(($hadir / $total) * 100, 2) : 0,
            ];
        }

        return Inertia::render('Kepsek/Laporan/RekapAbsensi', [
            'rekap' => collect($rekap)->map(fn (array $item) => [
                'kelas_id' => $item['kelas']->id,
                'kelas' => $item['kelas']->nama_kelas,
                'jumlah_siswa' => (int) ($item['kelas']->siswa_count ?? 0),
                'total_absensi' => (int) $item['total_absensi'],
                'total_hadir' => (int) $item['total_hadir'],
                'persen' => (float) $item['persen'],
            ]),
            'exportUrls' => [
                'excel' => route('kepsek.export.laporan.rekap-absensi.excel'),
                'pdf' => route('kepsek.export.laporan.rekap-absensi.pdf'),
            ],
        ]);
    }

    public function rekapTugas(Request $request)
    {
        $kelas = Kelas::orderBy('tingkat')->orderBy('nama_kelas')->get();

        $query = Tugas::with([
            'kelasMapel.kelas',
            'kelasMapel.mataPelajaran',
            'kelasMapel.guru',
            'pengumpulan.siswa.user',
        ])->whereHas('kelasMapel', fn($q) => $q->aktif());

        if ($request->filled('kelas_id')) {
            $query->whereHas('kelasMapel', fn($q) => $q->where('kelas_id', $request->kelas_id));
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where('judul', 'like', "%{$s}%");
        }

        $tugas = $query->orderBy('batas_waktu', 'desc')->paginate(20)->withQueryString();

        // Hitung statistik per tugas
        foreach ($tugas as $t) {
            $t->total_siswa = $t->pengumpulan->count();
            $t->sudah_kumpul = $t->pengumpulan->whereIn('status', PengumpulanTugas::STATUS_SUBMITTED)->count();
            $t->belum_kumpul = $t->pengumpulan->where('status', 'belum')->count();
            $t->rata_nilai = $t->pengumpulan->whereNotNull('nilai')->avg('nilai');
        }

        return Inertia::render('Kepsek/Laporan/RekapTugas', [
            'tugas' => $tugas->through(fn (Tugas $item) => [
                'id' => $item->id,
                'judul' => $item->judul,
                'judul_ringkas' => \Illuminate\Support\Str::limit($item->judul, 35),
                'kategori_nilai' => $item->kategori_nilai,
                'mapel' => $item->kelasMapel?->mataPelajaran?->nama_mapel ?? '-',
                'kelas' => $item->kelasMapel?->kelas?->nama_kelas ?? '-',
                'guru' => $item->kelasMapel?->guru?->nama_lengkap ?? '-',
                'batas_waktu' => $item->batas_waktu?->format('d M Y'),
                'is_past_due' => $item->batas_waktu ? $item->batas_waktu->isPast() : false,
                'total_siswa' => (int) $item->total_siswa,
                'sudah_kumpul' => (int) $item->sudah_kumpul,
                'belum_kumpul' => (int) $item->belum_kumpul,
                'rata_nilai' => is_numeric($item->rata_nilai) ? number_format($item->rata_nilai, 1) : null,
                'persen_kumpul' => $item->total_siswa > 0 ? round(($item->sudah_kumpul / max($item->total_siswa, 1)) * 100) : null,
            ]),
            'kelasOptions' => $kelas->map(fn (Kelas $item) => [
                'value' => $item->id,
                'label' => $item->nama_kelas,
            ]),
            'filters' => [
                'kelas_id' => $request->input('kelas_id', ''),
                'search' => $request->input('search', ''),
            ],
            'resetUrl' => route('kepsek.laporan.rekap-tugas'),
            'exportUrls' => [
                'excel' => route('kepsek.export.laporan.rekap-tugas.excel'),
                'pdf' => route('kepsek.export.laporan.rekap-tugas.pdf'),
            ],
        ]);
    }

    public function rekapSikap(Request $request)
    {
        $kelas = Kelas::orderBy('tingkat')->orderBy('nama_kelas')->get();

        $kelasId = $request->input('kelas_id');
        $taAktif = TahunAjaran::getAktif();
        $semester = Pengaturan::getValue('semester_aktif', '1');

        // Sikap Sosial
        $sosialQuery = SikapSosial::with(['siswa.user', 'siswa.kelas', 'kelasMapel.mataPelajaran'])
            ->where('tahun_ajaran_id', $taAktif?->id)
            ->where('semester', $semester);

        if ($kelasId) {
            $sosialQuery->whereHas('siswa', fn($q) => $q->where('kelas_id', $kelasId));
        }

        $sikapSosial = $sosialQuery->get()->groupBy('siswa_id')->map(function ($records) {
            $first = $records->first();
            return [
                'siswa' => $first->siswa,
                'mapel_count' => $records->count(),
                'empati' => round($records->avg('empati'), 1),
                'kerjasama' => round($records->avg('kerjasama'), 1),
                'toleransi' => round($records->avg('toleransi'), 1),
                'percaya_diri' => round($records->avg('percaya_diri'), 1),
                'komunikasi' => round($records->avg('komunikasi'), 1),
            ];
        })->values();

        // Sikap Spiritual
        $spiritualQuery = SikapSpiritual::with(['siswa.user', 'siswa.kelas', 'kelasMapel.mataPelajaran'])
            ->where('tahun_ajaran_id', $taAktif?->id)
            ->where('semester', $semester);

        if ($kelasId) {
            $spiritualQuery->whereHas('siswa', fn($q) => $q->where('kelas_id', $kelasId));
        }

        $sikapSpiritual = $spiritualQuery->get()->groupBy('siswa_id')->map(function ($records) {
            $first = $records->first();
            return [
                'siswa' => $first->siswa,
                'mapel_count' => $records->count(),
                'taqwa' => round($records->avg('taqwa'), 1),
                'kejujuran' => round($records->avg('kejujuran'), 1),
                'disiplin' => round($records->avg('disiplin'), 1),
                'sabar' => round($records->avg('sabar'), 1),
                'syukur' => round($records->avg('syukur'), 1),
                'tawadhu' => round($records->avg('tawadhu'), 1),
            ];
        })->values();

        return Inertia::render('Kepsek/Laporan/RekapSikap', [
            'sikapSosial' => $sikapSosial->map(fn (array $item, int $index) => [
                'nomor' => $index + 1,
                'nama_siswa' => $item['siswa']?->user?->nama_lengkap ?? $item['siswa']?->nis ?? '-',
                'kelas' => $item['siswa']?->kelas?->nama_kelas ?? '-',
                'mapel_count' => $item['mapel_count'],
                'empati' => $item['empati'],
                'kerjasama' => $item['kerjasama'],
                'toleransi' => $item['toleransi'],
                'percaya_diri' => $item['percaya_diri'],
                'komunikasi' => $item['komunikasi'],
            ]),
            'sikapSpiritual' => $sikapSpiritual->map(fn (array $item, int $index) => [
                'nomor' => $index + 1,
                'nama_siswa' => $item['siswa']?->user?->nama_lengkap ?? $item['siswa']?->nis ?? '-',
                'kelas' => $item['siswa']?->kelas?->nama_kelas ?? '-',
                'mapel_count' => $item['mapel_count'],
                'taqwa' => $item['taqwa'],
                'kejujuran' => $item['kejujuran'],
                'disiplin' => $item['disiplin'],
                'sabar' => $item['sabar'],
                'syukur' => $item['syukur'],
                'tawadhu' => $item['tawadhu'],
            ]),
            'kelasOptions' => $kelas->map(fn (Kelas $item) => [
                'value' => $item->id,
                'label' => $item->nama_kelas,
            ]),
            'filters' => [
                'kelas_id' => $kelasId ?? '',
            ],
            'semester' => $semester,
            'taAktif' => $taAktif ? [
                'id' => $taAktif->id,
                'tahun' => $taAktif->tahun,
            ] : null,
            'resetUrl' => route('kepsek.laporan.rekap-sikap'),
            'exportUrls' => [
                'excel' => route('kepsek.export.laporan.rekap-sikap.excel'),
                'pdf' => route('kepsek.export.laporan.rekap-sikap.pdf'),
            ],
        ]);
    }

    public function waliKelas()
    {
        $waliKelas = WaliKelas::with(['kelas', 'guru', 'tahunAjaran'])
            ->aktif()
            ->withCount([
                'absensi',
                'pertemuan',
                'penangananSiswa',
                'penangananSiswa as penanganan_aktif_count' => fn($q) => $q->whereIn('status', ['baru', 'proses']),
            ])
            ->orderBy('kelas_id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Kepsek/Laporan/WaliKelas/Index', [
            'waliKelas' => $waliKelas->through(fn (WaliKelas $item) => [
                'id' => $item->id,
                'kelas' => trim(($item->kelas?->tingkat ?? '') . ' ' . ($item->kelas?->nama_kelas ?? '')) ?: '-',
                'guru' => $item->guru?->nama_lengkap ?? '-',
                'tahun_ajaran' => $item->tahunAjaran?->tahun ?? '-',
                'absensi_count' => $item->absensi_count,
                'pertemuan_count' => $item->pertemuan_count,
                'penanganan_siswa_count' => $item->penanganan_siswa_count,
                'penanganan_aktif_count' => $item->penanganan_aktif_count,
                'show_url' => route('kepsek.laporan.wali-kelas.show', $item),
            ]),
        ]);
    }

    public function waliKelasShow(Request $request, WaliKelas $waliKelas)
    {
        $this->authorize('lihat-laporan-wali-kelas', $waliKelas);

        $request->validate([
            'bulan' => 'nullable|date_format:Y-m',
        ]);

        $bulan = $request->input('bulan', date('Y-m'));
        $bulanOptions = $this->waliKelasMonthOptions($waliKelas, $bulan);
        $tanggalList = $this->schoolDays($bulan);
        $siswaList = $waliKelas->kelas->siswa()
            ->with('user')
            ->where('status', 'aktif')
            ->orderBy('nis')
            ->get();

        $absensiRaw = $waliKelas->absensi()
            ->whereIn('siswa_id', $siswaList->pluck('id'))
            ->whereBetween('tanggal', ["{$bulan}-01", Carbon::createFromFormat('Y-m-d', "{$bulan}-01")->endOfMonth()->format('Y-m-d')])
            ->get();

        $absensiData = [];
        foreach ($absensiRaw as $row) {
            $absensiData[$row->siswa_id][$row->tanggal->format('Y-m-d')] = $row->status;
        }

        $pertemuan = $waliKelas->pertemuan()
            ->orderBy('tanggal', 'desc')
            ->take(20)
            ->get();

        $penanganan = $waliKelas->penangananSiswa()
            ->with('siswa.user')
            ->orderByRaw("case status when 'baru' then 1 when 'proses' then 2 else 3 end")
            ->orderBy('updated_at', 'desc')
            ->take(20)
            ->get();

        $tanggalProps = collect($tanggalList)->map(fn (Carbon $tanggal) => [
            'date' => $tanggal->format('Y-m-d'),
            'day' => $tanggal->format('d'),
        ]);

        return Inertia::render('Kepsek/Laporan/WaliKelas/Show', [
            'waliKelas' => [
                'id' => $waliKelas->id,
                'title' => trim(($waliKelas->kelas?->tingkat ?? '') . ' ' . ($waliKelas->kelas?->nama_kelas ?? '') . ' - ' . ($waliKelas->guru?->nama_lengkap ?? '')) ?: '-',
                'kelas' => trim(($waliKelas->kelas?->tingkat ?? '') . ' ' . ($waliKelas->kelas?->nama_kelas ?? '')) ?: '-',
                'guru' => $waliKelas->guru?->nama_lengkap ?? '-',
            ],
            'bulan' => $bulan,
            'bulanOptions' => collect($bulanOptions)->map(fn ($label, $value) => [
                'value' => $value,
                'label' => $label,
            ])->values(),
            'tanggalList' => $tanggalProps,
            'siswaRows' => $siswaList->map(function ($siswa) use ($tanggalProps, $absensiData) {
                $counts = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpha' => 0];
                $statuses = $tanggalProps->map(function (array $tanggal) use ($siswa, $absensiData, &$counts) {
                    $status = $absensiData[$siswa->id][$tanggal['date']] ?? null;
                    if ($status && array_key_exists($status, $counts)) {
                        $counts[$status]++;
                    }

                    return [
                        'date' => $tanggal['date'],
                        'status' => $status,
                        'label' => ['hadir' => 'H', 'sakit' => 'S', 'izin' => 'I', 'alpha' => 'A'][$status] ?? '-',
                    ];
                });

                return [
                    'id' => $siswa->id,
                    'nis' => $siswa->nis,
                    'nama' => $siswa->user?->nama_lengkap ?? '-',
                    'statuses' => $statuses,
                    'counts' => $counts,
                ];
            }),
            'pertemuan' => $pertemuan->map(fn ($item) => [
                'id' => $item->id,
                'tanggal' => $item->tanggal?->format('d/m/Y') ?? '-',
                'topik' => $item->topik,
                'hasil' => $item->hasil,
            ]),
            'penanganan' => $penanganan->map(fn ($item) => [
                'id' => $item->id,
                'siswa' => $item->siswa?->user?->nama_lengkap ?? '-',
                'nis' => $item->siswa?->nis,
                'kondisi' => $item->kondisi,
                'tindak_lanjut' => $item->tindak_lanjut,
                'status' => $item->status,
            ]),
            'backUrl' => route('kepsek.laporan.wali-kelas'),
            'resetUrl' => route('kepsek.laporan.wali-kelas.show', $waliKelas),
        ]);
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

    private function waliKelasMonthOptions(WaliKelas $waliKelas, string $bulan): array
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
}
