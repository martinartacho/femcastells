<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Casteller;
use App\Models\Noticia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileHomeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var Casteller $casteller */
        $casteller = $request->user();

        $seenAt = $casteller->noticies_last_seen_at;

        $base = Noticia::published()
            ->where(fn ($q) => $q
                ->whereNull('colla_id')
                ->orWhere('colla_id', $casteller->colla_id)
            )
            ->orderByDesc('published_at');

        $unreadCount = (clone $base)
            ->when($seenAt, fn ($q) => $q->where('published_at', '>', $seenAt))
            ->count();

        $recent = (clone $base)
            ->limit(5)
            ->get(['id', 'title', 'body', 'labels', 'published_at'])
            ->map(fn ($n) => [
                'id'           => $n->id,
                'title'        => $n->title,
                'body'         => $n->body,
                'labels'       => $n->labels ?? [],
                'published_at' => $n->published_at,
                'unread'       => $seenAt === null || $n->published_at > $seenAt,
            ]);

        return response()->json([
            'noticies' => [
                'unread_count' => $unreadCount,
                'recent'       => $recent,
            ],
            'loginStats' => [
                'lastLoginAt' => $casteller->last_login_at?->diffForHumans(),
                'loginCount'  => $casteller->login_count ?? 0,
            ],
        ]);
    }
}
