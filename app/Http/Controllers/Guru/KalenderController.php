<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
//Sama dengan Admin\KalenderController, namun ini untuk guru
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
            ->orderBy('event_date')
            ->get();
        $events = $monthEvents->groupBy(fn($event) => $event->event_date->format('Y-m-d'));

        $prevMonth = $firstDay->copy()->subMonth();
        $nextMonth = $firstDay->copy()->addMonth();

        $bulanIndo = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $hariIndo = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        $today = now()->toDateString();

        $eventProps = $monthEvents->map(fn (CalendarEvent $event) => $this->eventProps($event))->values();
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

        return Inertia::render('Guru/Kalender/Index', [
            'calendar' => [
                'year' => $year,
                'month' => $month,
                'month_label' => $bulanIndo[(int) $month],
                'title' => $bulanIndo[(int) $month] . ' ' . $year,
                'today' => $today,
                'today_url' => route('guru.kalender', ['year' => now()->year, 'month' => now()->month]),
                'prev_url' => route('guru.kalender', ['year' => $prevMonth->year, 'month' => $prevMonth->month]),
                'prev_label' => $bulanIndo[$prevMonth->month],
                'next_url' => route('guru.kalender', ['year' => $nextMonth->year, 'month' => $nextMonth->month]),
                'next_label' => $bulanIndo[$nextMonth->month],
                'weekdays' => $hariIndo,
                'weeks' => $weeks,
            ],
            'monthEvents' => $eventProps,
            'storeUrl' => route('guru.kalender.store'),
            'createTitle' => 'Tambah Event Pribadi',
            'fixedScope' => 'user',
            'pageTitle' => 'Kalender & Reminder',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'event_date' => 'required|date',
            'description' => 'nullable|string',
            'is_holiday' => 'boolean',
            'is_done' => 'boolean',
        ]);

        CalendarEvent::create($validated + [
            'user_id' => auth()->id(),
            'scope' => 'user',
            'is_holiday' => $request->boolean('is_holiday'),
            'is_done' => $request->boolean('is_done'),
        ]);

        return back()->with('success', 'Event berhasil ditambahkan.');
    }

    public function update(Request $request, CalendarEvent $calendarEvent)
    {
        if ((int) $calendarEvent->user_id !== (int) auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'event_date' => 'required|date',
            'description' => 'nullable|string',
            'is_holiday' => 'boolean',
            'is_done' => 'boolean',
        ]);

        $calendarEvent->update($validated + [
            'is_holiday' => $request->boolean('is_holiday'),
            'is_done' => $request->boolean('is_done'),
        ]);

        return back()->with('success', 'Event berhasil diperbarui.');
    }

    public function destroy(CalendarEvent $calendarEvent)
    {
        if ($calendarEvent->user_id !== auth()->id()) {
            abort(403);
        }
        $calendarEvent->delete();
        return back()->with('success', 'Event berhasil dihapus.');
    }

    private function eventProps(CalendarEvent $event): array
    {
        $canManage = (int) $event->user_id === (int) auth()->id();

        return [
            'id' => $event->id,
            'title' => $event->title,
            'title_short' => Str::limit($event->title, 16),
            'description' => $event->description,
            'event_date' => $event->event_date?->format('Y-m-d'),
            'event_date_label' => $event->event_date?->format('d M Y') ?? '-',
            'is_holiday' => $event->is_holiday,
            'is_done' => $event->is_done,
            'scope' => $event->scope,
            'can_manage' => $canManage,
            'update_url' => $canManage ? route('guru.kalender.update', $event) : null,
            'delete_url' => $canManage ? route('guru.kalender.destroy', $event) : null,
            'toggle_done_url' => null,
        ];
    }
}
