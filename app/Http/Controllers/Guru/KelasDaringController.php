<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guru\StoreKelasDaringRequest;
use App\Http\Requests\Guru\UpdateKelasDaringStatusRequest;
use App\Models\JadwalMengajar;
use App\Models\KelasDaring;
use App\Models\KelasMapel;
use App\Services\NotifikasiService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class KelasDaringController extends Controller
{
    public function index()
    {
        $kelasMapel = $this->assignedKelasMapelQuery()->get();
        $sessions = KelasDaring::with(['kelasMapel.kelas', 'kelasMapel.mataPelajaran'])
            ->where('guru_id', Auth::id())
            ->orderByDesc('tanggal')
            ->orderBy('pelajaran_ke')
            ->get();

        return Inertia::render('Guru/KelasDaring/Index', [
            'kelasMapel' => $kelasMapel->map(fn (KelasMapel $item) => [
                'id' => $item->id,
                'label' => ($item->kelas?->displayName() ?? '-').' - '.($item->mataPelajaran?->nama_mapel ?? '-').' (Sem. '.$item->semester.')',
            ])->values(),
            'lessonSlots' => collect(range(1, 5))->map(fn ($slot) => ['value' => $slot, 'label' => "Pelajaran ke-{$slot}"])->values(),
            'sessions' => $sessions->map(fn (KelasDaring $item) => $this->formatSession($item))->values(),
            'storeUrl' => route('guru.kelas-daring.store'),
        ]);
    }

    public function store(StoreKelasDaringRequest $request, NotifikasiService $notifikasiService)
    {
        $validated = $request->validated();

        $kelasMapel = $this->assignedKelasMapelQuery()
            ->whereKey($validated['kelas_mapel_id'])
            ->first();

        if (! $kelasMapel) {
            throw ValidationException::withMessages([
                'kelas_mapel_id' => 'Kelas dan mata pelajaran tidak valid untuk akun guru ini.',
            ]);
        }

        $dayOfWeek = (int) date('N', strtotime($validated['tanggal']));
        $hasSchedule = JadwalMengajar::where('kelas_mapel_id', $kelasMapel->id)
            ->where('hari', $dayOfWeek)
            ->where('pelajaran_ke', $validated['pelajaran_ke'])
            ->exists();

        if (! $hasSchedule) {
            throw ValidationException::withMessages([
                'pelajaran_ke' => 'Tanggal dan jam pelajaran belum sesuai dengan jadwal mengajar.',
            ]);
        }

        DB::transaction(function () use ($validated, $kelasMapel, $notifikasiService) {
            $session = KelasDaring::create([
                ...$validated,
                'guru_id' => Auth::id(),
                'status' => $validated['status'] ?? KelasDaring::STATUS_TERJADWAL,
            ]);

            $notifikasiService->notifikasiKelasMapel(
                $kelasMapel->id,
                'kelas_daring',
                'Kelas Daring Baru',
                "Kelas daring '{$session->judul}' dijadwalkan pada ".$session->tanggal->format('d/m/Y').'.',
                route('siswa.kelas-mapel.show', $kelasMapel)
            );
        });

        return back()->with('success', 'Kelas daring berhasil dijadwalkan.');
    }

    public function updateStatus(UpdateKelasDaringStatusRequest $request, KelasDaring $kelasDaring)
    {
        abort_unless((int) $kelasDaring->guru_id === (int) Auth::id(), 403);

        $validated = $request->validated();

        $kelasDaring->update($validated);

        return back()->with('success', 'Status kelas daring berhasil diperbarui.');
    }

    public function destroy(KelasDaring $kelasDaring)
    {
        abort_unless((int) $kelasDaring->guru_id === (int) Auth::id(), 403);

        $kelasDaring->delete();

        return back()->with('success', 'Kelas daring berhasil dihapus.');
    }

    private function assignedKelasMapelQuery()
    {
        return KelasMapel::with(['kelas', 'mataPelajaran', 'tahunAjaran'])
            ->where('guru_id', Auth::id())
            ->aktif();
    }

    private function formatSession(KelasDaring $item): array
    {
        return [
            'id' => $item->id,
            'judul' => $item->judul,
            'deskripsi' => $item->deskripsi,
            'kelas_mapel' => ($item->kelasMapel?->kelas?->displayName() ?? '-').' - '.($item->kelasMapel?->mataPelajaran?->nama_mapel ?? '-'),
            'tanggal' => $item->tanggal?->format('d M Y'),
            'tanggal_iso' => $item->tanggal?->format('Y-m-d'),
            'pelajaran_ke' => $item->pelajaran_ke,
            'meeting_url' => $item->meeting_url,
            'status' => $item->status,
            'status_url' => route('guru.kelas-daring.status', $item),
            'delete_url' => route('guru.kelas-daring.destroy', $item),
        ];
    }
}
