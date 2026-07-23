<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Casteller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileGdprController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        /** @var Casteller $casteller */
        $casteller = $request->user();
        $colla     = $casteller->colla;

        if (! $colla?->gdpr_text) {
            return response()->json(['required' => false]);
        }

        $needsConsent = $casteller->gdpr_accepted_at === null
            || $casteller->gdpr_version !== $colla->gdpr_version;

        return response()->json([
            'required' => $needsConsent,
            'text'     => $needsConsent ? $colla->resolveGdprText() : null,
            'version'  => $colla->gdpr_version,
        ]);
    }

    public function accept(Request $request): JsonResponse
    {
        /** @var Casteller $casteller */
        $casteller = $request->user();
        $colla     = $casteller->colla;

        $casteller->update([
            'gdpr_accepted_at' => now(),
            'gdpr_version'     => $colla?->gdpr_version ?? '1.0',
        ]);

        return response()->json(['message' => 'Consentiment acceptat']);
    }
}
