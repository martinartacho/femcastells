<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Colla;
use App\Observers\CollaObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Colla::observe(CollaObserver::class);
    }
}
