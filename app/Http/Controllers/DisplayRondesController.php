<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BoardSnapshot;
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
            ->get(['id', 'name', 'ronda_number', 'castle_type', 'public_token']);

        return view('display.rondes', compact('colla', 'event', 'rondes'));
    }
}
