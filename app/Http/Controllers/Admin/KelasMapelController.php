<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreKelasMapelRequest;
use App\Http\Requests\Admin\StoreWaliKelasRequest;
use App\Models\JadwalMengajar;
use App\Models\Kelas;
use App\Models\KelasMapel;
use App\Models\MataPelajaran;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Models\WaliKelas;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class KelasMapelController extends Controller
{
    public function index()
    {
        if (request()->user()?->isGuru()) {
            return redirect()->route('admin.mata-pelajaran.index')->with('info', 'Pengaturan kelas dan mata pelajaran berjalan otomatis untuk akun guru tunggal.');
        }

        $kelasMapel = KelasMapel::with(['kelas', 'mataPelajaran', 'guru', 'tahunAjaran'])
            ->orderBy('tahun_ajaran_id', 'desc')->orderBy('kelas_id')->orderBy('semester')->paginate(20);
        $waliKelas = WaliKelas::with(['kelas', 'guru', 'tahunAjaran'])
            ->orderBy('tahun_ajaran_id', 'desc')->orderBy('kelas_id')->paginate(20, ['*'], 'wali_page');
        $jadwalMengajar = JadwalMengajar::with(['guru', 'kelasMapel.kelas', 'kelasMapel.mataPelajaran'])
            ->orderBy('hari')
            ->orderBy('pelajaran_ke')
            ->get();
        $kelas = Kelas::all();
        $mapel = MataPelajaran::orderBy('urutan')->get();
        $guru = User::whereHas('role', fn ($q) => $q->where('nama_role', 'guru'))->where('is_active', true)->orderBy('nama_lengkap')->get();
        $tahunAjaran = TahunAjaran::orderBy('tahun', 'desc')->get();

        return Inertia::render('Admin/KelasMapel/Index', [
            'kelasMapel' => $kelasMapel->through(fn (KelasMapel $item) => [
                'id' => $item->id,
                'kelas' => trim(($item->kelas?->tingkat ? $item->kelas->tingkat.' ' : '').($item->kelas?->nama_kelas ?? '-')),
                'mapel' => $item->mataPelajaran?->nama_mapel ?? '-', 'mapel_kode' => $item->mataPelajaran?->kode ?? '-',
                'guru' => $item->guru?->nama_lengkap ?? '-', 'pertemuan_per_minggu' => (int) $item->pertemuan_per_minggu,
                'semester' => $item->semester, 'tahun_ajaran' => $item->tahunAjaran?->tahun ?? '-',
            ]),
            'waliKelas' => $waliKelas->through(fn (WaliKelas $item) => [
                'id' => $item->id,
                'kelas' => trim(($item->kelas?->tingkat ? $item->kelas->tingkat.' ' : '').($item->kelas?->nama_kelas ?? '-')),
                'guru' => $item->guru?->nama_lengkap ?? '-', 'tahun_ajaran' => $item->tahunAjaran?->tahun ?? '-',
            ]),
            'jadwalMengajar' => $jadwalMengajar->map(fn (JadwalMengajar $item) => [
                'id' => $item->id,
                'guru' => $item->guru?->nama_lengkap ?? '-',
                'hari' => $item->dayLabel(),
                'pelajaran_ke' => $item->pelajaran_ke,
                'kelas_mapel' => trim(($item->kelasMapel?->kelas?->nama_kelas ?? '-').' - '.($item->kelasMapel?->mataPelajaran?->nama_mapel ?? '-')),
                'delete_url' => route('admin.jadwal-mengajar.destroy', $item),
            ])->values(),
            'kelasOptions' => $kelas->map(fn (Kelas $item) => ['value' => $item->id, 'label' => trim(($item->tingkat ? $item->tingkat.' ' : '').$item->nama_kelas)])->values(),
            'mapelOptions' => $mapel->map(fn (MataPelajaran $item) => ['value' => $item->id, 'label' => trim($item->kode.' - '.$item->nama_mapel)])->values(),
            'guruOptions' => $guru->map(fn (User $item) => ['value' => $item->id, 'label' => $item->nama_lengkap])->values(),
            'tahunAjaranOptions' => $tahunAjaran->map(fn (TahunAjaran $item) => ['value' => $item->id, 'label' => $item->tahun.($item->is_active ? ' - Aktif' : '')])->values(),
        ]);
    }

    public function store(StoreKelasMapelRequest $request)
    {
        if ($request->user()?->isGuru()) {
            return redirect()->route('admin.mata-pelajaran.index')->with('info', 'Pengaturan kelas dan mata pelajaran berjalan otomatis untuk akun guru tunggal.');
        }

        $validated = $request->validated();
        $isGuru = User::whereKey($validated['guru_id'])->whereHas('role', fn ($q) => $q->where('nama_role', 'guru'))->where('is_active', true)->exists();
        if (! $isGuru) {
            throw ValidationException::withMessages(['guru_id' => 'Guru yang dipilih tidak aktif atau tidak valid.']);
        }
        $exists = KelasMapel::where(['kelas_id' => $validated['kelas_id'], 'mapel_id' => $validated['mapel_id'], 'tahun_ajaran_id' => $validated['tahun_ajaran_id'], 'semester' => $validated['semester']])->exists();
        if ($exists) {
            return back()->with('error', 'Kombinasi kelas, mapel, tahun ajaran, dan semester sudah ada.')->withInput();
        }
        KelasMapel::create($validated);

        return redirect()->route('admin.kelas-mapel.index')->with('success', 'Pengaturan kelas-mapel berhasil ditambahkan.');
    }

    public function destroy(KelasMapel $kelasMapel)
    {
        if (request()->user()?->isGuru()) {
            return redirect()->route('admin.mata-pelajaran.index')->with('info', 'Pengaturan kelas dan mata pelajaran berjalan otomatis untuk akun guru tunggal.');
        }

        $hasData = $kelasMapel->materi()->exists() || $kelasMapel->tugas()->exists() || $kelasMapel->absensi()->exists() || $kelasMapel->nilaiAkhir()->exists() || $kelasMapel->sikapSosial()->exists() || $kelasMapel->sikapSpiritual()->exists() || $kelasMapel->chatMessages()->exists() || $kelasMapel->jadwalMengajar()->exists() || $kelasMapel->kelasDaring()->exists();
        if ($hasData) {
            return back()->with('error', 'Pengajaran tidak dapat dihapus karena sudah memiliki data materi, tugas, absensi, nilai, sikap, chat, jadwal, atau kelas daring.');
        }
        $kelasMapel->delete();

        return redirect()->route('admin.kelas-mapel.index')->with('success', 'Pengaturan kelas-mapel berhasil dihapus.');
    }

    public function storeWaliKelas(StoreWaliKelasRequest $request)
    {
        $validated = $request->validated();
        $isGuru = User::whereKey($validated['guru_id'])->whereHas('role', fn ($q) => $q->where('nama_role', 'guru'))->where('is_active', true)->exists();
        if (! $isGuru) {
            throw ValidationException::withMessages(['guru_id' => 'Guru wali kelas yang dipilih tidak aktif atau tidak valid.']);
        }
        $exists = WaliKelas::where(['kelas_id' => $validated['kelas_id'], 'tahun_ajaran_id' => $validated['tahun_ajaran_id']])->exists();
        if ($exists) {
            return back()->with('error', 'Kelas ini sudah memiliki wali kelas pada tahun ajaran tersebut.')->withInput();
        }
        WaliKelas::create($validated);

        return redirect()->route('admin.kelas-mapel.index')->with('success', 'Penugasan wali kelas berhasil ditambahkan.');
    }

    public function destroyWaliKelas(WaliKelas $waliKelas)
    {
        $hasData = $waliKelas->absensi()->exists() || $waliKelas->pertemuan()->exists() || $waliKelas->penangananSiswa()->exists();
        if ($hasData) {
            return redirect()->route('admin.kelas-mapel.index')->with('error', 'Penugasan wali kelas tidak dapat dihapus karena sudah memiliki data absensi, pertemuan, atau penanganan siswa.');
        }
        $waliKelas->delete();

        return redirect()->route('admin.kelas-mapel.index')->with('success', 'Penugasan wali kelas berhasil dihapus.');
    }

    public function destroyJadwalMengajar(JadwalMengajar $jadwalMengajar)
    {
        $jadwalMengajar->delete();

        return redirect()->route('admin.kelas-mapel.index')->with('success', 'Jadwal mengajar berhasil dihapus.');
    }
}
