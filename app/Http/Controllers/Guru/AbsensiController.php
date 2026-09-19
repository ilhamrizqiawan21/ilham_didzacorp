<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guru\IndexAbsensiRequest;
use App\Http\Requests\Guru\RekapAbsensiRequest;
use App\Http\Requests\Guru\StoreAbsensiRequest;
use App\Models\Absensi;
use App\Models\AcademicAuditLog;
use App\Models\CalendarEvent;
use App\Models\JadwalMengajar;
use App\Models\KelasMapel;
use App\Models\Notifikasi;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AbsensiController extends Controller
{
    public function index(IndexAbsensiRequest $request)
    {
        $guruId = Auth::id();
        $bulan = $request->input('bulan', date('Y-m'));
        $kelasMapelId = $request->integer('kelas_mapel_id') ?: null;
        $highlightedSiswaId = $request->integer('siswa_id') ?: null;

        $kelasMapel = KelasMapel::with(['kelas', 'mataPelajaran', 'tahunAjaran'])
            ->where('guru_id', $guruId)
            ->aktif()
            ->get();

        if (! $kelasMapelId && $highlightedSiswaId) {
            $targetSiswa = Siswa::query()
                ->whereKey($highlightedSiswaId)
                ->where('status', 'aktif')
                ->first(['id', 'kelas_id']);

            $kelasMapelId = $targetSiswa
                ? $kelasMapel->firstWhere('kelas_id', $targetSiswa->kelas_id)?->id
                : null;
        }

        $kmData = null;
        $siswaList = collect();
        $meetings = collect();
        $absensiData = [];

        if ($kelasMapelId) {
            $kmData = $kelasMapel->firstWhere('id', (int) $kelasMapelId);

            if ($kmData) {
                $siswaList = Siswa::with('user')
                    ->where('kelas_id', $kmData->kelas_id)
                    ->where('status', 'aktif')
                    ->orderBy('nis')
                    ->get();

                $meetings = $this->attendanceMeetings($bulan, $kmData);

                $absensiRaw = Absensi::where('kelas_mapel_id', $kmData->id)
                    ->whereIn('siswa_id', $siswaList->pluck('id'))
                    ->whereBetween('tanggal', ["{$bulan}-01", date('Y-m-t', strtotime("{$bulan}-01"))])
                    ->get();

                foreach ($siswaList as $s) {
                    $absensiData[$s->id] = [];
                }
                foreach ($absensiRaw as $a) {
                    $tgl = $a->tanggal instanceof Carbon ? $a->tanggal->format('Y-m-d') : $a->tanggal;
                    $absensiData[$a->siswa_id][$tgl] = $a->status;
                }
            }
        }

        if (! $siswaList->contains('id', $highlightedSiswaId)) {
            $highlightedSiswaId = null;
        }

        return Inertia::render('Guru/Absensi/Index', [
            'kelasMapel' => $kelasMapel->map(fn (KelasMapel $item) => [
                'id' => $item->id,
                'label' => trim(($item->kelas?->nama_kelas ?? '-').' - '.($item->mataPelajaran?->nama_mapel ?? '-').' ('.(int) $item->pertemuan_per_minggu.'x/minggu)'),
                'kelas' => $item->kelas?->nama_kelas ?? '-',
                'mata_pelajaran' => $item->mataPelajaran?->nama_mapel ?? '-',
                'pertemuan_per_minggu' => (int) $item->pertemuan_per_minggu,
                'workspace_url' => route('guru.kelas-mapel.show', $item),
            ])->values(),
            'filters' => [
                'kelas_mapel_id' => $kelasMapelId ? (string) $kelasMapelId : '',
                'bulan' => $bulan,
                'siswa_id' => $highlightedSiswaId ? (string) $highlightedSiswaId : '',
            ],
            'highlightedSiswaId' => $highlightedSiswaId,
            'selected' => $kmData ? [
                'id' => $kmData->id,
                'kelas' => $kmData->kelas?->nama_kelas ?? '-',
                'mata_pelajaran' => $kmData->mataPelajaran?->nama_mapel ?? '-',
                'pertemuan_per_minggu' => (int) $kmData->pertemuan_per_minggu,
                'has_schedule' => JadwalMengajar::where('kelas_mapel_id', $kmData->id)->exists(),
                'schedule_url' => route('guru.jadwal-mengajar.index'),
                'workspace_url' => route('guru.kelas-mapel.show', $kmData),
                'store_url' => route('guru.absensi.store', $kmData),
                'export_excel_url' => route('guru.absensi.export.excel', $kmData),
                'export_pdf_url' => route('guru.absensi.export.pdf', $kmData),
            ] : null,
            'weeks' => $meetings,
            'students' => $siswaList->values()->map(function (Siswa $siswa, int $index) use ($meetings, $absensiData) {
                $weekly = [];

                foreach ($meetings as $meeting) {
                    $date = $meeting['date'];
                    $weekly[$meeting['key']] = $date ? ($absensiData[$siswa->id][$date] ?? '') : '';
                }

                return [
                    'id' => $siswa->id,
                    'no' => $index + 1,
                    'nis' => $siswa->nis,
                    'nama' => $siswa->user?->nama_lengkap ?? '-',
                    'absensi' => $weekly,
                ];
            }),
        ]);
    }

    public function create(KelasMapel $kelasMapel)
    {
        $this->authorize('mengajar', $kelasMapel);

        return redirect()->route('guru.absensi.index', ['kelas_mapel_id' => $kelasMapel->id]);
    }

    // Simpan Absensi
    public function store(StoreAbsensiRequest $request, KelasMapel $kelasMapel)
    {
        $this->authorize('mengajar', $kelasMapel);

        $validated = $request->validated();

        $absensiInput = $validated['absensi'] ?? [];
        $validSiswaIds = Siswa::where('kelas_id', $kelasMapel->kelas_id)
            ->where('status', 'aktif')
            ->pluck('id')
            ->map(fn ($id) => (string) $id);

        if (collect(array_keys($absensiInput))->diff($validSiswaIds)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'absensi' => 'Data absensi berisi siswa yang tidak termasuk kelas ini.',
            ]);
        }

        $bulan = $validated['bulan'];
        $meetings = $this->attendanceMeetings($bulan, $kelasMapel)
            ->keyBy('key');

        $invalidMeetingKeys = collect($absensiInput)
            ->flatMap(fn (array $meetingData) => array_keys($meetingData))
            ->unique()
            ->diff($meetings->keys());

        if ($invalidMeetingKeys->isNotEmpty()) {
            throw ValidationException::withMessages([
                'absensi' => 'Data absensi berisi pertemuan yang tidak valid untuk bulan ini.',
            ]);
        }

        $meetingDates = $meetings->pluck('date')->filter()->values();
        $existingAbsensi = Absensi::where('kelas_mapel_id', $kelasMapel->id)
            ->whereIn('siswa_id', $validSiswaIds->map(fn ($id) => (int) $id))
            ->whereIn('tanggal', $meetingDates)
            ->get()
            ->keyBy(fn (Absensi $absensi) => $absensi->siswa_id.'|'.$absensi->tanggal->format('Y-m-d'));
        $siswasForLog = Siswa::with('user')
            ->whereIn('id', $validSiswaIds->map(fn ($id) => (int) $id))
            ->get()
            ->keyBy('id');
        $changedSiswaIds = collect();

        DB::transaction(function () use ($absensiInput, $meetings, $kelasMapel, $siswasForLog, $existingAbsensi, $validated, &$changedSiswaIds) {
            if ($absensiInput) {
                foreach ($absensiInput as $siswaId => $mingguData) {
                    foreach ($mingguData as $meetingKey => $status) {
                        $meeting = $meetings->get($meetingKey);

                        if ($meeting) {
                            $scope = [
                                'siswa_id' => (int) $siswaId,
                                'kelas_mapel_id' => $kelasMapel->id,
                                'tanggal' => $meeting['date'],
                            ];
                            $existingKey = ((int) $siswaId).'|'.$meeting['date'];
                            $existing = $existingAbsensi->get($existingKey);
                            $existingStatus = $existing?->status;

                            if (($existingStatus ?? '') === ($status ?? '')) {
                                continue;
                            }

                            $changedSiswaIds->push((int) $siswaId);

                            if (! $status) {
                                Absensi::where($scope)->delete();
                                $this->logAbsensiChange($kelasMapel, $siswasForLog->get((int) $siswaId), $meeting['date'], $existing, $existingStatus, null);

                                continue;
                            }

                            $absensi = Absensi::updateOrCreate(
                                $scope,
                                ['status' => $status]
                            );
                            $this->logAbsensiChange($kelasMapel, $siswasForLog->get((int) $siswaId), $meeting['date'], $absensi, $existingStatus, $status);
                        }
                    }
                }
            }

            // Kirim notifikasi hanya untuk siswa yang datanya benar-benar berubah.
            if ($changedSiswaIds->isNotEmpty()) {
                $siswaIds = $changedSiswaIds->unique()->values();
                $siswas = Siswa::whereIn('id', $siswaIds)->get()->keyBy('id');
                $bulanLabel = $validated['bulan'];

                foreach ($siswaIds as $siswaId) {
                    $siswa = $siswas->get((int) $siswaId);
                    if (! $siswa || ! $siswa->user_id) {
                        continue;
                    }

                    Notifikasi::firstOrCreate([
                        'user_id' => $siswa->user_id,
                        'tipe' => 'absensi',
                        'judul' => 'Absensi Diperbarui',
                        'pesan' => "Absensi {$kelasMapel->mataPelajaran?->nama_mapel} bulan {$bulanLabel} telah dicatat.",
                        'link' => route('siswa.progress'),
                        'is_read' => false,
                    ]);
                }
            }
        });

        return back()->with('success', 'Absensi berhasil disimpan.');
    }

    public function rekap(KelasMapel $kelasMapel)
    {
        $this->authorize('mengajar', $kelasMapel);

        return redirect()->route('guru.absensi.index', ['kelas_mapel_id' => $kelasMapel->id]);
    }

    public function rekapAbsensi(RekapAbsensiRequest $request)
    {
        $validated = $request->validated();

        $mode = $validated['mode'] ?? 'bulanan';
        $bulan = $validated['bulan'] ?? date('Y-m');
        $kelasMapel = KelasMapel::with(['kelas', 'mataPelajaran', 'tahunAjaran'])
            ->where('guru_id', Auth::id())
            ->aktif()
            ->get();
        $selectedId = $validated['kelas_mapel_id'] ?? null;
        $selected = $selectedId ? $kelasMapel->firstWhere('id', (int) $selectedId) : null;
        $rows = [];

        if ($selected) {
            $rows = $this->rekapAbsensiRows($selected, $mode, $bulan);
        }

        return Inertia::render('Guru/Rekap/Absensi', [
            'kelasMapel' => $kelasMapel->map(fn (KelasMapel $item) => [
                'id' => $item->id,
                'label' => trim(($item->kelas?->nama_kelas ?? '-').' - '.($item->mataPelajaran?->nama_mapel ?? '-').' (Sem. '.$item->semester.')'),
                'kelas' => $item->kelas?->nama_kelas ?? '-',
                'mata_pelajaran' => $item->mataPelajaran?->nama_mapel ?? '-',
                'semester' => $item->semester,
            ])->values(),
            'filters' => [
                'kelas_mapel_id' => $selected ? (string) $selected->id : '',
                'mode' => $mode,
                'bulan' => $bulan,
            ],
            'selected' => $selected ? [
                'id' => $selected->id,
                'kelas' => $selected->kelas?->nama_kelas ?? '-',
                'mata_pelajaran' => $selected->mataPelajaran?->nama_mapel ?? '-',
                'semester' => $selected->semester,
            ] : null,
            'rekap' => $rows,
            'exportUrls' => [
                'excel' => route('guru.rekap-absensi.export.excel'),
                'pdf' => route('guru.rekap-absensi.export.pdf'),
            ],
        ]);
    }

    private function rekapAbsensiRows(KelasMapel $kelasMapel, string $mode, string $bulan): array
    {
        $students = Siswa::with('user')
            ->where('kelas_id', $kelasMapel->kelas_id)
            ->where('status', 'aktif')
            ->orderBy('nis')
            ->get();
        $query = Absensi::where('kelas_mapel_id', $kelasMapel->id)
            ->whereIn('siswa_id', $students->pluck('id'));

        if ($mode === 'bulanan') {
            $query->whereBetween('tanggal', ["{$bulan}-01", date('Y-m-t', strtotime("{$bulan}-01"))]);
        }

        $absensiBySiswa = $query->get()->groupBy('siswa_id');

        return $students->values()->map(function (Siswa $siswa, int $index) use ($absensiBySiswa) {
            $records = $absensiBySiswa->get($siswa->id, collect());
            $counts = [
                'hadir' => $records->where('status', 'hadir')->count(),
                'sakit' => $records->where('status', 'sakit')->count(),
                'izin' => $records->where('status', 'izin')->count(),
                'alpha' => $records->where('status', 'alpha')->count(),
            ];
            $total = array_sum($counts);

            return [
                'no' => $index + 1,
                'nis' => $siswa->nis,
                'nama' => $siswa->user?->nama_lengkap ?? '-',
                ...$counts,
                'total' => $total,
                'persen_hadir' => $total > 0 ? round(($counts['hadir'] / $total) * 100, 2) : 0,
            ];
        })->all();
    }

    private function attendanceMeetings(string $bulan, KelasMapel $kelasMapel): Collection
    {
        $schedules = JadwalMengajar::where('kelas_mapel_id', $kelasMapel->id)
            ->orderBy('hari')
            ->orderBy('pelajaran_ke')
            ->get()
            ->groupBy('hari');

        if ($schedules->isEmpty()) {
            return collect();
        }

        $start = Carbon::createFromFormat('Y-m-d', "{$bulan}-01")->startOfDay();
        $end = $start->copy()->endOfMonth();
        $holidays = CalendarEvent::where('is_holiday', true)
            ->whereBetween('event_date', [$start->toDateString(), $end->toDateString()])
            ->pluck('event_date')
            ->map(fn ($date) => $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString())
            ->flip();
        $meetings = [];
        $meetingNumber = 1;

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dayNumber = (int) $date->format('N');

            if ($dayNumber < 1 || $dayNumber > 5 || ! $schedules->has($dayNumber) || $holidays->has($date->toDateString())) {
                continue;
            }

            $slots = $schedules->get($dayNumber)
                ->pluck('pelajaran_ke')
                ->unique()
                ->sort()
                ->values();

            $meetings[] = [
                'key' => $date->toDateString(),
                'week' => (int) ceil((int) $date->format('j') / 7),
                'meeting' => $meetingNumber,
                'date' => $date->toDateString(),
                'label' => $date->format('d/m'),
                'title' => JadwalMengajar::DAYS[$dayNumber].' P'.$meetingNumber,
                'lesson_title' => 'Jam ke-'.$slots->implode('/'),
            ];
            $meetingNumber++;
        }

        return collect($meetings);
    }

    private function legacyAttendanceMeetings(string $bulan, int $meetingsPerWeek): Collection
    {
        $meetingsPerWeek = max(1, min($meetingsPerWeek, 6));
        $monthNumber = (int) substr($bulan, 5, 2);
        $firstDay = Carbon::create((int) substr($bulan, 0, 4), $monthNumber, 1);
        $firstMonday = $firstDay->copy();

        if ($firstDay->dayOfWeek !== 1) {
            $firstMonday->addDays((8 - $firstDay->dayOfWeek) % 7);
        }

        $meetings = [];

        for ($week = 1; $week <= 5; $week++) {
            $weekStart = $firstMonday->copy()->addDays(($week - 1) * 7);

            for ($meeting = 1; $meeting <= $meetingsPerWeek; $meeting++) {
                $offset = (int) round((($meeting - 1) * 6) / $meetingsPerWeek);
                $date = $weekStart->copy()->addDays($offset);

                if ((int) $date->format('m') !== $monthNumber) {
                    continue;
                }

                $meetings[] = [
                    'key' => "{$week}-{$meeting}",
                    'week' => $week,
                    'meeting' => $meeting,
                    'date' => $date->format('Y-m-d'),
                    'label' => $date->format('d/m'),
                    'title' => $meetingsPerWeek > 1 ? "M{$week} P{$meeting}" : "Minggu {$week}",
                ];
            }
        }

        return collect($meetings);
    }

    private function logAbsensiChange(KelasMapel $kelasMapel, ?Siswa $siswa, string $tanggal, ?Absensi $absensi, ?string $before, ?string $after): void
    {
        if (! $siswa) {
            return;
        }

        try {
            AcademicAuditLog::create([
                'actor_id' => Auth::id(),
                'module' => 'absensi',
                'action' => $after ? 'update' : 'delete',
                'auditable_type' => Absensi::class,
                'auditable_id' => $absensi?->id,
                'before_values' => ['status' => $before ?: '-'],
                'after_values' => ['status' => $after ?: '-'],
                'metadata' => [
                    'siswa' => $siswa->user?->nama_lengkap ?? $siswa->nis,
                    'nis' => $siswa->nis,
                    'kelas' => $kelasMapel->kelas?->nama_kelas ?? '-',
                    'mata_pelajaran' => $kelasMapel->mataPelajaran?->nama_mapel ?? '-',
                    'tanggal' => $tanggal,
                ],
            ]);
        } catch (\Throwable) {
            // Audit log tidak boleh menggagalkan simpan absensi.
        }
    }
}
