<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

class CollaBaseSeeder extends Seeder
{
    public function run(): void
    {
        // Valida contrasenyes al .env abans de fer res
        env('SEEDER_ADMIN_PASSWORD')
            ?? throw new \RuntimeException('SEEDER_ADMIN_PASSWORD no definit al .env');
        env('SEEDER_USER_PASSWORD')
            ?? throw new \RuntimeException('SEEDER_USER_PASSWORD no definit al .env');

        // Crea la colla demo amb tots els registres associats
        Artisan::call('colla:create', [
            'nom'          => 'Demo Colla',
            'num_castellers' => 125,
            '--shortname'  => 'demo',
            '--city'       => 'Barcelona',
            '--email'      => 'admin@demo.test',
        ]);

        $this->command->line(Artisan::output());

        // L'admin de demo ha de ser super-admin (el command crea 'admin' per defecte)
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $admin      = Role::firstOrCreate(['name' => 'admin',       'guard_name' => 'web']);

        $user = User::where('email', 'admin@demo.test')->first();
        $user->removeRole($admin);
        $user->assignRole($superAdmin);
    }
}
