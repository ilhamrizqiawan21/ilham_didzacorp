<?php

namespace App\Http\Controllers\Kepsek;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

// Kalender Kepala Sekolah bersifat monitoring-only.
class KalenderController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'year' => 'nullable|integer|min:2000|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
        ]);

        $year = (int) ($validated['year'] ?? date('Y'));
        $month = (int) ($validated['month'] ?? date('m'));

        $firstDay = Carbon::create($year, $month, 1);
        $daysInMonth = $firstDay->daysInMonth;
        $startDayOfWeek = $firstDay->dayOfWeek;

        $monthEvents = CalendarEvent::where('scope', 'school')
            ->with('user')
            ->whereYear('event_date', $year)
            ->whereMonth('event_date', $month)
            ->orderBy('event_date')
            ->get();

        $prevMonth = $firstDay->copy()->subMonth();
        $nextMonth = $firstDay->copy()->addMonth();

        $eventProps = $monthEvents->map(fn (CalendarEvent $event) => $this->eventProps($event))->values();

        return Inertia::render('Kepsek/Kalender/Index', [
            'calendar' => $this->calendarProps($year, $month, $daysInMonth, $startDayOfWeek, $prevMonth, $nextMonth, $eventProps),
            'monthEvents' => $eventProps,
            'pageTitle' => 'Kalender & Monitoring Event Sekolah',
        ]);
    }

    public function store(Request $request)
    {
        abort(403, 'Kepala sekolah hanya dapat memantau kalender.');
    }

    public function update(Request $request, CalendarEvent $calendarEvent)
    {
        abort(403, 'Kepala sekolah hanya dapat memantau kalender.');
    }

    public function destroy(CalendarEvent $calendarEvent)
    {
        abort(403, 'Kepala sekolah hanya dapat memantau kalender.');
    }

    public function toggleDone(CalendarEvent $calendarEvent)
    {
        abort(403, 'Kepala sekolah hanya dapat memantau kalender.');
    }

    private function calendarProps(int $year, int $month, int $daysInMonth, int $startDayOfWeek, Carbon $prevMonth, Carbon $nextMonth, $eventProps): array
    {
        $bulanIndo = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $hariIndo = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        $today = now()->toDateString();
        $eventsByDate = $eventProps->groupBy('event_date')->map->values();
        $weeks = [];
        $day = 1;
        $done = false;

        for ($row = 0; $row < 6; $row++) {
            $week = [];

            for ($col = 0; $col < 7; $col++) {
                $index = $row * 7 + $col;
                $cellDate = null;

                if ($index >= $startDayOfWeek && !$done && $day <= $daysInMonth) {
                    $cellDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
                    $day++;
                } elseif ($day > $daysInMonth) {
                    $done = true;
                }

                $week[] = [
                    'date' => $cellDate,
                    'day' => $cellDate ? (int) substr($cellDate, 8, 2) : null,
                    'is_today' => $cellDate === $today,
                    'events' => $cellDate ? ($eventsByDate[$cellDate] ?? collect())->values() : [],
                ];
            }

            $weeks[] = $week;

            if ($done && $day > $daysInMonth) {
                break;
            }
        }

        return [
            'year' => $year,
            'month' => $month,
            'month_label' => $bulanIndo[(int) $month],
            'title' => $bulanIndo[(int) $month] . ' ' . $year,
            'today' => $today,
            'today_url' => route('kepsek.kalender', ['year' => now()->year, 'month' => now()->month]),
            'prev_url' => route('kepsek.kalender', ['year' => $prevMonth->year, 'month' => $prevMonth->month]),
            'prev_label' => $bulanIndo[$prevMonth->month],
            'next_url' => route('kepsek.kalender', ['year' => $nextMonth->year, 'month' => $nextMonth->month]),
            'next_label' => $bulanIndo[$nextMonth->month],
            'weekdays' => $hariIndo,
            'weeks' => $weeks,
        ];
    }

    private function eventProps(CalendarEvent $event): array
    {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'title_short' => Str::limit($event->title, 18),
            'description' => $event->description,
            'event_date' => $event->event_date?->format('Y-m-d'),
            'event_date_label' => $event->event_date?->format('d M Y') ?? '-',
            'is_holiday' => $event->is_holiday,
            'is_done' => $event->is_done,
            'scope' => $event->scope,
            'created_by' => $event->user?->nama_lengkap ?? '-',
            'can_manage' => false,
        ];
    }
}
