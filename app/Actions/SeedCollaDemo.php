<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Casteller;
use App\Models\Colla;
use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SeedCollaDemo
{
    private const CASTELLERS = [
        ['alias' => 'CARRU',   'name' => 'Carles',  'last_name' => 'Rull'],
        ['alias' => 'FEDE',    'name' => 'Frederic', 'last_name' => 'Domènech'],
        ['alias' => 'MONYOS',  'name' => 'Montserrat', 'last_name' => 'Puig'],
        ['alias' => 'RENE',    'name' => 'René',    'last_name' => 'Soler'],
        ['alias' => 'EMMA',    'name' => 'Emma',    'last_name' => 'Ferrer'],
        ['alias' => 'MANSO',   'name' => 'Manel',   'last_name' => 'Manso'],
        ['alias' => 'CIRAC',   'name' => 'Jordi',   'last_name' => 'Cirac'],
        ['alias' => 'FAUST',   'name' => 'Faust',   'last_name' => 'Palau'],
        ['alias' => 'RIELLS',  'name' => 'Anna',    'last_name' => 'Riells'],
        ['alias' => 'CESC',    'name' => 'Francesc','last_name' => 'Vidal'],
        ['alias' => 'BEDIA',   'name' => 'Beatriz', 'last_name' => 'Bedia'],
        ['alias' => 'CANDELA', 'name' => 'Candela', 'last_name' => 'Martín'],
        ['alias' => 'JUVANY',  'name' => 'Pau',     'last_name' => 'Juvany'],
        ['alias' => 'BALAGUER','name' => 'Sílvia',  'last_name' => 'Balaguer'],
        ['alias' => 'XAVI',    'name' => 'Xavier',  'last_name' => 'Bosch'],
    ];

    public function execute(Colla $colla): void
    {
        $password = env('SEEDER_USER_PASSWORD')
            ?? throw new \RuntimeException('SEEDER_USER_PASSWORD no definit al .env');

        $this->createCastellers($colla, $password);
        $this->createEventsAndAttendance($colla);
    }

    private function createCastellers(Colla $colla, string $password): void
    {
        $now    = now()->toDateTimeString();
        $hashed = Hash::make($password);
        $rows   = [];

        foreach (self::CASTELLERS as $i => $c) {
            $rows[] = [
                'colla_id'        => $colla->id,
                'alias'           => $c['alias'],
                'name'            => $c['name'],
                'last_name'       => $c['last_name'],
                'email'           => strtolower($c['alias']) . '@' . $colla->shortname . '.test',
                'password'        => $hashed,
                'status'          => 1,
                'height'          => 160 + ($i * 3 % 30),
                'weight'          => 55 + ($i * 5 % 50),
                'shoulder_height' => 138 + ($i * 3 % 25),
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }

        DB::table('castellers')->insert($rows);
    }

    private function createEventsAndAttendance(Colla $colla): void
    {
        $now = now();

        $events = [
            [
                'colla_id'   => $colla->id,
                'name'       => 'Assaig ' . $now->copy()->subWeeks(2)->format('d/m/Y'),
                'type'       => 1,
                'start_date' => $now->copy()->subWeeks(2)->setTime(20, 0),
                'open_date'  => $now->copy()->subWeeks(3),
                'close_date' => $now->copy()->subWeeks(2)->setTime(20, 0),
                'visibility' => true,
                'companions' => false,
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ],
            [
                'colla_id'   => $colla->id,
                'name'       => 'Actuació Festa Major ' . $colla->city,
                'type'       => 2,
                'start_date' => $now->copy()->subWeeks(4)->setTime(18, 0),
                'open_date'  => $now->copy()->subWeeks(6),
                'close_date' => $now->copy()->subWeeks(4)->setTime(18, 0),
                'visibility' => true,
                'companions' => true,
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ],
            [
                'colla_id'   => $colla->id,
                'name'       => 'Assaig ' . $now->copy()->addWeek()->format('d/m/Y'),
                'type'       => 1,
                'start_date' => $now->copy()->addWeek()->setTime(20, 0),
                'open_date'  => $now->copy()->subDays(2),
                'close_date' => $now->copy()->addDays(6),
                'visibility' => true,
                'companions' => false,
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ],
        ];

        DB::table('events')->insert($events);

        $castellerIds = DB::table('castellers')
            ->where('colla_id', $colla->id)
            ->pluck('id')
            ->toArray();

        $eventRecords = Event::where('colla_id', $colla->id)
            ->where('start_date', '<', $now)
            ->get();

        $attendanceRows = [];
        $ts = $now->toDateTimeString();

        foreach ($eventRecords as $event) {
            foreach ($castellerIds as $castellerId) {
                $status = match (true) {
                    rand(1, 10) <= 7 => 'ok',
                    rand(1, 10) <= 8 => 'nok',
                    default          => 'unknown',
                };
                $attendanceRows[] = [
                    'event_id'     => $event->id,
                    'casteller_id' => $castellerId,
                    'status'       => $status,
                    'companions'   => ($event->companions && $status === 'ok') ? rand(0, 1) : 0,
                    'created_at'   => $ts,
                    'updated_at'   => $ts,
                ];
            }
        }

        if ($attendanceRows) {
            DB::table('attendance')->insert($attendanceRows);
        }
    }
}
