<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\KelasMapel;
use App\Models\Pengumuman;
use App\Models\Tugas;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CalendarTimelineService
{
    public function forUser(User $user, int $year, int $month): Collection
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth()->endOfDay();

        return collect()
            ->merge($this->calendarEvents($user, $start, $end))
            ->merge($this->tasks($user, $start, $end))
            ->merge($this->announcements($user, $start, $end))
            ->sortBy(fn (array $item) => [$item['date'], $item['priority'], $item['title']])
            ->values();
    }

    private function calendarEvents(User $user, Carbon $start, Carbon $end): Collection
    {
        $events = CalendarEvent::whereBetween('event_date', [$start->toDateString(), $end->toDateString()])
            ->where(fn ($q) => $q->where('scope', 'school')->orWhere('user_id', $user->id))
            ->orderBy('event_date')
            ->get();

        return $events->map(fn (CalendarEvent $event) => [
            'id' => 'calendar-'.$event->id,
            'source_id' => $event->id,
            'type' => 'calendar',
            'type_label' => $event->is_holiday ? 'Hari Libur' : 'Event',
            'title' => $event->title,
            'description' => $event->description,
            'date' => $event->event_date->format('Y-m-d'),
            'date_label' => $event->event_date->translatedFormat('d F Y'),
            'time_label' => null,
            'is_done' => (bool) $event->is_done,
            'is_holiday' => (bool) $event->is_holiday,
            'scope' => $event->scope,
            'priority' => 20,
            'detail_url' => null,
            'can_manage' => (int) $event->user_id === (int) $user->id,
        ]);
    }

    private function tasks(User $user, Carbon $start, Carbon $end): Collection
    {
        $query = Tugas::with(['kelasMapel.kelas', 'kelasMapel.mataPelajaran', 'kelasMapel.guru'])
            ->whereBetween('batas_waktu', [$start, $end]);

        if ($user->isGuru()) {
            $query->whereHas('kelasMapel', fn ($q) => $q->where('guru_id', $user->id));
        } elseif ($user->isSiswa()) {
            $kelasId = $user->siswa?->kelas_id;
            if (!$kelasId) {
                return collect();
            }
            $query->whereHas('kelasMapel', fn ($q) => $q->where('kelas_id', $kelasId));
        }

        $tasks = $query->orderBy('batas_waktu')->get();

        if ($user->isGuru()) {
            return $this->groupTeacherTasks($tasks, $user);
        }

        return $tasks->map(fn (Tugas $task) => $this->taskItem($task, $user));
    }

    private function groupTeacherTasks(Collection $tasks, User $user): Collection
    {
        return $tasks
            ->groupBy(fn (Tugas $task) => $this->teacherTaskGroupKey($task))
            ->map(function (Collection $group) use ($user) {
                /** @var Tugas $first */
                $first = $group->first();
                $deadline = $first->batas_waktu;
                $mapel = $first->kelasMapel?->mataPelajaran?->nama_mapel ?? '-';

                $targets = $group
                    ->map(function (Tugas $task) {
                        $kelasMapel = $task->kelasMapel;

                        return [
                            'kelas' => $kelasMapel?->kelas?->displayName() ?? '-',
                            'url' => $kelasMapel
                                ? route('guru.tugas.pengumpulan', [$kelasMapel->id, $task->id])
                                : null,
                        ];
                    })
                    ->filter(fn (array $target) => $target['url'])
                    ->unique('kelas')
                    ->sortBy('kelas', SORT_NATURAL)
                    ->values();

                if ($targets->count() <= 1) {
                    return $this->taskItem($first, $user);
                }

                $groupIds = $group->pluck('id')->sort()->implode('-');

                return [
                    'id' => 'task-group-'.sha1($groupIds),
                    'source_id' => $first->id,
                    'type' => 'task',
                    'type_label' => 'Deadline Tugas',
                    'title' => $first->judul,
                    'description' => $first->deskripsi,
                    'date' => $deadline?->format('Y-m-d'),
                    'date_label' => $deadline?->translatedFormat('d F Y') ?? '-',
                    'time_label' => $deadline?->format('H:i'),
                    'is_done' => false,
                    'is_holiday' => false,
                    'scope' => 'academic',
                    'priority' => 10,
                    'detail_url' => null,
                    'detail_links' => $targets->map(fn (array $target) => [
                        'label' => $target['kelas'],
                        'url' => $target['url'],
                    ])->all(),
                    'can_manage' => true,
                    'meta' => $mapel.' · '.$targets->count().' kelas',
                    'target_classes' => $targets->pluck('kelas')->all(),
                    'group_count' => $targets->count(),
                ];
            })
            ->values();
    }

    private function teacherTaskGroupKey(Tugas $task): string
    {
        $description = preg_replace('/\s+/u', ' ', trim((string) $task->deskripsi));
        $kelasMapel = $task->kelasMapel;

        return implode('|', [
            (string) ($kelasMapel?->mapel_id ?? 0),
            (string) ($kelasMapel?->tahun_ajaran_id ?? 0),
            (string) ($kelasMapel?->semester ?? ''),
            mb_strtolower(trim((string) $task->judul)),
            mb_strtolower($description ?? ''),
            $task->batas_waktu?->format('Y-m-d H:i:s') ?? '',
            (string) ($task->kategori_nilai ?? ''),
        ]);
    }

    private function taskItem(Tugas $task, User $user): array
    {
        $mapel = $task->kelasMapel?->mataPelajaran?->nama_mapel ?? '-';
        $kelas = $task->kelasMapel?->kelas?->displayName() ?? '-';
        $deadline = $task->batas_waktu;
        $detailUrl = null;

        if ($user->isSiswa()) {
            $detailUrl = route('siswa.tugas.show', $task);
        } elseif ($user->isGuru()) {
            $detailUrl = route('guru.tugas.pengumpulan', [$task->kelas_mapel_id, $task->id]);
        }

        return [
            'id' => 'task-'.$task->id,
            'source_id' => $task->id,
            'type' => 'task',
            'type_label' => 'Deadline Tugas',
            'title' => $task->judul,
            'description' => $task->deskripsi,
            'date' => $deadline?->format('Y-m-d'),
            'date_label' => $deadline?->translatedFormat('d F Y') ?? '-',
            'time_label' => $deadline?->format('H:i'),
            'is_done' => false,
            'is_holiday' => false,
            'scope' => 'academic',
            'priority' => 10,
            'detail_url' => $detailUrl,
            'can_manage' => $user->isGuru(),
            'meta' => trim($mapel.' · '.$kelas, ' ·'),
        ];
    }

    private function announcements(User $user, Carbon $start, Carbon $end): Collection
    {
        $query = Pengumuman::with(['creator', 'kelasMapel.kelas', 'kelasMapel.mataPelajaran'])
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at');

        if ($user->isGuru()) {
            $query->where(function ($q) use ($user) {
                $q->whereIn('target', ['semua', 'guru'])
                    ->orWhere('created_by', $user->id)
                    ->orWhere(function ($q) use ($user) {
                        $q->where('target', 'kelas_mapel')
                            ->whereIn('kelas_mapel_id', KelasMapel::where('guru_id', $user->id)->select('id'));
                    });
            });
        } elseif ($user->isSiswa()) {
            $kelasId = $user->siswa?->kelas_id;
            $query->where(function ($q) use ($kelasId) {
                $q->whereIn('target', ['semua', 'siswa'])
                    ->orWhere(function ($q) use ($kelasId) {
                        $q->where('target', 'kelas_mapel')
                            ->where(function ($q) use ($kelasId) {
                                $q->whereIn('kelas_mapel_id', KelasMapel::where('kelas_id', $kelasId)->select('id'))
                                    ->orWhere('target_kelas', 'like', '%"'.$kelasId.'"%');
                            });
                    });
            });
        } elseif ($user->isKepalaSekolah()) {
            $query->whereIn('target', ['semua', 'guru'])
                ->orWhere('created_by', $user->id);
        }

        return $query->get()->map(fn (Pengumuman $item) => [
            'id' => 'announcement-'.$item->id,
            'source_id' => $item->id,
            'type' => 'announcement',
            'type_label' => 'Pengumuman',
            'title' => $item->judul,
            'description' => $item->isi,
            'date' => Carbon::parse($item->created_at)->format('Y-m-d'),
            'date_label' => Carbon::parse($item->created_at)->translatedFormat('d F Y'),
            'time_label' => Carbon::parse($item->created_at)->format('H:i'),
            'is_done' => false,
            'is_holiday' => false,
            'scope' => $item->target,
            'priority' => 30,
            'detail_url' => $this->announcementUrl($user, $item),
            'can_manage' => false,
            'meta' => $item->creator?->nama_lengkap ?? '-',
        ]);
    }

    private function announcementUrl(User $user, Pengumuman $item): string
    {
        return match ($user->role?->nama_role) {
            'siswa' => route('siswa.pengumuman.show', $item),
            'guru' => route('guru.pengumuman.show', $item),
            'kepala_sekolah' => route('kepsek.pengumuman.show', $item),
            default => route('admin.pengumuman.show', $item),
        };
    }
}
