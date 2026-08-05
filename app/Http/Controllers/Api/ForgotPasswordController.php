<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ResetCodeMail;
use App\Models\Casteller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class ForgotPasswordController extends Controller
{
    public function sendLink(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $email = mb_strtolower(trim($request->input('email')));

        // Always return success to avoid user enumeration
        if (Casteller::where('email', $email)->exists()) {
            $code = $this->generateCode();
            Cache::put("pwd_reset_{$email}", Hash::make($code), now()->addMinutes(15));
            Mail::to($email)->send(new ResetCodeMail($code));
        }

        return response()->json(['message' => 'Si el compte existeix, rebràs un codi al correu en uns instants.']);
    }

    public function reset(Request $request): JsonResponse
    {
        $request->validate([
            'email'                 => 'required|email',
            'code'                  => 'required|string',
            'password'              => 'required|min:8|confirmed',
        ]);

        $email = mb_strtolower(trim($request->input('email')));
        $cacheKey = "pwd_reset_{$email}";
        $stored = Cache::get($cacheKey);

        if (! $stored || ! Hash::check(mb_strtoupper(trim($request->input('code'))), $stored)) {
            return response()->json(['message' => 'Codi incorrecte o caducat. Torna a demanar-ne un de nou.'], 422);
        }

        $casteller = Casteller::where('email', $email)->first();
        if (! $casteller) {
            return response()->json(['message' => 'Compte no trobat.'], 422);
        }

        $casteller->update(['password' => Hash::make($request->input('password'))]);
        Cache::forget($cacheKey);

        return response()->json(['message' => 'Contrasenya actualitzada correctament.']);
    }

    private function generateCode(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ'; // sense I, O per evitar confusió
        $letters = $chars[rand(0, strlen($chars) - 1)]
                 . $chars[rand(0, strlen($chars) - 1)]
                 . $chars[rand(0, strlen($chars) - 1)];

        return $letters . '-' . rand(100, 999);
    }
}
