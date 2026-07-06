<?php

declare(strict_types=1);

namespace App\Services;

class PinyaMotor
{
    private const W = 34.0;
    private const H = 15.0;

    private const CASTELLS_SIMPLES = [
        1 => ['nom' => 'Pilar',  'anglesBaix' => [0]],
        2 => ['nom' => 'Torre',  'anglesBaix' => [90, 270]],
        3 => ['nom' => 'Tres',   'anglesBaix' => [90, 210, 330]],
        4 => ['nom' => 'Quatre', 'anglesBaix' => [45, 135, 225, 315]],
    ];

    private const NOMS_BAIX = [
        1 => ['Rengle'],
        2 => ['Una', 'Dues'],
        3 => ['Rengle', 'Plena', 'Buida'],
        4 => ['Una', 'Dues', 'Tres', 'Quatre'],
    ];

    public function generate(int $numBaixos, int $pisos): array
    {
        if (!isset(self::CASTELLS_SIMPLES[$numBaixos])) {
            throw new \InvalidArgumentException("Castell simple de {$numBaixos} baixos no definit");
        }

        $info  = self::CASTELLS_SIMPLES[$numBaixos];
        $noms  = self::NOMS_BAIX[$numBaixos];
        $id    = 0;
        $positions = [];

        foreach ($info['anglesBaix'] as $i => $angle) {
            $nomBaix = $noms[$i] ?? ('Baix ' . ($i + 1));
            foreach ($this->generarNucli($nomBaix, (float) $angle, $pisos) as $p) {
                $positions[] = [
                    'id'    => 'pos_' . $id++,
                    'cx'    => round($p['x'], 2),
                    'cy'    => round($p['y'], 2),
                    'w'     => self::W,
                    'h'     => self::H,
                    'angle' => round($p['angle'], 2),
                    'grup'  => $p['grup'],
                    'nom'   => $p['nom'],
                ];
            }
        }

        return $positions;
    }

    public function nomCastell(int $numBaixos, int $pisos): string
    {
        $nomBase = self::CASTELLS_SIMPLES[$numBaixos]['nom'] ?? "Castell {$numBaixos}";
        return "{$nomBase} de {$pisos}";
    }

    public function castells(): array
    {
        return array_map(
            fn($info) => $info['nom'],
            self::CASTELLS_SIMPLES
        );
    }

    private function generarNucli(string $nomBaix, float $angleBraç, int $pisos): array
    {
        $pas  = 22.0;
        $base = 50.0;
        $p    = fn(float $dist, float $off = 0.0) => $this->polar($angleBraç, $dist, $off);
        $peces = [];

        [$x, $y, $a] = $p(0);        $peces[] = ['nom' => $nomBaix,     'x' => $x, 'y' => $y, 'angle' => $a, 'grup' => 'baix'];
        [$x, $y, $a] = $p(18);       $peces[] = ['nom' => 'agulla',     'x' => $x, 'y' => $y, 'angle' => $a, 'grup' => 'nucli'];
        [$x, $y, $a] = $p(34);       $peces[] = ['nom' => 'contrafort', 'x' => $x, 'y' => $y, 'angle' => $a, 'grup' => 'nucli'];
        [$x, $y, $a] = $p(30, +20);  $peces[] = ['nom' => 'crossa D',   'x' => $x, 'y' => $y, 'angle' => $a, 'grup' => 'nucli_D'];
        [$x, $y, $a] = $p(30, -20);  $peces[] = ['nom' => 'crossa E',   'x' => $x, 'y' => $y, 'angle' => $a, 'grup' => 'nucli_E'];
        [$x, $y, $a] = $p(34);       $peces[] = ['nom' => 'dau',        'x' => $x, 'y' => $y, 'angle' => $a, 'grup' => 'nucli'];

        for ($i = 1; $i < $pisos; $i++) {
            $dist = $base + ($i - 1) * $pas;
            [$x, $y, $a] = $p($dist, +24); $peces[] = ['nom' => "laterals {$i} D", 'x' => $x, 'y' => $y, 'angle' => $a, 'grup' => 'lateral_D'];
            [$x, $y, $a] = $p($dist, -24); $peces[] = ['nom' => "laterals {$i} E", 'x' => $x, 'y' => $y, 'angle' => $a, 'grup' => 'lateral_E'];
        }

        for ($i = 1; $i <= $pisos; $i++) {
            $dist = $base + ($i - 1) * $pas;
            [$x, $y, $a] = $p($dist, 0); $peces[] = ['nom' => "rengle {$i}", 'x' => $x, 'y' => $y, 'angle' => $a, 'grup' => 'rengle'];
        }

        $distFalca = $base + ($pisos - 1) * $pas + $pas * 0.5;
        $distAngle = $distFalca + $pas * 0.6;

        [$x, $y, $a] = $p($distFalca, +24); $peces[] = ['nom' => 'falques', 'x' => $x, 'y' => $y, 'angle' => $a, 'grup' => 'falca_D'];
        [$x, $y, $a] = $p($distFalca, -24); $peces[] = ['nom' => 'falques', 'x' => $x, 'y' => $y, 'angle' => $a, 'grup' => 'falca_E'];
        [$x, $y, $a] = $p($distAngle, +34); $peces[] = ['nom' => 'angles',  'x' => $x, 'y' => $y, 'angle' => $a, 'grup' => 'angle_D'];
        [$x, $y, $a] = $p($distAngle, -34); $peces[] = ['nom' => 'angles',  'x' => $x, 'y' => $y, 'angle' => $a, 'grup' => 'angle_E'];

        return $peces;
    }

    private function polar(float $angleBraç, float $dist, float $angOffset = 0.0): array
    {
        $ang = $angleBraç + $angOffset;
        $rad = deg2rad($ang - 90.0);
        return [
            $dist * cos($rad),
            $dist * sin($rad),
            $ang,
        ];
    }
}
