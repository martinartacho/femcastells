<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CastleTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Les plantilles per defecte es creen automàticament via CollaObserver::created().
        // Aquest seeder no cal que faci res addicional.
    }
}
