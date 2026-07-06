<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Pages\Page;

class GeneradorPage extends Page
{
    protected string $view = 'filament.pages.generador';

    protected static ?string $navigationLabel = 'Generador';

    protected static \UnitEnum|string|null $navigationGroup = 'Pissarra';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'generador';

    // ── Selecció del castell ──
    public string $familia = 'tres';    // pilar | torre | tres | quatre
    public int    $pisos   = 6;
    public string $nivell  = 'base';    // base | folre | manilles

    // ── Paràmetres visuals ──
    public string $shape       = 'circle';
    public int    $mida        = 100;
    public int    $densitat    = 100;
    public bool   $mostrarPeus = false;
    public bool   $nomsPos     = false;
    public string $vista       = 'superior'; // superior | frontal | lateral | 3d

    // ─────────────────────────────────────────────────────────────
    // Dades dels castells
    // ─────────────────────────────────────────────────────────────

    public function getFamilies(): array
    {
        return [
            'pilar'  => 'Pilar',
            'torre'  => 'Torre',
            'tres'   => 'Tres',
            'quatre' => 'Quatre',
        ];
    }

    private function familiaToBaixos(): int
    {
        return match ($this->familia) {
            'pilar'  => 1,
            'torre'  => 2,
            'quatre' => 4,
            default  => 3, // tres
        };
    }

    public function getNivellOptions(): array
    {
        return [
            'base'     => 'Base',
            'folre'    => 'Amb folre',
            'manilles' => 'Amb folre i manilles',
        ];
    }

    public function getShapeOptions(): array
    {
        return [
            'circle'    => 'Cercle',
            'ellipse'   => 'El·lipse',
            'rectangle' => 'Rectangle',
        ];
    }

    public function getCastellName(): string
    {
        $families = $this->getFamilies();
        $fam  = $families[$this->familia] ?? ucfirst($this->familia);
        $suf  = match ($this->nivell) {
            'folre'    => ' amb folre',
            'manilles' => ' amb folre i manilles',
            default    => '',
        };
        return $fam . ' de ' . $this->pisos . $suf;
    }

    // ─────────────────────────────────────────────────────────────
    // Càlcul de distribució
    // ─────────────────────────────────────────────────────────────

    public function getDistribution(): array
    {
        $n = $this->familiaToBaixos();
        $p = $this->pisos;

        $dist = [
            'tronc'     => max(4, $n * 4),
            'contrafort'=> max(6, $n * ($p < 7 ? 5 : 6)),
            'laterals'  => max(8, $n * max(3, $p - 1)),
            'mitjans'   => max(10, $n * ($p + 1)),
        ];

        if (in_array($this->nivell, ['folre', 'manilles'])) {
            $dist['folre'] = max(12, $n * ($p + 3));
        }

        if ($this->nivell === 'manilles') {
            $dist['manilles'] = max(8, $n * ($p + 1));
        }

        $dist['baixos'] = max(16, $n * ($p + 6));

        return $dist;
    }

    public function getTotalCastellers(): int
    {
        return 1 + array_sum($this->getDistribution());
    }

    // ─────────────────────────────────────────────────────────────
    // Generació SVG
    // ─────────────────────────────────────────────────────────────

    public function getGeneratedSvg(): string
    {
        $scale   = $this->mida / 100;
        $posR    = round(11 * $scale, 1);
        $ringGap = round($posR * 2.3, 1);
        $dens    = $this->densitat / 100;

        $dist     = $this->getDistribution();
        $ringIdx  = 0;
        $elements = [];

        // Centre: agulla (1 posició)
        $elements[] = $this->svgShape(0, 0, $posR, $this->catColor('agulla'));

        foreach ($dist as $cat => $total) {
            $remaining = $total;
            while ($remaining > 0) {
                $ringIdx++;
                $r        = $ringIdx * $ringGap;
                $capacity = max(4, (int) floor(2 * M_PI * $r / ($posR * 2.2 / $dens)));
                $inRing   = min($remaining, $capacity);

                for ($i = 0; $i < $inRing; $i++) {
                    $angle = ($i / $inRing) * 2 * M_PI - M_PI / 2;
                    $x     = round($r * cos($angle), 1);
                    $y     = round($r * sin($angle), 1);
                    $elements[] = $this->svgShape($x, $y, $posR, $this->catColor($cat));

                    if ($this->mostrarPeus) {
                        $px = round(($r + $posR * 1.1) * cos($angle), 1);
                        $py = round(($r + $posR * 1.1) * sin($angle), 1);
                        $elements[] = sprintf('<circle cx="%.1f" cy="%.1f" r="%.1f" fill="#00000033"/>', $px, $py, $posR * 0.38);
                    }
                }

                $remaining -= $inRing;
            }
        }

        $radius  = $ringIdx * $ringGap + $posR + 8;
        $vb      = round(-$radius - 4);
        $size    = round($radius * 2 + 8);

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="%s %s %s %s" style="width:100%%;height:auto;max-height:520px;">%s</svg>',
            $vb, $vb, $size, $size,
            implode('', $elements)
        );
    }

    private function svgShape(float $x, float $y, float $r, string $color): string
    {
        $stroke = '#00000022';
        return match ($this->shape) {
            'ellipse'   => sprintf('<ellipse cx="%.1f" cy="%.1f" rx="%.1f" ry="%.1f" fill="%s" stroke="%s" stroke-width="0.5"/>', $x, $y, $r * 1.5, $r, $color, $stroke),
            'rectangle' => sprintf('<rect x="%.1f" y="%.1f" width="%.1f" height="%.1f" rx="3" fill="%s" stroke="%s" stroke-width="0.5"/>', $x - $r * 1.5, $y - $r, $r * 3, $r * 2, $color, $stroke),
            default     => sprintf('<circle cx="%.1f" cy="%.1f" r="%.1f" fill="%s" stroke="%s" stroke-width="0.5"/>', $x, $y, $r, $color, $stroke),
        };
    }

    // ─────────────────────────────────────────────────────────────
    // Colors i etiquetes
    // ─────────────────────────────────────────────────────────────

    public function catColor(string $cat): string
    {
        return [
            'agulla'    => '#1a237e',
            'tronc'     => '#e65100',
            'contrafort'=> '#f9a825',
            'laterals'  => '#2e7d32',
            'mitjans'   => '#1565c0',
            'folre'     => '#6a1b9a',
            'manilles'  => '#c62828',
            'baixos'    => '#90a4ae',
        ][$cat] ?? '#cccccc';
    }

    public function catLabel(string $cat): string
    {
        return [
            'agulla'    => 'Agulla / Enxaneta',
            'tronc'     => 'Tronc',
            'contrafort'=> 'Contrafort',
            'laterals'  => 'Laterals',
            'mitjans'   => 'Mitjans',
            'folre'     => 'Folre',
            'manilles'  => 'Manilles',
            'baixos'    => 'Baixos / Exteriors',
        ][$cat] ?? ucfirst($cat);
    }
}
