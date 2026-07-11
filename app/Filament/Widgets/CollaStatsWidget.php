<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Casteller;
use App\Models\Event;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CollaStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $collaId = filament()->getTenant()?->id;

        if (! $collaId) {
            return [];
        }

        $total   = Casteller::where('colla_id', $collaId)->count();
        $actius  = Casteller::where('colla_id', $collaId)->where('status', 1)->count();
        $inactius = Casteller::where('colla_id', $collaId)->where('status', 2)->count();
        $baixa   = Casteller::where('colla_id', $collaId)->where('status', 3)->count();

        $propersEvents = Event::where('colla_id', $collaId)
            ->where('start_date', '>=', now())
            ->where('start_date', '<=', now()->addDays(30))
            ->count();

        $senseLogin = Casteller::where('colla_id', $collaId)
            ->where('status', 1)
            ->whereNull('last_login_at')
            ->count();

        $inactius30 = Casteller::where('colla_id', $collaId)
            ->where('status', 1)
            ->whereNotNull('last_login_at')
            ->where('last_login_at', '<', now()->subDays(30))
            ->count();

        return [
            Stat::make('Castellers actius', $actius)
                ->description("{$inactius} inactius · {$baixa} baixa · {$total} total")
                ->color('success'),

            Stat::make('Events propers 30 dies', $propersEvents)
                ->color('info'),

            Stat::make('Mai han accedit al portal', $senseLogin)
                ->description("{$inactius30} sense accés fa >30 dies")
                ->color($senseLogin > 0 ? 'warning' : 'success'),
        ];
    }
}
