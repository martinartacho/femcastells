<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Casteller;
use App\Models\CastleTemplate;
use Illuminate\Support\Collection;

class PinyaGeneratorService
{
    // Greedy order: fill most-constrained positions first
    private const GRUP_PRIORITY = [
        'baix'         => 1,
        'baixos'       => 1,
        'Plena'        => 1,
        'Carregada'    => 1,
        'Buida'        => 1,
        'Descarregada' => 1,
        'Una'          => 1,
        'Dues'         => 1,
        'Tres'         => 1,
        'Quatre'       => 1,
        'Cinc'         => 1,
        'Sis'          => 1,
        'Set'          => 1,
        'Rengle'       => 1,
        'agulla'       => 2,
        'rengle'       => 3,
        'falca_D'      => 3,
        'falca_E'      => 3,
        'angle_D'      => 3,
        'angle_E'      => 3,
        'lateral_D'    => 4,
        'lateral_E'    => 4,
        'nucli'        => 5,
        'nucli_D'      => 5,
        'nucli_E'      => 5,
        'tronc'        => 6,
        'contrafort'   => 6,
        'laterals'     => 6,
        'mitjans'      => 6,
        'folre'        => 7,
        'manilles'     => 7,
    ];

    // Profile tag → bonus for matching grups (soft preference, not a hard rule)
    private const TAG_PREFERRED_GRUPS = [
        'Baix'    => ['baix', 'baixos', 'Plena', 'Carregada', 'Buida', 'Descarregada', 'Una', 'Dues', 'Tres', 'Quatre', 'Cinc', 'Sis', 'Set', 'Rengle'],
        'Segundo' => ['rengle', 'falca_D', 'falca_E', 'angle_D', 'angle_E', 'lateral_D', 'lateral_E'],
        'Terç'    => ['nucli', 'nucli_D', 'nucli_E', 'tronc', 'contrafort', 'laterals', 'mitjans'],
        'Quart'   => ['agulla', 'folre', 'manilles'],
        'Canalla' => ['agulla'],
    ];

    private array $config = [];

    /**
     * Generates a pinya assignment proposal.
     *
     * @return array<string, string>  positionId → castellerAlias
     */
    public function generate(int $eventId, string $castleType, int $collaId): array
    {
        $colla            = \App\Models\Colla::find($collaId);
        $this->config     = $colla?->generator_config ?? [];

        $castellers = $this->getConfirmedCastellers($eventId, $collaId);
        $positions  = $this->getTemplatePositions($castleType, $collaId);

        if ($castellers->isEmpty() || empty($positions)) {
            return [];
        }

        usort($positions, fn ($a, $b) =>
            $this->grupPriority($a['grup'] ?? '') <=> $this->grupPriority($b['grup'] ?? '')
        );

        $available   = $castellers->keyBy('alias')->toArray();
        $assignments = [];

        foreach ($positions as $pos) {
            if (empty($available)) {
                break;
            }

            $scores = [];
            foreach ($available as $alias => $casteller) {
                $scores[$alias] = $this->score($casteller, $pos['grup'] ?? '');
            }

            arsort($scores);
            $bestAlias = (string) array_key_first($scores);

            $assignments[$pos['id']] = $bestAlias;
            unset($available[$bestAlias]);
        }

        return $assignments;
    }

    private function score(array $casteller, string $grup): float
    {
        return $this->physicalScore($casteller, $grup)
             + $this->profileBonus($casteller['tags'], $grup);
    }

    private function physicalScore(array $casteller, string $grup): float
    {
        $weight = (float) ($casteller['weight']          ?? 70);
        $height = (float) ($casteller['height']          ?? 170);
        $sh     = (float) ($casteller['shoulder_height'] ?? 150);

        // Base positions
        if (in_array($grup, ['baix', 'baixos', 'Plena', 'Carregada', 'Buida', 'Descarregada',
                              'Una', 'Dues', 'Tres', 'Quatre', 'Cinc', 'Sis', 'Set', 'Rengle'], true)) {
            $cfg = $this->config['baix'] ?? [];
            $w   = (float) ($cfg['weight']   ?? 60) / 100;
            $s   = (float) ($cfg['shoulder'] ?? 40) / 100;
            $h   = (float) ($cfg['height']   ?? 0)  / 100;
            return $weight * $w + $sh * $s + $height * $h;
        }

        // Top position: lightest
        if ($grup === 'agulla') {
            $cfg  = $this->config['agulla'] ?? [];
            $base = (float) ($cfg['base']   ?? 200);
            $w    = (float) ($cfg['weight'] ?? -200) / 100;
            return max(0.0, $base + $weight * $w);
        }

        // First ring
        if (in_array($grup, ['rengle', 'falca_D', 'falca_E', 'angle_D', 'angle_E',
                              'lateral_D', 'lateral_E'], true)) {
            $cfg = $this->config['rengle'] ?? [];
            $w   = (float) ($cfg['weight']   ?? 50) / 100;
            $s   = (float) ($cfg['shoulder'] ?? 20) / 100;
            $h   = (float) ($cfg['height']   ?? 30) / 100;
            return $weight * $w + $sh * $s + $height * $h;
        }

        // Core / upper floors
        if (in_array($grup, ['nucli', 'nucli_D', 'nucli_E', 'tronc', 'contrafort',
                              'laterals', 'mitjans', 'folre', 'manilles'], true)) {
            $cfg  = $this->config['nucli'] ?? [];
            $base = (float) ($cfg['base']   ?? 150);
            $w    = (float) ($cfg['weight'] ?? -50) / 100;
            $h    = (float) ($cfg['height'] ?? 20)  / 100;
            return max(0.0, $base + $weight * $w + $height * $h);
        }

        return 50.0;
    }

    private function profileBonus(array $tags, string $grup): float
    {
        $bonus = 0.0;
        foreach ($tags as $tagName) {
            if (in_array($grup, self::TAG_PREFERRED_GRUPS[$tagName] ?? [], true)) {
                $bonus += 20.0;
            }
        }
        return $bonus;
    }

    private function grupPriority(string $grup): int
    {
        return self::GRUP_PRIORITY[$grup] ?? 99;
    }

    private function getConfirmedCastellers(int $eventId, int $collaId): Collection
    {
        // Prioritat: checked_in (verificada) → status='ok' (prevista)
        return Casteller::where('castellers.colla_id', $collaId)
            ->where('castellers.status', 1)
            ->join('attendance', function ($join) use ($eventId) {
                $join->on('attendance.casteller_id', '=', 'castellers.id')
                     ->where('attendance.event_id', '=', $eventId)
                     ->where(function ($q) {
                         $q->where('attendance.checked_in', true)
                           ->orWhere(function ($q2) {
                               $q2->whereNull('attendance.checked_in')
                                  ->where('attendance.status', 'ok');
                           });
                     });
            })
            ->with(['tags' => fn ($q) => $q->where('group', 'Posicio')])
            ->select('castellers.id', 'castellers.alias', 'castellers.weight',
                     'castellers.height', 'castellers.shoulder_height')
            ->get()
            ->map(fn ($c) => [
                'alias'           => $c->alias,
                'weight'          => $c->weight,
                'height'          => $c->height,
                'shoulder_height' => $c->shoulder_height,
                'tags'            => $c->tags->pluck('name')->toArray(),
            ]);
    }

    private function getTemplatePositions(string $castleType, int $collaId): array
    {
        $template = CastleTemplate::where('colla_id', $collaId)
            ->where('key', $castleType)
            ->first();

        if (!$template || empty($template->positions)) {
            return [];
        }

        $positions = $template->positions;

        if (isset($positions['base'])) {
            $all = [];
            foreach ($positions as $viewPositions) {
                if (is_array($viewPositions)) {
                    foreach ($viewPositions as $pos) {
                        if (isset($pos['id'])) {
                            $all[] = $pos;
                        }
                    }
                }
            }
            return $all;
        }

        return array_filter($positions, fn ($p) => isset($p['id']));
    }
}
