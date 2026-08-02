<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Colla;
use App\Models\ProductVariant;
use App\Services\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function show(string $shortname): View|RedirectResponse
    {
        $colla = Colla::where('shortname', $shortname)->firstOrFail();
        abort_unless($colla->isShopEnabled(), 404);

        if ($colla->shopNeedsLogin()) {
            return redirect()->guest(route('filament.member.auth.login'));
        }

        $isMember = $colla->authenticatedMemberIsMine();
        $cart = new Cart($colla->id);

        return view('botiga.carret', [
            'colla'       => $colla,
            'isMember'    => $isMember,
            'lines'       => $cart->lines($isMember),
            'subtotal'    => $cart->subtotal($isMember),
            'taxTotal'    => $cart->taxTotal($isMember),
            'total'       => $cart->total($isMember),
            'stripeReady' => $colla->isStripeConnected(),
        ]);
    }

    public function add(Request $request, string $shortname): RedirectResponse
    {
        $colla = Colla::where('shortname', $shortname)->firstOrFail();
        abort_unless($colla->isShopEnabled(), 404);

        $data = $request->validate([
            'variant_id' => ['required', 'integer'],
            'quantity'   => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $isMember = $colla->authenticatedMemberIsMine();

        $variant = ProductVariant::where('id', $data['variant_id'])
            ->where('active', true)
            ->whereHas('product', fn ($q) => $q
                ->where('colla_id', $colla->id)
                ->where('active', true)
                ->when(!$isMember, fn ($q2) => $q2->where('members_only', false))
            )
            ->firstOrFail();

        (new Cart($colla->id))->add($variant->id, $data['quantity'] ?? 1);

        return back()->with('success', 'Afegit al carret.');
    }

    public function update(Request $request, string $shortname): RedirectResponse
    {
        $colla = Colla::where('shortname', $shortname)->firstOrFail();

        $data = $request->validate([
            'variant_id' => ['required', 'integer'],
            'quantity'   => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        (new Cart($colla->id))->update($data['variant_id'], $data['quantity']);

        return back();
    }

    public function remove(string $shortname, int $variant): RedirectResponse
    {
        $colla = Colla::where('shortname', $shortname)->firstOrFail();

        (new Cart($colla->id))->remove($variant);

        return back();
    }
}
