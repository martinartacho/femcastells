<?php

declare(strict_types=1);

namespace App\Filament\Member\Pages;

use App\Models\Attendance;
use App\Models\Event;
use BackedEnum;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class MonthlyCalendar extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?string $navigationLabel = 'Calendari mensual';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.member.pages.monthly-calendar';

    protected static ?string $title = 'Calendari';

    public int $year;
    public int $month;

    public function mount(): void
    {
        $this->year  = (int) now()->format('Y');
        $this->month = (int) now()->format('n');
    }

    public function previousMonth(): void
    {
        $date = Carbon::create($this->year, $this->month)->subMonth();
        $this->year  = $date->year;
        $this->month = $date->month;
    }

    public function nextMonth(): void
    {
        $date = Carbon::create($this->year, $this->month)->addMonth();
        $this->year  = $date->year;
        $this->month = $date->month;
    }

    public function getMonthLabel(): string
    {
        return ucfirst(Carbon::create($this->year, $this->month)->locale('ca')->isoFormat('MMMM YYYY'));
    }

    public function getCalendarData(): array
    {
        $colla      = filament()->getTenant();
        $casteller  = auth('member')->user();
        $startOfMonth = Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $endOfMonth   = $startOfMonth->copy()->endOfMonth();

        $events = Event::where('colla_id', $colla->id)
            ->where('visibility', true)
            ->whereBetween('start_date', [$startOfMonth, $endOfMonth])
            ->orderBy('start_date')
            ->get();

        $attendances = Attendance::where('casteller_id', $casteller->id)
            ->whereIn('event_id', $events->pluck('id'))
            ->pluck('status', 'event_id');

        $eventsByDay = $events->groupBy(fn (Event $e) => Carbon::parse($e->start_date)->format('j'));

        $firstDayOfWeek = ($startOfMonth->dayOfWeek + 6) % 7; // 0=Dll, 6=Dg
        $daysInMonth    = $startOfMonth->daysInMonth;

        $weeks   = [];
        $day     = 1;
        $weekDay = $firstDayOfWeek;
        $week    = array_fill(0, 7, null);

        while ($day <= $daysInMonth) {
            $week[$weekDay] = [
                'day'    => $day,
                'today'  => $day === (int) now()->format('j') && $this->month === (int) now()->format('n') && $this->year === (int) now()->format('Y'),
                'events' => ($eventsByDay->get((string) $day) ?? collect())->map(fn (Event $e) => [
                    'id'         => $e->id,
                    'name'       => $e->name,
                    'time'       => Carbon::parse($e->start_date)->format('H:i'),
                    'type'       => $e->type,
                    'attendance' => $attendances->get($e->id),
                ])->values()->toArray(),
            ];

            $weekDay++;
            if ($weekDay === 7) {
                $weeks[]  = $week;
                $week     = array_fill(0, 7, null);
                $weekDay  = 0;
            }
            $day++;
        }

        if ($weekDay > 0) {
            $weeks[] = $week;
        }

        return $weeks;
    }
}
