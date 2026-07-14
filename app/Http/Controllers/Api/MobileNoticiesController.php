<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Casteller;
use App\Models\Noticia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MobileNoticiesController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var Casteller $casteller */
        $casteller = $request->user();

        $seenAt = $casteller->noticies_last_seen_at;

        $noticies = Noticia::published()
            ->where(fn ($q) => $q
                ->whereNull('colla_id')
                ->orWhere('colla_id', $casteller->colla_id)
            )
            ->orderByDesc('published_at')
            ->get(['id', 'title', 'body', 'labels', 'published_at'])
            ->map(fn ($n) => [
                'id'           => $n->id,
                'title'        => $n->title,
                'body'         => $n->body,
                'labels'       => $n->labels ?? [],
                'published_at' => $n->published_at,
                'unread'       => $seenAt === null || $n->published_at > $seenAt,
            ]);

        $casteller->noticies_last_seen_at = Carbon::now();
        $casteller->saveQuietly();

        return response()->json(['noticies' => $noticies]);
    }
}
