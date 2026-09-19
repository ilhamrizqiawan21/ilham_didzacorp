<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\JadwalMengajar;
use App\Models\KelasMapel;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class JadwalMengajarController extends Controller
{
    public function index()
    {
        $kelasMapel = $this->assignedKelasMapelQuery()->get();

        $schedules = JadwalMengajar::with(['kelasMapel.kelas', 'kelasMapel.mataPelajaran'])
            ->where('guru_id', Auth::id())
            ->orderBy('hari')
            ->orderBy('pelajaran_ke')
            ->get();

        return Inertia::render('Guru/JadwalMengajar/Index', [
            'days' => collect(JadwalMengajar::DAYS)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),
            'lessonSlots' => collect(range(1, 5))->map(fn ($slot) => ['value' => $slot, 'label' => "Pelajaran ke-{$slot}"])->values(),
            'kelasMapel' => $kelasMapel->map(fn (KelasMapel $item) => [
                'id' => $item->id,
                'label' => ($item->kelas?->displayName() ?? '-') . ' - ' . ($item->mataPelajaran?->nama_mapel ?? '-') . ' (Sem. ' . $item->semester . ')',
            ])->values(),
            'schedules' => $schedules->map(fn (JadwalMengajar $item) => $this->formatSchedule($item))->values(),
            'storeUrl' => route('guru.jadwal-mengajar.store'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kelas_mapel_id' => 'required|integer',
            'hari' => 'required|integer|min:1|max:5',
            'pelajaran_ke' => 'required|integer|min:1|max:5',
        ]);

        $kelasMapel = $this->assignedKelasMapelQuery()
            ->whereKey($validated['kelas_mapel_id'])
            ->first();

        if (! $kelasMapel) {
            throw ValidationException::withMessages([
                'kelas_mapel_id' => 'Kelas dan mata pelajaran tidak valid untuk akun guru ini.',
            ]);
        }

        if (JadwalMengajar::where('guru_id', Auth::id())
            ->where('hari', $validated['hari'])
            ->where('pelajaran_ke', $validated['pelajaran_ke'])
            ->exists()) {
            throw ValidationException::withMessages([
                'pelajaran_ke' => 'Anda sudah memiliki jadwal pada hari dan jam pelajaran ini.',
            ]);
        }

        if (JadwalMengajar::where('kelas_id', $kelasMapel->kelas_id)
            ->where('hari', $validated['hari'])
            ->where('pelajaran_ke', $validated['pelajaran_ke'])
            ->exists()) {
            throw ValidationException::withMessages([
                'pelajaran_ke' => 'Kelas ini sudah memiliki jadwal lain pada slot tersebut.',
            ]);
        }

        try {
            JadwalMengajar::create([
                'guru_id' => Auth::id(),
                'kelas_id' => $kelasMapel->kelas_id,
                'kelas_mapel_id' => $kelasMapel->id,
                'hari' => $validated['hari'],
                'pelajaran_ke' => $validated['pelajaran_ke'],
            ]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'pelajaran_ke' => 'Slot jadwal sudah terpakai.',
            ]);
        }

        return back()->with('success', 'Jadwal mengajar berhasil ditambahkan.');
    }

    public function destroy(JadwalMengajar $jadwalMengajar)
    {
        abort_unless((int) $jadwalMengajar->guru_id === (int) Auth::id(), 403);

        $jadwalMengajar->delete();

        return back()->with('success', 'Jadwal mengajar berhasil dihapus.');
    }

    private function assignedKelasMapelQuery()
    {
        return KelasMapel::with(['kelas', 'mataPelajaran', 'tahunAjaran'])
            ->where('guru_id', Auth::id())
            ->aktif();
    }

    private function formatSchedule(JadwalMengajar $item): array
    {
        $kelas = $item->kelasMapel?->kelas?->displayName() ?? '-';
        $mapel = $item->kelasMapel?->mataPelajaran?->nama_mapel ?? '-';

        return [
            'id' => $item->id,
            'hari' => $item->hari,
            'hari_label' => $item->dayLabel(),
            'pelajaran_ke' => $item->pelajaran_ke,
            'kelas' => $kelas,
            'mapel' => $mapel,
            'kelas_mapel' => trim($kelas . ' - ' . $mapel),
            'delete_url' => route('guru.jadwal-mengajar.destroy', $item),
        ];
    }
}
