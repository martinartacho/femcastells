<?php

declare(strict_types=1);

namespace App\Filament\Member\Widgets;

use App\Models\Casteller;
use Filament\Widgets\Widget;

class LoginStatsWidget extends Widget
{
    protected static ?int $sort = 2;

    protected string $view = 'filament.member.widgets.login-stats-widget';

    protected int|string|array $columnSpan = 'full';

    public function getLastLogin(): string
    {
        /** @var Casteller $casteller */
        $casteller = auth('member')->user();

        return $casteller->last_login_at
            ? $casteller->last_login_at->diffForHumans()
            : 'Mai';
    }

    public function getLoginCount(): int
    {
        /** @var Casteller $casteller */
        $casteller = auth('member')->user();

        return (int) $casteller->login_count;
    }
}
