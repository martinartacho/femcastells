<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CastleTemplate;
use App\Models\Colla;
use Illuminate\Console\Command;

/**
 * Actualitza el camp `nom` de les posicions d'un CastleTemplate existent
 * a partir d'un JSON de labels extret del DOM de FP3.
 *
 * El JSON prové del F12 de la pàgina board event a app.fempinya.cat:
 *   const labels = {};
 *   document.querySelectorAll('[id][data-position]').forEach(div => {
 *     const txt = div.textContent.trim();
 *     const skip = ['PLENA','BUIDA','RENGLA','RENGLE'];
 *     if (txt && !skip.includes(txt)) labels[div.id] = txt;
 *   });
 *   console.log(JSON.stringify(labels));
 *
 * Ús:
 *   php artisan template:update-labels --key=fp3_584 --labels-file=temp/labels_bvano.json
 */
class UpdateTemplateLabels extends Command
{
    protected $signature = 'template:update-labels
        {--fp4-shortname= : Shortname de la colla a FP4 (obligatori)}
        {--key=           : Clau del CastleTemplate (ex: fp3_584)}
        {--labels-file=   : Fitxer JSON {div_id → label} extret del DOM de FP3}
        {--dry-run        : Mostra els canvis sense escriure}';

    protected $description = 'Actualitza els noms semàntics de les posicions d\'un CastleTemplate des del DOM de FP3';

    public function handle(): int
    {
        $fp4Shortname = $this->option('fp4-shortname');
        $key          = $this->option('key');
        $labelsFile   = $this->option('labels-file');
        $dryRun       = (bool) $this->option('dry-run');

        if (! $fp4Shortname || ! $key || ! $labelsFile) {
            $this->error('Cal indicar --fp4-shortname, --key i --labels-file.');
            return self::FAILURE;
        }

        if (! file_exists($labelsFile)) {
            $this->error("Fitxer no trobat: {$labelsFile}");
            return self::FAILURE;
        }

        $fp4Colla = Colla::where('shortname', $fp4Shortname)->first();
        if (! $fp4Colla) {
            $this->error("Colla '{$fp4Shortname}' no trobada.");
            return self::FAILURE;
        }

        $template = CastleTemplate::where('colla_id', $fp4Colla->id)
            ->where('key', $key)
            ->first();

        if (! $template) {
            $this->error("CastleTemplate amb key='{$key}' no trobat per la colla '{$fp4Shortname}'.");
            return self::FAILURE;
        }

        $labels = json_decode(file_get_contents($labelsFile), true);
        if (! is_array($labels)) {
            $this->error('JSON invàlid al fitxer de labels.');
            return self::FAILURE;
        }

        $emptyCount = count(array_filter($labels, fn($v) => $v === '' || $v === null));
        if ($emptyCount > 0) {
            $this->warn("⚠  {$emptyCount} labels buides al fitxer — el JSON prové de la API (row.position buit).");
            $this->warn('   Cal usar el script DOM en lloc de la API load-map.');
            return self::FAILURE;
        }

        $positions   = $template->positions ?? [];
        $updated     = 0;
        $notFound    = 0;

        foreach ($positions as &$pos) {
            // pos_N → N (div_id)
            $divId = preg_replace('/^pos_/', '', (string) ($pos['id'] ?? ''));

            if (isset($labels[$divId]) && $labels[$divId] !== '') {
                $oldNom = $pos['nom'] ?? '';
                $newNom = $labels[$divId];
                if ($oldNom !== $newNom) {
                    if ($dryRun) {
                        $this->line("  pos_{$divId}: '{$oldNom}' → '{$newNom}'");
                    }
                    $pos['nom'] = $newNom;
                    $updated++;
                }
            } else {
                $notFound++;
            }
        }
        unset($pos);

        $this->info("Template: {$template->name} (key={$key})");
        $this->info("Posicions totals: " . count($positions));
        $this->info("Actualitzades: {$updated}");

        if ($notFound > 0) {
            $this->warn("Sense label: {$notFound}");
        }

        if ($dryRun) {
            $this->warn('DRY-RUN — no s\'ha escrit res.');
            return self::SUCCESS;
        }

        $template->positions = $positions;
        $template->save();

        $this->info("✓ CastleTemplate '{$template->name}' actualitzat.");

        return self::SUCCESS;
    }
}
