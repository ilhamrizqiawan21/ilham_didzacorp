<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guru\StoreBulkMateriRequest;
use App\Http\Requests\Guru\StoreMateriRequest;
use App\Models\KelasMapel;
use App\Models\Materi;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class MateriController extends Controller
{
    public function index()
    {
        $kelasMapel = KelasMapel::with(['kelas', 'mataPelajaran', 'tahunAjaran'])
            ->where('guru_id', Auth::id())
            ->aktif()
            ->get();

        $materi = Materi::with(['kelasMapel.kelas', 'kelasMapel.mataPelajaran'])
            ->whereHas('kelasMapel', fn ($query) => $query
                ->where('guru_id', Auth::id())
                ->aktif())
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Guru/Materi/Index', [
            'kelasMapel' => $this->formatKelasMapelOptions($kelasMapel),
            'materi' => $materi->map(fn (Materi $item) => $this->formatMateri($item))->values(),
            'storeUrl' => route('guru.materi.store.bulk'),
        ]);
    }

    // Daftar materi yang diupload oleh guru untuk kelas dan mata pelajaran tertentu
    public function list(KelasMapel $kelasMapel)
    {
        $this->authorize('mengajar', $kelasMapel);

        $materi = Materi::where('kelas_mapel_id', $kelasMapel->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Guru/Materi/List', [
            'kelasMapel' => [
                'id' => $kelasMapel->id,
                'kelas' => $kelasMapel->kelas?->displayName() ?? '-',
                'mata_pelajaran' => $kelasMapel->mataPelajaran?->nama_mapel ?? '-',
                'store_url' => route('guru.materi.store', $kelasMapel),
                'back_url' => route('guru.materi.index'),
                'workspace_url' => route('guru.kelas-mapel.show', $kelasMapel),
            ],
            'materi' => $materi->map(fn (Materi $item) => [
                'id' => $item->id,
                'judul' => $item->judul,
                'deskripsi' => $item->deskripsi,
                'deskripsi_ringkas' => Str::limit((string) $item->deskripsi, 60),
                'tanggal' => $item->created_at?->format('d M Y') ?? '-',
                'download_url' => $item->file_path ? route('guru.materi.download', [$kelasMapel, $item]) : null,
                'delete_url' => route('guru.materi.destroy', [$kelasMapel, $item]),
            ])->values(),
        ]);
    }

    public function storeBulk(StoreBulkMateriRequest $request)
    {
        $validated = $request->validated();

        $kelasMapel = $this->assignedKelasMapelQuery()
            ->whereIn('id', $validated['kelas_mapel_ids'])
            ->get();

        if ($kelasMapel->count() !== count(array_unique($validated['kelas_mapel_ids']))) {
            return back()->withInput()->with('error', 'Pilihan kelas tidak valid.');
        }

        $file = $request->file('file_materi');
        $storedPaths = [];

        try {
            DB::beginTransaction();

            foreach ($kelasMapel as $item) {
                $path = $file->store('materi/'.$item->id, 'local');
                $storedPaths[] = $path;

                Materi::create([
                    'kelas_mapel_id' => $item->id,
                    'judul' => $validated['judul'],
                    'deskripsi' => $validated['deskripsi'] ?? null,
                    'file_path' => $path,
                ]);
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

            return back()->withInput()->with('error', 'Materi gagal diupload. Silakan coba lagi.');
        }

        return redirect()->route('guru.materi.index')
            ->with('success', 'Materi berhasil diupload ke kelas yang dipilih.');
    }

    // Menyimpan materi baru
    public function store(StoreMateriRequest $request, KelasMapel $kelasMapel)
    {
        $this->authorize('mengajar', $kelasMapel);

        $validated = $request->validated();

        $file = $request->file('file_materi');
        $path = null;

        try {
            DB::beginTransaction();

            $path = $file->store('materi/'.$kelasMapel->id, 'local');

            Materi::create([
                'kelas_mapel_id' => $kelasMapel->id,
                'judul' => $validated['judul'],
                'deskripsi' => $validated['deskripsi'],
                'file_path' => $path,
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            if ($path) {
                Storage::disk('local')->delete($path);
            }

            report($e);

            return back()->withInput()->with('error', 'Materi gagal diupload. Silakan coba lagi.');
        }

        return redirect()->route('guru.materi.list', $kelasMapel)
            ->with('success', 'Materi berhasil diupload.');
    }

    // Menghapus materi yang sudah diupload oleh guru untuk kelas dan mata pelajaran tertentu
    public function download(KelasMapel $kelasMapel, Materi $materi)
    {
        $this->authorize('mengajar', $kelasMapel);
        $this->ensureMateriBelongsToKelasMapel($materi, $kelasMapel);

        $disk = $this->materiDisk($materi->file_path);
        if (! $materi->file_path || ! $disk) {
            return back()->with('error', 'File materi tidak ditemukan.');
        }

        return response()->download($disk->path($materi->file_path), $materi->judul.'_'.basename($materi->file_path));
    }

    // Menghapus materi yang sudah diupload oleh guru untuk kelas dan mata pelajaran tertentu
    public function destroy(KelasMapel $kelasMapel, Materi $materi)
    {
        $this->authorize('mengajar', $kelasMapel);
        $this->ensureMateriBelongsToKelasMapel($materi, $kelasMapel);

        if ($materi->file_path) {
            Storage::disk('local')->delete($materi->file_path);
            Storage::disk('public')->delete($materi->file_path);
        }

        $materi->delete();

        return redirect()->route('guru.materi.index')
            ->with('success', 'Materi berhasil dihapus.');
    }

    private function ensureMateriBelongsToKelasMapel(Materi $materi, KelasMapel $kelasMapel): void
    {
        abort_unless((int) $materi->kelas_mapel_id === (int) $kelasMapel->id, 403);
    }

    private function materiDisk(?string $path): ?FilesystemAdapter
    {
        if (! $path) {
            return null;
        }

        $local = Storage::disk('local');

        return $local->exists($path) ? $local : (Storage::disk('public')->exists($path) ? Storage::disk('public') : null);
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
            'href' => route('guru.materi.list', $item),
        ])->values();
    }

    private function formatMateri(Materi $item): array
    {
        $kelasMapel = $item->kelasMapel;

        return [
            'id' => $item->id,
            'judul' => $item->judul,
            'deskripsi' => $item->deskripsi,
            'deskripsi_ringkas' => Str::limit((string) $item->deskripsi, 60),
            'tanggal' => $item->created_at?->format('d M Y') ?? '-',
            'kelas' => $kelasMapel?->kelas?->displayName() ?? '-',
            'mata_pelajaran' => $kelasMapel?->mataPelajaran?->nama_mapel ?? '-',
            'download_url' => $item->file_path && $kelasMapel ? route('guru.materi.download', [$kelasMapel, $item]) : null,
            'delete_url' => $kelasMapel ? route('guru.materi.destroy', [$kelasMapel, $item]) : null,
        ];
    }
}
