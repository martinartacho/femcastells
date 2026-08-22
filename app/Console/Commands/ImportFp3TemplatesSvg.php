<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CastleTemplate;
use App\Models\Colla;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use SimpleXMLElement;

/**
 * Importa plantilles FP3 → FP4 CastleTemplates usant l'SVG original com a font de geometria.
 *
 * Replicació exacta de la lògica JS de FP4 (template-editor.js::importSVG):
 *   1. Llegeix rectangles SVG en ordre → cada rect N = div_id N
 *   2. Aplica rotate() al centre cx/cy
 *   3. Centra tot respecte la mitjana
 *   4. Creua amb FP3 rows per div_id → noms semàntics reals
 *
 * Ús:
 *   docker cp backend-app-1:/var/www/html/public/media/colles/xicgra/svg/ temp/fp3_svgs
 *   php artisan fp3:import-templates-from-svg xicgra --fp4-shortname=xics --svg-dir=temp/fp3_svgs
 */
class ImportFp3TemplatesSvg extends Command
{
    protected $signature = 'fp3:import-templates-from-svg
        {shortname : Shortname de la colla a FP3}
        {--fp4-shortname= : Shortname a FP4 si difereix de FP3}
        {--svg-dir= : Directori local amb els fitxers SVG (ex: temp/fp3_svgs)}
        {--dry-run : Simula sense escriure a la BD}';

    protected $description = 'Importa plantilles FP3 → FP4 usant SVG original per geometria + rows per noms semàntics';

    public function handle(): int
    {
        $shortname    = $this->argument('shortname');
        $fp4Shortname = $this->option('fp4-shortname') ?: $shortname;
        $svgDir       = rtrim($this->option('svg-dir') ?: 'temp/fp3_svgs', '/\\');
        $dryRun       = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('⚠  DRY-RUN — no s\'escriurà res');
        }

        $fp3Colla = DB::connection('mysql_fp3')
            ->table('colles')
            ->where('shortname', $shortname)
            ->first();

        if (! $fp3Colla) {
            $this->error("Colla '{$shortname}' no trobada a FP3.");
            return self::FAILURE;
        }

        $fp4Colla = Colla::where('shortname', $fp4Shortname)->first();

        if (! $fp4Colla) {
            $this->error("Colla '{$fp4Shortname}' no trobada a FP4. Executa primer: php artisan fp3:import {$shortname} --fp4-shortname={$fp4Shortname}");
            return self::FAILURE;
        }

        $this->info("Colla: {$fp3Colla->name} (FP3 id={$fp3Colla->id_colla} → FP4 id={$fp4Colla->id})");
        $this->newLine();

        $boards = DB::connection('mysql_fp3')
            ->table('boards')
            ->where('colla_id', $fp3Colla->id_colla)
            ->get();

        $updated = 0;
        $skipped = 0;

        foreach ($boards as $board) {
            $this->info("→ Board {$board->id_board}: {$board->name}");

            $svgFile = $this->resolveSvgPath($board->data_code, $svgDir);

            if (! $svgFile) {
                $this->warn('   ⚠  data_code sense SVG — skip');
                $skipped++;
                continue;
            }

            if (! file_exists($svgFile)) {
                $this->warn("   ⚠  SVG no trobat: {$svgFile} — skip");
                $skipped++;
                continue;
            }

            $this->line("   SVG: " . basename($svgFile));

            $geoByIndex = $this->parseSvg($svgFile);

            if (empty($geoByIndex)) {
                $this->warn('   ⚠  Cap rectangle al SVG — skip');
                $skipped++;
                continue;
            }

            $rows = DB::connection('mysql_fp3')
                ->table('rows')
                ->where('board_id', $board->id_board)
                ->orderBy('div_id')
                ->get()
                ->keyBy('div_id');

            $positions = [];
            $withNames = 0;

            foreach ($geoByIndex as $divId => $geo) {
                $row = $rows->get($divId);

                $pos = [
                    'id'    => $row ? 'fp3_' . $row->id : 'div_' . $divId,
                    'cx'    => $geo['cx'],
                    'cy'    => $geo['cy'],
                    'x'     => round($geo['cx'] - $geo['w'] / 2, 1),
                    'y'     => round($geo['cy'] - $geo['h'] / 2, 1),
                    'w'     => $geo['w'],
                    'h'     => $geo['h'],
                    'angle' => $geo['angle'],
                    'div_id' => $divId,
                    'nom'   => $row ? $this->buildNom($row) : 'pos ' . $divId,
                    'grup'  => $row ? $row->position            : 'rengle',
                    'shape' => 'rect',
                ];

                if ($row) {
                    $pos['position'] = $row->position;
                    $pos['cord']     = (int) $row->cord;
                    $pos['side']     = $row->side ?? '';
                    $pos['row']      = $row->row;
                    $pos['base']     = $row->base;
                    $withNames++;
                }

                $positions[] = $pos;
            }

            $this->line("   Posicions: " . count($positions) . " ({$withNames} amb nom semàntic)");

            if (! $dryRun) {
                CastleTemplate::updateOrCreate(
                    ['colla_id' => $fp4Colla->id, 'key' => 'fp3_' . $board->id_board],
                    [
                        'name'      => $board->name,
                        'positions' => $positions,
                        'ready'     => true,
                    ]
                );
                $this->line('   ✓ CastleTemplate actualitzat (via SVG)');
            } else {
                $this->line('   (dry-run: no escrit)');
            }

            $updated++;
        }

        $this->newLine();
        $this->info("Resultat: {$updated} plantilles actualitzades, {$skipped} omeses.");

        return self::SUCCESS;
    }

    /**
     * Parseja el SVG i retorna posicions centrades, indexades per div_id (1-indexed per ordre).
     * Replica exactament la lògica de template-editor.js::importSVG().
     *
     * @return array<int, array{cx:float, cy:float, w:int, h:int, angle:float}>
     */
    private function parseSvg(string $path): array
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($path);
        libxml_clear_errors();

        if (! $xml) {
            return [];
        }

        $xml->registerXPathNamespace('svg', 'http://www.w3.org/2000/svg');
        // Tots els rects fora de <defs>
        $rects = $xml->xpath('//svg:rect[@width and @height]');

        if (empty($rects)) {
            // Fallback sense namespace
            $rects = $xml->xpath('//rect[@width and @height]');
        }

        $raw = [];
        foreach ($rects as $rect) {
            $x = (float) ($rect['x'] ?? 0);
            $y = (float) ($rect['y'] ?? 0);
            $w = (float) ($rect['width'] ?? 34);
            $h = (float) ($rect['height'] ?? 15);
            $transform = (string) ($rect['transform'] ?? '');

            $cx    = $x + $w / 2;
            $cy    = $y + $h / 2;
            $angle = 0.0;

            if (preg_match('/rotate\(\s*([-\d.]+)/', $transform, $m)) {
                $angle = (float) $m[1];
                $rad   = $angle * M_PI / 180.0;
                $nx    = $cx * cos($rad) - $cy * sin($rad);
                $cy    = $cx * sin($rad) + $cy * cos($rad);
                $cx    = $nx;
            } elseif (preg_match('/scale\(\s*([-\d.]+)/', $transform, $m)) {
                $s  = (float) $m[1];
                $cx *= $s;
                $cy *= $s;
                if ($s < 0) {
                    $angle = 180.0;
                }
            }

            $raw[] = ['cx' => $cx, 'cy' => $cy, 'w' => (int) round($w), 'h' => (int) round($h), 'angle' => round($angle * 10) / 10];
        }

        if (empty($raw)) {
            return [];
        }

        // Centra respecte la mitjana (idèntic al JS)
        $avgX = array_sum(array_column($raw, 'cx')) / count($raw);
        $avgY = array_sum(array_column($raw, 'cy')) / count($raw);

        $result = [];
        foreach ($raw as $i => $p) {
            $divId = $i + 1; // div_id és 1-indexed per ordre d'aparició al SVG
            $result[$divId] = [
                'cx'    => round(($p['cx'] - $avgX) * 10) / 10,
                'cy'    => round(($p['cy'] - $avgY) * 10) / 10,
                'w'     => $p['w'],
                'h'     => $p['h'],
                'angle' => $p['angle'],
            ];
        }

        return $result;
    }

    /** Construeix el nom llegible d'una posició: "Rengla 3", "Laterals 2 LEFT", "baix". */
    private function buildNom(object $row): string
    {
        $parts = [trim((string) $row->position)];
        if ((int) $row->cord > 0) {
            $parts[] = (string) (int) $row->cord;
        }
        if (! empty($row->side)) {
            $parts[] = trim((string) $row->side);
        }
        return implode(' ', array_filter($parts));
    }

    /**
     * Obté el path local de l'SVG a partir del JSON data_code del board.
     * data_code format: {"pinya":{"svg":"2_PINYA.svg","structure":null}}
     */
    private function resolveSvgPath(?string $dataCode, string $svgDir): ?string
    {
        if (! $dataCode) {
            return null;
        }

        $data = json_decode($dataCode, true);
        $svgFilename = $data['pinya']['svg'] ?? null;

        if (! $svgFilename) {
            return null;
        }

        return $svgDir . DIRECTORY_SEPARATOR . $svgFilename;
    }
}
