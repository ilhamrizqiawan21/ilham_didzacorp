<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\PengumpulanFile;
use App\Models\PengumpulanTugas;
use App\Models\Siswa;
use App\Models\Tugas;
use App\Services\NotifikasiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class TugasController extends Controller
{
    private const MAX_UPLOAD_FILES = 5;

    private const UPLOAD_MAX_KB = 5120;

    private const UPLOAD_TOTAL_MAX_KB = 20480;

    private const UPLOAD_EXTENSIONS = 'jpg,jpeg,pdf';

    /**
     * Aturan validasi satu file tugas.
     * Sengaja TANPA validasi MIME/konten (mimetypes/mimes) karena tebakan
     * MIME server tidak konsisten antar perangkat dan sering menolak file
     * .jpg/.pdf yang sah walau ukurannya di bawah batas. Cukup validasi
     * ekstensi + ukuran, konsisten dengan validasi di sisi frontend.
     */
    public static function uploadFileRules(): string
    {
        return 'nullable|file|extensions:'.self::UPLOAD_EXTENSIONS.'|max:'.self::UPLOAD_MAX_KB;
    }

    public function index()
    {
        $user = Auth::user();
        $siswa = $user->siswa;

        if (! $siswa) {
            return redirect()->route('login')->with('error', 'Data siswa tidak ditemukan.');
        }

        $tugas = Tugas::with(['kelasMapel.mataPelajaran', 'pengumpulan' => function ($q) use ($siswa) {
            $q->where('siswa_id', $siswa->id);
        }])
            ->whereHas('kelasMapel', function ($q) use ($siswa) {
                $q->where('kelas_id', $siswa->kelas_id);
                $q->aktif();
            })
            ->orderBy('batas_waktu', 'desc')
            ->get();

        return Inertia::render('Siswa/Tugas/Index', [
            'tugas' => $tugas->map(function (Tugas $item) use ($siswa) {
                $pengumpulan = $item->pengumpulan->where('siswa_id', $siswa->id)->first();

                return [
                    'id' => $item->id,
                    'judul' => $item->judul,
                    'mata_pelajaran' => $item->kelasMapel?->mataPelajaran?->nama_mapel ?? '-',
                    'batas_waktu' => $item->batas_waktu ? Carbon::parse($item->batas_waktu)->format('d/m/Y') : '-',
                    'status' => $pengumpulan?->status,
                    'nilai' => $pengumpulan?->nilai ?? '-',
                    'show_url' => route('siswa.tugas.show', $item),
                    'workspace_url' => $item->kelasMapel ? route('siswa.kelas-mapel.show', $item->kelasMapel) : null,
                ];
            })->values(),
        ]);
    }

    public function show(Tugas $tugas)
    {
        $user = Auth::user();
        $siswa = $user->siswa;
        $tugas->loadMissing('kelasMapel.tahunAjaran');

        $this->ensureTugasAktifUntukSiswa($tugas, $siswa);

        $pengumpulan = PengumpulanTugas::with('files')
            ->where('tugas_id', $tugas->id)
            ->where('siswa_id', $siswa->id)
            ->first();

        $tugas->loadMissing(['kelasMapel.mataPelajaran', 'kelasMapel.guru', 'kelasMapel.kelas']);

        return Inertia::render('Siswa/Tugas/Show', [
            'tugas' => [
                'id' => $tugas->id,
                'judul' => $tugas->judul,
                'kategori_nilai' => $tugas->kategori_nilai ?? 'NH',
                'mata_pelajaran' => $tugas->kelasMapel?->mataPelajaran?->nama_mapel ?? '-',
                'guru' => $tugas->kelasMapel?->guru?->nama_lengkap ?? '-',
                'kelas' => trim(($tugas->kelasMapel?->kelas?->tingkat ? $tugas->kelasMapel?->kelas?->tingkat.' ' : '').($tugas->kelasMapel?->kelas?->nama_kelas ?? '')),
                'batas_waktu' => $tugas->batas_waktu ? Carbon::parse($tugas->batas_waktu)->format('d M Y') : '-',
                'is_late' => $tugas->batas_waktu ? now()->gt($tugas->batas_waktu) : false,
                'deskripsi' => $tugas->deskripsi ?? 'Tidak ada deskripsi',
                'store_url' => route('siswa.tugas.kumpul', $tugas),
                'back_url' => route('siswa.tugas.index'),
            ],
            'pengumpulan' => $pengumpulan ? [
                'id' => $pengumpulan->id,
                'status' => $pengumpulan->status,
                'tanggal_kumpul' => $pengumpulan->tanggal_kumpul ? Carbon::parse($pengumpulan->tanggal_kumpul)->format('d M Y H:i') : '-',
                'nilai' => $pengumpulan->nilai,
                'teks_jawaban' => $pengumpulan->teks_jawaban,
                'catatan' => $pengumpulan->catatan,
                'legacy_file_url' => $pengumpulan->file_upload ? route('siswa.tugas.pengumpulan.download', [$tugas, $pengumpulan]) : null,
                'files' => $pengumpulan->files->map(fn (PengumpulanFile $file) => [
                    'id' => $file->id,
                    'name' => $file->file_name,
                    'url' => route('siswa.tugas.file.download', [$tugas, $file]),
                ])->values(),
            ] : null,
            'canSubmit' => ! $pengumpulan || in_array($pengumpulan->status, [
                PengumpulanTugas::STATUS_BELUM,
                PengumpulanTugas::STATUS_PERLU_PERBAIKAN,
            ]),
        ]);
    }

    public function store(Request $request, Tugas $tugas)
    {
        $user = Auth::user();
        $siswa = $user->siswa;

        $fileRules = self::uploadFileRules();
        $validated = $request->validate([
            'files' => 'nullable|array|max:'.self::MAX_UPLOAD_FILES,
            'file_upload' => $fileRules,
            'files.*' => $fileRules,
            'teks_jawaban' => 'nullable|string|max:5000',
        ], [
            'file_upload.file' => 'Upload harus berupa file.',
            'file_upload.extensions' => 'Ekstensi file harus .jpg, .jpeg, atau .pdf.',
            'file_upload.max' => 'Ukuran file maksimal 5MB.',
            'files.array' => 'Upload file tidak valid. Silakan pilih file ulang.',
            'files.max' => 'Maksimal '.self::MAX_UPLOAD_FILES.' file untuk satu pengumpulan tugas.',
            'files.*.file' => 'Upload harus berupa file.',
            'files.*.extensions' => 'Ekstensi file harus .jpg, .jpeg, atau .pdf.',
            'files.*.max' => 'Ukuran setiap file maksimal 5MB.',
        ]);

        $tugas->loadMissing('kelasMapel.tahunAjaran');

        $this->ensureTugasAktifUntukSiswa($tugas, $siswa);

        $hasTextJawaban = filled($validated['teks_jawaban'] ?? null);
        $hasSingleFile = $request->hasFile('file_upload');
        $hasMultipleFiles = collect($request->file('files', []))->filter()->isNotEmpty();
        $totalUploadedFiles = ($hasSingleFile ? 1 : 0) + collect($request->file('files', []))->filter()->count();

        if (! $hasTextJawaban && ! $hasSingleFile && ! $hasMultipleFiles) {
            return back()->withInput()->withErrors(['file_upload' => 'Upload file atau isi jawaban teks terlebih dahulu.']);
        }

        if ($totalUploadedFiles > self::MAX_UPLOAD_FILES) {
            return back()->withInput()->withErrors(['files' => 'Maksimal '.self::MAX_UPLOAD_FILES.' file untuk satu pengumpulan tugas.']);
        }

        $totalUploadBytes = 0;
        if ($request->hasFile('file_upload')) {
            $totalUploadBytes += (int) $request->file('file_upload')->getSize();
        }
        foreach (collect($request->file('files', []))->filter() as $file) {
            $totalUploadBytes += (int) $file->getSize();
        }

        if ($totalUploadBytes > self::UPLOAD_TOTAL_MAX_KB * 1024) {
            $limitMb = (int) (self::UPLOAD_TOTAL_MAX_KB / 1024);

            return back()->withInput()->withErrors(['files' => 'Total ukuran file melebihi batas maksimal '.$limitMb.'MB.']);
        }

        $existingPengumpulan = PengumpulanTugas::where('tugas_id', $tugas->id)
            ->where('siswa_id', $siswa->id)
            ->first();

        if ($existingPengumpulan && ! in_array($existingPengumpulan->status, [
            PengumpulanTugas::STATUS_BELUM,
            PengumpulanTugas::STATUS_PERLU_PERBAIKAN,
        ])) {
            return back()->with('error', 'Tugas ini sudah dikumpulkan dan tidak dapat diubah.');
        }

        $statusPengumpulan = $tugas->batas_waktu && now()->gt($tugas->batas_waktu)
            ? PengumpulanTugas::STATUS_TERLAMBAT
            : PengumpulanTugas::STATUS_SUDAH;
        $uploadedFiles = [];
        $storedPaths = [];

        try {
            DB::beginTransaction();
            // Re-check the submission while holding the row lock. The check
            // above is only an early response; concurrent requests must not
            // both pass it and append duplicate files.
            $lockedPengumpulan = PengumpulanTugas::where('tugas_id', $tugas->id)
                ->where('siswa_id', $siswa->id)
                ->lockForUpdate()
                ->first();
            if ($lockedPengumpulan && ! in_array($lockedPengumpulan->status, [
                PengumpulanTugas::STATUS_BELUM,
                PengumpulanTugas::STATUS_PERLU_PERBAIKAN,
            ], true)) {
                throw new \RuntimeException('Tugas ini sudah dikumpulkan dan tidak dapat diubah.');
            }

            $pengumpulan = PengumpulanTugas::updateOrCreate(
                ['tugas_id' => $tugas->id, 'siswa_id' => $siswa->id],
                ['status' => $statusPengumpulan, 'file_upload' => null, 'teks_jawaban' => $validated['teks_jawaban'] ?? null, 'tanggal_kumpul' => now(), 'graded_at' => null]
            );

            if ($request->hasFile('file_upload')) {
                $file = $request->file('file_upload');
                $path = $file->store('tugas/'.$tugas->id.'/'.$siswa->id, 'local');
                $storedPaths[] = $path;
                $uploadedFiles[] = ['pengumpulan_id' => $pengumpulan->id, 'file_name' => $file->getClientOriginalName(), 'file_path' => $path, 'uploaded_at' => now()];
            }

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $path = $file->store('tugas/'.$tugas->id.'/'.$siswa->id, 'local');
                    $storedPaths[] = $path;
                    $uploadedFiles[] = ['pengumpulan_id' => $pengumpulan->id, 'file_name' => $file->getClientOriginalName(), 'file_path' => $path, 'uploaded_at' => now()];
                }
            }

            if (count($uploadedFiles) > 0) {
                PengumpulanFile::insert($uploadedFiles);
                $pengumpulan->update(['file_upload' => $uploadedFiles[0]['file_path']]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            foreach ($storedPaths as $path) {
                Storage::disk('local')->delete($path);
            }
            report($e);

            return back()->withInput()->with('error', 'Tugas gagal dikumpulkan. Silakan coba lagi.');
        }

        $guruId = $tugas->kelasMapel->guru_id;
        app(NotifikasiService::class)->notifikasiUser(
            $guruId,
            'kumpul_tugas',
            'Siswa mengumpulkan tugas',
            "{$user->nama_lengkap} telah mengumpulkan tugas '{$tugas->judul}'.",
            route('guru.tugas.pengumpulan', [$tugas->kelas_mapel_id, $tugas->id])
        );

        return redirect()->route('siswa.tugas.show', $tugas)->with('success', 'Tugas berhasil dikumpulkan.');
    }

    public function downloadFile(Tugas $tugas, PengumpulanFile $file)
    {
        $user = Auth::user();
        $siswa = $user->siswa;
        $tugas->loadMissing('kelasMapel.tahunAjaran');
        $this->ensureTugasAktifUntukSiswa($tugas, $siswa);
        $file->loadMissing('pengumpulan');
        abort_unless($file->pengumpulan, 404);
        $this->ensurePengumpulanMilikSiswaDanTugas($file->pengumpulan, $tugas, $siswa);

        return $this->downloadPengumpulanPath($file->file_path, $file->file_name);
    }

    public function downloadLegacyFile(Tugas $tugas, PengumpulanTugas $pengumpulan)
    {
        $user = Auth::user();
        $siswa = $user->siswa;
        $tugas->loadMissing('kelasMapel.tahunAjaran');
        $this->ensureTugasAktifUntukSiswa($tugas, $siswa);
        $this->ensurePengumpulanMilikSiswaDanTugas($pengumpulan, $tugas, $siswa);

        return $this->downloadPengumpulanPath($pengumpulan->file_upload, basename((string) $pengumpulan->file_upload));
    }

    private function ensureTugasAktifUntukSiswa(Tugas $tugas, ?Siswa $siswa): void
    {
        abort_unless($siswa && $tugas->kelasMapel && (int) $siswa->kelas_id === (int) $tugas->kelasMapel->kelas_id && $tugas->kelasMapel->isAktif(), 403, 'Anda tidak memiliki akses ke tugas ini.');
    }

    private function ensurePengumpulanMilikSiswaDanTugas(PengumpulanTugas $pengumpulan, Tugas $tugas, ?Siswa $siswa): void
    {
        abort_unless($siswa && (int) $pengumpulan->siswa_id === (int) $siswa->id && (int) $pengumpulan->tugas_id === (int) $tugas->id, 403);
    }

    private function downloadPengumpulanPath(?string $path, string $downloadName)
    {
        abort_unless($path, 404);
        $disk = Storage::disk('local');
        if (! $disk->exists($path)) {
            $disk = Storage::disk('public');
        }
        abort_unless($disk->exists($path), 404);

        return response()->download($disk->path($path), basename($downloadName));
    }
}
