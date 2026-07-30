<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Colla;
use App\Models\CastleTemplate;
use App\Models\Event;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class CreateColla extends Command
{
    protected $signature = 'colla:create
        {nom : Nom de la colla}
        {num_castellers : Nombre de castellers a crear}
        {--shortname= : Shortname (per defecte: derivat del nom)}
        {--city=Barcelona : Ciutat de la colla}
        {--email= : Email admin (per defecte: admin@{shortname}.test)}';

    protected $description = 'Crea una colla amb castellers, events, rondes, respostes i notícies de mostra';

    private const ALIASES = [
        'LLOP','GUINEU','ÓS','TEIXÓ','ERMINI','VISÓ','LLÚDRIA','GAT',
        'GOS','CAVALL','VACA','PORC','OVELLA','CABRA','CÉRVOL','DAINA',
        'ISARD','CABIROL','SENGLAR','CONILL','LLEBRE','RATOLÍ','RATA',
        'ESQUIROL','LIRÓ','TALP','RATPENAT','DOFÍ','ORCA','BALENA',
        'CATXALOT','FOCA','ELEFANT','RINOCERONT','HIPOPÒTAM',
        'GIRAFA','LLEOPARD','JAGUAR','PANTERA','TIGRE','LLEÓ','BABUÍ',
        'ÀGUILA','FALCÓ','MILÀ','ALIGOT','VOLTOR','TRENCALÒS','ÒLIBA',
        'MUSSOL','DUC','CORB','GRAULA','GAIG','GARSA','ORENETA',
        'FALCIOT','COTXA','PIT-ROIG','PARDAL','CADERNERA','VERDUM',
        'PICOT','AGRÓ','CIGONYA','GRUA','ÀNEC','OCA','CIGNE',
        'PERDIU','GUATLLA','FAISÀ','GALL','GALLINA','FLAMENC','GAVIÀ',
        'SERP','COLOBRA','ESCURÇÓ','DRAGÓ','SARGANTANA','CAMALEÓ',
        'COCODRIL','TORTUGA','GRANOTA','GRIPAU','SALAMANDRA','TRITÓ',
        'TRUITA','SALMÓ','CARPA','TAURÓ','SARDINA','ANXOVA','LLOBARRO',
        'PÍ','AVELLANER','ALZINA','SURERA','ROURE','FAIG','CASTANYER',
        'NOGUER','OLIVERA','AMETLLER','MAGRANER','FIGUERA','PRUNERA',
        'CIRERER','PRESSEGUER','ALBERCOQUER','TARONGER','LLIMONER',
        'POMER','PERERA','ACÀCIA','PLÀTAN','OLM','SALZE','XIPRER',
        'PALMERA','AVET','ULLASTRE','LLORER','MURTA','GINESTA','BRUC',
        'VERN','FREIXE','SABINA','GINEBRE','CARXOFA','ESPÀRREC','BLEDA',
        'BRÒQUIL','COL','COLIFLOR','API','ESCAROLA','ESPINAC',
        'FONOLL','MONGETA','PÈSOL','LLETUGA','RAVE','NAP','CEBA','ALL',
        'CARABASSA','COGOMBRE','PEBROT','TOMÀQUET','PATATA',
        'PASTANAGA','RÚCULA','MENTA','JULIVERT','FARIGOLA','ALFÀBREGA',
        'BANANA','PINYA','MANGO','KIWI','ALVOCAT','RAÏM',
        'MADUIXA','GERD','MORA','NABIU','CIRESA','PRÉSSEC',
        'ALBERCOC','TARONJA','POMA','PERA','MAGRANA','FIGA',
        'NOU','PISTATX','CACAUET',
    ];
    

    private const NOMS_MASCULINS = ['Pau','Marc','Jordi','Miquel','Andreu','Pere','Joan','Sergi','Albert','Guillem','Arnau','Oriol','Francesc','Xavier','David'];
    private const NOMS_FEMENINS  = ['Laia','Marta','Anna','Neus','Clara','Nuria','Meritxell','Rosa','Aina','Alba','Laura','Silvia','Montserrat','Teresa','Julia'];
    private const COGNOMS        = ['Garcia','Martinez','Lopez','Sanchez','Gonzalez','Perez','Rodriguez','Fernandez','Puig','Roca','Vila','Soler','Font','Mas','Costa'];

    public function handle(): int
    {
        $nom           = $this->argument('nom');
        $numCastellers = (int) $this->argument('num_castellers');
        $shortname     = $this->option('shortname') ?? $this->deriveShortname($nom);
        $city          = $this->option('city');
        $adminEmail    = $this->option('email') ?? "admin@{$shortname}.test";

        $adminPassword  = env('SEEDER_ADMIN_PASSWORD')
            ?? throw new \RuntimeException('SEEDER_ADMIN_PASSWORD no definit al .env');
        $memberPassword = env('SEEDER_USER_PASSWORD')
            ?? throw new \RuntimeException('SEEDER_USER_PASSWORD no definit al .env');

        if (Colla::where('shortname', $shortname)->exists()) {
            $this->error("Ja existeix una colla amb shortname '{$shortname}'.");
            return self::FAILURE;
        }

        $this->info("Creant colla '{$nom}' ({$shortname})...");

        // Colla (CollaObserver crea les castle_templates automàticament)
        $colla = Colla::create([
            'name'         => $nom,
            'shortname'    => $shortname,
            'email'        => $adminEmail,
            'city'         => $city,
            'country'      => 'ES',
            'gdpr_version' => '1.0',
            'gdpr_text'    => Colla::defaultGdprText($nom, $adminEmail),
        ]);
        $this->line("  colla       id={$colla->id}");

        // Admin
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        User::create([
            'name'     => "Admin {$nom}",
            'email'    => $adminEmail,
            'password' => Hash::make($adminPassword),
            'colla_id' => $colla->id,
        ])->assignRole($role);
        $this->line("  admin       {$adminEmail}");

        // Castellers
        $aliases = $this->buildAliases($numCastellers);
        $this->seedCastellers($colla, $memberPassword, $aliases);
        $this->line("  castellers  {$numCastellers}");

        // Periods
        $this->seedPeriods($colla);
        $this->line('  periods     2');

        // Tags
        [$castellerTagIds, $eventTagIds] = $this->seedTags($colla);
        $this->line('  tags        ' . (count($castellerTagIds) + count($eventTagIds)));

        // casteller_tag
        $castellerIds = DB::table('castellers')->where('colla_id', $colla->id)->pluck('id')->toArray();
        $this->seedCastellerTags($castellerIds, $castellerTagIds);
        $this->line('  casteller_tag');

        // Events
        $eventIds = $this->seedEvents($colla);
        $this->line('  events      ' . count($eventIds));

        // event_tag
        $this->seedEventTags($eventIds, $eventTagIds);
        $this->line('  event_tag');

        // Attendance
        $this->seedAttendance($colla, $eventIds);
        $this->line('  attendance');

        // Questions + event_question + event_answers
        $questionId = $this->seedQuestionsAndLink($colla, $eventIds);
        $this->line('  questions + event_question + event_answers');

        // Board snapshots (rondes incloses)
        $this->seedBoardSnapshots($colla, $eventIds, $aliases);
        $this->line('  board_snapshots (amb rondes)');

        // Noticies
        $this->seedNoticies($colla, $nom);
        $this->line('  noticies    3');

        // Resum plantilles (creades per observer)
        $templates = CastleTemplate::where('colla_id', $colla->id)->count();
        $this->line("  castle_templates  {$templates} (via observer)");

        $this->newLine();
        $this->info("Colla '{$nom}' creada correctament.");
        $this->line("  Panel admin:  /admin/{$shortname}/");
        $this->line("  Portal membre: /member/{$shortname}/");
        $this->line("  Admin:  {$adminEmail} / SEEDER_ADMIN_PASSWORD");
        $this->line("  Membres: alias@{$shortname}.test / SEEDER_USER_PASSWORD");

        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function deriveShortname(string $nom): string
    {
        $map = ['à'=>'a','á'=>'a','è'=>'e','é'=>'e','í'=>'i','ï'=>'i',
                'ò'=>'o','ó'=>'o','ú'=>'u','ü'=>'u','ç'=>'c','·'=>'',
                'À'=>'a','Á'=>'a','È'=>'e','É'=>'e','Í'=>'i','Ï'=>'i',
                'Ò'=>'o','Ó'=>'o','Ú'=>'u','Ü'=>'u','Ç'=>'c'];
        $nom = strtr($nom, $map);
        return strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($nom)));
    }

    private function buildAliases(int $count): array
    {
        $pool   = self::ALIASES;
        $result = array_slice($pool, 0, min($count, count($pool)));
        $i = 1;
        while (count($result) < $count) {
            $result[] = $pool[($i - 1) % count($pool)] . $i;
            $i++;
        }
        return $result;
    }

    // -------------------------------------------------------------------------
    // Seeders individuals
    // -------------------------------------------------------------------------

    private function seedPeriods(Colla $colla): void
    {
        $year = (int) date('Y');
        // Si estem a la primera meitat de l'any, la temporada actual va de l'any passat
        $startYear = date('n') >= 9 ? $year : $year - 1;

        DB::table('periods')->insert([
            [
                'colla_id'   => $colla->id,
                'name'       => "Temporada {$startYear}-" . ($startYear + 1),
                'start_date' => "{$startYear}-09-01",
                'end_date'   => ($startYear + 1) . '-07-31',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'colla_id'   => $colla->id,
                'name'       => 'Temporada ' . ($startYear - 1) . "-{$startYear}",
                'start_date' => ($startYear - 1) . '-09-01',
                'end_date'   => "{$startYear}-07-31",
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    private function seedTags(Colla $colla): array
    {
        $now = now()->toDateTimeString();

        $castellerTags = [
            ['name' => 'Baix',    'group' => 'Posicio', 'type' => 'CASTELLERS'],
            ['name' => 'Segundo', 'group' => 'Posicio', 'type' => 'CASTELLERS'],
            ['name' => 'Terç',    'group' => 'Posicio', 'type' => 'CASTELLERS'],
            ['name' => 'Quart',   'group' => 'Posicio', 'type' => 'CASTELLERS'],
            ['name' => 'Canalla', 'group' => 'Grup',    'type' => 'CASTELLERS'],
            ['name' => 'Grallers','group' => 'Grup',    'type' => 'CASTELLERS'],
        ];

        $eventTags = [
            ['name' => 'Diada',      'group' => 'Tipus', 'type' => 'EVENTS'],
            ['name' => 'Concurs',    'group' => 'Tipus', 'type' => 'EVENTS'],
            ['name' => 'Solidaria',  'group' => 'Tipus', 'type' => 'EVENTS'],
        ];

        $castellerTagIds = [];
        foreach ($castellerTags as $tag) {
            $castellerTagIds[] = DB::table('tags')->insertGetId(array_merge(
                $tag, ['colla_id' => $colla->id, 'created_at' => $now, 'updated_at' => $now]
            ));
        }

        $eventTagIds = [];
        foreach ($eventTags as $tag) {
            $eventTagIds[] = DB::table('tags')->insertGetId(array_merge(
                $tag, ['colla_id' => $colla->id, 'created_at' => $now, 'updated_at' => $now]
            ));
        }

        return [$castellerTagIds, $eventTagIds];
    }

    private function seedCastellerTags(array $castellerIds, array $tagIds): void
    {
        if (empty($castellerIds) || empty($tagIds)) return;

        $rows = [];
        foreach ($castellerIds as $i => $castellerId) {
            // Assigna 1-2 tags per casteller de forma determinista
            $rows[] = ['casteller_id' => $castellerId, 'tag_id' => $tagIds[$i % count($tagIds)]];
            if ($i % 3 === 0) {
                $nextTag = $tagIds[($i + 2) % count($tagIds)];
                // Evita duplicats
                if ($nextTag !== $tagIds[$i % count($tagIds)]) {
                    $rows[] = ['casteller_id' => $castellerId, 'tag_id' => $nextTag];
                }
            }
        }

        // insertOrIgnore per si hi hagués duplicats
        DB::table('casteller_tag')->insertOrIgnore($rows);
    }

    private function seedEventTags(array $eventIds, array $tagIds): void
    {
        if (empty($eventIds) || empty($tagIds)) return;

        $rows = [];
        foreach ($eventIds as $i => $eventId) {
            $rows[] = ['event_id' => $eventId, 'tag_id' => $tagIds[$i % count($tagIds)]];
        }

        DB::table('event_tag')->insertOrIgnore($rows);
    }

    private function seedCastellers(Colla $colla, string $password, array $aliases): void
    {
        $hashedPassword = Hash::make($password);
        $now            = now()->toDateTimeString();
        $shortname      = $colla->shortname;

        $statusPool = [
            ...array_fill(0, 65, 1),
            ...array_fill(0, 15, 2),
            ...array_fill(0, 10, 4),
            ...array_fill(0, 10, 3),
        ];

        $rows = [];
        foreach ($aliases as $i => $alias) {
            $isMale  = ($i % 2 === 0);
            $nomPool = $isMale ? self::NOMS_MASCULINS : self::NOMS_FEMENINS;

            $rows[] = [
                'colla_id'        => $colla->id,
                'alias'           => $alias,
                'name'            => $nomPool[$i % count($nomPool)],
                'last_name'       => self::COGNOMS[$i % 15] . ' ' . self::COGNOMS[($i + 5) % 15],
                'email'           => strtolower($alias) . '@' . $shortname . '.test',
                'password'        => $hashedPassword,
                'birthdate'       => date('Y-m-d', mktime(0, 0, 0, ($i % 12) + 1, ($i % 28) + 1, 1970 + ($i % 42) + 18)),
                'status'          => $statusPool[$i % count($statusPool)],
                'height'          => 155 + ($i % 41),
                'weight'          => 50 + ($i % 61),
                'shoulder_height' => 130 + ($i % 46),
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }

        DB::table('castellers')->insert($rows);
    }

    private function seedEvents(Colla $colla): array
    {
        $now  = now();
        $now2 = $now->toDateTimeString();

        $defs = [
            ['name' => 'Assaig ' . $now->copy()->subWeeks(4)->format('d/m/Y'), 'type' => 1,
             'start_date' => $now->copy()->subWeeks(4)->setTime(20, 0),
             'open_date'  => $now->copy()->subWeeks(5),
             'close_date' => $now->copy()->subWeeks(4)->setTime(20, 0), 'companions' => false],
            ['name' => 'Assaig ' . $now->copy()->subWeeks(3)->format('d/m/Y'), 'type' => 1,
             'start_date' => $now->copy()->subWeeks(3)->setTime(20, 0),
             'open_date'  => $now->copy()->subWeeks(4),
             'close_date' => $now->copy()->subWeeks(3)->setTime(20, 0), 'companions' => false],
            ['name' => 'Assaig ' . $now->copy()->subWeeks(2)->format('d/m/Y'), 'type' => 1,
             'start_date' => $now->copy()->subWeeks(2)->setTime(20, 0),
             'open_date'  => $now->copy()->subWeeks(3),
             'close_date' => $now->copy()->subWeeks(2)->setTime(20, 0), 'companions' => false],
            ['name' => 'Actuació Festa Major ' . $colla->city, 'type' => 2,
             'start_date' => $now->copy()->subWeeks(5)->setTime(18, 0),
             'open_date'  => $now->copy()->subWeeks(7),
             'close_date' => $now->copy()->subWeeks(5)->setTime(18, 0), 'companions' => true],
            ['name' => 'Assaig ' . $now->copy()->addWeek()->format('d/m/Y'), 'type' => 1,
             'start_date' => $now->copy()->addWeek()->setTime(20, 0),
             'open_date'  => $now->copy()->subDays(3),
             'close_date' => $now->copy()->addDays(5), 'companions' => false],
            ['name' => 'Actuació Diada Castellera', 'type' => 2,
             'start_date' => $now->copy()->addWeeks(3)->setTime(11, 0),
             'open_date'  => $now->copy()->addDays(2),
             'close_date' => $now->copy()->addWeeks(2), 'companions' => true],
        ];

        $rows = array_map(fn ($d) => array_merge($d, [
            'colla_id'   => $colla->id,
            'visibility' => true,
            'created_at' => $now2,
            'updated_at' => $now2,
        ]), $defs);

        DB::table('events')->insert($rows);

        return Event::where('colla_id', $colla->id)->pluck('id')->toArray();
    }

    private function seedAttendance(Colla $colla, array $eventIds): void
    {
        $now        = now()->toDateTimeString();
        $castellers = DB::table('castellers')
            ->where('colla_id', $colla->id)
            ->where('status', 1)
            ->pluck('id')
            ->toArray();

        $events = Event::whereIn('id', $eventIds)->get();

        foreach ($events as $event) {
            $isPast = $event->start_date->isPast();
            $rows   = [];

            foreach ($castellers as $i => $castellerId) {
                $responseRate = $isPast ? 85 : 55;
                if (($i * 7 + $event->id) % 100 >= $responseRate) continue;

                $roll   = ($i * 13 + $event->id) % 100;
                $status = $isPast
                    ? ($roll < 75 ? 'ok' : 'nok')
                    : ($roll < 70 ? 'ok' : ($roll < 90 ? 'nok' : 'unknown'));

                // Events passats: ~80% dels 'ok' han fet check-in físic
                $checkedIn   = null;
                $checkedInAt = null;
                if ($isPast && $status === 'ok') {
                    $checkedIn = ($i * 11 + $event->id) % 10 < 8;
                    if ($checkedIn) {
                        $checkedInAt = $event->start_date->copy()
                            ->subMinutes(30)
                            ->addMinutes(($i * 7) % 45)
                            ->toDateTimeString();
                    }
                }

                $rows[] = [
                    'event_id'      => $event->id,
                    'casteller_id'  => $castellerId,
                    'status'        => $status,
                    'companions'    => ($event->companions && $status === 'ok') ? ($i % 3) : 0,
                    'checked_in'    => $checkedIn,
                    'checked_in_at' => $checkedInAt,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }

            if ($rows) DB::table('attendance')->insert($rows);
        }
    }

    private function seedQuestionsAndLink(Colla $colla, array $eventIds): void
    {
        $now = now()->toDateTimeString();

        // Pregunta estàndard per a events futurs amb acompanyants
        $questionId = DB::table('questions')->insertGetId([
            'colla_id'   => $colla->id,
            'text'       => 'Vindràs amb cotxe i pots portar gent?',
            'type'       => 'radio',
            'options'    => json_encode(['Sí, tinc plaça', 'No tinc cotxe', 'Vindré en transport públic']),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Assigna la pregunta als events futurs (index 4 i 5 — addWeek i addWeeks(3))
        $futureEventIds = array_slice($eventIds, 4);
        foreach ($futureEventIds as $order => $eventId) {
            DB::table('event_question')->insertOrIgnore([
                'event_id'    => $eventId,
                'question_id' => $questionId,
                'required'    => false,
                'order'       => $order,
            ]);
        }

        // Respostes per als assistents 'ok' d'aquests events
        foreach ($futureEventIds as $eventId) {
            $attendanceRows = DB::table('attendance')
                ->where('event_id', $eventId)
                ->where('status', 'ok')
                ->pluck('id')
                ->toArray();

            $answerOptions = ['Sí, tinc plaça', 'No tinc cotxe', 'Vindré en transport públic'];
            $answerRows    = [];

            foreach ($attendanceRows as $i => $attendanceId) {
                $answerRows[] = [
                    'attendance_id' => $attendanceId,
                    'question_id'   => $questionId,
                    'answer'        => json_encode($answerOptions[$i % 3]),
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }

            if ($answerRows) DB::table('event_answers')->insertOrIgnore($answerRows);
        }
    }

    private function seedBoardSnapshots(Colla $colla, array $eventIds, array $aliases): void
    {
        $now = now()->toDateTimeString();

        $template = CastleTemplate::where('colla_id', $colla->id)
            ->where('key', '3d6')
            ->first();

        if (!$template || empty($template->positions)) return;

        $positions    = $template->positions;
        $activeAlias  = array_slice($aliases, 0, min(count($aliases), count($positions)));

        // Assignació determinista: alias per ordre de posició
        $makeAssignments = function (array $aliasSubset) use ($positions): array {
            $assignments = [];
            foreach (array_values($positions) as $i => $pos) {
                if (!isset($aliasSubset[$i])) break;
                $assignments[$pos['id']] = $aliasSubset[$i];
            }
            return $assignments;
        };

        // Snapshot sense event (plantilla guardada)
        DB::table('board_snapshots')->insert([
            'colla_id'     => $colla->id,
            'event_id'     => null,
            'castle_type'  => '3d6',
            'name'         => '3 de 6 — Formació tipus',
            'is_primary'   => true,
            'public_token' => (string) Str::uuid(),
            'ronda_number' => null,
            'assignments'  => json_encode($makeAssignments($activeAlias)),
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        // Per a cada event d'actuació: 2 rondes
        $actuacioIds = array_filter(
            $eventIds,
            fn ($id, $i) => in_array($i, [3, 5], true),
            ARRAY_FILTER_USE_BOTH
        );

        foreach (array_values($actuacioIds) as $eventId) {
            DB::table('board_snapshots')->insert([
                [
                    'colla_id'     => $colla->id,
                    'event_id'     => $eventId,
                    'castle_type'  => '3d6',
                    'name'         => '3 de 6',
                    'is_primary'   => false,
                    'public_token' => (string) Str::uuid(),
                    'ronda_number' => 1,
                    'assignments'  => json_encode($makeAssignments($activeAlias)),
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ],
                [
                    'colla_id'     => $colla->id,
                    'event_id'     => $eventId,
                    'castle_type'  => '3d6',
                    'name'         => '3 de 6 (B)',
                    'is_primary'   => false,
                    'public_token' => (string) Str::uuid(),
                    'ronda_number' => 2,
                    'assignments'  => json_encode($makeAssignments(array_reverse($activeAlias))),
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ],
            ]);
        }
    }

    private function seedNoticies(Colla $colla, string $nomColla): void
    {
        $now = now()->toDateTimeString();

        DB::table('noticies')->insert([
            [
                'colla_id'     => $colla->id,
                'tag_id'       => null,
                'title'        => "Benvinguts a FemPinya — {$nomColla}",
                'summary'      => 'La vostra colla ja és al sistema. Podeu gestionar castellers, events i assistència.',
                'body'         => "<p>Hola a tothom!</p><p>Ja teniu accés al nou sistema de gestió de <strong>{$nomColla}</strong>. Des d'aquí podreu:</p><ul><li>Gestionar els castellers</li><li>Crear i gestionar events</li><li>Fer el seguiment d'assistència</li><li>Preparar les pinyes des del board editor</li></ul><p>Qualsevol dubte, contacteu amb l'administrador.</p>",
                'labels'       => json_encode(['benvinguda', 'inici']),
                'version'      => '1.0',
                'is_public'    => true,
                'published_at' => $now,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'colla_id'     => $colla->id,
                'tag_id'       => null,
                'title'        => 'Com confirmar assistència a un event',
                'summary'      => 'Guia ràpida per confirmar o denegar assistència als events de la colla.',
                'body'         => "<p>Per confirmar la teva assistència a un event:</p><ol><li>Obre l'app o accedeix al portal membre</li><li>Ves a la secció <strong>Events</strong></li><li>Selecciona l'event i prem <em>Confirmo assistència</em></li></ol><p>Recorda que tens fins a la data de tancament per canviar la teva resposta.</p>",
                'labels'       => json_encode(['guia', 'assistencia']),
                'version'      => null,
                'is_public'    => true,
                'published_at' => $now,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'colla_id'     => $colla->id,
                'tag_id'       => null,
                'title'        => 'Board Editor — prepara les pinyes',
                'summary'      => 'El Board Editor permet assignar castellers a posicions i gestionar les rondes.',
                'body'         => "<p>La tècnica pot preparar les pinyes abans de cada actuació:</p><ul><li>Obre l'event al panell d'administració</li><li>Accedeix al <strong>Board Editor</strong></li><li>Assigna castellers a les posicions arrossegant o clicant</li><li>Desa la pissarra i assigna'l a una ronda</li></ul>",
                'labels'       => json_encode(['board', 'tecnica']),
                'version'      => null,
                'is_public'    => false,
                'published_at' => $now,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
        ]);
    }
}
