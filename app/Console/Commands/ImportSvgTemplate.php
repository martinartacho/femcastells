<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CastleTemplate;
use App\Models\Colla;
use Illuminate\Console\Command;

/**
 * Importa un SVG de pinya directament com a CastleTemplate, sense necessitat de BD FP3.
 *
 * Ús:
 *   php artisan template:import-svg --fp4-shortname=xics --svg=temp/583_PINYA_XICS_B_set.svg --name="B set" --key=fp3_583
 */
class ImportSvgTemplate extends Command
{
    protected $signature = 'template:import-svg
        {--fp4-shortname= : Shortname de la colla a FP4 (obligatori)}
        {--svg=           : Path local al fitxer SVG (obligatori)}
        {--name=          : Nom de la plantilla (obligatori)}
        {--key=           : Clau única per updateOrCreate (ex: fp3_583)}
        {--dry-run        : Simula sense escriure a la BD}';

    protected $description = 'Importa un SVG de pinya directament com a CastleTemplate de FP4';

    public function handle(): int
    {
        $fp4Shortname = $this->option('fp4-shortname');
        $svgPath      = $this->option('svg');
        $name         = $this->option('name');
        $key          = $this->option('key');
        $dryRun       = (bool) $this->option('dry-run');

        if (! $fp4Shortname || ! $svgPath || ! $name) {
            $this->error('Cal indicar --fp4-shortname, --svg i --name.');
            return self::FAILURE;
        }

        if (! file_exists($svgPath)) {
            $this->error("SVG no trobat: {$svgPath}");
            return self::FAILURE;
        }

        $fp4Colla = Colla::where('shortname', $fp4Shortname)->first();
        if (! $fp4Colla) {
            $this->error("Colla '{$fp4Shortname}' no trobada a FP4.");
            return self::FAILURE;
        }

        if ($dryRun) {
            $this->warn('⚠  DRY-RUN — no s\'escriurà res');
        }

        $positions = $this->parseSvg($svgPath);

        if (empty($positions)) {
            $this->error('Cap rectangle trobat al SVG.');
            return self::FAILURE;
        }

        $this->info("SVG: " . basename($svgPath) . " → " . count($positions) . " posicions");

        if (! $dryRun) {
            $criteria = ['colla_id' => $fp4Colla->id];
            if ($key) {
                $criteria['key'] = $key;
            } else {
                $criteria['name'] = $name;
            }

            CastleTemplate::updateOrCreate(
                $criteria,
                ['name' => $name, 'positions' => $positions, 'ready' => true]
            );
            $this->info("✓ CastleTemplate '{$name}' desat a colla '{$fp4Shortname}'");
        } else {
            $this->line('(dry-run: no escrit)');
        }

        return self::SUCCESS;
    }

    /**
     * Parseja SVG → posicions centrades. Replica template-editor.js::importSVG().
     *
     * @return array<int, array{id:string, cx:float, cy:float, x:float, y:float, w:int, h:int, angle:float, nom:string, grup:string}>
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
        $rects = $xml->xpath('//svg:rect[@width and @height]');

        if (empty($rects)) {
            $rects = $xml->xpath('//rect[@width and @height]');
        }

        $raw = [];
        foreach ($rects as $rect) {
            $x         = (float) ($rect['x'] ?? 0);
            $y         = (float) ($rect['y'] ?? 0);
            $w         = (float) ($rect['width'] ?? 34);
            $h         = (float) ($rect['height'] ?? 15);
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

            $raw[] = [
                'cx'    => $cx,
                'cy'    => $cy,
                'w'     => (int) round($w),
                'h'     => (int) round($h),
                'angle' => round($angle * 10) / 10,
            ];
        }

        if (empty($raw)) {
            return [];
        }

        $avgX = array_sum(array_column($raw, 'cx')) / count($raw);
        $avgY = array_sum(array_column($raw, 'cy')) / count($raw);

        $positions = [];
        foreach ($raw as $i => $p) {
            $n  = $i + 1;
            $cx = round(($p['cx'] - $avgX) * 10) / 10;
            $cy = round(($p['cy'] - $avgY) * 10) / 10;

            $positions[] = [
                'id'    => 'pos_' . $n,
                'cx'    => $cx,
                'cy'    => $cy,
                'x'     => round($cx - $p['w'] / 2, 1),
                'y'     => round($cy - $p['h'] / 2, 1),
                'w'     => $p['w'],
                'h'     => $p['h'],
                'angle' => $p['angle'],
                'nom'   => 'pos ' . $n,
                'grup'  => 'rengle',
                'shape' => 'rect',
            ];
        }

        return $positions;
    }
}
