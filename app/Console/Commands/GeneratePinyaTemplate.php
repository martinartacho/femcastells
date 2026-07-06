<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CastleTemplate;
use App\Models\Colla;
use App\Services\PinyaMotor;
use Illuminate\Console\Command;

class GeneratePinyaTemplate extends Command
{
    protected $signature = 'pinya:generate {num_baixos : 1=Pilar 2=Torre 3=Tres 4=Quatre} {pisos}';
    protected $description = 'Genera una plantilla de pinya usant el motor paramètric';

    public function handle(PinyaMotor $motor): int
    {
        $numBaixos = (int) $this->argument('num_baixos');
        $pisos     = (int) $this->argument('pisos');

        try {
            $positions = $motor->generate($numBaixos, $pisos);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $nom = $motor->nomCastell($numBaixos, $pisos);
        $key = strtolower(str_replace(' ', '_', $nom));

        $colles = Colla::all();
        foreach ($colles as $colla) {
            $template = CastleTemplate::firstOrCreate(
                ['colla_id' => $colla->id, 'key' => $key],
                ['name' => $nom, 'positions' => $positions, 'ready' => true]
            );

            if (!$template->wasRecentlyCreated) {
                $template->update(['positions' => $positions, 'ready' => true]);
                $this->line("  Actualitzat: colla {$colla->name}");
            } else {
                $this->line("  Creat: colla {$colla->name}");
            }
        }

        $this->info("'{$nom}' generat per a {$colles->count()} colles (" . count($positions) . " posicions).");
        return self::SUCCESS;
    }
}
