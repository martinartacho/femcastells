<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Casteller;
use App\Models\CastleTemplate;
use App\Models\Colla;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectorDisplayController extends Controller
{
    public function __invoke(Request $request, string $shortname, string $token): View
    {
        $colla = Colla::where('shortname', $shortname)
            ->where('display_token', $token)
            ->firstOrFail();

        $snapshot = $colla->projectedSnapshot?->load('event:id,name,start_date');

        $template = null;
        $boardPositions = [];

        if ($snapshot) {
            $template = CastleTemplate::where('colla_id', $colla->id)
                ->where('key', $snapshot->castle_type)
                ->first(['key', 'name', 'positions']);

            $boardPositions = $template
                ? [$template->key => $template->positions]
                : [];
        }

        // Pissarra Personal: si el casteller està autenticat al panell membre
        $highlightPosition = null;
        $casteller = auth('member')->user();
        if ($casteller instanceof Casteller && $snapshot && !empty($snapshot->assignments)) {
            $highlightPosition = array_search($casteller->alias, $snapshot->assignments, true) ?: null;
        }

        return view('display.projector', compact(
            'colla',
            'snapshot',
            'template',
            'boardPositions',
            'highlightPosition',
            'casteller',
        ));
    }
}
