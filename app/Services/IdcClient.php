<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Casteller;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class IdcClient
{
    public function findOrCreateCastellerUuid(Casteller $casteller): ?string
    {
        if ($casteller->idc_uuid) {
            return $casteller->idc_uuid;
        }

        if (! $casteller->email) {
            return null;
        }

        try {
            $response = $this->client()->post('/api/v1/castellers', [
                'email' => $casteller->email,
                'first_name' => $casteller->name,
                'last_name' => $casteller->last_name,
            ]);

            if (! $response->successful()) {
                Log::warning('IDC: no s\'ha pogut crear/trobar el casteller', [
                    'casteller_id' => $casteller->id,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $uuid = $response->json('uuid');
            $casteller->update(['idc_uuid' => $uuid]);

            return $uuid;
        } catch (Throwable $e) {
            Log::warning('IDC: error de connexió cercant/creant el casteller', [
                'casteller_id' => $casteller->id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function issueCredential(string $castellerUuid, string $typeSlug): bool
    {
        try {
            $response = $this->client()->post('/api/v1/credentials', [
                'casteller_uuid' => $castellerUuid,
                'credential_type' => $typeSlug,
            ]);

            if (! $response->successful()) {
                Log::warning('IDC: no s\'ha pogut emetre la credencial', [
                    'casteller_uuid' => $castellerUuid,
                    'credential_type' => $typeSlug,
                    'status' => $response->status(),
                ]);

                return false;
            }

            return true;
        } catch (Throwable $e) {
            Log::warning('IDC: error de connexió emetent la credencial', [
                'casteller_uuid' => $castellerUuid,
                'credential_type' => $typeSlug,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl((string) config('services.idc.base_url'))
            ->withToken((string) config('services.idc.token'))
            ->acceptJson();
    }
}
