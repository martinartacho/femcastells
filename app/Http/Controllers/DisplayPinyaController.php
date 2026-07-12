<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BoardSnapshot;
use App\Models\CastleTemplate;
use App\Models\Colla;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DisplayPinyaController extends Controller
{
    public function __invoke(Request $request, string $shortname, string $token): View
    {
        $colla = Colla::where('shortname', $shortname)->firstOrFail();

        $snapshot = BoardSnapshot::where('colla_id', $colla->id)
            ->where('public_token', $token)
            ->with('event:id,name,start_date')
            ->firstOrFail();

        $template = CastleTemplate::where('colla_id', $colla->id)
            ->where('key', $snapshot->castle_type)
            ->first(['key', 'name', 'positions']);

        $boardPositions = $template
            ? [$template->key => $template->positions]
            : [];

        return view('display.pinya', compact('colla', 'snapshot', 'template', 'boardPositions'));
    }
}
