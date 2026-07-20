<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Colla;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreateCollaCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // El command necessita aquests rols i contrasenyes al .env
        Role::create(['name' => 'admin',       'guard_name' => 'web']);
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        config([
            'app.env' => 'testing',
        ]);
        // Simula les variables d'entorn que necessita el command
        putenv('SEEDER_ADMIN_PASSWORD=TestAdmin123');
        putenv('SEEDER_USER_PASSWORD=TestUser123');
    }

    public function test_crea_colla_amb_nom_i_shortname(): void
    {
        $this->artisan('colla:create', [
            'nom'            => 'Castellers de Vic',
            'num_castellers' => 10,
            '--shortname'    => 'cdv',
            '--city'         => 'Vic',
        ])->assertSuccessful();

        $this->assertDatabaseHas('colles', [
            'name'      => 'Castellers de Vic',
            'shortname' => 'cdv',
            'city'      => 'Vic',
        ]);
    }

    public function test_crea_admin_user(): void
    {
        $this->artisan('colla:create', [
            'nom'            => 'Castellers de Vic',
            'num_castellers' => 5,
            '--shortname'    => 'cdv',
        ])->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'admin@cdv.test']);
        $admin = User::where('email', 'admin@cdv.test')->first();
        $this->assertTrue($admin->hasRole('admin'));
    }

    public function test_crea_el_nombre_correcte_de_castellers(): void
    {
        $this->artisan('colla:create', [
            'nom'            => 'Castellers de Vic',
            'num_castellers' => 20,
            '--shortname'    => 'cdv',
        ])->assertSuccessful();

        $colla = Colla::where('shortname', 'cdv')->first();
        $this->assertCount(20, $colla->castellers);
    }

    public function test_crea_events(): void
    {
        $this->artisan('colla:create', [
            'nom'            => 'Castellers de Vic',
            'num_castellers' => 5,
            '--shortname'    => 'cdv',
        ])->assertSuccessful();

        $colla = Colla::where('shortname', 'cdv')->first();
        $this->assertGreaterThanOrEqual(1, $colla->events()->count());
    }

    public function test_falla_si_shortname_ja_existeix(): void
    {
        Colla::factory()->create(['shortname' => 'cdv']);

        $this->artisan('colla:create', [
            'nom'            => 'Altra Colla',
            'num_castellers' => 5,
            '--shortname'    => 'cdv',
        ])->assertFailed();
    }

    public function test_shortname_es_deriva_del_nom(): void
    {
        $this->artisan('colla:create', [
            'nom'            => 'Castellers de Vic',
            'num_castellers' => 5,
        ])->assertSuccessful();

        $this->assertDatabaseHas('colles', ['shortname' => 'castellers-de-vic']);
    }

    public function test_crea_noticies_de_benvinguda(): void
    {
        $this->artisan('colla:create', [
            'nom'            => 'Castellers de Vic',
            'num_castellers' => 5,
            '--shortname'    => 'cdv',
        ])->assertSuccessful();

        $colla = Colla::where('shortname', 'cdv')->first();
        $this->assertGreaterThanOrEqual(1, \DB::table('noticies')->where('colla_id', $colla->id)->count());
    }

    public function test_crea_periodes(): void
    {
        $this->artisan('colla:create', [
            'nom'            => 'Castellers de Vic',
            'num_castellers' => 5,
            '--shortname'    => 'cdv',
        ])->assertSuccessful();

        $colla = Colla::where('shortname', 'cdv')->first();
        $this->assertEquals(2, \DB::table('periods')->where('colla_id', $colla->id)->count());
    }
}
