<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengaturan;
use App\Models\TahunAjaran;
use App\Services\SingleTeacherTeachingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TahunAjaranController extends Controller
{
    public function index()
    {
        $tahunAjaran = TahunAjaran::orderBy('tahun', 'desc')->get();

        return Inertia::render('Admin/TahunAjaran/Index', [
            'tahunAjaran' => $tahunAjaran,
        ]);
    }
    //Menyimpan Tahun Ajaran Baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tahun' => ['required', 'string', 'max:9', 'regex:/^\\d{4}\\/\\d{4}$/', 'unique:tahun_ajaran,tahun'],
            'is_active' => 'boolean',
        ]);

        DB::transaction(function () use ($request, $validated) {
            $tahunAjaran = TahunAjaran::create($validated);

            if ($request->boolean('is_active')) {
                $this->aktifkanTahunAjaran($tahunAjaran);
            }
        });

        return redirect()->route('admin.tahun-ajaran.index')
            ->with('success', 'Tahun ajaran berhasil ditambahkan.');
    }
    //Edit Tahun Ajaran
    public function update(Request $request, TahunAjaran $tahunAjaran)
    {
        $validated = $request->validate([
            'tahun' => ['required', 'string', 'max:9', 'regex:/^\\d{4}\\/\\d{4}$/', 'unique:tahun_ajaran,tahun,' . $tahunAjaran->id],
            'is_active' => 'boolean',
        ]);

        if ($tahunAjaran->is_active && !$request->boolean('is_active')) {
            return back()->withInput()->with('error', 'Tahun ajaran aktif tidak dapat dinonaktifkan tanpa mengaktifkan tahun ajaran pengganti.');
        }

        DB::transaction(function () use ($request, $tahunAjaran, $validated) {
            $wasActive = $tahunAjaran->is_active;
            $tahunAjaran->update($validated);

            if ($request->boolean('is_active') && !$wasActive) {
                $this->aktifkanTahunAjaran($tahunAjaran);
            }
        });

        return redirect()->route('admin.tahun-ajaran.index')
            ->with('success', 'Tahun ajaran berhasil diperbarui.');
    }
    //Hapus Tahun Ajaran
    public function destroy(TahunAjaran $tahunAjaran)
    {
        if ($tahunAjaran->is_active) {
            return back()->with('error', 'Tahun ajaran aktif tidak dapat dihapus.');
        }

        if ($tahunAjaran->kelasMapel()->exists()) {
            return back()->with('error', 'Tahun ajaran tidak dapat dihapus karena sudah digunakan oleh data pembelajaran.');
        }

        $tahunAjaran->delete();
        return redirect()->route('admin.tahun-ajaran.index')
            ->with('success', 'Tahun ajaran berhasil dihapus.');
    }
    //Set Tahun Ajaran Aktif
    public function setAktif(TahunAjaran $tahunAjaran)
    {
        DB::transaction(function () use ($tahunAjaran) {
            $this->aktifkanTahunAjaran($tahunAjaran);
        });

        return back()->with('success', "Tahun ajaran {$tahunAjaran->tahun} sekarang aktif.");
    }

    private function aktifkanTahunAjaran(TahunAjaran $tahunAjaran): void
    {
        TahunAjaran::where('is_active', true)
            ->where('id', '!=', $tahunAjaran->id)
            ->update(['is_active' => false]);

        $tahunAjaran->update(['is_active' => true]);
        Pengaturan::setValue('semester_aktif', '1');
        app(SingleTeacherTeachingService::class)->syncYear($tahunAjaran);
    }
}
