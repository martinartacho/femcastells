<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ExtractBoardPositions extends Command
{
    protected $signature = 'board:extract {svg : SVG filename in temp/provesBoard/} {--out= : Output JSON filename in resources/js/boards/}';

    protected $description = 'Extract rect positions from a Birraires SVG reference file';

    public function handle(): int
    {
        $svgFile = base_path('temp/provesBoard/' . $this->argument('svg'));

        if (!file_exists($svgFile)) {
            $this->error("File not found: {$svgFile}");
            return 1;
        }

        $xml = new \SimpleXMLElement(file_get_contents($svgFile));
        $xml->registerXPathNamespace('svg', 'http://www.w3.org/2000/svg');

        $rects = $xml->xpath('//svg:rect') ?: $xml->xpath('//rect');

        $positions = [];
        foreach ($rects as $rect) {
            $attrs = (array) $rect->attributes();
            $a = $attrs['@attributes'] ?? [];

            $x = (float) ($a['x'] ?? 0);
            $y = (float) ($a['y'] ?? 0);
            $w = (float) ($a['width'] ?? 0);
            $h = (float) ($a['height'] ?? 0);
            $id = (string) ($a['id'] ?? '');
            $transform = (string) ($a['transform'] ?? '');

            $matrix = [1, 0, 0, 1, 0, 0];
            if (preg_match('/matrix\(([^)]+)\)/', $transform, $m)) {
                $matrix = array_map('floatval', preg_split('/[\s,]+/', trim($m[1])));
            }

            [$ma, $mb, $mc, $md, $me, $mf] = array_pad($matrix, 6, 0);

            $lx = $x + $w / 2;
            $ly = $y + $h / 2;

            $cx = round($ma * $lx + $mc * $ly + $me, 2);
            $cy = round($mb * $lx + $md * $ly + $mf, 2);

            $angle = round(rad2deg(atan2($mb, $ma)), 1);

            $positions[] = [
                'id'     => $id,
                'x'      => round($x, 2),
                'y'      => round($y, 2),
                'w'      => round($w, 2),
                'h'      => round($h, 2),
                'cx'     => $cx,
                'cy'     => $cy,
                'angle'  => $angle,
                'matrix' => array_map(fn ($v) => round($v, 4), $matrix),
            ];
        }

        $outName = $this->option('out') ?? pathinfo($this->argument('svg'), PATHINFO_FILENAME) . '.json';
        $outDir  = base_path('resources/js/boards');

        if (!is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }

        $outFile = $outDir . '/' . $outName;
        file_put_contents($outFile, json_encode($positions, JSON_PRETTY_PRINT));

        $this->info('Extracted ' . count($positions) . " positions → resources/js/boards/{$outName}");

        return 0;
    }
}
