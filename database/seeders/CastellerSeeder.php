<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Colla;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CastellerSeeder extends Seeder
{
    private const PER_COLLA = 150;

    // Typical casteller aliases — nicknames or surname-based
    private const ALIAS_POOL = [
        'LLOP','GUINEU','ÓS','TEIXÓ','ERMINI','VISÓ','LLÚDRIA','GAT',
        'GOS','CAVALL','VACA','PORC','OVELLA','CABRA','CÉRVOL','DAINA',
        'ISARD','CABIROL','SENGLAR','CONILL','LLEBRE','RATOLÍ','RATA',
        'ESQUIROL','LIRÓ','TALP','RATPENAT','DOFÍ','ORCA','BALENA',
        'CATXALOT','FOCA','LLEÓ MARÍ','ELEFANT','RINOCERONT','HIPOPÒTAM',
        'GIRAFA','LLEOPARD','JAGUAR','PANTERA','TIGRE','LLEÓ','BABUÍ',
        'GORIL·LA','XIMPANZÉ','MICO','ÀGUILA','FALCÓ','MILÀ','ALIGOT',
        'VOLTOR','TRENCALÒS','ÒLIBA','MUSSOL','DUC','CORB','GRAULA',
        'GAIG','GARSA','ORENETA','FALCIOT','COTXA','PIT-ROIG','PARDAL',
        'CADERNERA','VERDUM','PICOT','AGRÓ','CIGONYA','GRUA','ÀNEC',
        'OCA','CIGNE','PERDIU','GUATLLA','FAISÀ','GALL','GALLINA',
        'FLAMENC','PELICÀ','PINGÜÍ','GAVIÀ','SERP','COLOBRA','ESCURÇÓ',
        'DRAGÓ','SARGANTANA','CAMALEÓ','COCODRIL','CAIMAN','TORTUGA',
        'GRANOTA','GRIPAU','SALAMANDRA','TRITÓ','TRUITA','SALMÓ','CARPA',
        'PEIX ESPASA','TAURÓ','RAJADA','VERAT','TONYINA','BACALLÀ','CONGRE',
        'ANGULA','CAVALLET DE MAR','LLAMPUGA','ESMERLA','MOLL','SARDINA',
        'ANXOVA','PAGRE','LLOBARRO','DORADA','BESUC','RASCASSA','ESCORPINA',
        'MANTA','NARVAL','BELUGA','DUGONG','MANATÍ','IGUANA',
        'PÍ','AVELLANER','ALZINA','SURERA','ROURE','FAIG','CASTANYER',
        'NOGUER','OLIVERA','AMETLLER','GINJOLER','MAGRANER','FIGUERA','PRUNERA',
        'CIRERER','PRESSEGUER','ALBERCOQUER','TARONGER','LLIMONER','MANDARINER',
        'POMER','PERERA','NESPRER','CAROBA','ACÀCIA','PLÀTAN','OLM','SALZE','XIPRER',
        'TIL·LER','LLEDONER','SERVERA','MOIXERA','TEC','CAOBÀ','EBAN','BANYAN','SECOIA',
        'GINKGO','PALMERA','AVET','ULLASTRE','LLORER','MURTA','GINESTA','BRUC',
        'VERN','FREIXE','SABINA','GINEBRE','CARXOFA','ESPÀRREC','BLEDA',
        'REMOLATXA','BRÒQUIL','COL','COLIFLOR','API','ESCAROLA','ESPINAC',
        'FONOLL','MONGETA','PÈSOL','LLETUGA','RAVE','NAP','CEBA','ALL','PORRO',
        'CARABASSA','CARABASSÓ','COGOMBRE','PEPINO','PEBROT','TOMÀQUET','PATATA',
        'MONIATO','PASTANAGA','XIRIVIA','OKRA','BERENZENA','YUCA','TARO','CRENADA',
        'RÚCULA','MENTA','JULIVERT','FARIGOLA','ALFÀBREGA','ROMÀ','BANANA','PINYA',
        'MANGO','PAPAIA','KIWI','ALVOCAT','GUAIABA','LLIMA','ARANJA','RAÏM',
        'MADUIXA','GERD','MORA','NABIU','GROSELLA','CIRESA','PRÉSSEC',
        'ALBERCOC','TARONJA','LLIMONA','MANDARINA','POMA','PERA','NÉSPOLA',
        'MAGRANA','FIGA','GINJOL','OLIVA','AMETLLA','AVELLANA','CASTANYA',
        'NOU','PISTATX','CACAUET'
    ];

    public function run(): void
    {
        $faker = Faker::create('es_ES');
        $password = env('SEEDER_USER_PASSWORD')
            ?? throw new \RuntimeException('SEEDER_USER_PASSWORD no definit al .env');

        foreach (Colla::all() as $colla) {
            if (DB::table('castellers')->where('colla_id', $colla->id)->exists()) {
                continue;
            }
            $this->seedForColla($colla->id, $faker, $password);
        }

        // Casteller de demo per a proves (Play Store review / QA)
        $demoColla = Colla::where('shortname', 'demo')->first();
        if ($demoColla) {
            $alreadyExists = DB::table('castellers')
                ->where('colla_id', $demoColla->id)
                ->where('alias', 'DEMO')
                ->exists();

            if (! $alreadyExists) {
                DB::table('castellers')->insert([
                    'colla_id'   => $demoColla->id,
                    'alias'      => 'DEMO',
                    'name'       => 'Usuari',
                    'last_name'  => 'Demo',
                    'email'      => 'femcastells@artacho.org',
                    'password'   => Hash::make($password),
                    'status'     => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function seedForColla(int $collaId, \Faker\Generator $faker, string $password): void
    {
        $aliases   = $this->generateAliases(self::PER_COLLA);
        $rows      = [];
        $now       = now()->toDateTimeString();

        // Status distribution: 65% actiu, 15% inactiu, 10% candidat, 10% baixa
        $statusPool = array_merge(
            array_fill(0, 65, 1),
            array_fill(0, 15, 2),
            array_fill(0, 10, 4),
            array_fill(0, 10, 3),
        );

        $hashedPassword = Hash::make($password);

        foreach ($aliases as $i => $alias) {
            $isMale    = $faker->boolean(55);
            $firstName = $isMale ? $faker->firstNameMale : $faker->firstNameFemale;
            $lastName  = $faker->lastName . ' ' . $faker->lastName;
            $status    = $statusPool[$i % count($statusPool)];

            $rows[] = [
                'colla_id'         => $collaId,
                'alias'            => $alias,
                'name'             => $firstName,
                'last_name'        => $lastName,
                'email'            => self::aliasToEmailSlug($alias) . '.c' . $collaId . '@fempinya.test',
                'password'         => $hashedPassword,
                'phone'            => $faker->phoneNumber,
                'birthdate'        => $faker->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
                'status'           => $status,
                'height'           => $faker->numberBetween(155, 195),
                'weight'           => $faker->numberBetween(50, 110),
                'shoulder_height'  => $faker->numberBetween(130, 175),
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
        }

        DB::table('castellers')->insert($rows);
    }

    private function generateAliases(int $count): array
    {
        $pool   = self::ALIAS_POOL;
        shuffle($pool);

        $result = [];
        $used   = [];
        $extras = 0;

        foreach ($pool as $alias) {
            if (count($result) >= $count) break;
            $result[] = $alias;
            $used[$alias] = true;
        }

        // Fill the rest with numbered variants
        $base = $pool;
        shuffle($base);
        $idx = 0;
        while (count($result) < $count) {
            $candidate = $base[$idx % count($base)] . ++$extras;
            if (! isset($used[$candidate])) {
                $result[] = $candidate;
                $used[$candidate] = true;
            }
            $idx++;
        }

        return $result;
    }

    private static function aliasToEmailSlug(string $alias): string
    {
        $map = [
            'À'=>'A','Á'=>'A','Â'=>'A','Ä'=>'A','Ã'=>'A','Å'=>'A',
            'È'=>'E','É'=>'E','Ê'=>'E','Ë'=>'E',
            'Ì'=>'I','Í'=>'I','Î'=>'I','Ï'=>'I',
            'Ò'=>'O','Ó'=>'O','Ô'=>'O','Ö'=>'O','Õ'=>'O',
            'Ù'=>'U','Ú'=>'U','Û'=>'U','Ü'=>'U',
            'Ç'=>'C','Ñ'=>'N','·'=>'',
        ];
        $slug = strtr($alias, $map);
        return strtolower(preg_replace('/[^a-z0-9]+/i', '', $slug));
    }
}
