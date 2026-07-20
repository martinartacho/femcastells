<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Colla;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CollaSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_te_rol_correcte(): void
    {
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $colla = Colla::factory()->create();
        $admin = User::factory()->create(['colla_id' => $colla->id]);
        $admin->assignRole('admin');

        $this->assertTrue($admin->fresh()->hasRole('admin'));
    }

    public function test_assaigs_weeks_visible_te_valor_per_defecte(): void
    {
        $colla = Colla::factory()->create();

        $this->assertEquals(2, $colla->assaigs_weeks_visible);
    }

    public function test_colla_pot_actualitzar_assaigs_weeks_visible(): void
    {
        $colla = Colla::factory()->create();

        $colla->update(['assaigs_weeks_visible' => 4]);

        $this->assertEquals(4, $colla->fresh()->assaigs_weeks_visible);
    }

    public function test_colla_te_shortname_unic(): void
    {
        Colla::factory()->create(['shortname' => 'test-colla']);

        $this->assertDatabaseHas('colles', ['shortname' => 'test-colla']);
        $this->expectException(\Illuminate\Database\QueryException::class);
        Colla::factory()->create(['shortname' => 'test-colla']);
    }
}
