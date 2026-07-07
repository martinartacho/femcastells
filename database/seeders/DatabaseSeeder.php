<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Colla;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $adminPassword = env('SEEDER_ADMIN_PASSWORD')
            ?? throw new \RuntimeException('SEEDER_ADMIN_PASSWORD no definit al .env');

        // Colla 1 — Demo
        $demo = Colla::create([
            'name'      => 'Demo Colla',
            'shortname' => 'demo',
            'email'     => 'admin@fempinya.test',
            'city'      => 'Barcelona',
            'country'   => 'ES',
        ]);
        User::create([
            'name'     => 'Admin Demo',
            'email'    => 'admin@fempinya.test',
            'password' => Hash::make($adminPassword),
            'colla_id' => $demo->id,
        ]);

        // Colla 2 — Pitxapins
        $pitxapins = Colla::create([
            'name'      => 'Pitxapins',
            'shortname' => 'pitxapins',
            'email'     => 'admin@pitxapins.cat',
            'city'      => 'Terrassa',
            'country'   => 'ES',
        ]);
        User::create([
            'name'     => 'Admin Pitxapins',
            'email'    => 'pitxapins@fempinya.test',
            'password' => Hash::make($adminPassword),
            'colla_id' => $pitxapins->id,
        ]);

        $this->call([
            CastellerSeeder::class,
            EventSeeder::class,
            AttendanceSeeder::class,
            CastleTemplateSeeder::class,
        ]);
    }
}
