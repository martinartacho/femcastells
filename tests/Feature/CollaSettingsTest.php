<?php

namespace Tests\Feature;

use App\Models\Colla;
use App\Models\User;
use Tests\TestCase;

class CollaSettingsTest extends TestCase
{
    public function test_admin_te_rol_correcte(): void
    {
        $admin = User::where('email', 'pitxapins@fempinya.test')->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole('admin'));
    }

    public function test_assaigs_weeks_visible_te_valor_per_defecte(): void
    {
        $colla = Colla::first();
        $this->assertNotNull($colla);
        $this->assertGreaterThanOrEqual(1, $colla->assaigs_weeks_visible);
    }

    public function test_colla_pot_actualitzar_assaigs_weeks_visible(): void
    {
        $colla = Colla::first();
        $colla->update(['assaigs_weeks_visible' => 4]);
        $this->assertEquals(4, $colla->fresh()->assaigs_weeks_visible);
        $colla->update(['assaigs_weeks_visible' => 2]);
    }
}
