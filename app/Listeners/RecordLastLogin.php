<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\Casteller;
use App\Models\User;
use Illuminate\Auth\Events\Login;

class RecordLastLogin
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        if ($user instanceof User) {
            $user->updateQuietly(['last_login_at' => now()]);
        } elseif ($user instanceof Casteller) {
            $user->updateQuietly([
                'last_login_at' => now(),
                'login_count'   => $user->login_count + 1,
            ]);
        }
    }
}
