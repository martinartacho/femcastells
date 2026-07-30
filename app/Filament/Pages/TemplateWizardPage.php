<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\CastleTemplate;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\On;

class TemplateWizardPage extends Page
{
    protected static ?string $navigationLabel = 'Nova plantilla';

    protected static ?string $slug = 'wizard-plantilla';

    protected static \UnitEnum|string|null $navigationGroup = 'Pissarra';

    protected static ?int $navigationSort = 3;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-building-library';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canDo('generador') ?? false;
    }

    public function getView(): string
    {
        return 'filament.pages.template-wizard';
    }

    // ── Wizard state ──
    public string $familia = 'tres';
    public int    $pisos   = 6;
    public string $nivell  = 'base';
    public string $templateName = '';
    public int    $defaultW     = 100;
    public int    $defaultH     = 30;
    public int    $defaultAngle = -90;
    public int    $cordonsN     = 1;
    public int    $anells       = 2;

    public function mount(): void
    {
        $this->templateName = $this->getCastellName();
    }

    public function updatedFamilia(): void { $this->templateName = $this->getCastellName(); }
    public function updatedPisos(): void   { $this->templateName = $this->getCastellName(); }
    public function updatedNivell(): void
    {
        $this->templateName = $this->getCastellName();
        $this->dispatch('template-view-change', nivell: $this->nivell);
    }

    public function addStep(int $step): void
    {
        $positions = $this->getStepPositions($step);
        if (!empty($positions)) {
            $this->dispatch('template-add-positions', positions: $positions);
        }
    }

    public function addAllSteps(): void
    {
        if (in_array($this->familia, ['cinc', 'set', 'nou'])) {
            Notification::make()->title('Funcionalitat en construcció')->warning()->send();
            return;
        }

        $positions = match ($this->nivell) {
            'tronc'    => $this->getTroncPositions(),
            'folre'    => $this->getFolrePositions(1.0),
            'manilles' => $this->getFolrePositions(1.7),
            default    => array_merge(
                ...array_map(fn($s) => $this->getStepPositions($s), range(1, 7))
            ),
        };

        if (!empty($positions)) {
            $this->dispatch('template-clear-and-add', positions: $positions);
        }
    }

    public function addCordons(int $count): void
    {
        $positions = [];
        for ($n = 1; $n <= min($count, 9); $n++) {
            $positions = array_merge($positions, $this->getStepPositions(7 + $n));
        }
        if (!empty($positions)) {
            $this->dispatch('template-add-positions', positions: $positions);
        }
    }

    public function addCordonsWired(): void
    {
        $this->addCordons($this->cordonsN);
    }

    // ── Helpers ──
    public function getCastellName(): string
    {
        $suf = match ($this->nivell) {
            'folre'    => ' amb folre',
            'manilles' => ' amb folre i manilles',
            default    => '',
        };
        return $this->getFamilyLabel() . ' de ' . $this->pisos . $suf;
    }

    public function getFamilyLabel(): string
    {
        return [
            'pilar'  => 'Pilar',  'torre'  => 'Torre',
            'tres'   => 'Tres',   'quatre' => 'Quatre',
            'cinc'   => 'Cinc',   'set'    => 'Set',
            'nou'    => 'Nou',
        ][$this->familia] ?? ucfirst($this->familia);
    }

    public function getFamilies(): array
    {
        return [
            'pilar'  => ['nom' => 'Pilar',  'baixos' => 1, 'nota' => 'espadat'],
            'torre'  => ['nom' => 'Torre',  'baixos' => 2, 'nota' => 'o Dos'],
            'tres'   => ['nom' => 'Tres',   'baixos' => 3, 'nota' => ''],
            'quatre' => ['nom' => 'Quatre', 'baixos' => 4, 'nota' => ''],
            'cinc'   => ['nom' => 'Cinc',   'baixos' => 5, 'nota' => 'tres+torre'],
            'set'    => ['nom' => 'Set',    'baixos' => 7, 'nota' => 'quatre+tres'],
            'nou'    => ['nom' => 'Nou',    'baixos' => 9, 'nota' => '3×3'],
        ];
    }

    // ── Genera posicions inicials amb noms reals ──────────────────────────────
    public function getInitialPositions(): array
    {
        $n    = $this->familiaToBaixos();
        $p    = $this->pisos;
        $gap  = 20;   // espai vertical entre files del tronc
        $posW = 34;
        $posH = 15;
        $id   = 0;
        $pos  = [];

        // ─ Tronc (columna central vertical) ─
        $troncRows = $this->buildTroncLabels($p);
        foreach ($troncRows as $ri => $row) {
            $pos[] = [
                'id' => 'pos_' . $id++,
                'cx' => 0.0,
                'cy' => (float) ($ri * $gap),
                'w'  => $posW, 'h' => $posH, 'angle' => 0.0,
                'grup' => $row['grup'], 'nom' => $row['nom'], 'shape' => 'rect',
            ];
        }

        $troncBottom = ($p - 1) * $gap;

        if ($this->nivell === 'tronc') {
            return $pos;
        }

        // ─ Mans per braç ─
        $mansPerBrac = max(2, (int) ceil($p * 0.8));
        $bracAngles  = $this->getBracAngles($n);

        foreach ($bracAngles as $ai => $armDeg) {
            $armRad = deg2rad($armDeg);
            for ($mi = 1; $mi <= $mansPerBrac; $mi++) {
                $r    = $mi * $gap * 1.3 + $gap * 0.5;
                $nom  = 'mans ' . $mi . ($n > 1 ? ' ' . $this->bracLabel($ai, $n) : '');
                $pos[] = [
                    'id' => 'pos_' . $id++,
                    'cx' => round($r * cos($armRad), 1),
                    'cy' => round($r * sin($armRad) + $troncBottom + $gap, 1),
                    'w'  => $posW, 'h' => $posH,
                    'angle' => round($armDeg + 90, 1),
                    'grup' => 'mans', 'nom' => $nom, 'shape' => 'rect',
                ];
            }
        }

        // ─ Baixos (rengle / buida / plena) ─
        $baixData = $this->getBaixosData($n, $troncBottom + $gap);
        foreach ($baixData as $b) {
            $pos[] = array_merge(['id' => 'pos_' . $id++, 'w' => $posW, 'h' => $posH, 'shape' => 'rect'], $b);
        }

        // ─ Cordo (anell exterior) ─
        $cordoCount = max(8, $n * ($p + 3));
        $cordoR     = ($mansPerBrac + 1.5) * $gap * 1.3 + $gap * 2;
        $cordoCY    = $troncBottom + $gap * 2.5;
        for ($ci = 0; $ci < $cordoCount; $ci++) {
            $ang = ($ci / $cordoCount) * 2 * M_PI;
            $pos[] = [
                'id' => 'pos_' . $id++,
                'cx' => round($cordoR * cos($ang), 1),
                'cy' => round($cordoR * sin($ang) + $cordoCY, 1),
                'w'  => $posW, 'h' => $posH,
                'angle' => round(rad2deg($ang) + 90.0, 1),
                'grup' => 'cordo', 'nom' => 'cordo ' . ($ci + 1), 'shape' => 'rect',
            ];
        }

        // ─ Vent (el·lipses, si hi ha folre) ─
        if (in_array($this->nivell, ['folre', 'manilles'])) {
            $ventCount = max(6, $n * $p);
            $ventR     = $cordoR + $gap * 2.5;
            $ventCY    = $cordoCY;
            for ($vi = 0; $vi < $ventCount; $vi++) {
                $ang = ($vi / $ventCount) * 2 * M_PI;
                $pos[] = [
                    'id' => 'pos_' . $id++,
                    'cx' => round($ventR * cos($ang), 1),
                    'cy' => round($ventR * sin($ang) + $ventCY, 1),
                    'w'  => 30, 'h' => 19,
                    'angle' => round(rad2deg($ang), 1),
                    'grup' => 'vent', 'nom' => 'vent ' . ($vi + 1), 'shape' => 'rect',
                ];
            }
        }

        return $pos;
    }

    private function familiaToBaixos(): int
    {
        return match ($this->familia) {
            'pilar'  => 1,
            'torre'  => 2,
            'tres'   => 3,
            'quatre' => 4,
            'cinc'   => 5,
            'set'    => 7,
            'nou'    => 9,
            default  => 3,
        };
    }

    private function getTroncPositions(): array
    {
        $n    = $this->familiaToBaixos();
        $p    = $this->pisos;
        $uid  = (string) now()->getTimestampMs();
        $ang  = (float) $this->defaultAngle;

        $posW = $this->defaultW;   // w=100 → dimensió visual vertical (angle=-90°)
        $posH = $this->defaultH;   // h=30  → dimensió visual horitzontal (angle=-90°)

        // Quan angle=-90°, la figura és h d'ample × w d'alt visualment.
        // colSpacing basat en posH (amplada visual); levelGap basat en posW (alçada visual).
        $colSpacing = (int) round($posH * 1.93);  // per h=30 → ~58px
        $levelGap   = (int) round($posW * 1.10);  // per w=100 → ~110px

        $colCx = [];
        for ($g = 0; $g < $n; $g++) {
            $colCx[$g] = ($g - ($n - 1) / 2.0) * $colSpacing;
        }

        $levels    = $this->buildTroncLevels($p);
        $numLevels = count($levels);
        $pos       = [];
        $id        = 0;

        // Nivell inferior (segs.) just per sobre y=0; la resta puja de $levelGap en $levelGap
        $cyBase = -(int) round($posH * 1.7);   // per h=30 → -51 ≈ -50 (ref. id=26)

        foreach ($levels as $li => $level) {
            $cy = (float) ($cyBase - ($numLevels - 1 - $li) * $levelGap);

            switch ($level['type']) {
                case 'single':
                    $pos[] = $this->makePos("t_{$id}_{$uid}", 0.0, $cy,
                        $posW, $posH, $ang, $level['grup'], $level['nom'], $level['ordre'] ?? null);
                    $id++;
                    break;

                case 'dosos':
                    $ordre = $level['ordre'] ?? null;
                    if ($n === 1) {
                        // Pilar: 1 sol dos, centrat
                        $pos[] = $this->makePos("t_{$id}_{$uid}", 0.0, $cy,
                            $posW, $posH, $ang, $level['grup'], 'dos', $ordre);
                        $id++;
                    } else {
                        // Torre/Tres/Quatre: 2 dosos simètrics
                        $dx = $colSpacing / 2.0;
                        $pos[] = $this->makePos("t_{$id}_{$uid}", -$dx, $cy,
                            $posW, $posH, $ang, $level['grup'], 'dos D', $ordre);
                        $id++;
                        $pos[] = $this->makePos("t_{$id}_{$uid}", +$dx, $cy,
                            $posW, $posH, $ang, $level['grup'], 'dos E', $ordre);
                        $id++;
                    }
                    break;

                case 'multi':
                    $ordre = $level['ordre'] ?? null;
                    for ($g = 0; $g < $n; $g++) {
                        $label  = $this->bracLabel($g, $n);
                        $suffix = $label !== '' ? ' ' . $label : '';
                        $pos[]  = $this->makePos("t_{$id}_{$uid}", $colCx[$g], $cy,
                            $posW, $posH, $ang, $level['grup'],
                            $level['nom'] . $suffix, $ordre);
                        $id++;
                    }
                    break;
            }
        }

        return $pos;
    }

    private function buildTroncLevels(int $p): array
    {
        // Pom de dalt ocupa els 3 pisos superiors: p=enxaneta, p-1=acotxador, p-2=dos
        $levels = [
            ['type' => 'single', 'grup' => 'enxaneta',  'nom' => 'enxaneta',  'ordre' => $p],
            ['type' => 'single', 'grup' => 'acotxador', 'nom' => 'acotxador', 'ordre' => $p - 1],
            ['type' => 'dosos',  'grup' => 'dosos',      'nom' => 'dosos',     'ordre' => $p - 2],
        ];
        // Pisos intermedis: de p-3 fins a 2 (pis 1 = baix, és a la base)
        for ($pis = $p - 3; $pis >= 2; $pis--) {
            $grup = match (true) {
                $pis === 2 => 'segon',
                $pis === 3 => 'terc',
                default    => 'quart',
            };
            $nom = match ($pis) {
                2 => 'segs.', 3 => 'terços', 4 => 'quarts',
                5 => 'quints', 6 => 'sisens', 7 => 'setens',
                default => $pis . 'ns',
            };
            $levels[] = ['type' => 'multi', 'grup' => $grup, 'nom' => $nom, 'ordre' => $pis];
        }
        return $levels;
    }

    private function buildTroncLabels(int $p): array
    {
        $rows = [
            ['grup' => 'enxaneta',  'nom' => 'enxaneta'],
            ['grup' => 'acotxador', 'nom' => 'acotxador'],
        ];
        for ($pis = $p - 2; $pis >= 2; $pis--) {
            $grup = match (true) {
                $pis === 2 => 'segon',
                $pis === 3 => 'terc',
                default    => 'quart',
            };
            $nom = match ($pis) {
                2 => 'segs.', 3 => 'terços', 4 => 'quarts',
                5 => 'quints', 6 => 'sisens', 7 => 'setens',
                default => $pis . 'ns',
            };
            $rows[] = ['grup' => $grup, 'nom' => $nom];
        }
        $rows[] = ['grup' => 'contrafort', 'nom' => 'contrafort'];
        $rows[] = ['grup' => 'rengle',     'nom' => 'rengle'];
        return $rows;
    }

    private function getBracAngles(int $n): array
    {
        return match ($n) {
            1 => [90],
            2 => [45, 135],
            3 => [25, 90, 155],
            4 => [20, 65, 115, 160],
            default => array_map(fn ($i) => 180 / ($n + 1) * ($i + 1), range(0, $n - 1)),
        };
    }

    // ── Construcció pas a pas ────────────────────────────────────────────────────

    // Angles del triangle equilàter del Tres (en Konva: y cap avall)
    // rengla=270° (cap amunt), buida D=30°, plena E=150°
    private function getBaixBracAngles(int $n): array
    {
        return match ($n) {
            1 => [270],
            2 => [0, 180],
            3 => [30, 150, 270],
            4 => [0, 90, 180, 270],
            default => array_map(fn ($i) => (int) round(360 / $n * $i + 270) % 360, range(0, $n - 1)),
        };
    }

    private function getBaixArmConfig(): array
    {
        $n           = $this->familiaToBaixos();
        $p           = $this->pisos;
        $gap         = 20;
        $bracAngles  = $this->getBaixBracAngles($n);
        $mansPerBrac = max(2, (int) ceil($p * 0.8));
        $baixR       = ($n === 1)
            ? $gap * 0.75  // Pilar: rengla molt propera al centre (calibrat versió 15)
            : max($gap * 3.0, $mansPerBrac * $gap * 1.3 - $gap * 1.6);

        // Ordre: primer els que van cap avall (D, E), després el que va cap amunt (C/rengla)
        $baixNames = match ($n) {
            1 => [['grup' => 'rengle', 'nom' => 'rengla']],
            2 => [['grup' => 'buida',  'nom' => 'buida D'], ['grup' => 'plena', 'nom' => 'plena E']],
            3 => [['grup' => 'buida',  'nom' => 'buida D'], ['grup' => 'plena', 'nom' => 'plena E'], ['grup' => 'rengle', 'nom' => 'rengla']],
            4 => [['grup' => 'buida',  'nom' => 'buida D'], ['grup' => 'rengle', 'nom' => 'rengla'], ['grup' => 'plena', 'nom' => 'plena E'], ['grup' => 'rengle', 'nom' => 'rengla E']],
            default => array_map(fn ($i) => ['grup' => 'rengle', 'nom' => 'rengla ' . ($i + 1)], range(0, $n - 1)),
        };

        // Labels per als mans: D, E, C (centre/rengla)
        $bracLabels = match ($n) {
            2 => ['D', 'E'],
            3 => ['D', 'E', 'C'],
            4 => ['D', 'C', 'E', 'E2'],
            default => null,
        };

        $arms = [];
        foreach ($bracAngles as $ai => $armDeg) {
            $armRad  = deg2rad($armDeg);
            $perpRad = $armRad + M_PI / 2;
            $arms[]  = [
                'deg'     => $armDeg,
                'rad'     => $armRad,
                'perpRad' => $perpRad,
                'r'       => $baixR,
                'bx'      => round($baixR * cos($armRad), 1),
                'by'      => round($baixR * sin($armRad), 1),
                'angle'   => round($armDeg + 90, 1),
                'grup'    => $baixNames[$ai]['grup'],
                'nom'     => $baixNames[$ai]['nom'],
                'label'   => $bracLabels[$ai] ?? $this->bracLabel($ai, $n),
            ];
        }
        return $arms;
    }

    // ── Generador de Folre i Manilles ────────────────────────────────────────────

    private function getFolrePositions(float $scale = 1.0): array
    {
        $n   = $this->familiaToBaixos();
        $uid = (string) now()->getTimestampMs();
        $ph  = max(15, $this->defaultH);
        $pw  = max(34, $this->defaultW);
        $pos = [];

        // Radis calibrats des de templates reals:
        //   n=4 (Quatre de 7 folre): r_cross=55, r_baix=105, r2=155, r3=205
        //   n=3 (Tres de 8 folre):   r_cross=55, r_baix=95,  r2=130, r3=155
        [$r_cross_base, $r_baix_base, $r2_base, $r3_base] = match ($n) {
            4       => [55, 105, 155, 205],
            3       => [55,  95, 130, 155],
            default => [55,  80, 120, 160],
        };
        $r_cross = (int) round($r_cross_base * $scale);
        $r_baix  = (int) round($r_baix_base  * $scale);
        $r2      = (int) round($r2_base      * $scale);
        $r3      = (int) round($r3_base      * $scale);

        $armAngles = $this->getBaixBracAngles($n);

        // Centre: agulles (únicament en el folre, no en manilles)
        if ($scale <= 1.0) {
            $pos[] = $this->makePos("f_ag_{$uid}", 0.0, 0.0, $pw, $ph, 0.0, 'agulla', 'agulles', 1);
        }

        // Anell interior: baixos als eixos (r_baix), crosses entre eixos (r_cross)
        if ($n === 1) {
            // Pilar: 1 baix + 3 crosses formant creu
            $armDeg = (float) $armAngles[0];
            $cx = round($r_baix * cos(deg2rad($armDeg)), 1);
            $cy = round($r_baix * sin(deg2rad($armDeg)), 1);
            $pos[] = $this->makePos("f_baix_0_{$uid}", $cx, $cy, $pw, $ph, fmod($armDeg + 90, 360), 'rengle', 'baix', 1);
            foreach ([0, 90, 180] as $j => $extraDeg) {
                $cx = round($r_cross * cos(deg2rad((float) $extraDeg)), 1);
                $cy = round($r_cross * sin(deg2rad((float) $extraDeg)), 1);
                $pos[] = $this->makePos("f_cr_{$j}_{$uid}", $cx, $cy, $pw, $ph, fmod($extraDeg + 90, 360), 'porta', 'crossa ' . ($j + 1), 1);
            }
        } else {
            // Torre/Tres/Quatre: baixos als eixos (r_baix), crosses entre eixos (r_cross)
            foreach ($armAngles as $i => $armDeg) {
                $armDeg = (float) $armDeg;
                $cx = round($r_baix * cos(deg2rad($armDeg)), 1);
                $cy = round($r_baix * sin(deg2rad($armDeg)), 1);
                $label = $this->bracLabel($i, $n) !== '' ? 'baix ' . $this->bracLabel($i, $n) : 'baixos';
                $pos[] = $this->makePos("f_baix_{$i}_{$uid}", $cx, $cy, $pw, $ph, fmod($armDeg + 90, 360), 'rengle', $label, 1);
            }
            for ($i = 0; $i < $n; $i++) {
                $a1 = (float) $armAngles[$i];
                $a2 = (float) $armAngles[($i + 1) % $n];
                $midDeg = $this->midAngle($a1, $a2);
                $cx = round($r_cross * cos(deg2rad($midDeg)), 1);
                $cy = round($r_cross * sin(deg2rad($midDeg)), 1);
                $label = match ($i) { 0 => 'crosses D', 1 => 'crosses E', default => 'crossa ' . ($i + 1) };
                $pos[] = $this->makePos("f_cr_{$i}_{$uid}", $cx, $cy, $pw, $ph, fmod($midDeg + 90, 360), 'porta', $label, 1);
            }
        }

        // Anell mig: laterals
        if ($this->anells >= 2) {
            $totalLats = $n * 2;
            for ($i = 0; $i < $totalLats; $i++) {
                $deg    = fmod((360.0 / $totalLats) * $i + (180.0 / $totalLats), 360.0);
                $cx     = round($r2 * cos(deg2rad($deg)), 1);
                $cy     = round($r2 * sin(deg2rad($deg)), 1);
                $suffix = $i % 2 === 0 ? 'D' : 'E';
                $num    = (int) floor($i / 2) + 1;
                $pos[] = $this->makePos("f_lat_{$i}_{$uid}", $cx, $cy, $pw, $ph, fmod($deg + 90, 360), 'lateral', "lateral {$num} {$suffix}", 2);
            }
        }

        // Anell exterior: mans altes
        if ($this->anells >= 2) {
            $mansN = max(6, $n * 3);
            for ($i = 0; $i < $mansN; $i++) {
                $deg = fmod((360.0 / $mansN) * $i, 360.0);
                $cx  = round($r3 * cos(deg2rad($deg)), 1);
                $cy  = round($r3 * sin(deg2rad($deg)), 1);
                $pos[] = $this->makePos("f_mans_{$i}_{$uid}", $cx, $cy, $pw, $ph, fmod($deg + 90, 360), 'mans_altes', 'mans-altes', 3);
            }
        }

        return $pos;
    }

    private function midAngle(float $a1, float $a2): float
    {
        $diff = fmod($a2 - $a1 + 360.0, 360.0);
        return fmod($a1 + $diff / 2.0, 360.0);
    }

    private function makePos(string $id, float $cx, float $cy, int $w, int $h, float $angle, string $grup, string $nom, ?int $ordre = null): array
    {
        $pos = [
            'id' => $id, 'cx' => $cx, 'cy' => $cy,
            'w' => $w, 'h' => $h, 'angle' => $angle,
            'grup' => $grup, 'nom' => $nom,
            'shape' => 'rect',
        ];
        if ($ordre !== null) {
            $pos['ordre'] = $ordre;
        }
        return $pos;
    }

    private function getStepPositions(int $step): array
    {
        $arms  = $this->getBaixArmConfig();
        $gap   = 20;
        $posW  = $this->defaultW;
        $posH  = $this->defaultH;
        $pos   = [];
        $uid   = (string) now()->getTimestampMs();

        switch ($step) {
            case 1: // Baixos — orientats radialment cap al centre
                foreach ($arms as $ai => $arm) {
                    $pos[] = $this->makePos("s1_{$ai}_{$uid}", $arm['bx'], $arm['by'],
                        $posW, $posH, $arm['angle'], $arm['grup'], $arm['nom']);
                }
                break;

            case 2: // Agulles — perpendiculars al braç, a mansPerBrac*gap*1.3 del centre
                foreach ($arms as $ai => $arm) {
                    $r = round($arm['r'] - $gap * 2.0, 1);
                    $pos[] = $this->makePos("s2_{$ai}_{$uid}",
                        round($r * cos($arm['rad']), 1),
                        round($r * sin($arm['rad']), 1),
                        100, 30,
                        $arm['angle'],    // perpendicular al braç (igual que els baixos)
                        'agulla', 'agulla ' . $arm['label']);
                }
                break;

            case 3: // Contraforts (cap al tronc)
                foreach ($arms as $ai => $arm) {
                    $r = round($arm['r'] + $gap * 2.0, 1);
                    $pos[] = $this->makePos("s3_{$ai}_{$uid}",
                        round($r * cos($arm['rad']), 1),
                        round($r * sin($arm['rad']), 1),
                        $posW, $posH, $arm['angle'], 'contrafort', 'contrafort ' . $arm['label']);
                }
                break;

            case 4: // Croses — al nivell del baix+gap*0.4, desplaçades gap×3.6 perpendicular
                foreach ($arms as $ai => $arm) {
                    $rAg   = $arm['r'] + $gap * 0.4;
                    $dPerp = $gap * 3.6;
                    $ax    = round($rAg * cos($arm['rad']), 1);
                    $ay    = round($rAg * sin($arm['rad']), 1);
                    $pos[] = $this->makePos("s4d_{$ai}_{$uid}",
                        round($ax + $dPerp * cos($arm['perpRad']), 1),
                        round($ay + $dPerp * sin($arm['perpRad']), 1),
                        $posW, $posH, $arm['deg'], 'contrafort', 'crossa D ' . $arm['label']);
                    $pos[] = $this->makePos("s4e_{$ai}_{$uid}",
                        round($ax - $dPerp * cos($arm['perpRad']), 1),
                        round($ay - $dPerp * sin($arm['perpRad']), 1),
                        $posW, $posH, $arm['deg'], 'contrafort', 'crossa E ' . $arm['label']);
                }
                break;

            case 5: // Portacroses — offset relatiu al baix de cada braç, rotat per l'angle del braç
                // Calibrat: Tres de 6 (v10) + Pilar de 6 (v15) → offset consistent respecte al baix
                $refDx = -108.8; $refDy = -23.1;  // offset D: ±perp, -along_arm
                $refEx =  112.8; $refEy = -35.3;  // offset E: +12.2 along arm vs D
                foreach ($arms as $ai => $arm) {
                    $rotRad = deg2rad($arm['deg'] - 270);
                    $cosR   = cos($rotRad);
                    $sinR   = sin($rotRad);
                    $pdx = round($arm['bx'] + $refDx * $cosR - $refDy * $sinR, 1);
                    $pdy = round($arm['by'] + $refDx * $sinR + $refDy * $cosR, 1);
                    $pex = round($arm['bx'] + $refEx * $cosR - $refEy * $sinR, 1);
                    $pey = round($arm['by'] + $refEx * $sinR + $refEy * $cosR, 1);
                    $pos[] = $this->makePos("s5d_{$ai}_{$uid}", $pdx, $pdy,
                        $posW, $posH, $arm['deg'], 'porta', 'porta D ' . $arm['label']);
                    $pos[] = $this->makePos("s5e_{$ai}_{$uid}", $pex, $pey,
                        $posW, $posH, $arm['deg'], 'porta', 'porta E ' . $arm['label']);
                }
                break;

            case 6: // Mans — del contrafort cap enfora, tants com pisos
                foreach ($arms as $ai => $arm) {
                    $startR = $arm['r'] + $gap * 4;  // just beyond contrafort
                    for ($mi = 1; $mi <= $this->pisos; $mi++) {
                        $r = $startR + ($mi - 1) * $gap * 2;
                        $pos[] = $this->makePos("s6m_{$ai}_{$mi}_{$uid}",
                            round($r * cos($arm['rad']), 1),
                            round($r * sin($arm['rad']), 1),
                            $posW, $posH, $arm['angle'], 'mans', 'mans ' . $mi . ' ' . $arm['label']);
                    }
                }
                break;

            case 7: // Vents — a la bisectriu entre braços adjacents
                $n           = count($arms);
                $mansPerBrac = max(2, (int) ceil($this->pisos * 0.8));
                $ventArmR    = $mansPerBrac * $gap * 1.3 + $gap * 2.0;
                // Pilar (n=1): vents al costat oposat al braç, just passat l'agulla
                $startR = ($n === 1)
                    ? $gap * 4.0 - $arms[0]['r']       // 4*gap - arm_r ≈ 65 (calibrat v15)
                    : $ventArmR + $gap * sqrt(2.0);    // ≈198 per Tres de 6
                $ventPer  = $this->pisos;
                for ($ai = 0; $ai < $n; $ai++) {
                    $d1 = $arms[$ai]['deg'];
                    $d2 = $arms[($ai + 1) % $n]['deg'];
                    if ($d2 <= $d1) $d2 += 360;
                    $midDeg   = ($d1 + $d2) / 2.0;
                    $midRad   = deg2rad($midDeg);
                    $midAngle = fmod($midDeg + 90.0, 360.0);
                    for ($vi = 1; $vi <= $ventPer; $vi++) {
                        $r = $startR + ($vi - 1) * $gap * 2;
                        $pos[] = $this->makePos("s7_{$ai}_{$vi}_{$uid}",
                            round($r * cos($midRad), 1),
                            round($r * sin($midRad), 1),
                            $posW, $posH, $midAngle, 'vent', 'vent ' . $vi . ' ' . $arms[$ai]['label']);
                    }
                }
                break;

            case 8:  // Cordons 1 —  6 pos
            case 9:  // Cordons 2 — 12 pos
            case 10: // Cordons 3 — 18 pos
            case 11: // Cordons 4 — 24 pos
            case 12: // Cordons 5 — 30 pos
            case 13: // Cordons 6 — 36 pos
            case 14: // Cordons 7 — 42 pos
            case 15: // Cordons 8 — 48 pos
            case 16: // Cordons 9 — 54 pos
                // Cordó N: N posicions/costat a k×interDeg/(2×(N+1)), k=1..N
                $cordNum     = $step - 7;
                $n           = count($arms);
                $mansPerBrac = max(2, (int) ceil($this->pisos * 0.8));
                $ventArmR    = $mansPerBrac * $gap * 1.3 + $gap * 2.0;
                $ventBase    = ($n === 1)
                    ? $gap * 4.0 - $arms[0]['r'] - $gap * sqrt(2.0)  // base equivalent a n=1
                    : $ventArmR;
                $startR      = $ventBase + $gap * (sqrt(2.0) + 2.0 * $cordNum);
                $interDeg    = 360.0 / $n;
                for ($ai = 0; $ai < $n; $ai++) {
                    $armDeg = $arms[$ai]['deg'];
                    $sides  = [
                        ['sign' =>  1, 'adj' => $arms[($ai + 1) % $n]['label'], 'key' => 'D'],
                        ['sign' => -1, 'adj' => $arms[($ai - 1 + $n) % $n]['label'], 'key' => 'E'],
                    ];
                    foreach ($sides as $s) {
                        for ($k = 1; $k <= $cordNum; $k++) {
                            $offset    = $k * $interDeg / (2.0 * ($cordNum + 1));
                            $cordDeg   = $armDeg + $s['sign'] * $offset;
                            $cordRad   = deg2rad($cordDeg);
                            $cordAngle = fmod($cordDeg + 90.0, 360.0);
                            if ($cordAngle > 180.0) $cordAngle -= 360.0;
                            $pos[] = $this->makePos("s{$step}_{$ai}_{$s['key']}{$k}_{$uid}",
                                round($startR * cos($cordRad), 1),
                                round($startR * sin($cordRad), 1),
                                (int) round($posW * 0.8), $posH, $cordAngle, 'cordo',
                                'cordo ' . $arms[$ai]['label'] . ' ' . $s['adj'] . ' ' . $cordNum);
                        }
                    }
                }
                break;
        }

        return $pos;
    }

    private function getBaixosData(int $n, float $baseCY): array
    {
        $r  = 90.0;
        $r2 = 130.0;
        return match ($n) {
            1 => [
                ['cx' => 0.0,        'cy' => $baseCY + 20, 'angle' => 0.0,   'grup' => 'rengle', 'nom' => 'rengle'],
                ['cx' => 0.0,        'cy' => $baseCY + 40, 'angle' => 0.0,   'grup' => 'buida',  'nom' => 'buida D'],
                ['cx' => 0.0,        'cy' => $baseCY + 60, 'angle' => 0.0,   'grup' => 'plena',  'nom' => 'plena D'],
            ],
            2 => [
                ['cx' =>  0.0,       'cy' => $baseCY + 20, 'angle' =>  0.0,  'grup' => 'rengle', 'nom' => 'rengle'],
                ['cx' =>  $r * .6,   'cy' => $baseCY + 35, 'angle' => 55.0,  'grup' => 'buida',  'nom' => 'buida D'],
                ['cx' => -$r * .6,   'cy' => $baseCY + 35, 'angle' => 125.0, 'grup' => 'buida',  'nom' => 'buida E'],
                ['cx' =>  $r,        'cy' => $baseCY + 55, 'angle' => 55.0,  'grup' => 'plena',  'nom' => 'plena D'],
                ['cx' => -$r,        'cy' => $baseCY + 55, 'angle' => 125.0, 'grup' => 'plena',  'nom' => 'plena E'],
            ],
            3 => [
                ['cx' =>  0.0,       'cy' => $baseCY + 20, 'angle' =>  0.0,  'grup' => 'rengle', 'nom' => 'rengle'],
                ['cx' =>  $r * .5,   'cy' => $baseCY + 38, 'angle' => 40.0,  'grup' => 'buida',  'nom' => 'buida D'],
                ['cx' => -$r * .5,   'cy' => $baseCY + 38, 'angle' => 140.0, 'grup' => 'buida',  'nom' => 'buida E'],
                ['cx' =>  $r2 * .55, 'cy' => $baseCY + 60, 'angle' => 35.0,  'grup' => 'plena',  'nom' => 'plena D'],
                ['cx' => -$r2 * .55, 'cy' => $baseCY + 60, 'angle' => 145.0, 'grup' => 'plena',  'nom' => 'plena E'],
            ],
            4 => [
                ['cx' =>  0.0,       'cy' => $baseCY + 20, 'angle' =>  0.0,  'grup' => 'rengle', 'nom' => 'rengle'],
                ['cx' =>  $r * .45,  'cy' => $baseCY + 35, 'angle' => 35.0,  'grup' => 'buida',  'nom' => 'buida D1'],
                ['cx' => -$r * .45,  'cy' => $baseCY + 35, 'angle' => 145.0, 'grup' => 'buida',  'nom' => 'buida E1'],
                ['cx' =>  $r * .8,   'cy' => $baseCY + 50, 'angle' => 50.0,  'grup' => 'buida',  'nom' => 'buida D2'],
                ['cx' => -$r * .8,   'cy' => $baseCY + 50, 'angle' => 130.0, 'grup' => 'buida',  'nom' => 'buida E2'],
                ['cx' =>  $r2 * .6,  'cy' => $baseCY + 68, 'angle' => 30.0,  'grup' => 'plena',  'nom' => 'plena D'],
                ['cx' => -$r2 * .6,  'cy' => $baseCY + 68, 'angle' => 150.0, 'grup' => 'plena',  'nom' => 'plena E'],
            ],
            default => [],
        };
    }

    private function bracLabel(int $idx, int $total): string
    {
        if ($total === 1) return '';
        return ['D', 'C', 'E', 'D2'][$idx] ?? (string) ($idx + 1);
    }

    // ── Configuració de categories per colla ─────────────────────────────────
    public function getCatSettings(): array
    {
        $colla    = auth()->user()?->colla;
        $saved    = $colla?->board_settings['cat_settings'] ?? [];
        $defaults = [
            // La Pinya
            'rengle'     => ['label' => 'Baix',       'color' => '#e0e7ff', 'shape' => 'rect'],
            'agulla'     => ['label' => 'Agulla',     'color' => '#c7d2fe', 'shape' => 'rect'],
            'contrafort' => ['label' => 'Contrafort', 'color' => '#fef3c7', 'shape' => 'rect'],
            'cordo'      => ['label' => 'Cordó',      'color' => '#f1f5f9', 'shape' => 'rect'],
            'mans'       => ['label' => 'Mans',       'color' => '#bfdbfe', 'shape' => 'rect'],
            'porta'      => ['label' => 'Crossa',     'color' => '#ffedd5', 'shape' => 'rect'],
            'vent'       => ['label' => 'Vent',       'color' => '#bbf7d0', 'shape' => 'ellipse'],
            // El Tronc
            'segon'      => ['label' => 'Segon',      'color' => '#fce7f3', 'shape' => 'rect'],
            'terc'       => ['label' => 'Terç',       'color' => '#fbcfe8', 'shape' => 'rect'],
            'quart'      => ['label' => 'Quart+',     'color' => '#f9a8d4', 'shape' => 'rect'],
            // El Pom de Dalt
            'dos'        => ['label' => 'Dos',        'color' => '#fef9c3', 'shape' => 'rect'],
            'acotxador'  => ['label' => 'Acotxador',  'color' => '#fef08a', 'shape' => 'rect'],
            'enxaneta'   => ['label' => 'Enxaneta',   'color' => '#fde047', 'shape' => 'circle'],
            // Auxiliar
            'etiqueta'   => ['label' => 'Etiqueta',   'color' => 'transparent', 'shape' => 'label'],
            // Backward compat
            'buida'      => ['label' => 'Buida',      'color' => '#c7d2fe', 'shape' => 'rect'],
            'plena'      => ['label' => 'Plena',      'color' => '#a5b4fc', 'shape' => 'rect'],
        ];
        return array_merge($defaults, $saved);
    }

    public function saveCatSettings(array $settings, array $custom = []): void
    {
        $colla = auth()->user()?->colla;
        if (!$colla) return;
        $bs = $colla->board_settings ?? [];
        $bs['cat_settings'] = $settings;
        if (!empty($custom)) $bs['cat_custom'] = $custom;
        $colla->update(['board_settings' => $bs]);

        Notification::make()->title('Categories desades')->success()->send();
    }

    /** @deprecated Use getCatSettings() */
    public function getCatLabels(): array
    {
        return array_map(fn($cfg) => $cfg['label'], $this->getCatSettings());
    }

    // ── Llista i càrrega de plantilles existents ─────────────────────────────
    public function getTemplatesList(): array
    {
        return CastleTemplate::where('colla_id', auth()->user()?->colla_id)
            ->where('ready', true)
            ->orderBy('name')
            ->get(['id', 'name', 'positions'])
            ->map(fn ($t) => [
                'id'    => $t->id,
                'name'  => $t->name,
                'count' => count($t->positions ?? []),
            ])
            ->toArray();
    }

    public function loadTemplate(int $id): void
    {
        $template = CastleTemplate::where('id', $id)
            ->where('colla_id', auth()->user()?->colla_id)
            ->firstOrFail();

        $this->templateName = $template->name;
        $this->dispatch('template-regenerate', positions: $template->positions ?? []);
    }

    // ── Desa la plantilla (cridat des de JS via Livewire dispatch) ────────────
    #[On('template-save')]
    public function saveTemplate(string $name, string $positionsJson): void
    {
        $collaId   = auth()->user()?->colla_id;
        $name      = trim($name) ?: $this->getCastellName();
        $key       = \Illuminate\Support\Str::slug($name);
        $decoded = json_decode($positionsJson, true) ?? [];

        // Detecta si és format multi-vista { base:[...], tronc:[...] } o pla [...]
        if (array_is_list($decoded)) {
            $positions = ['base' => $decoded];
        } else {
            $positions = array_filter($decoded, fn($v) => is_array($v) && !empty($v));
        }

        $totalCount = array_sum(array_map('count', $positions));
        if ($totalCount === 0) {
            Notification::make()->title('Cap posició al canvas')->warning()->send();
            return;
        }

        $template = CastleTemplate::updateOrCreate(
            ['colla_id' => $collaId, 'key' => $key],
            ['name' => $name, 'positions' => $positions, 'ready' => true]
        );

        $template->saveVersion('Wizard ' . now()->format('d/m/Y H:i'));

        Notification::make()
            ->title('Plantilla desada: ' . $template->name . ' (' . $totalCount . ' posicions)')
            ->success()
            ->send();
    }
}
