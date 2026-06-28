<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CastleTemplate;
use App\Models\Colla;
use Illuminate\Database\Seeder;

class CastleTemplateSeeder extends Seeder
{
    public function run(): void
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
}
