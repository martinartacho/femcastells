<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CastleTemplate;
use App\Models\Colla;
use DOMDocument;
use DOMXPath;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Importa plantilles FP3 → FP4 CastleTemplates amb coordenades completes.
 *
 * Fonts:
 *   - rows        → dades semàntiques (position, cord, side, row, base)
 *   - html_pinya  → geometria (x, y, w, h, angle)
 *
 * Avantatge respecte fp3:import: aquest command inclou coordenades automàticament,
 * eliminant la necessitat d'extracció manual via consola del navegador.
 */
class ImportFp3Templates extends Command
{
    protected $signature = 'fp3:import-plantilles-to-template
        {shortname : Shortname de la colla a FP3}
        {--fp4-shortname= : Shortname a FP4 si difereix de FP3}
        {--dry-run : Simula sense escriure a la BD}';

    protected $description = 'Importa plantilles FP3 → FP4 CastleTemplates, parseant html_pinya per coordenades';

    public function handle(): int
    {
        $shortname = $this->argument('shortname');
        $dryRun    = (bool) $this->option('dry-run');

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

        $fp4Shortname = $this->option('fp4-shortname') ?: $shortname;
        $fp4Colla     = Colla::where('shortname', $fp4Shortname)->first();

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

            if (empty($board->html_pinya)) {
                $this->warn('   ⚠  html_pinya buit — skip');
                $skipped++;
                continue;
            }

            $geoByDivId = collect($this->parseHtmlPinya($board->html_pinya))->keyBy('div_id');

            if ($geoByDivId->isEmpty()) {
                $this->warn('   ⚠  Cap div trobat a html_pinya — skip');
                $skipped++;
                continue;
            }

            $rows = DB::connection('mysql_fp3')
                ->table('rows')
                ->where('board_id', $board->id_board)
                ->orderBy('id')
                ->get();

            $positions = $rows->map(function (object $r) use ($geoByDivId): array {
                $geo = $geoByDivId->get($r->div_id);

                $pos = [
                    'id'       => 'fp3_' . $r->id,
                    'position' => $r->position,
                    'cord'     => (int) $r->cord,
                    'side'     => $r->side ?? '',
                    'div_id'   => $r->div_id,
                    'row'      => $r->row,
                    'base'     => $r->base,
                    'nom'      => $r->row,
                    'grup'     => $r->position,
                ];

                if ($geo) {
                    $pos['x']     = $geo['x'];
                    $pos['y']     = $geo['y'];
                    $pos['w']     = $geo['w'];
                    $pos['h']     = $geo['h'];
                    $pos['cx']    = $geo['cx'];
                    $pos['cy']    = $geo['cy'];
                    $pos['angle'] = $geo['angle'];
                }

                return $pos;
            })->values()->toArray();

            $withCoords = collect($positions)->filter(fn ($p) => isset($p['x']))->count();
            $this->line("   Posicions: " . count($positions) . " ({$withCoords} amb coordenades, " . ($geoByDivId->count() - $withCoords) . " divs sense row)");

            if (! $dryRun) {
                CastleTemplate::updateOrCreate(
                    ['colla_id' => $fp4Colla->id, 'key' => 'fp3_' . $board->id_board],
                    [
                        'name'      => $board->name,
                        'positions' => $positions,
                        'ready'     => true,
                    ]
                );
                $this->line('   ✓ CastleTemplate actualitzat');
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
     * Parseja html_pinya i retorna array de posicions geomètriques normalitzades.
     *
     * Cada <div id="{div_id}" style="left:{x}px; top:{y}px; width:{w}px; height:{h}px; transform:rotate({a}deg)">
     * es converteix en {div_id, x, y, w, h, cx, cy, angle} amb coordenades normalitzades (minX=0, minY=0).
     *
     * @return array<int, array{div_id:int, x:int, y:int, w:int, h:int, cx:int, cy:int, angle:float}>
     */
    private function parseHtmlPinya(string $html): array
    {
        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->loadHTML('<?xml encoding="utf-8"?><div>' . $html . '</div>', LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();

        $xpath = new DOMXPath($doc);
        $divs  = $xpath->query('//div[@id]');

        $raw = [];
        $xs  = [];
        $ys  = [];

        foreach ($divs as $div) {
            $divId = (int) $div->getAttribute('id');
            if ($divId <= 0) {
                continue;
            }

            $style = $div->getAttribute('style');
            $x     = $this->extractPx($style, 'left');
            $y     = $this->extractPx($style, 'top');
            $w     = $this->extractPx($style, 'width') ?? 63.0;
            $h     = $this->extractPx($style, 'height') ?? 27.0;

            if ($x === null || $y === null) {
                continue;
            }

            $xs[]  = $x;
            $ys[]  = $y;
            $raw[] = ['divId' => $divId, 'x' => $x, 'y' => $y, 'w' => $w, 'h' => $h, 'angle' => $this->extractRotation($style)];
        }

        if (empty($raw)) {
            return [];
        }

        $minX = min($xs);
        $minY = min($ys);

        return array_map(function (array $r) use ($minX, $minY): array {
            $nx = (int) round($r['x'] - $minX);
            $ny = (int) round($r['y'] - $minY);
            $w  = (int) round($r['w']);
            $h  = (int) round($r['h']);

            return [
                'div_id' => $r['divId'],
                'x'      => $nx,
                'y'      => $ny,
                'w'      => $w,
                'h'      => $h,
                'cx'     => $nx + intdiv($w, 2),
                'cy'     => $ny + intdiv($h, 2),
                'angle'  => $r['angle'],
            ];
        }, $raw);
    }

    private function extractPx(string $style, string $property): ?float
    {
        if (preg_match('/' . preg_quote($property, '/') . '\s*:\s*([-\d.]+)px/', $style, $m)) {
            return (float) $m[1];
        }
        return null;
    }

    private function extractRotation(string $style): float
    {
        if (preg_match('/rotate\(([-\d.]+)deg\)/', $style, $m)) {
            return (float) $m[1];
        }
        return 0.0;
    }
}
