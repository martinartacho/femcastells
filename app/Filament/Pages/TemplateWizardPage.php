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

    public static function canAccess(): bool
    {
        return auth()->user()?->canDo('generador') ?? false;
    }

    public function getView(): string
    {
        return 'filament.pages.template-wizard';
    }

    // ── Wizard state ──
    public int    $step   = 1;
    public string $familia = 'tres';
    public int    $pisos   = 6;
    public string $nivell  = 'base';
    public string $templateName = '';
    public int    $defaultW    = 100;
    public int    $defaultH    = 30;

    // ── Step navigation ──
    public function generate(): void
    {
        $this->templateName = $this->getCastellName();
        $this->step = 2;
    }

    public function back(): void
    {
        $this->step = 1;
    }

    public function regenerate(): void
    {
        $this->templateName = $this->getCastellName();
        $this->dispatch('template-regenerate', positions: $this->getInitialPositions());
    }

    public function addStep(int $step): void
    {
        $positions = $this->getStepPositions($step);
        if (!empty($positions)) {
            $this->dispatch('template-add-positions', positions: $positions);
        }
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
                    'grup' => 'vent', 'nom' => 'vent ' . ($vi + 1), 'shape' => 'ellipse',
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

    private function buildTroncLabels(int $p): array
    {
        $rows = [
            ['grup' => 'agulla', 'nom' => 'agulla'],
            ['grup' => 'mans',   'nom' => 'acotxador'],
        ];
        for ($pis = $p - 2; $pis >= 2; $pis--) {
            $rows[] = ['grup' => 'mans', 'nom' => match ($pis) {
                2 => 'segs.', 3 => 'terços', 4 => 'quarts',
                5 => 'quints', 6 => 'sisens', 7 => 'setens',
                default => $pis . 'ns',
            }];
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
            2 => [30, 150],
            3 => [30, 150, 270],
            4 => [20, 90, 160, 270],
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
        $baixR       = max($gap * 3.0, $mansPerBrac * $gap * 1.3 - $gap * 1.6);

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
            3 => ['D', 'E', 'C'],
            4 => ['D', 'C', 'E', 'E2'],
            default => null, // usa bracLabel genèric
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

    private function makePos(string $id, float $cx, float $cy, int $w, int $h, float $angle, string $grup, string $nom): array
    {
        return [
            'id' => $id, 'cx' => $cx, 'cy' => $cy,
            'w' => $w, 'h' => $h, 'angle' => $angle,
            'grup' => $grup, 'nom' => $nom,
            'shape' => $grup === 'vent' ? 'ellipse' : 'rect',
        ];
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

            case 5: // Portacroses — referència del braç C (270°), rotada per a cada braç
                // Referència calibrada (versió 10): porta D C = (-108.8,-121.1), porta E C = (112.8,-133.3)
                $refDx = -108.8; $refDy = -121.1;
                $refEx =  112.8; $refEy = -133.3;
                foreach ($arms as $ai => $arm) {
                    $rotRad = deg2rad($arm['deg'] - 270);
                    $cosR   = cos($rotRad);
                    $sinR   = sin($rotRad);
                    $pdx = round($refDx * $cosR - $refDy * $sinR, 1);
                    $pdy = round($refDx * $sinR + $refDy * $cosR, 1);
                    $pex = round($refEx * $cosR - $refEy * $sinR, 1);
                    $pey = round($refEx * $sinR + $refEy * $cosR, 1);
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
                // Radi dels vents basat en arm_r original (sense la correcció interna):
                // els vents mantenen la distància independent de l'ajust del nucli
                $ventArmR = $mansPerBrac * $gap * 1.3 + $gap * 2.0;  // arm_r sense correcció
                $startR   = $ventArmR + $gap * sqrt(2.0);             // ≈198 per pisos=6
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

            case 8:  // Cordons 1 —  6 pos (1/costat/braç)
            case 9:  // Cordons 2 — 12 pos (2/costat/braç)
            case 10: // Cordons 3 — 18 pos (3/costat/braç)
                // Cordó N: N posicions/costat a k×interDeg/(2×(N+1)), k=1..N
                $cordNum     = $step - 7;
                $n           = count($arms);
                $mansPerBrac = max(2, (int) ceil($this->pisos * 0.8));
                $ventArmR    = $mansPerBrac * $gap * 1.3 + $gap * 2.0;
                $startR      = $ventArmR + $gap * (sqrt(2.0) + 2.0 * $cordNum);
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
                                $posW, $posH, $cordAngle, 'cordo',
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

    // ── Desa la plantilla (cridat des de JS via Livewire dispatch) ────────────
    #[On('template-save')]
    public function saveTemplate(string $name, string $positionsJson): void
    {
        $collaId   = auth()->user()?->colla_id;
        $name      = trim($name) ?: $this->getCastellName();
        $key       = \Illuminate\Support\Str::slug($name);
        $positions = json_decode($positionsJson, true) ?? [];

        if (empty($positions)) {
            Notification::make()->title('Cap posició al canvas')->warning()->send();
            return;
        }

        $template = CastleTemplate::updateOrCreate(
            ['colla_id' => $collaId, 'key' => $key],
            ['name' => $name, 'positions' => $positions, 'ready' => true]
        );

        $template->saveVersion('Wizard ' . now()->format('d/m/Y H:i'));

        Notification::make()
            ->title('Plantilla desada: ' . $template->name . ' (' . count($positions) . ' posicions)')
            ->success()
            ->send();
    }
}
