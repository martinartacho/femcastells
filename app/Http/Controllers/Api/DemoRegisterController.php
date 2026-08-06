<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Casteller;
use App\Models\Colla;
use App\Models\GlobalSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class DemoRegisterController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'enabled' => GlobalSetting::get('demo_enabled', '1') === '1',
        ]);
    }

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

        if (GlobalSetting::get('demo_enabled', '1') !== '1') {
            return response()->json(['message' => 'La demo no està disponible en aquest moment.'], 503);
        }

        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        $email = mb_strtolower(trim($request->input('email')));
        $colla = Colla::where('shortname', 'demo')->first();

        if (! $colla) {
            return response()->json(['message' => 'La colla demo no està disponible.'], 503);
        }

        // Returning demo user
        $casteller = Casteller::where('colla_id', $colla->id)
            ->where('email', $email)
            ->first();

        if ($casteller) {
            if (! Hash::check($request->input('password'), $casteller->password)) {
                return response()->json(['message' => 'Contrasenya incorrecta per a aquest compte demo.'], 422);
            }
        } else {
            // New demo user: assign an available @demo.test casteller
            $casteller = Casteller::where('colla_id', $colla->id)
                ->where('email', 'like', '%@demo.test')
                ->inRandomOrder()
                ->first();

            if (! $casteller) {
                return response()->json(['message' => 'No hi ha comptes demo disponibles. Torna-ho a provar demà.'], 503);
            }

            $casteller->update([
                'email'    => $email,
                'password' => Hash::make($request->input('password')),
            ]);
        }

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
