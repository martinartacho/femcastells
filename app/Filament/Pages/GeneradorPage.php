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
    // Vista frontal — tronc 2D
    // ─────────────────────────────────────────────────────────────

    public function getTroncSvg(): string
    {
        $n  = $this->familiaToBaixos();
        $p  = $this->pisos;
        $s  = max(0.5, min(1.8, $this->mida / 100));

        $posW   = (int) round(28 * $s);
        $posH   = (int) round(18 * $s);
        $gapX   = (int) round(3 * $s);
        $gapY   = (int) round(6 * $s);
        $armGap = (int) round(12 * $s);
        $mleft  = 72;
        $mtop   = 12;

        // Enxaneta portrait (swap w/h → taller than wide)
        $enxW = $posH;
        $enxH = $posW;

        $wMid  = 3 * $posW + 2 * $gapX;
        $wBaix = 5 * $posW + 4 * $gapX;

        $folreCount    = 7;
        $manillesCount = 9;
        $wFolre    = $folreCount * $posW + ($folreCount - 1) * $gapX;
        $wManilles = $manillesCount * $posW + ($manillesCount - 1) * $gapX;

        $rowWMid      = $n * $wMid + ($n - 1) * $armGap;
        $rowWBaix     = $n * $wBaix + ($n - 1) * $armGap;
        $rowWFolre    = $n * $wFolre + ($n - 1) * $armGap;
        $rowWManilles = $n * $wManilles + ($n - 1) * $armGap;

        $hasFolre    = in_array($this->nivell, ['folre', 'manilles']);
        $hasManilles = $this->nivell === 'manilles';

        $maxW = max(
            $enxW, $posW, $rowWMid, $rowWBaix,
            $hasFolre ? $rowWFolre : 0,
            $hasManilles ? $rowWManilles : 0
        );

        $svgW     = $mleft + $maxW + $mleft;
        $parts    = [];
        $currentY = (float) $mtop;

        $addLabel = function (float $y, float $h, string $text) use (&$parts, $mleft): void {
            $parts[] = sprintf(
                '<text x="%d" y="%.1f" font-size="9" fill="#9ca3af" text-anchor="end" font-family="sans-serif">%s</text>',
                $mleft - 6,
                $y + $h / 2 + 3,
                $text
            );
        };

        // Enxaneta — portrait
        $addLabel($currentY, $enxH, 'Enxaneta');
        $parts[] = $this->troncRect(
            (int) round($mleft + ($maxW - $enxW) / 2),
            (int) $currentY, $enxW, $enxH, ['#1a237e', 'Enx', '#fff']
        );
        $currentY += $enxH + $gapY;

        // Acotxador
        $addLabel($currentY, $posH, 'Acotxador');
        $parts[] = $this->troncRect(
            (int) round($mleft + ($maxW - $posW) / 2),
            (int) $currentY, $posW, $posH, ['#1565c0', 'Acot', '#fff']
        );
        $currentY += $posH + $gapY;

        // Mid pisos (pis p-2 down to pis 2)
        $posMid = [['#2e7d32', 'LE', '#fff'], ['#fef9c3', 'R', '#555'], ['#1565c0', 'LD', '#fff']];
        for ($pis = $p - 2; $pis >= 2; $pis--) {
            $addLabel($currentY, $posH, $this->pisLabel($pis, $p));
            $startX = $mleft + ($maxW - $rowWMid) / 2;
            for ($arm = 0; $arm < $n; $arm++) {
                $armX = $startX + $arm * ($wMid + $armGap);
                foreach ($posMid as $pi => $posData) {
                    $parts[] = $this->troncRect(
                        (int) round($armX + $pi * ($posW + $gapX)),
                        (int) $currentY, $posW, $posH, $posData
                    );
                }
            }
            $currentY += $posH + $gapY;
        }

        // Pinya (pis 1)
        $posBaix = [
            ['#f9a825', 'Ct', '#333'],
            ['#37474f', 'B',  '#fff'],
            ['#e65100', 'Ag', '#fff'],
            ['#37474f', 'B',  '#fff'],
            ['#f9a825', 'Ct', '#333'],
        ];
        $addLabel($currentY, $posH, 'Pinya');
        $startX = $mleft + ($maxW - $rowWBaix) / 2;
        for ($arm = 0; $arm < $n; $arm++) {
            $armX = $startX + $arm * ($wBaix + $armGap);
            foreach ($posBaix as $pi => $posData) {
                $parts[] = $this->troncRect(
                    (int) round($armX + $pi * ($posW + $gapX)),
                    (int) $currentY, $posW, $posH, $posData
                );
            }
        }
        $currentY += $posH;

        // Folre
        if ($hasFolre) {
            $currentY += $gapY;
            $addLabel($currentY, $posH, 'Folre');
            $startX = $mleft + ($maxW - $rowWFolre) / 2;
            for ($arm = 0; $arm < $n; $arm++) {
                $armX = $startX + $arm * ($wFolre + $armGap);
                for ($pi = 0; $pi < $folreCount; $pi++) {
                    $parts[] = $this->troncRect(
                        (int) round($armX + $pi * ($posW + $gapX)),
                        (int) $currentY, $posW, $posH, ['#6a1b9a', '', '#fff']
                    );
                }
            }
            $currentY += $posH;
        }

        // Manilles
        if ($hasManilles) {
            $currentY += $gapY;
            $addLabel($currentY, $posH, 'Manilles');
            $startX = $mleft + ($maxW - $rowWManilles) / 2;
            for ($arm = 0; $arm < $n; $arm++) {
                $armX = $startX + $arm * ($wManilles + $armGap);
                for ($pi = 0; $pi < $manillesCount; $pi++) {
                    $parts[] = $this->troncRect(
                        (int) round($armX + $pi * ($posW + $gapX)),
                        (int) $currentY, $posW, $posH, ['#c62828', '', '#fff']
                    );
                }
            }
            $currentY += $posH;
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" style="width:100%%;height:auto;max-height:520px;">%s</svg>',
            (int) ceil($svgW),
            (int) ceil($currentY + $mtop),
            implode('', $parts)
        );
    }

    private function troncRect(int $x, int $y, int $w, int $h, array $pos): string
    {
        [$color, $label, $tc] = array_pad($pos, 3, '#fff');
        $rect = sprintf('<rect x="%d" y="%d" width="%d" height="%d" rx="3" fill="%s" stroke="rgba(0,0,0,.12)" stroke-width="0.5"/>', $x, $y, $w, $h, $color);
        $text = $label ? sprintf('<text x="%d" y="%.1f" font-size="7" fill="%s" text-anchor="middle" font-weight="bold" font-family="sans-serif">%s</text>', $x + $w / 2, $y + $h / 2 + 3, $tc, $label) : '';
        return $rect . $text;
    }

    private function pisLabel(int $pis, int $totalPisos): string
    {
        if ($pis === $totalPisos) return 'Enxaneta';
        if ($pis === $totalPisos - 1) return 'Acotxador';
        if ($pis === 1) return 'Pinya';
        return match ($pis) {
            2 => 'Segs.', 3 => 'Terços', 4 => 'Quarts',
            5 => 'Quints', 6 => 'Sisens', 7 => 'Setens',
            8 => 'Vuitens', 9 => 'Novens', default => $pis . 'è',
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
