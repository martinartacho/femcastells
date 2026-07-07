<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Colla;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        foreach (Colla::all() as $colla) {
            $events = [
                // Assajos passats
                [
                    'colla_id'   => $colla->id,
                    'name'       => 'Assaig ' . $now->copy()->subWeeks(4)->format('d/m/Y'),
                    'type'       => 1,
                    'start_date' => $now->copy()->subWeeks(4)->setTime(20, 0),
                    'open_date'  => $now->copy()->subWeeks(5),
                    'close_date' => $now->copy()->subWeeks(4)->setTime(20, 0),
                    'visibility' => true,
                    'companions' => false,
                ],
                [
                    'colla_id'   => $colla->id,
                    'name'       => 'Assaig ' . $now->copy()->subWeeks(3)->format('d/m/Y'),
                    'type'       => 1,
                    'start_date' => $now->copy()->subWeeks(3)->setTime(20, 0),
                    'open_date'  => $now->copy()->subWeeks(4),
                    'close_date' => $now->copy()->subWeeks(3)->setTime(20, 0),
                    'visibility' => true,
                    'companions' => false,
                ],
                [
                    'colla_id'   => $colla->id,
                    'name'       => 'Assaig ' . $now->copy()->subWeeks(2)->format('d/m/Y'),
                    'type'       => 1,
                    'start_date' => $now->copy()->subWeeks(2)->setTime(20, 0),
                    'open_date'  => $now->copy()->subWeeks(3),
                    'close_date' => $now->copy()->subWeeks(2)->setTime(20, 0),
                    'visibility' => true,
                    'companions' => false,
                ],
                // Actuació passada
                [
                    'colla_id'   => $colla->id,
                    'name'       => 'Actuació Festa Major ' . $colla->city,
                    'type'       => 2,
                    'start_date' => $now->copy()->subWeeks(5)->setTime(18, 0),
                    'open_date'  => $now->copy()->subWeeks(7),
                    'close_date' => $now->copy()->subWeeks(5)->setTime(18, 0),
                    'visibility' => true,
                    'companions' => true,
                ],
                // Assaig futur (pròxima setmana)
                [
                    'colla_id'   => $colla->id,
                    'name'       => 'Assaig ' . $now->copy()->addWeek()->format('d/m/Y'),
                    'type'       => 1,
                    'start_date' => $now->copy()->addWeek()->setTime(20, 0),
                    'open_date'  => $now->copy()->subDays(3),
                    'close_date' => $now->copy()->addDays(5),
                    'visibility' => true,
                    'companions' => false,
                ],
                // Actuació futura (proper mes)
                [
                    'colla_id'   => $colla->id,
                    'name'       => 'Actuació Diada Castellera',
                    'type'       => 2,
                    'start_date' => $now->copy()->addWeeks(3)->setTime(11, 0),
                    'open_date'  => $now->copy()->addDays(2),
                    'close_date' => $now->copy()->addWeeks(2),
                    'visibility' => true,
                    'companions' => true,
                ],
            ];

            $now2 = now()->toDateTimeString();
            foreach ($events as &$e) {
                $e['created_at'] = $now2;
                $e['updated_at'] = $now2;
            }

            DB::table('events')->insert($events);
        }
    }
}
