<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Colla;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $adminPassword = env('SEEDER_ADMIN_PASSWORD')
            ?? throw new \RuntimeException('SEEDER_ADMIN_PASSWORD no definit al .env');

        $rolSuperAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $rolAdmin      = Role::firstOrCreate(['name' => 'admin',       'guard_name' => 'web']);

        // Colla 1 — Demo (super-admin)
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
        ])->assignRole($rolSuperAdmin);

        // Colla 2 — Pitxapins (admin)
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
        ])->assignRole($rolAdmin);

        $this->call([
            CastellerSeeder::class,
            EventSeeder::class,
            AttendanceSeeder::class,
            CastleTemplateSeeder::class,
        ]);
    }
}
