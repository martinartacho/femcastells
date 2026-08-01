<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Filament\Pages\CollaSettings;
use App\Models\Colla;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Stripe\OAuth;
use Stripe\Stripe;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StripeConnectController extends Controller
{
    public function redirect(Request $request, string $shortname): RedirectResponse
    {
        $colla = Colla::where('shortname', $shortname)->firstOrFail();

        abort_unless($request->user()?->canAccessTenant($colla), 403);

        $state = Crypt::encryptString((string) $colla->id);

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => config('services.stripe.connect_client_id'),
            'scope' => 'read_write',
            'redirect_uri' => route('stripe.connect.callback'),
            'state' => $state,
        ]);

        return redirect()->away("https://connect.stripe.com/oauth/authorize?{$query}");
    }

    public function callback(Request $request): RedirectResponse
    {
        $colla = $this->resolveCollaFromState($request->string('state')->toString());

        if ($request->filled('error')) {
            Notification::make()
                ->title('Connexió amb Stripe cancel·lada')
                ->body($request->string('error_description')->toString())
                ->warning()
                ->send();

            return redirect(CollaSettings::getUrl(tenant: $colla));
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $response = OAuth::token([
            'grant_type' => 'authorization_code',
            'code' => $request->string('code')->toString(),
        ]);

        $colla->update([
            'stripe_account_id' => $response->stripe_user_id,
            'stripe_onboarded_at' => now(),
        ]);

        Notification::make()
            ->title('Compte Stripe connectat correctament')
            ->success()
            ->send();

        return redirect(CollaSettings::getUrl(tenant: $colla));
    }

    private function resolveCollaFromState(string $state): Colla
    {
        try {
            $collaId = (int) Crypt::decryptString($state);
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            throw new HttpException(400, 'Estat OAuth invàlid.');
        }

        return Colla::findOrFail($collaId);
    }
}
