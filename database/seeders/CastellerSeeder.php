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

    public function run(): void
    {
        $faker = Faker::create('es_ES');
        $password = env('SEEDER_USER_PASSWORD')
            ?? throw new \RuntimeException('SEEDER_USER_PASSWORD no definit al .env');

        foreach (Colla::all() as $colla) {
            $this->seedForColla($colla->id, $faker, $password);
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
                'email'            => strtolower($alias) . '.c' . $collaId . '@fempinya.test',
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
}
