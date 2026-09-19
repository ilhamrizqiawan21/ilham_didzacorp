<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class KalenderController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'year' => 'nullable|integer|between:2000,2100',
            'month' => 'nullable|integer|between:1,12',
        ]);

        $year = $validated['year'] ?? (int) date('Y');
        $month = $validated['month'] ?? (int) date('m');

        $firstDay = Carbon::create($year, $month, 1);
        $daysInMonth = $firstDay->daysInMonth;
        $startDayOfWeek = $firstDay->dayOfWeek;

        $monthEvents = CalendarEvent::whereYear('event_date', $year)
            ->whereMonth('event_date', $month)
            ->where(fn($q) => $q->where('scope', 'school')->orWhere('user_id', auth()->id()))
            ->orderBy('event_date')->get();
        $prevMonth = $firstDay->copy()->subMonth();
        $nextMonth = $firstDay->copy()->addMonth();

        $bulanIndo = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $hariIndo = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        $today = now()->toDateString();

        $eventProps = $monthEvents->map(fn (CalendarEvent $event) => [
            'id' => $event->id,
            'title' => $event->title,
            'title_short' => Str::limit($event->title, 14),
            'description' => $event->description ?: 'Tidak ada deskripsi',
            'event_date' => $event->event_date?->format('Y-m-d'),
            'event_date_label' => $event->event_date?->format('d M Y') ?? '-',
            'is_holiday' => $event->is_holiday,
            'is_done' => $event->is_done,
        ])->values();
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

        return Inertia::render('Siswa/Kalender/Index', [
            'calendar' => [
                'year' => $year,
                'month' => $month,
                'month_label' => $bulanIndo[(int) $month],
                'title' => $bulanIndo[(int) $month] . ' ' . $year,
                'today' => $today,
                'today_url' => route('siswa.kalender', ['year' => now()->year, 'month' => now()->month]),
                'prev_url' => route('siswa.kalender', ['year' => $prevMonth->year, 'month' => $prevMonth->month]),
                'prev_label' => $bulanIndo[$prevMonth->month],
                'next_url' => route('siswa.kalender', ['year' => $nextMonth->year, 'month' => $nextMonth->month]),
                'next_label' => $bulanIndo[$nextMonth->month],
                'weekdays' => $hariIndo,
                'weeks' => $weeks,
            ],
            'monthEvents' => $eventProps,
            'pageTitle' => 'Kalender & Reminder',
        ]);
    }
}
