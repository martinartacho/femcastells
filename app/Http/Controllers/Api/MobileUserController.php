<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Casteller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class MobileUserController extends Controller
{
    public function context(Request $request): JsonResponse
    {
        /** @var Casteller $casteller */
        $casteller = $request->user();
        $colla     = $casteller->colla;

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
            'boardsEnabled'  => true,
            'collaName'      => $colla?->name,
            'collaLogoUrl'   => ($colla?->show_logo_in_pinya && $colla?->logo)
                ? $colla->getFilamentAvatarUrl()
                : null,
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        /** @var Casteller $casteller */
        $casteller = $request->user();

        $data = $request->validate([
            'email'           => ['required', 'email', 'unique:castellers,email,' . $casteller->id],
            'email2'          => ['nullable', 'email'],
            'phone'           => ['nullable', 'string', 'max:30'],
            'mobile_phone'    => ['nullable', 'string', 'max:30'],
            'emergency_phone' => ['nullable', 'string', 'max:30'],
            'address'         => ['nullable', 'string', 'max:255'],
            'postal_code'     => ['nullable', 'string', 'max:10'],
            'city'            => ['nullable', 'string', 'max:100'],
            'province'        => ['nullable', 'string', 'max:100'],
            'country'         => ['nullable', 'string', 'max:100'],
            'password'        => ['nullable', 'confirmed', Password::min(8)],
        ]);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $casteller->update($data);

        return response()->json(['message' => 'Perfil actualitzat correctament']);
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
