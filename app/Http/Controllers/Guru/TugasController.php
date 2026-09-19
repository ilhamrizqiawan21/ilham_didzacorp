<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guru\GradeTugasRequest;
use App\Http\Requests\Guru\StoreBulkTugasRequest;
use App\Http\Requests\Guru\StoreTugasRequest;
use App\Models\KelasMapel;
use App\Models\NilaiAkhir;
use App\Models\Pengaturan;
use App\Models\PengumpulanFile;
use App\Models\PengumpulanTugas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use App\Models\WhatsAppMessageLog;
use App\Services\NilaiService;
use App\Services\NotifikasiService;
use App\Services\WhatsAppService;
use App\Support\WhatsAppPhone;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TugasController extends Controller
{
    protected NotifikasiService $notifikasiService;

    protected NilaiService $nilaiService;

    public function __construct(NotifikasiService $notifikasiService, NilaiService $nilaiService)
    {
        $this->notifikasiService = $notifikasiService;
        $this->nilaiService = $nilaiService;
    }

    public function index()
    {
        $kelasMapel = KelasMapel::with(['kelas', 'mataPelajaran', 'tahunAjaran'])
            ->where('guru_id', Auth::id())
            ->aktif()
            ->get();

        $totalSiswaByKelas = Siswa::whereIn('kelas_id', $kelasMapel->pluck('kelas_id')->unique())
            ->where('status', 'aktif')
            ->selectRaw('kelas_id, count(*) as total')
            ->groupBy('kelas_id')
            ->pluck('total', 'kelas_id');

        $tugas = Tugas::with(['kelasMapel.kelas', 'kelasMapel.mataPelajaran'])
            ->whereIn('kelas_mapel_id', $kelasMapel->pluck('id'))
            ->withCount(['pengumpulan as sudah_mengumpulkan' => function ($q) {
                $q->whereIn('status', PengumpulanTugas::STATUS_SUBMITTED);
            }])
            ->withCount(['pengumpulan as perlu_dinilai' => function ($q) {
                $q->whereIn('status', PengumpulanTugas::STATUS_PERLU_DINILAI)
                    ->whereNull('nilai');
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Guru/Tugas/Index', [
            'kelasMapel' => $this->formatKelasMapelOptions($kelasMapel),
            'tugas' => $tugas->map(fn (Tugas $item) => $this->formatTugas(
                $item,
                (int) ($totalSiswaByKelas[$item->kelasMapel?->kelas_id] ?? 0)
            ))->values(),
            'storeUrl' => route('guru.tugas.store.bulk'),
        ]);
    }

    public function list(KelasMapel $kelasMapel)
    {
        $this->authorize('mengajar', $kelasMapel);

        $tugas = Tugas::where('kelas_mapel_id', $kelasMapel->id)
            ->withCount(['pengumpulan as sudah_mengumpulkan' => function ($q) use ($kelasMapel) {
                $q->whereIn('status', PengumpulanTugas::STATUS_SUBMITTED)
                    ->whereHas('siswa', fn ($siswa) => $siswa
                        ->where('kelas_id', $kelasMapel->kelas_id)
                        ->where('status', 'aktif'));
            }])
            ->withCount(['pengumpulan as perlu_dinilai' => function ($q) use ($kelasMapel) {
                $q->whereIn('status', PengumpulanTugas::STATUS_PERLU_DINILAI)
                    ->whereNull('nilai')
                    ->whereHas('siswa', fn ($siswa) => $siswa
                        ->where('kelas_id', $kelasMapel->kelas_id)
                        ->where('status', 'aktif'));
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        $totalSiswa = Siswa::where('kelas_id', $kelasMapel->kelas_id)
            ->where('status', 'aktif')
            ->count();

        return Inertia::render('Guru/Tugas/List', [
            'kelasMapel' => [
                'id' => $kelasMapel->id,
                'kelas' => $kelasMapel->kelas?->displayName() ?? '-',
                'mata_pelajaran' => $kelasMapel->mataPelajaran?->nama_mapel ?? '-',
                'store_url' => route('guru.tugas.store', $kelasMapel),
                'export_excel_url' => route('guru.tugas.export.excel', $kelasMapel),
                'export_pdf_url' => route('guru.tugas.export.pdf', $kelasMapel),
                'workspace_url' => route('guru.kelas-mapel.show', $kelasMapel),
                'back_url' => route('guru.tugas.index'),
            ],
            'tugas' => $tugas->map(fn (Tugas $item) => [
                'id' => $item->id,
                'judul' => $item->judul,
                'deskripsi' => Str::limit((string) $item->deskripsi, 80),
                'batas_waktu' => $item->batas_waktu?->format('d M Y'),
                'sudah_mengumpulkan' => $item->sudah_mengumpulkan ?? 0,
                'perlu_dinilai' => $item->perlu_dinilai ?? 0,
                'progress_percent' => $totalSiswa > 0 ? round((($item->sudah_mengumpulkan ?? 0) / $totalSiswa) * 100) : 0,
                'is_overdue' => $item->batas_waktu ? now()->startOfDay()->gt($item->batas_waktu->copy()->startOfDay()) : false,
                'pengumpulan_url' => route('guru.tugas.pengumpulan', [$kelasMapel, $item]),
                'delete_url' => route('guru.tugas.destroy', $item),
            ])->values(),
            'totalSiswa' => $totalSiswa,
        ]);
    }

    public function store(StoreTugasRequest $request, KelasMapel $kelasMapel)
    {
        $this->authorize('mengajar', $kelasMapel);

        $validated = $request->validated();

        $batasWaktu = Carbon::parse($validated['batas_waktu'])->endOfDay();

        DB::transaction(function () use ($kelasMapel, $validated, $batasWaktu) {
            $tugas = Tugas::create([
                'kelas_mapel_id' => $kelasMapel->id,
                'judul' => $validated['judul'],
                'deskripsi' => $validated['deskripsi'],
                'batas_waktu' => $batasWaktu,
            ]);

            $this->notifikasiService->notifikasiKelasMapel(
                $kelasMapel->id,
                'tugas_baru',
                'Tugas Baru',
                "Tugas '{$tugas->judul}' telah diberikan.",
                route('siswa.tugas.show', $tugas->id)
            );
        });

        return redirect()->route('guru.tugas.list', $kelasMapel)
            ->with('success', 'Tugas berhasil ditambahkan.');
    }

    public function storeBulk(StoreBulkTugasRequest $request)
    {
        $validated = $request->validated();

        $kelasMapel = $this->assignedKelasMapelQuery()
            ->whereIn('id', $validated['kelas_mapel_ids'])
            ->get();

        if ($kelasMapel->count() !== count(array_unique($validated['kelas_mapel_ids']))) {
            return back()->withInput()->with('error', 'Pilihan kelas tidak valid.');
        }

        $batasWaktu = Carbon::parse($validated['batas_waktu'])->endOfDay();

        DB::transaction(function () use ($kelasMapel, $validated, $batasWaktu) {
            foreach ($kelasMapel as $item) {
                $tugas = Tugas::create([
                    'kelas_mapel_id' => $item->id,
                    'judul' => $validated['judul'],
                    'deskripsi' => $validated['deskripsi'] ?? null,
                    'batas_waktu' => $batasWaktu,
                ]);

                $this->notifikasiService->notifikasiKelasMapel(
                    $item->id,
                    'tugas_baru',
                    'Tugas Baru',
                    "Tugas '{$tugas->judul}' telah diberikan.",
                    route('siswa.tugas.show', $tugas->id)
                );
            }
        });

        return redirect()->route('guru.tugas.index')
            ->with('success', 'Tugas berhasil ditambahkan ke kelas yang dipilih.');
    }

    public function pengumpulan(KelasMapel $kelasMapel, Tugas $tugas)
    {
        $this->authorize('mengajar', $kelasMapel);
        $this->ensureTugasBelongsToKelasMapel($tugas, $kelasMapel);
        $kelasMapel->loadMissing(['kelas', 'mataPelajaran']);

        $pengumpulan = PengumpulanTugas::with(['siswa.user', 'files'])
            ->where('tugas_id', $tugas->id)
            ->get()
            ->keyBy('siswa_id');

        $siswa = Siswa::with('user')
            ->where('kelas_id', $kelasMapel->kelas_id)
            ->where('status', 'aktif')
            ->orderBy('nis')
            ->get();
        $lastWhatsAppByStudent = WhatsAppMessageLog::query()
            ->where('guru_id', Auth::id())
            ->whereIn('siswa_id', $siswa->pluck('id'))
            ->orderByDesc('prepared_at')
            ->get()
            ->unique('siswa_id')
            ->keyBy('siswa_id');
        $penaltyPerDay = max(0, round((float) Pengaturan::getValue('penalty_terlambat_poin', '1'), 2));

        $missingSubmittedStudentIds = $pengumpulan->keys()
            ->diff($siswa->pluck('id'))
            ->values();

        if ($missingSubmittedStudentIds->isNotEmpty()) {
            $submittedStudents = Siswa::with('user')
                ->whereIn('id', $missingSubmittedStudentIds)
                ->orderBy('nis')
                ->get();

            $siswa = $siswa
                ->concat($submittedStudents)
                ->sortBy('nis', SORT_NATURAL)
                ->values();
        }

        return Inertia::render('Guru/Tugas/Pengumpulan', [
            'kelasMapel' => [
                'id' => $kelasMapel->id,
                'kelas' => $kelasMapel->kelas?->displayName() ?? '-',
                'mata_pelajaran' => $kelasMapel->mataPelajaran?->nama_mapel ?? '-',
                'back_url' => route('guru.tugas.list', $kelasMapel),
                'workspace_url' => route('guru.kelas-mapel.show', $kelasMapel),
                'export_excel_url' => route('guru.tugas.pengumpulan.export.excel', [$kelasMapel, $tugas]),
                'export_pdf_url' => route('guru.tugas.pengumpulan.export.pdf', [$kelasMapel, $tugas]),
            ],
            'tugas' => [
                'id' => $tugas->id,
                'judul' => $tugas->judul,
                'deskripsi' => $tugas->deskripsi,
                'batas_waktu' => $tugas->batas_waktu?->format('d/m/Y'),
            ],
            'pengumpulan' => $siswa->map(function (Siswa $student, int $index) use ($pengumpulan, $kelasMapel, $tugas, $penaltyPerDay, $lastWhatsAppByStudent) {
                $item = $pengumpulan->get($student->id);
                $daysLate = $this->lateDays($item?->tanggal_kumpul, $tugas->batas_waktu);
                // A reminder remains available for every overdue row, including
                // direct-graded assignments that have no submission record.
                $canPrepareWhatsApp = $daysLate > 0;
                $lastWhatsApp = $lastWhatsAppByStudent->get($student->id);

                return [
                    'id' => $item?->id,
                    'key' => $item?->id ? 'pengumpulan-'.$item->id : 'siswa-'.$student->id,
                    'no' => $index + 1,
                    'siswa' => $student->user?->nama_lengkap ?: ($student->user?->username ?: $student->nis),
                    'nis' => $student->nis,
                    'status' => ($daysLate > 0 && in_array($item?->status, ['sudah', 'dinilai'], true)) ? 'terlambat' : ($item?->status ?? 'belum'),
                    'tanggal_kumpul' => $item?->tanggal_kumpul?->format('d/m/Y H:i'),
                    'hari_terlambat' => $daysLate,
                    'penalty_perkiraan' => round($daysLate * $penaltyPerDay, 2),
                    'teks_jawaban' => $item?->teks_jawaban,
                    'catatan' => $item?->catatan,
                    'nilai' => $item?->nilai,
                    'nilai_input' => $item?->nilai_sebelum_penalty ?? $item?->nilai,
                    'penalty_terlambat' => $item?->penalty_terlambat ?? 0,
                    'nilai_url' => route('guru.tugas.nilai', [$kelasMapel, $tugas, $student]),
                    'whatsapp_url' => $canPrepareWhatsApp ? route('guru.tugas.whatsapp', [$kelasMapel, $tugas, $student]) : null,
                    'whatsapp_last_prepared_at' => $lastWhatsApp?->prepared_at?->format('d/m/Y H:i'),
                    'whatsapp_last_sent_at' => $lastWhatsApp?->sent_marked_at?->format('d/m/Y H:i'),
                    'legacy_file_url' => $item?->file_upload ? route('guru.tugas.pengumpulan.download', [$kelasMapel, $tugas, $item]) : null,
                    'files' => $item?->files->map(fn (PengumpulanFile $file) => [
                        'id' => $file->id,
                        'name' => $file->file_name,
                        'url' => route('guru.tugas.file.download', [$kelasMapel, $tugas, $file]),
                    ])->values()->all() ?? [],
                ];
            })->values()->all(),
        ]);
    }

    public function nilai(GradeTugasRequest $request, KelasMapel $kelasMapel, Tugas $tugas, Siswa $siswa)
    {
        $this->authorize('mengajar', $kelasMapel);
        $this->ensureTugasBelongsToKelasMapel($tugas, $kelasMapel);
        $this->ensureSiswaBelongsToKelasMapel($siswa, $kelasMapel);

        $validated = $request->validated();

        $pengumpulan = PengumpulanTugas::where([
            'tugas_id' => $tugas->id,
            'siswa_id' => $siswa->id,
        ])->first();
        $nilaiInput = array_key_exists('nilai', $validated) && $validated['nilai'] !== null && $validated['nilai'] !== ''
            ? round((float) $validated['nilai'], 2)
            : null;
        $penalty = $nilaiInput !== null ? min($nilaiInput, $this->latePenaltyPoints($pengumpulan, $tugas)) : 0.0;
        $nilaiFinal = $nilaiInput !== null ? max(0, round($nilaiInput - $penalty, 2)) : null;

        if ($nilaiInput === null && blank($validated['catatan'] ?? null)) {
            // Form kosong: jangan ubah status/nilai yang sudah ada.
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Tidak ada nilai atau komentar yang diisi.',
                ], 422);
            }

            return back()->with('info', 'Tidak ada nilai atau komentar yang diisi.');
        }

        $values = [
            'catatan' => $validated['catatan'] ?? null,
        ];

        if ($nilaiInput !== null) {
            $values['nilai'] = $nilaiFinal;
            $values['nilai_sebelum_penalty'] = $nilaiInput;
            $values['penalty_terlambat'] = $penalty;
            $values['status'] = PengumpulanTugas::STATUS_DINILAI;
            $values['graded_at'] = now();
        } elseif ($pengumpulan && $pengumpulan->nilai === null
            && in_array($pengumpulan->status, PengumpulanTugas::STATUS_PERLU_DINILAI)) {
            // Komentar tanpa nilai: kembalikan ke siswa untuk diperbaiki,
            // sehingga tidak lagi masuk antrian "perlu dinilai".
            $values['status'] = PengumpulanTugas::STATUS_PERLU_PERBAIKAN;
            $values['penalty_terlambat'] = 0;
            $values['graded_at'] = now();
        } elseif (! $pengumpulan) {
            $values['status'] = PengumpulanTugas::STATUS_BELUM;
            $values['penalty_terlambat'] = 0;
            $values['graded_at'] = now();
        }

        $savedPengumpulan = DB::transaction(function () use (
            $tugas,
            $siswa,
            $values,
            $nilaiInput,
            $kelasMapel
        ): PengumpulanTugas {
            $saved = PengumpulanTugas::updateOrCreate(
                [
                    'tugas_id' => $tugas->id,
                    'siswa_id' => $siswa->id,
                ],
                $values
            );

            if ($nilaiInput !== null) {
                $this->syncNilaiHarian($kelasMapel, $siswa);
            }

            return $saved;
        });

        $message = $nilaiInput !== null
            ? 'Nilai tugas berhasil disimpan dan nilai harian diperbarui.'
            : 'Komentar tugas berhasil disimpan.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'id' => $savedPengumpulan->id,
                'status' => $savedPengumpulan->status,
                'nilai' => $savedPengumpulan->nilai,
                'nilai_input' => $savedPengumpulan->nilai_sebelum_penalty ?? $savedPengumpulan->nilai,
                'catatan' => $savedPengumpulan->catatan,
                'penalty_terlambat' => $savedPengumpulan->penalty_terlambat,
                'graded_at' => $savedPengumpulan->graded_at?->format('H:i'),
                'saved_at' => now()->format('H:i'),
            ]);
        }

        return back()->with('success', $message);
    }

    public function whatsapp(KelasMapel $kelasMapel, Tugas $tugas, Siswa $siswa, WhatsAppService $service)
    {
        $this->authorize('mengajar', $kelasMapel);
        $this->ensureTugasBelongsToKelasMapel($tugas, $kelasMapel);
        $this->ensureSiswaBelongsToKelasMapel($siswa, $kelasMapel);
        $normalizedPhone = WhatsAppPhone::normalize((string) $siswa->nomor_whatsapp);
        abort_unless($normalizedPhone !== null, 422, 'Nomor WhatsApp siswa belum valid. Periksa format nomor di pengaturan siswa.');
        abort_unless($siswa->whatsapp_opt_in, 422, 'Siswa belum menyetujui menerima informasi melalui WhatsApp.');
        if ($siswa->nomor_whatsapp !== $normalizedPhone) {
            $siswa->update(['nomor_whatsapp' => $normalizedPhone]);
        }

        return response()->json($service->prepareLateTaskMessage($siswa, $tugas, (int) Auth::id()));
    }

    public function whatsappMarkSent(WhatsAppMessageLog $log)
    {
        abort_unless((int) $log->guru_id === (int) Auth::id(), 403);
        $log->update(['sent_marked_at' => now()]);

        return back()->with('success', 'Pengingat WhatsApp ditandai sudah dikirim.');
    }

    public function downloadFile(KelasMapel $kelasMapel, Tugas $tugas, PengumpulanFile $file)
    {
        $this->authorize('mengajar', $kelasMapel);
        $this->ensureTugasBelongsToKelasMapel($tugas, $kelasMapel);
        $file->loadMissing('pengumpulan');
        abort_unless($file->pengumpulan, 404);
        $this->ensurePengumpulanBelongsToTugas($file->pengumpulan, $tugas);

        return $this->downloadPengumpulanPath($file->file_path, $file->file_name);
    }

    public function downloadLegacyFile(KelasMapel $kelasMapel, Tugas $tugas, PengumpulanTugas $pengumpulan)
    {
        $this->authorize('mengajar', $kelasMapel);
        $this->ensureTugasBelongsToKelasMapel($tugas, $kelasMapel);
        $this->ensurePengumpulanBelongsToTugas($pengumpulan, $tugas);

        return $this->downloadPengumpulanPath($pengumpulan->file_upload, basename((string) $pengumpulan->file_upload));
    }

    public function destroy(Tugas $tugas)
    {
        $kelasMapel = $tugas->kelasMapel;
        $this->authorize('mengajar', $kelasMapel);

        if ($tugas->pengumpulan()->exists()) {
            return back()->with('error', 'Tugas tidak dapat dihapus karena sudah memiliki pengumpulan siswa.');
        }

        $tugas->delete();

        return back()
            ->with('success', 'Tugas berhasil dihapus.');
    }

    private function ensureTugasBelongsToKelasMapel(Tugas $tugas, KelasMapel $kelasMapel): void
    {
        abort_unless((int) $tugas->kelas_mapel_id === (int) $kelasMapel->id, 403);
    }

    private function ensurePengumpulanBelongsToTugas(PengumpulanTugas $pengumpulan, Tugas $tugas): void
    {
        abort_unless((int) $pengumpulan->tugas_id === (int) $tugas->id, 403);
    }

    private function ensureSiswaBelongsToKelasMapel(Siswa $siswa, KelasMapel $kelasMapel): void
    {
        abort_unless(
            (int) $siswa->kelas_id === (int) $kelasMapel->kelas_id && $siswa->status === 'aktif',
            403
        );
    }

    private function syncNilaiHarian(KelasMapel $kelasMapel, Siswa $siswa): void
    {
        $tahunAjaran = TahunAjaran::getAktif();

        if (! $tahunAjaran) {
            return;
        }

        $semester = Pengaturan::getValue('semester_aktif', '1');
        $average = PengumpulanTugas::where('siswa_id', $siswa->id)
            ->whereNotNull('nilai')
            ->whereHas('tugas', fn ($query) => $query
                ->where('kelas_mapel_id', $kelasMapel->id)
                ->where('kategori_nilai', 'NH'))
            ->avg('nilai');

        $existing = NilaiAkhir::where([
            'siswa_id' => $siswa->id,
            'kelas_mapel_id' => $kelasMapel->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'semester' => $semester,
        ])->first();

        if ($average === null && ! $existing) {
            return;
        }

        $this->nilaiService->simpanNilai([
            'siswa_id' => $siswa->id,
            'kelas_mapel_id' => $kelasMapel->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'semester' => $semester,
            'sum1' => $existing?->sum1,
            'sum2' => $existing?->sum2,
            'sum3' => $existing?->sum3,
            'sum4' => $existing?->sum4,
            'nilai_harian' => $average !== null ? round((float) $average, 2) : null,
            'sts' => $existing?->sts,
            'sas' => $existing?->sas,
            'sat' => $existing?->sat,
        ]);
    }

    private function latePenaltyPoints(?PengumpulanTugas $pengumpulan, Tugas $tugas): float
    {
        $daysLate = $this->lateDays($pengumpulan?->tanggal_kumpul, $tugas->batas_waktu);
        if ($daysLate <= 0) {
            return 0.0;
        }

        $pointsPerDay = max(0, round((float) Pengaturan::getValue('penalty_terlambat_poin', '1'), 2));
        if ($pointsPerDay <= 0) {
            return 0.0;
        }

        // Selisih hari kalender antara tanggal kumpul dan tanggal batas waktu.
        // 1 hari keterlambatan = 1 poin (atau sesuai pengaturan per hari).
        return round($daysLate * $pointsPerDay, 2);
    }

    private function lateDays(?Carbon $submittedAt, ?Carbon $deadline): int
    {
        if (! $deadline) {
            return 0;
        }

        // Jika guru menilai tanpa record pengumpulan, gunakan waktu penilaian
        // sebagai batas efektif: tugas yang sudah lewat deadline tetap dipenalti.
        $submittedAt ??= now();

        return (int) max(0, $deadline->copy()->startOfDay()->diffInDays($submittedAt->copy()->startOfDay(), false));
    }

    private function downloadPengumpulanPath(?string $path, string $downloadName)
    {
        abort_unless($path, 404);

        $disk = Storage::disk('local');
        if (! $disk->exists($path)) {
            $disk = Storage::disk('public');
        }
        abort_unless($disk->exists($path), 404);

        return response()->download($disk->path($path), $downloadName);
    }

    private function assignedKelasMapelQuery()
    {
        return KelasMapel::with(['kelas', 'mataPelajaran', 'tahunAjaran'])
            ->where('guru_id', Auth::id())
            ->aktif();
    }

    private function formatKelasMapelOptions($kelasMapel)
    {
        return $kelasMapel->map(fn (KelasMapel $item) => [
            'id' => $item->id,
            'kelas' => $item->kelas?->displayName() ?? '-',
            'mata_pelajaran' => $item->mataPelajaran?->nama_mapel ?? '-',
            'semester' => $item->semester,
            'label' => ($item->kelas?->displayName() ?? '-').' - '.($item->mataPelajaran?->nama_mapel ?? '-').' (Sem. '.$item->semester.')',
            'href' => route('guru.tugas.list', $item),
        ])->values();
    }

    private function formatTugas(Tugas $item, int $totalSiswa): array
    {
        $kelasMapel = $item->kelasMapel;

        return [
            'id' => $item->id,
            'kelas_mapel_id' => $item->kelas_mapel_id,
            'judul' => $item->judul,
            'deskripsi' => Str::limit((string) $item->deskripsi, 80),
            'batas_waktu' => $item->batas_waktu?->format('d M Y'),
            'sudah_mengumpulkan' => $item->sudah_mengumpulkan ?? 0,
            'perlu_dinilai' => $item->perlu_dinilai ?? 0,
            'total_siswa' => $totalSiswa,
            'progress_percent' => $totalSiswa > 0 ? round((($item->sudah_mengumpulkan ?? 0) / $totalSiswa) * 100) : 0,
            'is_overdue' => $item->batas_waktu ? now()->startOfDay()->gt($item->batas_waktu->copy()->startOfDay()) : false,
            'kelas' => $kelasMapel?->kelas?->displayName() ?? '-',
            'mata_pelajaran' => $kelasMapel?->mataPelajaran?->nama_mapel ?? '-',
            'pengumpulan_url' => $kelasMapel ? route('guru.tugas.pengumpulan', [$kelasMapel, $item]) : null,
            'delete_url' => route('guru.tugas.destroy', $item),
        ];
    }

    private function deletePengumpulanPath(?string $path): void
    {
        if (! $path) {
            return;
        }

        Storage::disk('local')->delete($path);
        Storage::disk('public')->delete($path);
    }
}
