<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Colla;
use App\Models\Event;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $now = now()->toDateTimeString();

        foreach (Colla::all() as $colla) {
            $castellers = DB::table('castellers')
                ->where('colla_id', $colla->id)
                ->where('status', 1) // actius
                ->pluck('id')
                ->toArray();

            $events = Event::where('colla_id', $colla->id)->get();

            foreach ($events as $event) {
                $isPast   = $event->start_date->isPast();
                $rows     = [];

                foreach ($castellers as $castellerId) {
                    // Past events: 85% respond (ok or nok), 15% unknown
                    // Future events: 55% respond, 45% unknown
                    $responseRate = $isPast ? 85 : 55;
                    if (rand(1, 100) > $responseRate) {
                        continue; // skip → no attendance record (implicit unknown)
                    }

                    if ($isPast) {
                        $status = rand(1, 100) <= 75 ? 'ok' : 'nok';
                    } else {
                        $roll   = rand(1, 100);
                        $status = $roll <= 70 ? 'ok' : ($roll <= 90 ? 'nok' : 'unknown');
                    }

                    $companions  = ($event->companions && $status === 'ok') ? rand(0, 2) : 0;
                    $checkedIn   = null;
                    $checkedInAt = null;
                    if ($isPast && $status === 'ok' && rand(1, 10) <= 8) {
                        $checkedIn   = true;
                        $checkedInAt = $event->start_date->copy()
                            ->subMinutes(30)
                            ->addMinutes(rand(0, 44))
                            ->toDateTimeString();
                    }

                    $rows[] = [
                        'event_id'      => $event->id,
                        'casteller_id'  => $castellerId,
                        'status'        => $status,
                        'companions'    => $companions,
                        'checked_in'    => $checkedIn,
                        'checked_in_at' => $checkedInAt,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ];
                }

                if ($rows) {
                    DB::table('attendance')->insert($rows);
                }
            }
        }
    }
}
