<?php

declare(strict_types=1);

namespace App\Filament\Member\Widgets;

use App\Models\Casteller;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LoginStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        /** @var Casteller $casteller */
        $casteller = auth('member')->user();

        $lastLogin = $casteller->last_login_at
            ? $casteller->last_login_at->diffForHumans()
            : 'Mai';

        $count = $casteller->login_count;

        return [
            Stat::make('Últim accés', $lastLogin)
                ->color('info'),
            Stat::make('Total accessos', $count)
                ->color('gray'),
        ];
    }
}
