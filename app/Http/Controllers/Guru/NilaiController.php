<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guru\RekapNilaiRequest;
use App\Http\Requests\Guru\StoreBulkNilaiRequest;
use App\Http\Requests\Guru\StoreNilaiRequest;
use App\Models\AcademicAuditLog;
use App\Models\KelasMapel;
use App\Models\NilaiAkhir;
use App\Models\Notifikasi;
use App\Models\Pengaturan;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Services\NilaiService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
        $kelasMapel = KelasMapel::with(['kelas', 'mataPelajaran'])
            ->where('guru_id', Auth::id())
            ->aktif()
            ->get();

        $tahunAjaran = TahunAjaran::getAktif();
        $semester = Pengaturan::getValue('semester_aktif', '1');
        $fields = ['sum1', 'sum2', 'sum3', 'sum4', 'nilai_harian', 'sts', 'sas', 'sat'];

        return Inertia::render('Guru/Nilai/Index', [
            'kelasMapel' => $this->formatKelasMapelOptions($kelasMapel),
            'tahunAjaran' => $tahunAjaran ? [
                'id' => $tahunAjaran->id,
                'tahun' => $tahunAjaran->tahun,
            ] : null,
            'semester' => $semester,
            'groups' => $kelasMapel->map(fn (KelasMapel $item) => $this->buildNilaiGroup($item, $tahunAjaran, $semester, $fields))->values(),
            'storeUrl' => route('guru.nilai.store.bulk'),
        ]);
    }

    // Input nilai
    public function input(KelasMapel $kelasMapel)
    {
        $this->authorize('mengajar', $kelasMapel);

        $tahunAjaran = TahunAjaran::getAktif();
        $semester = Pengaturan::getValue('semester_aktif', '1');

        $siswa = Siswa::with('user')
            ->where('kelas_id', $kelasMapel->kelas_id)
            ->where('status', 'aktif')
            ->orderBy('nis')
            ->get();

        $nilaiList = NilaiAkhir::where('kelas_mapel_id', $kelasMapel->id)
            ->where('tahun_ajaran_id', $tahunAjaran?->id)
            ->where('semester', $semester)
            ->get()
            ->keyBy('siswa_id');

        $fields = ['sum1', 'sum2', 'sum3', 'sum4', 'nilai_harian', 'sts', 'sas', 'sat'];

        return Inertia::render('Guru/Nilai/Input', [
            'kelasMapel' => [
                'id' => $kelasMapel->id,
                'kelas' => $kelasMapel->kelas?->displayName() ?? '-',
                'mata_pelajaran' => $kelasMapel->mataPelajaran?->nama_mapel ?? '-',
                'workspace_url' => route('guru.kelas-mapel.show', $kelasMapel),
                'store_url' => route('guru.nilai.store', $kelasMapel),
                'export_excel_url' => route('guru.nilai.export.excel', $kelasMapel),
                'export_pdf_url' => route('guru.nilai.export.pdf', $kelasMapel),
            ],
            'tahunAjaran' => $tahunAjaran ? [
                'id' => $tahunAjaran->id,
                'tahun' => $tahunAjaran->tahun,
            ] : null,
            'semester' => $semester,
            'students' => $siswa->values()->map(function (Siswa $student, int $index) use ($nilaiList, $fields) {
                $nilai = $nilaiList->get($student->id);
                $scores = collect($fields)
                    ->mapWithKeys(fn (string $field) => [$field => $nilai?->$field])
                    ->all();

                return [
                    'id' => $student->id,
                    'no' => $index + 1,
                    'nis' => $student->nis,
                    'nama' => $student->user?->nama_lengkap ?? $student->nis,
                    'scores' => $scores,
                    'rata_akhir' => $nilai?->rata_akhir,
                ];
            }),
        ]);
    }

    // Simpan nilai
    public function store(StoreNilaiRequest $request, KelasMapel $kelasMapel)
    {
        $this->authorize('mengajar', $kelasMapel);

        $this->saveNilaiForKelasMapel($kelasMapel, $request->semester, $request->input('nilai', []));

        return redirect()->route('guru.nilai.input', $kelasMapel)
            ->with('success', 'Nilai berhasil disimpan.');
    }

    public function storeBulk(StoreBulkNilaiRequest $request)
    {
        $kelasMapel = KelasMapel::with(['kelas', 'mataPelajaran'])
            ->where('guru_id', Auth::id())
            ->aktif()
            ->whereIn('id', $request->input('kelas_mapel_ids', []))
            ->get();

        if ($kelasMapel->count() !== count(array_unique($request->input('kelas_mapel_ids', [])))) {
            return back()->withInput()->with('error', 'Pilihan kelas tidak valid.');
        }

        foreach ($kelasMapel as $item) {
            $this->saveNilaiForKelasMapel(
                $item,
                $request->semester,
                $request->input('nilai.'.$item->id, [])
            );
        }

        return redirect()->route('guru.nilai.index')
            ->with('success', 'Nilai berhasil disimpan untuk kelas yang dipilih.');
    }

    // Bahan untuk merekap nilai siswa per kelas dan mata pelajaran yang diampu oleh guru
    public function rekap(RekapNilaiRequest $request)
    {
        $kelasMapel = KelasMapel::with(['kelas', 'mataPelajaran'])
            ->where('guru_id', Auth::id())
            ->aktif()
            ->get();

        $tahunAjaran = TahunAjaran::getAktif();
        $semester = $request->input('semester', Pengaturan::getValue('semester_aktif', '1'));

        $query = NilaiAkhir::with(['siswa.user', 'siswa.kelas', 'kelasMapel.mataPelajaran'])
            ->where('tahun_ajaran_id', $tahunAjaran?->id)
            ->where('semester', $semester)
            ->whereHas('kelasMapel', fn ($q) => $q->where('guru_id', Auth::id())->aktif($semester));

        if ($request->filled('kelas_mapel_id')) {
            $query->where('kelas_mapel_id', $request->kelas_mapel_id);
        }

        $nilai = $query->orderBy('rata_akhir', 'desc')->paginate(30);

        return view('guru.rekap-nilai', compact('nilai', 'kelasMapel', 'semester'));
    }

    private function buildNilaiGroup(KelasMapel $kelasMapel, ?TahunAjaran $tahunAjaran, string $semester, array $fields): array
    {
        $siswa = Siswa::with('user')
            ->where('kelas_id', $kelasMapel->kelas_id)
            ->where('status', 'aktif')
            ->orderBy('nis')
            ->get();

        $nilaiList = NilaiAkhir::where('kelas_mapel_id', $kelasMapel->id)
            ->where('tahun_ajaran_id', $tahunAjaran?->id)
            ->where('semester', $semester)
            ->get()
            ->keyBy('siswa_id');

        $kelasLabel = $kelasMapel->kelas?->displayName() ?? '-';

        return [
            'kelas_mapel_id' => $kelasMapel->id,
            'kelas' => $kelasLabel,
            'mata_pelajaran' => $kelasMapel->mataPelajaran?->nama_mapel ?? '-',
            'label' => $kelasLabel.' - '.($kelasMapel->mataPelajaran?->nama_mapel ?? '-').' (Sem. '.$kelasMapel->semester.')',
            'export_excel_url' => route('guru.nilai.export.excel', $kelasMapel),
            'export_pdf_url' => route('guru.nilai.export.pdf', $kelasMapel),
            'students' => $siswa->values()->map(function (Siswa $student, int $index) use ($nilaiList, $fields) {
                $nilai = $nilaiList->get($student->id);
                $scores = collect($fields)
                    ->mapWithKeys(fn (string $field) => [$field => $nilai?->$field])
                    ->all();

                return [
                    'id' => $student->id,
                    'no' => $index + 1,
                    'nis' => $student->nis,
                    'nama' => $student->user?->nama_lengkap ?? $student->nis,
                    'scores' => $scores,
                    'rata_akhir' => $nilai?->rata_akhir,
                ];
            })->values(),
        ];
    }

    private function formatKelasMapelOptions($kelasMapel)
    {
        return $kelasMapel->map(function (KelasMapel $item) {
            $kelasLabel = $item->kelas?->displayName() ?? '-';

            return [
                'id' => $item->id,
                'kelas' => $kelasLabel,
                'mata_pelajaran' => $item->mataPelajaran?->nama_mapel ?? '-',
                'semester' => $item->semester,
                'label' => $kelasLabel.' - '.($item->mataPelajaran?->nama_mapel ?? '-').' (Sem. '.$item->semester.')',
                'href' => route('guru.nilai.input', $item),
            ];
        })->values();
    }

    private function saveNilaiForKelasMapel(KelasMapel $kelasMapel, string $semester, array $nilaiInput): void
    {
        $tahunAjaran = TahunAjaran::getAktif();
        if (! $tahunAjaran) {
            throw ValidationException::withMessages([
                'tahun_ajaran' => 'Tahun ajaran aktif belum diatur.',
            ]);
        }

        $siswas = Siswa::where('kelas_id', $kelasMapel->kelas_id)
            ->where('status', 'aktif')
            ->get()
            ->keyBy('id');

        $invalidSiswaIds = collect(array_keys($nilaiInput))
            ->reject(fn ($siswaId) => $siswas->has((int) $siswaId));

        if ($invalidSiswaIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                'nilai' => 'Data nilai berisi siswa yang tidak termasuk kelas ini.',
            ]);
        }

        $fields = ['sum1', 'sum2', 'sum3', 'sum4', 'sts', 'sas', 'sat'];
        $existingNilai = NilaiAkhir::where('kelas_mapel_id', $kelasMapel->id)
            ->where('tahun_ajaran_id', $tahunAjaran->id)
            ->where('semester', $semester)
            ->get()
            ->keyBy('siswa_id');

        DB::transaction(function () use ($nilaiInput, $fields, $existingNilai, $kelasMapel, $tahunAjaran, $semester, $siswas) {
            foreach ($nilaiInput as $siswaId => $data) {
                $nilaiData = collect($fields)
                    ->mapWithKeys(fn ($field) => [$field => ($data[$field] ?? null) === '' ? null : ($data[$field] ?? null)])
                    ->all();

                $existing = $existingNilai->get((int) $siswaId);
                $nilaiHarian = $existing?->nilai_harian;

                if (collect($nilaiData)->filter(fn ($value) => ! is_null($value))->isEmpty() && $nilaiHarian === null) {
                    NilaiAkhir::where([
                        'siswa_id' => (int) $siswaId,
                        'kelas_mapel_id' => $kelasMapel->id,
                        'tahun_ajaran_id' => $tahunAjaran->id,
                        'semester' => $semester,
                    ])->delete();

                    continue;
                }

                $nilai = $this->nilaiService->simpanNilai([
                    'siswa_id' => $siswaId,
                    'kelas_mapel_id' => $kelasMapel->id,
                    'tahun_ajaran_id' => $tahunAjaran->id,
                    'semester' => $semester,
                    'nilai_harian' => $nilaiHarian,
                ] + $nilaiData);

                $siswa = $siswas->get((int) $siswaId);
                $nilaiChanged = $nilai->wasRecentlyCreated || $nilai->wasChanged($fields);

                if ($siswa && $nilaiChanged) {
                    $this->logNilaiChange($kelasMapel, $siswa, $semester, $fields, $existing, $nilai);
                }

                if ($siswa && $siswa->user_id && $nilaiChanged) {
                    Notifikasi::firstOrCreate([
                        'user_id' => $siswa->user_id,
                        'tipe' => 'nilai_baru',
                        'judul' => 'Nilai Diperbarui',
                        'pesan' => "Nilai {$kelasMapel->mataPelajaran?->nama_mapel} semester {$semester} telah diinput.",
                        'link' => route('siswa.nilai.index'),
                        'is_read' => false,
                    ]);
                }
            }
        });
    }

    private function logNilaiChange(KelasMapel $kelasMapel, Siswa $siswa, string $semester, array $fields, ?NilaiAkhir $before, NilaiAkhir $after): void
    {
        try {
            AcademicAuditLog::create([
                'actor_id' => Auth::id(),
                'module' => 'nilai',
                'action' => $after->wasRecentlyCreated ? 'create' : 'update',
                'auditable_type' => NilaiAkhir::class,
                'auditable_id' => $after->id,
                'before_values' => $this->nilaiValues($before, $fields),
                'after_values' => $this->nilaiValues($after, $fields),
                'metadata' => [
                    'siswa' => $siswa->user?->nama_lengkap ?? $siswa->nis,
                    'nis' => $siswa->nis,
                    'kelas' => $kelasMapel->kelas?->displayName() ?? '-',
                    'mata_pelajaran' => $kelasMapel->mataPelajaran?->nama_mapel ?? '-',
                    'semester' => $semester,
                ],
            ]);
        } catch (\Throwable) {
            // Audit log tidak boleh menggagalkan simpan nilai.
        }
    }

    private function nilaiValues(?NilaiAkhir $nilai, array $fields): array
    {
        return collect($fields)
            ->mapWithKeys(fn (string $field) => [$field => $nilai?->$field])
            ->all();
    }
}
