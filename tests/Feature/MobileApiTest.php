<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Casteller;
use App\Models\Colla;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    private Colla $colla;
    private Casteller $casteller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->colla     = Colla::factory()->create();
        $this->casteller = Casteller::factory()->create([
            'colla_id' => $this->colla->id,
            'status'   => 1,
        ]);
    }

    public function test_login_retorna_token(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email'    => $this->casteller->email,
            'password' => 'password',
        ]);

        $response->assertOk()
                 ->assertJsonStructure(['access_token', 'token_type']);
    }

    public function test_login_falla_amb_credencials_incorrectes(): void
    {
        $this->postJson('/api/auth/login', [
            'email'    => $this->casteller->email,
            'password' => 'wrongpassword',
        ])->assertUnauthorized();
    }

    public function test_mobile_events_requereix_autenticacio(): void
    {
        $this->getJson('/api-fempinya/mobile_events')
             ->assertUnauthorized();
    }

    public function test_mobile_events_retorna_llista(): void
    {
        Event::factory()->count(3)->create(['colla_id' => $this->colla->id]);
        $token = $this->casteller->createToken('test')->plainTextToken;

        $this->getJson('/api-fempinya/mobile_events', [
            'Authorization' => "Bearer {$token}",
        ])->assertOk()
          ->assertJsonStructure([['id', 'title', 'type', 'startDate']]);
    }

    public function test_mobile_events_show_retorna_event(): void
    {
        $event = Event::factory()->create(['colla_id' => $this->colla->id]);
        $token = $this->casteller->createToken('test')->plainTextToken;

        $this->getJson("/api-fempinya/mobile_events/{$event->id}", [
            'Authorization' => "Bearer {$token}",
        ])->assertOk()
          ->assertJsonFragment(['id' => $event->id]);
    }

    public function test_mobile_user_context_retorna_dades_casteller(): void
    {
        $token = $this->casteller->createToken('test')->plainTextToken;

        $this->getJson('/api-fempinya/mobile_user_context', [
            'Authorization' => "Bearer {$token}",
        ])->assertOk()
          ->assertJsonStructure(['castellerActiveId', 'castellerActiveAlias']);
    }

    public function test_logout_elimina_token(): void
    {
        $token = $this->casteller->createToken('test')->plainTextToken;

        $this->postJson('/api/auth/logout', [], [
            'Authorization' => "Bearer {$token}",
        ])->assertOk();

        // Sanctum fa cache del token en memòria dins el mateix test;
        // verifiquem directament a la BD que ha estat eliminat
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
