<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Casteller;
use App\Models\Colla;
use App\Models\Event;
use App\Services\IdcClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IdcIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Colla $colla;

    private Event $actuacio;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.idc.base_url' => 'http://idc.test', 'services.idc.token' => 'fake-token']);

        $this->colla = Colla::factory()->create();
        $this->actuacio = Event::factory()->actuacio()->create(['colla_id' => $this->colla->id]);
    }

    public function test_checking_in_a_casteller_with_email_issues_an_idc_credential(): void
    {
        Http::fake([
            '*/api/v1/castellers' => Http::response(['uuid' => 'casteller-uuid-123'], 201),
            '*/api/v1/credentials' => Http::response(['uuid' => 'credential-uuid-456'], 201),
        ]);

        $casteller = Casteller::factory()->create(['colla_id' => $this->colla->id, 'email' => 'test@fempinya4.test']);
        $attendance = Attendance::create([
            'event_id' => $this->actuacio->id,
            'casteller_id' => $casteller->id,
            'status' => 'ok',
            'checked_in' => true,
            'checked_in_at' => now(),
        ]);

        $idc = app(IdcClient::class);
        $uuid = $idc->findOrCreateCastellerUuid($attendance->casteller);
        $idc->issueCredential($uuid, 'participacio-diada');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/api/v1/castellers'));
        Http::assertSent(fn ($request) => str_contains($request->url(), '/api/v1/credentials'));
        $this->assertSame('casteller-uuid-123', $casteller->fresh()->idc_uuid);
    }

    public function test_casteller_without_email_is_skipped_without_calling_idc(): void
    {
        Http::fake();

        $casteller = Casteller::factory()->create(['colla_id' => $this->colla->id, 'email' => null]);

        $uuid = app(IdcClient::class)->findOrCreateCastellerUuid($casteller);

        $this->assertNull($uuid);
        Http::assertNothingSent();
    }

    public function test_idc_being_unreachable_does_not_throw(): void
    {
        Http::fake(fn () => throw new ConnectionException('connection refused'));

        $casteller = Casteller::factory()->create(['colla_id' => $this->colla->id, 'email' => 'test2@fempinya4.test']);

        $uuid = app(IdcClient::class)->findOrCreateCastellerUuid($casteller);

        $this->assertNull($uuid);
    }
}
