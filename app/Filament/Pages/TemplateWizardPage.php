<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\CastleTemplate;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\On;

class TemplateWizardPage extends Page
{
    protected static string $view = 'filament.pages.template-wizard';

    protected static ?string $navigationLabel = 'Nova plantilla';

    protected static ?string $slug = 'wizard-plantilla';

    protected static \UnitEnum|string|null $navigationGroup = 'Pissarra';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return auth()->user()?->canDo('generador') ?? false;
    }

    // ── Wizard state ──
    public int    $step   = 1;
    public string $familia = 'tres';
    public int    $pisos   = 6;
    public string $nivell  = 'base';
    public string $templateName = '';

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
        return ['pilar' => 'Pilar', 'torre' => 'Torre',
                'tres'  => 'Tres',  'quatre' => 'Quatre'][$this->familia] ?? ucfirst($this->familia);
    }

    public function getFamilies(): array
    {
        return ['pilar' => 'Pilar', 'torre' => 'Torre', 'tres' => 'Tres', 'quatre' => 'Quatre'];
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
            'quatre' => 4,
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
