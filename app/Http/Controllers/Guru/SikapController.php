<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guru\RekapSikapRequest;
use App\Http\Requests\Guru\StoreBulkSikapRequest;
use App\Http\Requests\Guru\StoreSikapRequest;
use App\Models\KelasMapel;
use App\Models\Pengaturan;
use App\Models\SikapSosial;
use App\Models\SikapSpiritual;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class SikapController extends Controller
{
    private array $sosialFields = ['empati', 'kerjasama', 'toleransi', 'percaya_diri', 'komunikasi'];

    private array $spiritualFields = ['taqwa', 'kejujuran', 'disiplin', 'sabar', 'syukur', 'tawadhu'];

    public function index()
    {
        $kelasMapel = KelasMapel::with(['kelas', 'mataPelajaran'])
            ->where('guru_id', Auth::id())
            ->aktif()
            ->get();

        $tahunAjaran = TahunAjaran::getAktif();
        $semester = Pengaturan::getValue('semester_aktif', '1');

        return Inertia::render('Guru/Sikap/Index', [
            'kelasMapel' => $this->formatKelasMapelOptions($kelasMapel),
            'tahunAjaran' => $tahunAjaran ? [
                'id' => $tahunAjaran->id,
                'tahun' => $tahunAjaran->tahun,
            ] : null,
            'semester' => (string) $semester,
            'groups' => $kelasMapel->map(fn (KelasMapel $item) => $this->buildSikapGroup($item, $tahunAjaran, (string) $semester))->values(),
            'storeUrl' => route('guru.sikap.store.bulk'),
        ]);
    }

    // Input nilai sikap  siswa
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

        $sikapSosial = SikapSosial::where('kelas_mapel_id', $kelasMapel->id)
            ->where('tahun_ajaran_id', $tahunAjaran?->id)
            ->where('semester', $semester)
            ->get()
            ->keyBy('siswa_id');

        $sikapSpiritual = SikapSpiritual::where('kelas_mapel_id', $kelasMapel->id)
            ->where('tahun_ajaran_id', $tahunAjaran?->id)
            ->where('semester', $semester)
            ->get()
            ->keyBy('siswa_id');

        return Inertia::render('Guru/Sikap/Input', [
            'kelasMapel' => [
                'id' => $kelasMapel->id,
                'kelas' => $kelasMapel->kelas?->displayName() ?? '-',
                'mata_pelajaran' => $kelasMapel->mataPelajaran?->nama_mapel ?? '-',
                'store_url' => route('guru.sikap.store', $kelasMapel),
                'export_excel_url' => route('guru.sikap.export.excel', $kelasMapel),
                'export_pdf_url' => route('guru.sikap.export.pdf', $kelasMapel),
                'back_url' => route('guru.sikap.index'),
            ],
            'tahunAjaran' => $tahunAjaran ? [
                'id' => $tahunAjaran->id,
                'tahun' => $tahunAjaran->tahun,
            ] : null,
            'semester' => (string) $semester,
            'students' => $siswa->map(function (Siswa $student, int $index) use ($sikapSosial, $sikapSpiritual) {
                $sosial = $sikapSosial->get($student->id);
                $spiritual = $sikapSpiritual->get($student->id);

                return [
                    'id' => $student->id,
                    'no' => $index + 1,
                    'nis' => $student->nis,
                    'nama' => $student->user?->nama_lengkap ?? $student->nis,
                    'sosial' => collect($this->sosialFields)->mapWithKeys(fn (string $field) => [$field => $sosial?->{$field}])->all(),
                    'spiritual' => collect($this->spiritualFields)->mapWithKeys(fn (string $field) => [$field => $spiritual?->{$field}])->all(),
                ];
            })->values(),
        ]);
    }

    // Simpan nilai sikap siswa
    public function store(StoreSikapRequest $request, KelasMapel $kelasMapel)
    {
        $this->authorize('mengajar', $kelasMapel);

        $this->saveSikapForKelasMapel(
            $kelasMapel,
            $request->semester,
            $request->input('sosial', []),
            $request->input('spiritual', [])
        );

        return redirect()->route('guru.sikap.input', $kelasMapel)
            ->with('success', 'Nilai sikap berhasil disimpan.');
    }

    public function storeBulk(StoreBulkSikapRequest $request)
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
            $this->saveSikapForKelasMapel(
                $item,
                $request->semester,
                $request->input('sosial.'.$item->id, []),
                $request->input('spiritual.'.$item->id, [])
            );
        }

        return redirect()->route('guru.sikap.index')
            ->with('success', 'Nilai sikap berhasil disimpan untuk kelas yang dipilih.');
    }

    // Bahan untuk merekap nilai sikap siswa per kelas dan mata pelajaran yang diampu oleh guru
    public function rekap(RekapSikapRequest $request)
    {
        $kelasMapel = KelasMapel::with(['kelas', 'mataPelajaran'])
            ->where('guru_id', Auth::id())
            ->aktif()
            ->get();

        $tahunAjaran = TahunAjaran::getAktif();
        $semester = $request->input('semester', Pengaturan::getValue('semester_aktif', '1'));

        $kmId = $request->input('kelas_mapel_id');

        // Sikap Sosial
        $soFields = ['empati', 'kerjasama', 'toleransi', 'percaya_diri', 'komunikasi'];
        $sosialQuery = SikapSosial::with(['siswa.user', 'siswa.kelas', 'kelasMapel.mataPelajaran'])
            ->where('tahun_ajaran_id', $tahunAjaran?->id)
            ->where('semester', $semester)
            ->whereHas('kelasMapel', fn ($q) => $q->where('guru_id', Auth::id())->aktif($semester));

        if ($kmId) {
            $sosialQuery->where('kelas_mapel_id', $kmId);
        }

        $sikapSosial = $sosialQuery->get()->groupBy('siswa_id')->map(function ($records) use ($soFields) {
            $first = $records->first();
            $avg = [];
            foreach ($soFields as $f) {
                $avg[$f] = round($records->avg($f), 1);
            }
            $avg['rata'] = round(array_sum($avg) / count($soFields), 1);

            return ['siswa' => $first->siswa] + $avg;
        })->values();

        // Sikap Spiritual
        $spFields = ['taqwa', 'kejujuran', 'disiplin', 'sabar', 'syukur', 'tawadhu'];
        $spiritualQuery = SikapSpiritual::with(['siswa.user', 'siswa.kelas', 'kelasMapel.mataPelajaran'])
            ->where('tahun_ajaran_id', $tahunAjaran?->id)
            ->where('semester', $semester)
            ->whereHas('kelasMapel', fn ($q) => $q->where('guru_id', Auth::id())->aktif($semester));

        if ($kmId) {
            $spiritualQuery->where('kelas_mapel_id', $kmId);
        }

        $sikapSpiritual = $spiritualQuery->get()->groupBy('siswa_id')->map(function ($records) use ($spFields) {
            $first = $records->first();
            $avg = [];
            foreach ($spFields as $f) {
                $avg[$f] = round($records->avg($f), 1);
            }
            $avg['rata'] = round(array_sum($avg) / count($spFields), 1);

            return ['siswa' => $first->siswa] + $avg;
        })->values();

        return view('guru.rekap-sikap', compact('sikapSosial', 'sikapSpiritual', 'kelasMapel', 'semester'));
    }

    private function buildSikapGroup(KelasMapel $kelasMapel, ?TahunAjaran $tahunAjaran, string $semester): array
    {
        $siswa = Siswa::with('user')
            ->where('kelas_id', $kelasMapel->kelas_id)
            ->where('status', 'aktif')
            ->orderBy('nis')
            ->get();

        $sikapSosial = SikapSosial::where('kelas_mapel_id', $kelasMapel->id)
            ->where('tahun_ajaran_id', $tahunAjaran?->id)
            ->where('semester', $semester)
            ->get()
            ->keyBy('siswa_id');

        $sikapSpiritual = SikapSpiritual::where('kelas_mapel_id', $kelasMapel->id)
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
            'export_excel_url' => route('guru.sikap.export.excel', $kelasMapel),
            'export_pdf_url' => route('guru.sikap.export.pdf', $kelasMapel),
            'students' => $siswa->values()->map(function (Siswa $student, int $index) use ($sikapSosial, $sikapSpiritual) {
                $sosial = $sikapSosial->get($student->id);
                $spiritual = $sikapSpiritual->get($student->id);

                return [
                    'id' => $student->id,
                    'no' => $index + 1,
                    'nis' => $student->nis,
                    'nama' => $student->user?->nama_lengkap ?? $student->nis,
                    'sosial' => collect($this->sosialFields)->mapWithKeys(fn (string $field) => [$field => $sosial?->{$field}])->all(),
                    'spiritual' => collect($this->spiritualFields)->mapWithKeys(fn (string $field) => [$field => $spiritual?->{$field}])->all(),
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
                'href' => route('guru.sikap.input', $item),
            ];
        })->values();
    }

    private function saveSikapForKelasMapel(KelasMapel $kelasMapel, string $semester, array $sosialInput, array $spiritualInput): void
    {
        $tahunAjaran = TahunAjaran::getAktif();
        if (! $tahunAjaran) {
            throw ValidationException::withMessages([
                'tahun_ajaran' => 'Tahun ajaran aktif belum diatur.',
            ]);
        }

        $validSiswaIds = Siswa::where('kelas_id', $kelasMapel->kelas_id)
            ->where('status', 'aktif')
            ->pluck('id')
            ->map(fn ($id) => (string) $id);

        $submittedSiswaIds = collect(array_keys($sosialInput))
            ->merge(array_keys($spiritualInput))
            ->unique();

        if ($submittedSiswaIds->diff($validSiswaIds)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'sosial' => 'Data sikap berisi siswa yang tidak termasuk kelas ini.',
            ]);
        }

        DB::transaction(function () use ($sosialInput, $spiritualInput, $kelasMapel, $tahunAjaran, $semester) {
            foreach ($sosialInput as $siswaId => $data) {
                SikapSosial::updateOrCreate(
                    [
                        'siswa_id' => $siswaId,
                        'kelas_mapel_id' => $kelasMapel->id,
                        'tahun_ajaran_id' => $tahunAjaran->id,
                        'semester' => $semester,
                    ],
                    collect($this->sosialFields)
                        ->mapWithKeys(fn (string $field) => [$field => ($data[$field] ?? null) === '' ? null : ($data[$field] ?? null)])
                        ->all()
                );
            }

            foreach ($spiritualInput as $siswaId => $data) {
                SikapSpiritual::updateOrCreate(
                    [
                        'siswa_id' => $siswaId,
                        'kelas_mapel_id' => $kelasMapel->id,
                        'tahun_ajaran_id' => $tahunAjaran->id,
                        'semester' => $semester,
                    ],
                    collect($this->spiritualFields)
                        ->mapWithKeys(fn (string $field) => [$field => ($data[$field] ?? null) === '' ? null : ($data[$field] ?? null)])
                        ->all()
                );
            }
        });
    }
}
