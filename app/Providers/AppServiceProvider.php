<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\RecordLastLogin;
use App\Models\Casteller;
use App\Models\Colla;
use App\Models\Noticia;
use App\Observers\CollaObserver;
use App\Observers\NoticiaObserver;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Colla::observe(CollaObserver::class);
        Noticia::observe(NoticiaObserver::class);
        Event::listen(Login::class, RecordLastLogin::class);
    }
}
