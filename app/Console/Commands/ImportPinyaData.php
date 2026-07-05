<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CastleTemplate;
use App\Models\Colla;
use Illuminate\Console\Command;

class ImportPinyaData extends Command
{
    protected $signature = 'pinya:import {castle : cinc_de_6 o set_de_x}';
    protected $description = 'Importa posicions reals de pinyes des dels fitxers de recerca';

    private const SOURCES = [
        'cinc_de_6' => ['file' => 'cinc_de_6_amb_baix.json', 'name' => 'Cinc de 6'],
        'set_de_x'  => ['file' => 'set_de_x_baixos.json',    'name' => 'Set de X'],
    ];

    public function handle(): int
    {
        $castle = $this->argument('castle');

        if (!isset(self::SOURCES[$castle])) {
            $this->error('Castell no reconegut. Usa: cinc_de_6 | set_de_x');
            return self::FAILURE;
        }

        $config = self::SOURCES[$castle];
        $dataPath = base_path(
            'temp/projecte_pinyes_castelleres/projecte_pinyes/data/' . $config['file']
        );

        if (!file_exists($dataPath)) {
            $this->error("Fitxer no trobat: {$dataPath}");
            return self::FAILURE;
        }

        $raw = json_decode(file_get_contents($dataPath), true);
        $positions = $this->transform($raw);
        $numPositions = count($positions);

        $colles = Colla::all();
        foreach ($colles as $colla) {
            $template = CastleTemplate::firstOrCreate(
                ['colla_id' => $colla->id, 'key' => $castle],
                ['name' => $config['name'], 'positions' => $positions, 'ready' => true]
            );

            if (!$template->wasRecentlyCreated) {
                $template->update(['positions' => $positions, 'ready' => true]);
                $this->line("  Actualitzat: colla {$colla->name}");
            } else {
                $this->line("  Creat: colla {$colla->name}");
            }
        }

        $this->info("'{$config['name']}' importat per a {$colles->count()} colles ({$numPositions} posicions).");
        return self::SUCCESS;
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
