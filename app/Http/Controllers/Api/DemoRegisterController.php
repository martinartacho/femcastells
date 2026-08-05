<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Casteller;
use App\Models\Colla;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class DemoRegisterController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $key = 'demo-register:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, maxAttempts: 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'message' => "Massa registres seguits. Torna-ho a provar en {$seconds} segons.",
            ], 429);
        }

        RateLimiter::hit($key, decay: 3600);

        $colla = Colla::where('shortname', 'demo')->first();

        if (! $colla) {
            return response()->json(['message' => 'La colla demo no està disponible.'], 503);
        }

        $suffix = strtoupper(Str::random(4));
        $alias  = "Visitant-{$suffix}";
        $email  = "visitant.{$suffix}@demo.femcastells";

        $casteller = Casteller::create([
            'colla_id'        => $colla->id,
            'alias'           => $alias,
            'name'            => 'Visitant',
            'last_name'       => $suffix,
            'email'           => $email,
            'password'        => Hash::make(Str::random(32)),
            'gdpr_accepted_at' => now(),
            'gdpr_version'    => '1.0',
            'status'          => 'actiu',
        ]);

        $token = $casteller->createToken('mobile-demo')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'demo'         => true,
            'alias'        => $alias,
        ]);
    }
}
