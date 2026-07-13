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
            'id_casteller'          => $casteller->id,
            'id_casteller_external' => $casteller->id,
            'colla_id'              => $casteller->colla_id,
            'num_soci'              => $casteller->num_soci ?? '',
            'nationality'           => $casteller->nationality,
            'national_id_number'    => $casteller->national_id,
            'national_id_type'      => $casteller->national_id_type,
            'name'                  => $casteller->name,
            'last_name'             => $casteller->last_name,
            'family'                => $casteller->family,
            'family_head'           => $casteller->family_head,
            'alias'                 => $casteller->alias,
            'gender'                => $casteller->gender ?? 0,
            'birthdate'             => $casteller->birthdate?->toDateString(),
            'subscription_date'     => $casteller->subscription_date,
            'email'                 => $casteller->email ?? '',
            'email2'                => $casteller->email2 ?? '',
            'phone'                 => $casteller->phone ?? '',
            'mobile_phone'          => $casteller->mobile_phone,
            'emergency_phone'       => $casteller->emergency_phone,
            'address'               => $casteller->address,
            'postal_code'           => $casteller->postal_code,
            'city'                  => $casteller->city,
            'comarca'               => null,
            'province'              => $casteller->province,
            'country'               => $casteller->country,
            'comments'              => $casteller->comments,
            'photo'                 => $casteller->photo,
            'height'                => $casteller->height ?? 0,
            'weight'                => $casteller->weight ?? 0,
            'shoulder_height'       => $casteller->shoulder_height,
            'status'                => $casteller->status ?? 1,
            'language'              => null,
            'interaction_type'      => 0,
            'created_at'            => $casteller->created_at?->toIso8601String() ?? '',
            'updated_at'            => $casteller->updated_at?->toIso8601String() ?? '',
        ]);
    }
}
