<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MataPelajaran;
use App\Services\SingleTeacherTeachingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MataPelajaranController extends Controller
{
    /**
     * Tampilkan daftar mata pelajaran.
     */
    public function index()
    {
        $mapel = MataPelajaran::orderBy('urutan')->get();

        return Inertia::render('Admin/MataPelajaran/Index', [
            'mapel' => $mapel->map(fn (MataPelajaran $item) => [
                'id' => $item->id,
                'kode' => $item->kode,
                'nama_mapel' => $item->nama_mapel,
                'urutan' => $item->urutan,
            ])->values(),
        ]);
    }

    /**
     * Simpan mapel baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => 'required|string|max:10|unique:mata_pelajaran,kode',
            'nama_mapel' => 'required|string|max:100',
            'urutan' => 'nullable|integer|min:0',
        ]);

        $mataPelajaran = MataPelajaran::create($validated);
        app(SingleTeacherTeachingService::class)->syncSubject($mataPelajaran);

        return redirect()->route('admin.mata-pelajaran.index')
            ->with('success', 'Mata pelajaran berhasil ditambahkan.');
    }

    /**
     * Update mapel.
     */
    public function update(Request $request, MataPelajaran $mataPelajaran)
    {
        $validated = $request->validate([
            'kode' => 'required|string|max:10|unique:mata_pelajaran,kode,' . $mataPelajaran->id,
            'nama_mapel' => 'required|string|max:100',
            'urutan' => 'nullable|integer|min:0',
        ]);

        $mataPelajaran->update($validated);

        return redirect()->route('admin.mata-pelajaran.index')
            ->with('success', 'Mata pelajaran berhasil diperbarui.');
    }

    /**
     * Hapus mapel.
     */
    public function destroy(MataPelajaran $mataPelajaran)
    {
        // Relasi kelas_mapel beserta data pembelajarannya menggunakan
        // cascadeOnDelete, sehingga konfigurasi dan riwayat terkait ikut
        // dibersihkan ketika mapel dihapus.
        $mataPelajaran->delete();

        return redirect()->route('admin.mata-pelajaran.index')
            ->with('success', 'Mata pelajaran berhasil dihapus.');
    }
}
