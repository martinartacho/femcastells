<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BoardSnapshot;
use App\Models\Casteller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileRondesController extends Controller
{
    // type=1 assaig → sense historial; type=3 activitat (social) → sense historial
    // type=2 actuació → amb historial de rondes
    private const TYPES_WITH_RONDES = [Event::TYPE_ACTUACIO];

    public function index(Request $request): JsonResponse
    {
        /** @var Casteller $casteller */
        $casteller = $request->user();

        $hasRondes = fn ($q) => $q->whereNotNull('ronda_number')->whereNotNull('public_token');

        $event = $casteller->colla->events()
            ->whereIn('type', self::TYPES_WITH_RONDES)
            ->whereHas('boardSnapshots', $hasRondes)
            ->where('start_date', '>=', now())
            ->orderBy('start_date')
            ->first()
            ?? $casteller->colla->events()
                ->whereIn('type', self::TYPES_WITH_RONDES)
                ->whereHas('boardSnapshots', $hasRondes)
                ->orderByDesc('start_date')
                ->first();

        $snapshots = $event
            ? BoardSnapshot::where('colla_id', $casteller->colla_id)
                ->where('event_id', $event->id)
                ->whereNotNull('ronda_number')
                ->whereNotNull('public_token')
                ->orderBy('ronda_number')
                ->get()
            : collect();

        $colla = $casteller->colla;
        $alias = $casteller->alias;

        $result = $snapshots->map(fn (BoardSnapshot $s) => [
            'id'          => $s->id,
            'ronda'       => $s->name,
            'rondaNumber' => $s->ronda_number,
            'eventName'   => $s->event?->name ?? '',
            'publicUrl'   => route('display.pinya', [$colla->shortname, $s->public_token]) . '?alias=' . urlencode($alias),
        ]);

        return response()->json($result);
    }

    public function historial(Request $request): JsonResponse
    {
        /** @var Casteller $casteller */
        $casteller = $request->user();
        $colla     = $casteller->colla;
        $alias     = $casteller->alias;

        $hasRondes = fn ($q) => $q->whereNotNull('ronda_number')->whereNotNull('public_token');

        $events = $colla->events()
            ->whereIn('type', self::TYPES_WITH_RONDES)
            ->whereHas('boardSnapshots', $hasRondes)
            ->where('start_date', '<', now())
            ->orderByDesc('start_date')
            ->with(['boardSnapshots' => fn ($q) => $q
                ->whereNotNull('ronda_number')
                ->whereNotNull('public_token')
                ->orderBy('ronda_number'),
            ])
            ->get();

        $result = $events->map(fn (Event $e) => [
            'eventId'   => $e->id,
            'eventName' => $e->name,
            'eventDate' => $e->start_date->toDateString(),
            'eventType' => $e->type,
            'rondes'    => $e->boardSnapshots->map(fn (BoardSnapshot $s) => [
                'id'          => $s->id,
                'ronda'       => $s->name,
                'rondaNumber' => $s->ronda_number,
                'publicUrl'   => route('display.pinya', [$colla->shortname, $s->public_token]) . '?alias=' . urlencode($alias),
            ]),
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
            ->whereNotNull('ronda_number')
            ->whereNotNull('public_token')
            ->with('event')
            ->firstOrFail();

        return response()->json([
            'id'          => $snapshot->id,
            'ronda'       => $snapshot->name,
            'rondaNumber' => $snapshot->ronda_number,
            'eventName'   => $snapshot->event?->name ?? '',
            'publicUrl'   => route('display.pinya', [$colla->shortname, $snapshot->public_token]) . '?alias=' . urlencode($casteller->alias),
        ]);
    }
}
