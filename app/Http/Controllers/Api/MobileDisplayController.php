<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Casteller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileDisplayController extends Controller
{
    public function url(Request $request): JsonResponse
    {
        /** @var Casteller $casteller */
        $casteller = $request->user();
        $colla     = $casteller->colla;

        if (! $colla) {
            return response()->json(['publicUrl' => null]);
        }

        $displayToken = $colla->ensureDisplayToken();

        return response()->json([
            'publicUrl' => route('display.projector', [$colla->shortname, $displayToken]),
        ]);
    }
}
