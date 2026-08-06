<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HelpItem;
use Illuminate\Http\JsonResponse;

class MobileHelpController extends Controller
{
    public function index(): JsonResponse
    {
        $items = HelpItem::where('visible', true)
            ->orderBy('category')
            ->orderBy('sort')
            ->orderBy('question')
            ->get(['category', 'question', 'answer']);

        return response()->json($items);
    }
}
