<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Colla;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CollaBaseSeeder extends Seeder
{
    private const PER_COLLA = 125;

    private const ALIASES = [
        'CARRU','FEDE','MONYOS','RENE','EMMA','MANSO','CIRAC','FAUST',
        'RIELLS','CESC','BEDIA','CANDELA','LEIVA','PUGES','DANI','PINO',
        'JUVANY','BALAGUER','TONI','NURI','XAVI','LAIA','MARC','ALBA',
        'JORDÀ','PUJOL','SALA','ROCA','VILA','FONT','PUIG','MAS',
        'COSTA','BOSCH','CAMPS','TORT','CLUA','VIDAL','SOLER','GARRIGA',
        'MARTA','PERE','ANNA','JOAN','ROSA','LLUC','AINA','MIQUEL',
        'SANTI','CLARA','ARNAU','NEUS','ORIOL','MERITXELL','GUILLEM',
        'MAIOL','TARDA','SERRAT','CASAS','FERRER','MIR','RIUS','PLANES',
        'GOMIS','TURULL','GRAU','PONS','BUSQUETS','CAPELL','FOLCH',
        'BATLLE','CORTÈS','DURAN','ESPELT','FABRA','GISPERT','HERRERA',
        'IBORRA','JULIÀ','LLOPIS','MATEU','NADAL','OBIOLS','PAGÈS',
        'QUERALT','RAVENTÓS','SABATER','TARRAGÓ','URGELL','VAQUER',
        'XAMMAR','YUSTE','ZAFRA','AMAT','BENET','CADENA','DALMAU',
        'ESPLUGA','FENOLL','GINEBRA','HORTA','ISART','JUTGLAR',
        'KNOBEL','LLACH','MANERA','NOLLA','OLIVA','PALMER','QUINTÀ',
        'RAFART','SALOM','TARRÉS','URTASUN','VALLÈS','WERT','XERCAVINS',
        'YANEZ','ZABALA','ARDERIU','BRUCART','CUADRAS','DÉRIA','ESPASA',
    ];

    private const NOMS_MASCULINS = ['Pau','Marc','Jordi','Miquel','Andreu','Pere','Joan','Sergi','Albert','Guillem','Arnau','Oriol','Francesc','Xavier','David'];
    private const NOMS_FEMENINS  = ['Laia','Marta','Anna','Neus','Clara','Núria','Meritxell','Rosa','Aina','Alba','Laura','Sílvia','Montserrat','Teresa','Júlia'];
    private const COGNOMS        = ['Garcia','Martínez','López','Sánchez','González','Pérez','Rodríguez','Fernández','Puig','Roca','Vila','Soler','Font','Mas','Costa'];

    // Definició de les colles — editar aquí per afegir/canviar colles de demo
    // Emails basats en shortname: admin@{shortname}.test — consistent amb castellers
    private function collesConfig(string $adminPassword): array
    {
        return [
            [
                'name'      => 'Demo Colla',
                'shortname' => 'demo',
                'email'     => 'admin@demo.test',
                'city'      => 'Barcelona',
                'country'   => 'ES',
                'admin'     => [
                    'name'     => 'Admin Demo',
                    'email'    => 'admin@demo.test',
                    'password' => $adminPassword,
                    'role'     => 'super-admin',
                ],
            ],
            [
                'name'      => 'Pitxapins',
                'shortname' => 'pitxapins',
                'email'     => 'admin@pitxapins.test',
                'city'      => 'Terrassa',
                'country'   => 'ES',
                'admin'     => [
                    'name'     => 'Admin Pitxapins',
                    'email'    => 'admin@pitxapins.test',
                    'password' => $adminPassword,
                    'role'     => 'admin',
                ],
            ],
        ];
    }

    public function run(): void
    {
        $adminPassword  = env('SEEDER_ADMIN_PASSWORD')
            ?? throw new \RuntimeException('SEEDER_ADMIN_PASSWORD no definit al .env');
        $memberPassword = env('SEEDER_USER_PASSWORD')
            ?? throw new \RuntimeException('SEEDER_USER_PASSWORD no definit al .env');

        $roles = [
            'super-admin' => Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']),
            'admin'       => Role::firstOrCreate(['name' => 'admin',       'guard_name' => 'web']),
        ];

        foreach ($this->collesConfig($adminPassword) as $config) {
            $colla = Colla::create([
                'name'         => $config['name'],
                'shortname'    => $config['shortname'],
                'email'        => $config['email'],
                'city'         => $config['city'],
                'country'      => $config['country'],
                'gdpr_version' => '1.0',
                'gdpr_text'    => Colla::defaultGdprText($config['name'], $config['email']),
            ]);

            User::create([
                'name'     => $config['admin']['name'],
                'email'    => $config['admin']['email'],
                'password' => Hash::make($config['admin']['password']),
                'colla_id' => $colla->id,
            ])->assignRole($roles[$config['admin']['role']]);

            $this->seedCastellers($colla, $memberPassword);
            $eventIds = $this->seedEvents($colla);
            $this->seedAttendance($colla, $eventIds);
        }
    }

    private function seedCastellers(Colla $colla, string $password): void
    {
        $aliases        = $this->buildAliases(self::PER_COLLA);
        $hashedPassword = Hash::make($password);
        $now            = now()->toDateTimeString();
        $shortname      = $colla->shortname;

        // Status: 65% actiu, 15% inactiu, 10% candidat, 10% baixa
        $statusPool = [
            ...array_fill(0, 65, 1),
            ...array_fill(0, 15, 2),
            ...array_fill(0, 10, 4),
            ...array_fill(0, 10, 3),
        ];

        $rows = [];
        foreach ($aliases as $i => $alias) {
            $isMale    = ($i % 2 === 0);
            $nomPool   = $isMale ? self::NOMS_MASCULINS : self::NOMS_FEMENINS;
            $cogPool   = self::COGNOMS;

            $rows[] = [
                'colla_id'        => $colla->id,
                'alias'           => $alias,
                'name'            => $nomPool[$i % count($nomPool)],
                'last_name'       => $cogPool[$i % count($cogPool)] . ' ' . $cogPool[($i + 5) % count($cogPool)],
                // Email únic: alias@shortname.test — no usa l'ID numèric de la colla
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
                // Determinístic: no rand(), usa índex per evitar sorpreses
                if (($i * 7 + $event->id) % 100 >= $responseRate) {
                    continue;
                }

                $roll   = ($i * 13 + $event->id) % 100;
                $status = $isPast
                    ? ($roll < 75 ? 'ok' : 'nok')
                    : ($roll < 70 ? 'ok' : ($roll < 90 ? 'nok' : 'unknown'));

                $rows[] = [
                    'event_id'     => $event->id,
                    'casteller_id' => $castellerId,
                    'status'       => $status,
                    'companions'   => ($event->companions && $status === 'ok') ? ($i % 3) : 0,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ];
            }

            if ($rows) {
                DB::table('attendance')->insert($rows);
            }
        }
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
}
