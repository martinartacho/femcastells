<?php

use App\Console\Commands\DemoRefreshCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cada dilluns a les 02:00 afegeix l'assaig de la setmana i neteja antics
Schedule::command(DemoRefreshCommand::class)->weeklyOn(1, '02:00');
