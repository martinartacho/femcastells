<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Attendance;
use App\Models\Casteller;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class AttendancePeriodExport implements FromArray, WithHeadings, WithTitle
{
    private Collection $events;

    public function __construct(
        private int $collaId,
        private string $fromDate,
        private string $toDate,
    ) {
        $this->events = Event::where('colla_id', $this->collaId)
            ->whereBetween('start_date', [$this->fromDate . ' 00:00:00', $this->toDate . ' 23:59:59'])
            ->orderBy('start_date')
            ->get();
    }

    public function title(): string
    {
        return 'Assistència ' . $this->fromDate . ' - ' . $this->toDate;
    }

    public function headings(): array
    {
        $headers = ['Sobrenom', 'Nom'];
        foreach ($this->events as $event) {
            $headers[] = Carbon::parse($event->start_date)->format('d/m') . ' ' . $event->name;
        }
        $headers[] = 'Total ok';
        return $headers;
    }

    public function array(): array
    {
        $castellers = Casteller::where('colla_id', $this->collaId)
            ->orderBy('alias')
            ->get();

        $attendances = Attendance::whereIn('event_id', $this->events->pluck('id'))
            ->whereIn('casteller_id', $castellers->pluck('id'))
            ->get()
            ->groupBy(fn ($a) => $a->casteller_id . '_' . $a->event_id);

        $rows = [];
        foreach ($castellers as $casteller) {
            $row = [$casteller->alias, $casteller->full_name];
            $okCount = 0;
            foreach ($this->events as $event) {
                $key = $casteller->id . '_' . $event->id;
                $att = $attendances->get($key)?->first();
                $status = $att?->status ?? '-';
                $row[] = $status;
                if ($status === 'ok') {
                    $okCount++;
                }
            }
            $row[] = $okCount;
            $rows[] = $row;
        }

        return $rows;
    }
}
