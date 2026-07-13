<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BoardSnapshot;
use App\Models\CastleTemplate;
use App\Models\Colla;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DisplayRondesController extends Controller
{
    public function __invoke(Request $request, string $shortname, string $token): View
    {
        $colla = Colla::where('shortname', $shortname)->firstOrFail();

        $event = Event::where('colla_id', $colla->id)
            ->where('public_token', $token)
            ->firstOrFail();

        $rondes = BoardSnapshot::where('colla_id', $colla->id)
            ->where('event_id', $event->id)
            ->whereNotNull('ronda_number')
            ->orderBy('ronda_number')
            ->get(['id', 'name', 'ronda_number', 'castle_type', 'public_token', 'assignments']);

        // Posicions de totes les plantilles usades a les rondes (per al mode tabs)
        $castleTypes = $rondes->pluck('castle_type')->unique()->values();
        $boardPositions = CastleTemplate::where('colla_id', $colla->id)
            ->whereIn('key', $castleTypes)
            ->get(['key', 'positions'])
            ->mapWithKeys(fn ($t) => [$t->key => $t->positions])
            ->toArray();

        return view('display.rondes', compact('colla', 'event', 'rondes', 'boardPositions'));
    }
}
