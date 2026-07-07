<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Colla;
use Illuminate\Support\Facades\DB;

class CreateDefaultTemplates
{
    private const PINYA_SOURCES = [
        'cinc_de_6' => ['file' => 'cinc_de_6_amb_baix.json', 'name' => 'Cinc de 6'],
        'set_de_x'  => ['file' => 'set_de_x_baixos.json',    'name' => 'Set de X'],
    ];

    public function execute(Colla $colla): void
    {
        $this->upsert3d6($colla);
        $this->upsertFromPinyaData($colla, 'cinc_de_6');
        $this->upsertFromPinyaData($colla, 'set_de_x');
    }

    private function upsert3d6(Colla $colla): void
    {
        $jsonPath = base_path('resources/js/boards/3d6.json');
        if (! file_exists($jsonPath)) {
            return;
        }

        $positions = json_decode(file_get_contents($jsonPath), true);
        $now       = now()->toDateTimeString();

        // INSERT IGNORE INTO — atòmic, no llença excepció si ja existeix
        DB::table('castle_templates')->insertOrIgnore([
            'colla_id'   => $colla->id,
            'key'        => '3d6',
            'name'       => '3 de 6',
            'positions'  => json_encode($positions),
            'ready'      => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function upsertFromPinyaData(Colla $colla, string $key): void
    {
        $source   = self::PINYA_SOURCES[$key];
        $dataPath = base_path('temp/projecte_pinyes_castelleres/projecte_pinyes/data/' . $source['file']);

        if (! file_exists($dataPath)) {
            return;
        }

        $raw       = json_decode(file_get_contents($dataPath), true);
        $positions = $this->transform($raw);
        $now       = now()->toDateTimeString();

        DB::table('castle_templates')->insertOrIgnore([
            'colla_id'   => $colla->id,
            'key'        => $key,
            'name'       => $source['name'],
            'positions'  => json_encode($positions),
            'ready'      => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function transform(array $raw): array
    {
        $positions = [];
        foreach ($raw as $i => $item) {
            $positions[] = [
                'id'    => 'pos_' . $i,
                'cx'    => round((float) $item['fx'], 4),
                'cy'    => round((float) $item['fy'], 4),
                'w'     => round((float) $item['w'], 4),
                'h'     => round((float) $item['h'], 4),
                'angle' => round((float) $item['rot'], 4),
                'grup'  => $item['baix'],
                'nom'   => $item['baix'],
            ];
        }
        return $positions;
    }
}
