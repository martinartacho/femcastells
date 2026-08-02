<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Colla;
use App\Services\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function index(string $shortname): View|RedirectResponse
    {
        $colla = Colla::where('shortname', $shortname)->firstOrFail();

        abort_unless($colla->isShopEnabled(), 404);

        if ($colla->shopNeedsLogin()) {
            return redirect()->guest(route('filament.member.auth.login'));
        }

        $isMember = $colla->authenticatedMemberIsMine();

        $products = $colla->products()
            ->where('active', true)
            ->when(!$isMember, fn ($q) => $q->where('members_only', false))
            ->with(['variants' => fn ($q) => $q->where('active', true)])
            ->orderBy('name')
            ->get();

        return view('botiga.index', [
            'colla'     => $colla,
            'products'  => $products,
            'isMember'  => $isMember,
            'cartCount' => (new Cart($colla->id))->count(),
        ]);
    }
}
