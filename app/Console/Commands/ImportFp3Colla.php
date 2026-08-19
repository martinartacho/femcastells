<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\BoardSnapshot;
use App\Models\CastleTemplate;
use App\Models\Casteller;
use App\Models\Colla;
use App\Models\Event;
use App\Models\Tag;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportFp3Colla extends Command
{
    protected $signature = 'fp3:import {shortname : Shortname de la colla a FP3} {--dry-run : Simula la importació sense escriure a la BD}';

    protected $description = 'Importa una colla de FP3 a FP4 (colles, castellers, events, assistència, tags)';

    private bool $dryRun;
    private array $idMap = [
        'colla'     => [],
        'casteller' => [],
        'event'     => [],
        'tag'       => [],
        'board'     => [],
    ];
    private array $stats = [
        'colles'           => 0,
        'castellers'       => 0,
        'events'           => 0,
        'attendance'       => 0,
        'tags'             => 0,
        'castle_templates' => 0,
        'board_snapshots'  => 0,
    ];

    public function handle(): int
    {
        $shortname      = $this->argument('shortname');
        $this->dryRun   = (bool) $this->option('dry-run');

        if ($this->dryRun) {
            $this->warn('⚠  DRY-RUN actiu — no s\'escriurà res a la BD de FP4');
        }

        $this->info("Connectant a FP3 (BD: " . config('database.connections.mysql_fp3.database') . ")...");

        $fp3Colla = DB::connection('mysql_fp3')
            ->table('colles')
            ->where('shortname', $shortname)
            ->first();

        if (! $fp3Colla) {
            $this->error("Colla '{$shortname}' no trobada a FP3.");
            return self::FAILURE;
        }

        $this->info("Colla trobada: {$fp3Colla->name}");
        $this->newLine();

        $this->importColla($fp3Colla);
        $this->importTags($fp3Colla);
        $this->importCastellers($fp3Colla);
        $this->importEvents($fp3Colla);
        $this->importAttendance($fp3Colla);
        $this->importBoards($fp3Colla);
        $this->importCastellerTags($fp3Colla);
        $this->importEventTags($fp3Colla);

        $this->newLine();
        $this->printSummary();

        return self::SUCCESS;
    }

    private function importColla(object $fp3Colla): void
    {
        $this->info('→ Colla...');

        $data = [
            'name'      => $fp3Colla->name,
            'shortname' => $fp3Colla->shortname,
            'email'     => $fp3Colla->email,
            'phone'     => $fp3Colla->phone,
            'city'      => $fp3Colla->city,
            'country'   => $this->mapCountry($fp3Colla->country),
            'logo'      => $fp3Colla->logo,
            'color'     => $fp3Colla->color,
        ];

        if (! $this->dryRun) {
            $fp4Colla = Colla::updateOrCreate(['shortname' => $fp3Colla->shortname], $data);
            $this->idMap['colla'][$fp3Colla->id_colla] = $fp4Colla->id;
        } else {
            $this->idMap['colla'][$fp3Colla->id_colla] = 0;
        }

        $this->stats['colles']++;
        $this->line("   ✓ {$fp3Colla->name}");
    }

    private function importTags(object $fp3Colla): void
    {
        $this->info('→ Tags...');

        $fp3Tags = DB::connection('mysql_fp3')
            ->table('tags')
            ->where('colla_id', $fp3Colla->id_colla)
            ->whereIn('type', ['CASTELLERS', 'EVENTS', 'BOARDS', 'POSITIONS'])
            ->get();

        $fp4CollaId = $this->idMap['colla'][$fp3Colla->id_colla];

        foreach ($fp3Tags as $fp3Tag) {
            $data = [
                'colla_id' => $fp4CollaId,
                'name'     => $fp3Tag->name,
                'value'    => $fp3Tag->value,
                'group'    => $fp3Tag->group,
                'type'     => $fp3Tag->type,
            ];

            if (! $this->dryRun) {
                $fp4Tag = Tag::updateOrCreate(
                    ['colla_id' => $fp4CollaId, 'name' => $fp3Tag->name, 'type' => $fp3Tag->type],
                    $data
                );
                $this->idMap['tag'][$fp3Tag->id_tag] = $fp4Tag->id;
            } else {
                $this->idMap['tag'][$fp3Tag->id_tag] = 0;
            }

            $this->stats['tags']++;
        }

        $this->line("   ✓ {$this->stats['tags']} tags");
    }

    private function importCastellers(object $fp3Colla): void
    {
        $this->info('→ Castellers...');

        $fp3Castellers = DB::connection('mysql_fp3')
            ->table('castellers')
            ->where('colla_id', $fp3Colla->id_colla)
            ->get();

        $fp4CollaId = $this->idMap['colla'][$fp3Colla->id_colla];

        foreach ($fp3Castellers as $c) {
            $data = [
                'colla_id'          => $fp4CollaId,
                'alias'             => $c->alias,
                'name'              => $c->name,
                'last_name'         => $c->last_name,
                'email'             => $c->email,
                'email2'            => $c->email2 ?? null,
                'phone'             => $c->phone,
                'mobile_phone'      => $c->mobile_phone ?? null,
                'emergency_phone'   => $c->emergency_phone ?? null,
                'birthdate'         => $c->birthdate,
                'subscription_date' => $c->subscription_date ?? null,
                'gender'            => $c->gender ?? null,
                'status'            => $c->status,
                'height'            => $c->height ? (int) round($c->height) : null,
                'weight'            => $c->weight ? (int) round($c->weight) : null,
                'shoulder_height'   => $c->shoulder_height ? (int) round($c->shoulder_height) : null,
                'photo'             => $c->photo,
                'comments'          => $c->comments,
                'num_soci'          => is_numeric($c->num_soci) ? (int) $c->num_soci : null,
                'national_id'       => $c->national_id_number ?? null,
                'national_id_type'  => $c->national_id_type ?? null,
                'nationality'       => $c->nationality ?? null,
                'address'           => $c->address ?? null,
                'postal_code'       => $c->postal_code ?? null,
                'city'              => $c->city ?? null,
                'province'          => $c->province ?? null,
                'country'           => $c->country ?? null,
                'family'            => $c->family ?? null,
                'family_head'       => isset($c->family_head) ? (bool) $c->family_head : null,
            ];

            if (! $this->dryRun) {
                $fp4Casteller = Casteller::updateOrCreate(
                    ['colla_id' => $fp4CollaId, 'alias' => $c->alias],
                    $data
                );
                $this->idMap['casteller'][$c->id_casteller] = $fp4Casteller->id;
            } else {
                $this->idMap['casteller'][$c->id_casteller] = 0;
            }

            $this->stats['castellers']++;
        }

        $this->line("   ✓ {$this->stats['castellers']} castellers");
    }

    private function importEvents(object $fp3Colla): void
    {
        $this->info('→ Events...');

        $fp3Events = DB::connection('mysql_fp3')
            ->table('events')
            ->where('colla_id', $fp3Colla->id_colla)
            ->get();

        $fp4CollaId = $this->idMap['colla'][$fp3Colla->id_colla];

        foreach ($fp3Events as $e) {
            $data = [
                'colla_id'   => $fp4CollaId,
                'name'       => $e->name,
                'type'       => $e->type,
                'start_date' => $e->start_date,
                'open_date'  => $e->open_date,
                'close_date' => $e->close_date,
                'duration'   => $e->duration,
                'address'    => $e->address,
                'visibility' => (bool) $e->visibility,
                'companions' => (bool) $e->companions,
                'comments'   => $e->comments,
            ];

            if (! $this->dryRun) {
                $fp4Event = Event::updateOrCreate(
                    ['colla_id' => $fp4CollaId, 'name' => $e->name, 'start_date' => $e->start_date],
                    $data
                );
                $this->idMap['event'][$e->id_event] = $fp4Event->id;
            } else {
                $this->idMap['event'][$e->id_event] = 0;
            }

            $this->stats['events']++;
        }

        $this->line("   ✓ {$this->stats['events']} events");
    }

    private function importAttendance(object $fp3Colla): void
    {
        $this->info('→ Assistència...');

        $statusMap = [1 => 'ok', 2 => 'nok', 3 => 'unknown'];

        $fp3Attendance = DB::connection('mysql_fp3')
            ->table('attendance')
            ->whereIn('event_id', array_keys($this->idMap['event']))
            ->get();

        foreach ($fp3Attendance as $a) {
            $fp4EventId     = $this->idMap['event'][$a->event_id] ?? null;
            $fp4CastellerId = $this->idMap['casteller'][$a->casteller_id] ?? null;

            if (! $fp4EventId || ! $fp4CastellerId) {
                continue;
            }

            $data = [
                'event_id'     => $fp4EventId,
                'casteller_id' => $fp4CastellerId,
                'status'       => $statusMap[$a->status] ?? 'unknown',
                'companions'   => $a->companions ?? 0,
            ];

            if (! $this->dryRun) {
                Attendance::updateOrCreate(
                    ['event_id' => $fp4EventId, 'casteller_id' => $fp4CastellerId],
                    $data
                );
            }

            $this->stats['attendance']++;
        }

        $this->line("   ✓ {$this->stats['attendance']} registres d'assistència");
    }

    private function importCastellerTags(object $fp3Colla): void
    {
        $this->info('→ Tags de castellers...');

        if ($this->dryRun) {
            $this->line('   (dry-run: omès)');
            return;
        }

        $pivots = DB::connection('mysql_fp3')
            ->table('casteller_tag')
            ->whereIn('casteller_id', array_keys($this->idMap['casteller']))
            ->get();

        $count = 0;
        foreach ($pivots as $pivot) {
            $fp4CastellerId = $this->idMap['casteller'][$pivot->casteller_id] ?? null;
            $fp4TagId       = $this->idMap['tag'][$pivot->tag_id] ?? null;

            if (! $fp4CastellerId || ! $fp4TagId) {
                continue;
            }

            DB::table('casteller_tag')->updateOrInsert(
                ['casteller_id' => $fp4CastellerId, 'tag_id' => $fp4TagId]
            );
            $count++;
        }

        $this->line("   ✓ {$count} vincles casteller-tag");
    }

    private function importEventTags(object $fp3Colla): void
    {
        $this->info('→ Tags d\'events...');

        if ($this->dryRun) {
            $this->line('   (dry-run: omès)');
            return;
        }

        $pivots = DB::connection('mysql_fp3')
            ->table('event_tag')
            ->whereIn('event_id', array_keys($this->idMap['event']))
            ->get();

        $count = 0;
        foreach ($pivots as $pivot) {
            $fp4EventId = $this->idMap['event'][$pivot->event_id] ?? null;
            $fp4TagId   = $this->idMap['tag'][$pivot->tag_id] ?? null;

            if (! $fp4EventId || ! $fp4TagId) {
                continue;
            }

            DB::table('event_tag')->updateOrInsert(
                ['event_id' => $fp4EventId, 'tag_id' => $fp4TagId]
            );
            $count++;
        }

        $this->line("   ✓ {$count} vincles event-tag");
    }

    private function importBoards(object $fp3Colla): void
    {
        $this->info('→ Boards (plantilles i snapshots)...');

        $fp4CollaId = $this->idMap['colla'][$fp3Colla->id_colla];

        // Mapa fp4_casteller_id → alias per construir assignments en format {pos_id: alias}
        $aliasById = Casteller::where('colla_id', $fp4CollaId)->pluck('alias', 'id')->toArray();

        $fp3Boards = DB::connection('mysql_fp3')
            ->table('boards')
            ->where('colla_id', $fp3Colla->id_colla)
            ->get();

        foreach ($fp3Boards as $board) {
            $templateKey = 'fp3_' . $board->id_board;

            $rows = DB::connection('mysql_fp3')
                ->table('rows')
                ->where('board_id', $board->id_board)
                ->orderBy('id')
                ->get()
                ->keyBy('id');

            $positions = $rows->map(fn ($r) => [
                'id'       => 'fp3_' . $r->id,
                'position' => $r->position,
                'cord'     => (int) $r->cord,
                'side'     => $r->side ?? '',
                'div_id'   => $r->div_id,
                'row'      => $r->row,
                'base'     => $r->base,
            ])->values()->toArray();

            if (! $this->dryRun) {
                $template = CastleTemplate::updateOrCreate(
                    ['colla_id' => $fp4CollaId, 'name' => $board->name],
                    [
                        'key'       => $templateKey,
                        'positions' => $positions,
                        'ready'     => true,
                    ]
                );
                $this->idMap['board'][$board->id_board] = $template->id;
            } else {
                $this->idMap['board'][$board->id_board] = 0;
            }

            $this->stats['castle_templates']++;

            $boardEvents = DB::connection('mysql_fp3')
                ->table('board_event')
                ->where('board_id', $board->id_board)
                ->get();

            foreach ($boardEvents as $be) {
                $fp4EventId = $this->idMap['event'][$be->event_id] ?? null;
                if (! $fp4EventId) {
                    continue;
                }

                $bpRows = DB::connection('mysql_fp3')
                    ->table('board_position')
                    ->where('board_event_id', $be->id)
                    ->get();

                // Format FP4: {"fp3_{row_id}": "alias"}
                $assignments = [];
                foreach ($bpRows as $bp) {
                    $fp4CastellerId = $this->idMap['casteller'][$bp->casteller_id] ?? null;
                    $alias = $fp4CastellerId ? ($aliasById[$fp4CastellerId] ?? null) : null;
                    $row   = $rows[$bp->row_id] ?? null;
                    if (! $alias || ! $row) {
                        continue;
                    }
                    $assignments['fp3_' . $row->id] = $alias;
                }

                if (! $this->dryRun) {
                    BoardSnapshot::updateOrCreate(
                        ['colla_id' => $fp4CollaId, 'event_id' => $fp4EventId, 'castle_type' => $templateKey],
                        [
                            'name'        => $board->name,
                            'castle_type' => $templateKey,
                            'assignments' => $assignments,
                            'is_primary'  => true,
                        ]
                    );
                }

                $this->stats['board_snapshots']++;
            }
        }

        $this->line("   ✓ {$this->stats['castle_templates']} plantilles, {$this->stats['board_snapshots']} snapshots");
    }

    private function mapCountry(?string $country): string
    {
        if (! $country) {
            return 'ES';
        }

        $map = [
            'españa' => 'ES', 'espanya' => 'ES', 'spain' => 'ES',
            'france' => 'FR', 'frança' => 'FR',
            'andorra' => 'AD',
            'portugal' => 'PT',
        ];

        return $map[mb_strtolower(trim($country))] ?? 'ES';
    }

    private function printSummary(): void
    {
        $mode = $this->dryRun ? '(DRY-RUN)' : '';
        $this->info("Resum {$mode}:");
        $this->table(
            ['Entitat', 'Registres'],
            collect($this->stats)->map(fn ($v, $k) => [$k, $v])->values()->toArray()
        );

        if ($this->dryRun) {
            $this->warn('Cap dada ha estat escrita. Executa sense --dry-run per importar.');
        } else {
            $this->info('Importació completada.');
        }
    }
}
