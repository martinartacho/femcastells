<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;

class ForgotPasswordController extends Controller
{
    public function sendLink(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        ResetPasswordNotification::createUrlUsing(function ($notifiable, string $token) {
            return URL::signedRoute('filament.member.auth.password-reset.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);
        });

        $status = Password::broker('castellers')->sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Link de recuperació enviat. Comprova el correu.']);
        }

        return response()->json(['message' => 'No s\'ha trobat cap compte amb aquest correu.'], 422);
    }
}
