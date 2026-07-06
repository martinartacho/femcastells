<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CastleTemplate;
use App\Models\Colla;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class CastleTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedFromJsonFiles();
        $this->seedFromPinyaData();
    }

    private function seedFromJsonFiles(): void
    {
        $templates = [
            ['key' => '3d6',      'name' => '3 de 6'],
            ['key' => '4a_Santi', 'name' => '4 de 7 (Santi)'],
        ];

        foreach (Colla::all() as $colla) {
            foreach ($templates as $t) {
                $jsonPath = base_path('resources/js/boards/' . $t['key'] . '.json');
                if (!file_exists($jsonPath)) {
                    continue;
                }

                $positions = json_decode(file_get_contents($jsonPath), true);

                CastleTemplate::firstOrCreate(
                    ['colla_id' => $colla->id, 'key' => $t['key']],
                    ['name' => $t['name'], 'positions' => $positions, 'ready' => true]
                );
            }
        }
    }

    private function seedFromPinyaData(): void
    {
        foreach (['cinc_de_6', 'set_de_x'] as $castle) {
            $dataPath = base_path(
                'temp/projecte_pinyes_castelleres/projecte_pinyes/data/' .
                ($castle === 'cinc_de_6' ? 'cinc_de_6_amb_baix.json' : 'set_de_x_baixos.json')
            );

            if (!file_exists($dataPath)) {
                $this->command->warn("Fitxer no trobat per {$castle}, s'omet.");
                continue;
            }

            Artisan::call('pinya:import', ['castle' => $castle]);
            $this->command->info(Artisan::output());
        }
    }
}
