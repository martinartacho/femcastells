<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\SuggerimentRebut;
use App\Models\Casteller;
use App\Models\Suggeriment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MobileSuggerimentController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate(['text' => 'required|string|min:10|max:2000']);

        /** @var Casteller $casteller */
        $casteller = $request->user();

        $suggeriment = Suggeriment::create([
            'colla_id'     => $casteller->colla_id,
            'casteller_id' => $casteller->id,
            'text'         => $request->input('text'),
        ]);

        $suggeriment->load('casteller', 'colla');

        $adminEmail = $casteller->colla->email;
        if ($adminEmail) {
            Mail::to($adminEmail)->send(new SuggerimentRebut($suggeriment));
        }

        return response()->json(['message' => 'Suggeriment rebut. Gràcies!'], 201);
    }
}
