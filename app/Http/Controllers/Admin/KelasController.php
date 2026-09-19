<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Services\SingleTeacherTeachingService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class KelasController extends Controller
{
    /**
     * Tampilkan daftar kelas.
     */
    public function index()
    {
        $kelas = Kelas::withCount([
            'siswa' => fn ($query) => $query->where('status', 'aktif'),
            'kelasMapel',
            'waliKelas',
        ])
            ->orderByRaw("CASE tingkat WHEN '1' THEN 1 WHEN '2' THEN 2 WHEN '3' THEN 3 WHEN '4' THEN 4 WHEN '5' THEN 5 WHEN '6' THEN 6 WHEN '7' THEN 7 WHEN '8' THEN 8 WHEN '9' THEN 9 WHEN '10' THEN 10 WHEN '11' THEN 11 WHEN '12' THEN 12 WHEN 'VII' THEN 7 WHEN 'VIII' THEN 8 WHEN 'IX' THEN 9 WHEN 'Mahasiswa' THEN 13 ELSE 99 END")
            ->orderBy('nama_kelas')
            ->get();

        return Inertia::render('Admin/Kelas/Index', [
            'kelas' => $kelas->map(fn (Kelas $item) => [
                'id' => $item->id,
                'tingkat' => $item->tingkat,
                'nama_kelas' => $item->nama_kelas,
                'siswa_count' => $item->siswa_count,
                'kelas_mapel_count' => $item->kelas_mapel_count,
                'wali_kelas_count' => $item->wali_kelas_count,
                'siswa_url' => route('admin.kelas-siswa.index', ['kelas_id' => $item->id]),
            ])->values(),
            'metrics' => [
                'total_kelas' => $kelas->count(),
                'total_siswa' => $kelas->sum('siswa_count'),
                'total_penugasan' => $kelas->sum('kelas_mapel_count'),
            ],
        ]);
    }

    /**
     * Simpan kelas baru.
     */
    public function store(Request $request)
    {
        $this->normalizeNamaKelas($request);

        $validated = $request->validate([
            'tingkat' => 'required|in:1,2,3,4,5,6,7,8,9,10,11,12,Mahasiswa,VII,VIII,IX',
            'nama_kelas' => [
                'required', 'string', 'max:20', 'regex:/^[A-Z0-9][A-Z0-9 .-]*$/',
                Rule::unique('kelas', 'nama_kelas')->where(fn ($query) => $query->where('tingkat', $request->input('tingkat'))),
            ],
        ]);

        $kelas = Kelas::create($validated);
        app(SingleTeacherTeachingService::class)->syncClass($kelas);

        return redirect()->route('admin.kelas.index')
            ->with('success', 'Kelas berhasil ditambahkan.');
    }

    /**
     * Update kelas.
     */
    public function update(Request $request, Kelas $kelas)
    {
        $this->normalizeNamaKelas($request);

        $validated = $request->validate([
            'tingkat' => 'required|in:1,2,3,4,5,6,7,8,9,10,11,12,Mahasiswa,VII,VIII,IX',
            'nama_kelas' => [
                'required', 'string', 'max:20', 'regex:/^[A-Z0-9][A-Z0-9 .-]*$/',
                Rule::unique('kelas', 'nama_kelas')
                    ->ignore($kelas->id)
                    ->where(fn ($query) => $query->where('tingkat', $request->input('tingkat'))),
            ],
        ]);

        $kelas->update($validated);

        return redirect()->route('admin.kelas.index')
            ->with('success', 'Kelas berhasil diperbarui.');
    }

    /**
     * Hapus kelas.
     */
    public function destroy(Kelas $kelas)
    {
        if ($kelas->siswa()->exists()) {
            return back()->with('error', 'Tidak dapat menghapus kelas yang masih memiliki data siswa. Pindahkan atau arsipkan siswa terlebih dahulu.');
        }

        if ($kelas->kelasMapel()->exists()) {
            return back()->with('error', 'Tidak dapat menghapus kelas yang masih memiliki data pembelajaran. Hapus materi, tugas, absensi, dan nilai terkait terlebih dahulu.');
        }

        if ($kelas->waliKelas()->exists()) {
            return back()->with('error', 'Tidak dapat menghapus kelas yang masih memiliki wali kelas. Hapus penetapan wali kelas terlebih dahulu.');
        }

        $kelas->delete();
        return redirect()->route('admin.kelas.index')
            ->with('success', 'Kelas berhasil dihapus.');
    }

    private function normalizeNamaKelas(Request $request): void
    {
        $request->merge([
            'nama_kelas' => Str::upper(trim((string) $request->input('nama_kelas'))),
        ]);
    }
}
