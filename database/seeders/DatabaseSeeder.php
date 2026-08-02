<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CollaBaseSeeder::class,
            CastellerSeeder::class,
            NoticiesSeeder::class,
            CastleTemplateSeeder::class,
            ProductSeeder::class,
        ]);
    }
}
