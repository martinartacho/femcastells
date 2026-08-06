<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Casteller;
use App\Models\Colla;
use App\Models\Event;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoRefreshCommand extends Command
{
    protected $signature   = 'demo:refresh';
    protected $description = 'Afegeix events nous a la colla demo i neteja els antics';

    public function handle(): int
    {
        $colla = Colla::where('shortname', 'demo')->first();

        if (! $colla) {
            $this->error('Colla demo no trobada.');
            return self::FAILURE;
        }

        $this->resetDemoCredentials($colla);
        $this->addWeeklyAssaig($colla);
        $this->cleanOldEvents($colla);

        $this->info('Demo refreshed.');
        return self::SUCCESS;
    }

    private function resetDemoCredentials(Colla $colla): void
    {
        // Revoca tots els tokens demo i restaura email i password originals
        $castellers = Casteller::where('colla_id', $colla->id)
            ->where('email', 'not like', '%@demo.test')
            ->get();

        foreach ($castellers as $c) {
            $c->tokens()->where('name', 'mobile-demo')->delete();
            $c->update([
                'email'    => mb_strtolower($c->alias) . '@demo.test',
                'password' => Hash::make(Str::random(32)),
            ]);
        }

        if ($castellers->count() > 0) {
            $this->info("Credencials de {$castellers->count()} comptes demo restaurades.");
        }
    }

    private function addWeeklyAssaig(Colla $colla): void
    {
        $now       = now();
        $nextWeek  = $now->copy()->addWeek()->setTime(20, 0);
        $eventName = 'Assaig ' . $nextWeek->format('d/m/Y');

        // Evitar duplicats
        if (Event::where('colla_id', $colla->id)->where('name', $eventName)->exists()) {
            $this->line("Event '{$eventName}' ja existeix — omès.");
            return;
        }

        $event = Event::create([
            'colla_id'   => $colla->id,
            'name'       => $eventName,
            'type'       => 1,
            'start_date' => $nextWeek,
            'open_date'  => $now->copy()->subDay(),
            'close_date' => $nextWeek,
            'visibility' => true,
            'companions' => false,
        ]);

        $this->seedAttendances($colla, $event, future: true);
        $this->info("Event creat: {$eventName}");
    }

    private function seedAttendances(Colla $colla, Event $event, bool $future): void
    {
        $castellerIds = Casteller::where('colla_id', $colla->id)
            ->where('email', 'like', '%@demo.test')
            ->pluck('id')
            ->toArray();

        $rows = [];
        $now  = now()->toDateTimeString();

        foreach ($castellerIds as $id) {
            $responseRate = $future ? 55 : 85;
            if (rand(1, 100) > $responseRate) {
                continue;
            }

            if ($future) {
                $roll   = rand(1, 100);
                $status = $roll <= 70 ? 'ok' : ($roll <= 90 ? 'nok' : 'unknown');
            } else {
                $status = rand(1, 100) <= 75 ? 'ok' : 'nok';
            }

            $rows[] = [
                'event_id'     => $event->id,
                'casteller_id' => $id,
                'status'       => $status,
                'companions'   => 0,
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
        }

        if ($rows) {
            DB::table('attendance')->insert($rows);
        }
    }

    private function cleanOldEvents(Colla $colla): void
    {
        $cutoff = now()->subWeeks(8);

        $old = Event::where('colla_id', $colla->id)
            ->where('start_date', '<', $cutoff)
            ->get();

        foreach ($old as $event) {
            DB::table('attendance')->where('event_id', $event->id)->delete();
            $event->delete();
            $this->line("Event eliminat: {$event->name}");
        }
    }
}
