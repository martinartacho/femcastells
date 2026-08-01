<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Casteller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate(['token' => 'required|string|max:255']);

        /** @var Casteller $casteller */
        $casteller = $request->user();
        $casteller->update(['fcm_token' => $request->input('token')]);

        return response()->json(['message' => 'Token registrat']);
    }
}
