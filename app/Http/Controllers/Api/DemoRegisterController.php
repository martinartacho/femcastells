<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Casteller;
use App\Models\Colla;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class DemoRegisterController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $key = 'demo-register:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, maxAttempts: 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'message' => "Massa accessos seguits. Torna-ho a provar en {$seconds} segons.",
            ], 429);
        }

        RateLimiter::hit($key, 3600);

        $colla = Colla::where('shortname', 'demo')->first();

        if (! $colla) {
            return response()->json(['message' => 'La colla demo no està disponible.'], 503);
        }

        $casteller = Casteller::where('colla_id', $colla->id)
            ->where('email', 'like', '%@demo.test')
            ->inRandomOrder()
            ->first();

        if (! $casteller) {
            return response()->json(['message' => 'No hi ha castellers demo disponibles.'], 503);
        }

        // Revoca tokens de demo antics d'aquest casteller per mantenir-ho net
        $casteller->tokens()->where('name', 'mobile-demo')->delete();

        $token = $casteller->createToken('mobile-demo')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'demo'         => true,
            'alias'        => $casteller->alias,
        ]);
    }
}
