<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BoardSnapshot;
use App\Models\Casteller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileRondesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var Casteller $casteller */
        $casteller = $request->user();

        $snapshots = BoardSnapshot::where('colla_id', $casteller->colla_id)
            ->whereNotNull('public_token')
            ->with('event')
            ->orderByDesc('created_at')
            ->get();

        $colla = $casteller->colla;

        $result = $snapshots->map(fn (BoardSnapshot $s) => [
            'id'        => $s->id,
            'ronda'     => $s->name,
            'eventName' => $s->event?->name ?? '',
            'publicUrl' => route('display.pinya', [$colla->shortname, $s->public_token]),
        ]);

        return response()->json($result);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        /** @var Casteller $casteller */
        $casteller = $request->user();
        $colla     = $casteller->colla;

        $snapshot = BoardSnapshot::where('id', $id)
            ->where('colla_id', $colla->id)
            ->whereNotNull('public_token')
            ->with('event')
            ->firstOrFail();

        return response()->json([
            'id'        => $snapshot->id,
            'ronda'     => $snapshot->name,
            'eventName' => $snapshot->event?->name ?? '',
            'publicUrl' => route('display.pinya', [$colla->shortname, $snapshot->public_token]),
        ]);
    }
}
