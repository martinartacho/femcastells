<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\BoardSnapshot;
use App\Models\Colla;
use App\Models\Event;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Crea un BoardSnapshot a FP4 a partir d'un JSON d'assignacions FP3.
 *
 * El JSON és el resultat d'executar al F12 de la pàgina FP3 del board:
 *   const id = window.location.pathname.split('/').pop();
 *   fetch(`/event/board/load-map/${id}/PINYA`)
 *     .then(r => r.json())
 *     .then(data => {
 *       const a = {};
 *       data.forEach(e => { if (e.casteller?.alias) a[e.row.div_id] = e.casteller.alias; });
 *       console.log(JSON.stringify(a));
 *     });
 *
 * Ús:
 *   php artisan snapshot:import-fp3
 *     --fp4-shortname=xics
 *     --event-id=45
 *     --castle-type=fp3_584
 *     --name="B vano - Pilars inici"
 *     --ronda=1
 *     --json='{"5":"QUIM","12":"EMMA",...}'
 */
class ImportSnapshotFromFp3 extends Command
{
    protected $signature = 'snapshot:import-fp3
        {--fp4-shortname=  : Shortname de la colla a FP4}
        {--event-id=       : ID de l\'event a FP4}
        {--castle-type=    : Clau de la plantilla (ex: fp3_584)}
        {--name=           : Nom del snapshot}
        {--ronda=          : Número de ronda (opcional)}
        {--json=           : JSON de div_id → alias extret del F12 de FP3}
        {--json-file=      : Alternativa: fitxer .json amb el mateix contingut}
        {--primary         : Marcar com a snapshot primari}
        {--public          : Generar public_token (URL pública accessible sense login)}
        {--dry-run         : Simula sense escriure}';

    protected $description = 'Crea un BoardSnapshot FP4 a partir del JSON d\'assignacions extret de FP3';

    public function handle(): int
    {
        $fp4Shortname = $this->option('fp4-shortname');
        $eventId      = (int) $this->option('event-id');
        $castleType   = $this->option('castle-type');
        $name         = $this->option('name');
        $ronda        = $this->option('ronda') ? (int) $this->option('ronda') : null;
        $jsonStr      = $this->option('json');
        $jsonFile     = $this->option('json-file');
        $makePublic   = (bool) $this->option('public');
        $dryRun       = (bool) $this->option('dry-run');

        if (! $fp4Shortname || ! $eventId || ! $castleType || ! $name) {
            $this->error('Cal indicar --fp4-shortname, --event-id, --castle-type i --name.');
            return self::FAILURE;
        }

        if (! $jsonStr && ! $jsonFile) {
            $this->error('Cal indicar --json o --json-file.');
            return self::FAILURE;
        }

        if ($jsonFile) {
            if (! file_exists($jsonFile)) {
                $this->error("Fitxer no trobat: {$jsonFile}");
                return self::FAILURE;
            }
            $jsonStr = file_get_contents($jsonFile);
        }

        $fp3Data = json_decode($jsonStr, true);
        if (! is_array($fp3Data)) {
            $this->error('JSON invàlid.');
            return self::FAILURE;
        }

        $fp4Colla = Colla::where('shortname', $fp4Shortname)->first();
        if (! $fp4Colla) {
            $this->error("Colla '{$fp4Shortname}' no trobada a FP4.");
            return self::FAILURE;
        }

        $event = Event::where('id', $eventId)->where('colla_id', $fp4Colla->id)->first();
        if (! $event) {
            $this->error("Event {$eventId} no trobat per la colla '{$fp4Shortname}'.");
            return self::FAILURE;
        }

        // Convertir {div_id → alias} → {pos_{div_id} → alias}
        // El prefix depèn de com es van crear les posicions al CastleTemplate:
        //   - boards importats via ImportSvgTemplate → pos_{div_id}
        //   - boards importats via ImportFp3TemplatesSvg (amb rows locals) → fp3_{row_id}
        $assignments = [];
        $prefix = str_starts_with($castleType, 'fp3_2') ? 'fp3_' : 'pos_';

        // Per castle_type=fp3_2 (3d8 local) les posicions s'identifiquen per fp3_{row_id}
        // però no tenim la correlació div_id → row_id sense la BD local.
        // Per als demés (fp3_584, fp3_572, fp3_583, fp3_576) → pos_{div_id}
        if ($prefix === 'fp3_') {
            $this->warn('⚠  castle-type=fp3_2: les posicions usen fp3_{row_id}, no pos_{div_id}.');
            $this->warn('   Caldrà el mapatge div_id→row_id de la BD local FP3.');
            $this->warn('   Usa castle-type dels boards SVG directes (fp3_572, fp3_583, fp3_584, fp3_576).');
        }

        foreach ($fp3Data as $divId => $alias) {
            $posId = 'pos_' . $divId;
            $assignments[$posId] = $alias;
        }

        $count = count($assignments);
        $this->info("Assignacions: {$count}");
        $this->info("Event: {$event->name} (id={$eventId})");
        $this->info("Castle type: {$castleType}");
        $this->info("Snapshot: {$name}" . ($ronda ? " (ronda {$ronda})" : ''));

        if ($dryRun) {
            $this->warn('DRY-RUN — no s\'escriurà res.');
            $this->line('Sample assignacions (3 primers):');
            foreach (array_slice($assignments, 0, 3, true) as $k => $v) {
                $this->line("  {$k} → {$v}");
            }
            return self::SUCCESS;
        }

        BoardSnapshot::create([
            'colla_id'     => $fp4Colla->id,
            'event_id'     => $eventId,
            'castle_type'  => $castleType,
            'name'         => $name,
            'assignments'  => $assignments,
            'ronda_number' => $ronda,
            'is_primary'   => $this->option('primary') ? true : false,
            'public_token' => $makePublic ? (string) Str::uuid() : null,
        ]);

        $this->info("✓ BoardSnapshot '{$name}' creat.");

        return self::SUCCESS;
    }
}
