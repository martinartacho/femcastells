<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckGdprConsent
{
    public function handle(Request $request, Closure $next): Response
    {
        $casteller = auth('member')->user();

        if (! $casteller) {
            return $next($request);
        }

        $colla = $casteller->colla;

        if (! $colla || ! $colla->gdpr_text) {
            return $next($request);
        }

        $gdprRoute = route('filament.member.pages.gdpr-consent', ['tenant' => $colla->shortname]);

        if ($request->routeIs('filament.member.pages.gdpr-consent')) {
            return $next($request);
        }

        $needsConsent = $casteller->gdpr_accepted_at === null
            || $casteller->gdpr_version !== $colla->gdpr_version;

        if ($needsConsent) {
            return redirect($gdprRoute);
        }

        return $next($request);
    }
}
