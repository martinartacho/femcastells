<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Casteller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileUserController extends Controller
{
    public function context(Request $request): JsonResponse
    {
        /** @var Casteller $casteller */
        $casteller = $request->user();

        return response()->json([
            'castellerActiveId'    => $casteller->id,
            'castellerActiveAlias' => $casteller->alias,
            'linkedCastellers'     => [
                [
                    'idCastellerApiUser' => $casteller->id,
                    'apiUserId'          => $casteller->id,
                    'castellerId'        => $casteller->id,
                ],
            ],
            'boardsEnabled' => true,
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        /** @var Casteller $casteller */
        $casteller = $request->user();

        return response()->json([
            'id'               => $casteller->id,
            'alias'            => $casteller->alias,
            'name'             => $casteller->name,
            'lastName'         => $casteller->last_name,
            'email'            => $casteller->email,
            'phone'            => $casteller->phone,
            'mobilePhone'      => $casteller->mobile_phone,
            'address'          => $casteller->address,
            'postalCode'       => $casteller->postal_code,
            'city'             => $casteller->city,
            'province'         => $casteller->province,
            'country'          => $casteller->country,
            'birthdate'        => $casteller->birthdate?->toDateString(),
            'gender'           => $casteller->gender,
            'height'           => $casteller->height,
            'weight'           => $casteller->weight,
            'shoulderHeight'   => $casteller->shoulder_height,
            'numSoci'          => $casteller->num_soci,
            'status'           => $casteller->status,
        ]);
    }
}
