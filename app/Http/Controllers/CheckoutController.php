<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Colla;
use App\Models\Order;
use App\Services\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\Stripe;

class CheckoutController extends Controller
{
    public function store(string $shortname): RedirectResponse
    {
        $colla = Colla::where('shortname', $shortname)->firstOrFail();
        abort_unless($colla->isShopEnabled(), 404);

        if ($colla->shopNeedsLogin()) {
            return redirect()->guest(route('filament.member.auth.login'));
        }

        abort_unless($colla->isStripeConnected(), 422, 'La colla encara no ha connectat el seu compte de pagament.');

        $isMember = $colla->authenticatedMemberIsMine();
        $cart = new Cart($colla->id);
        $lines = $cart->lines($isMember);

        if ($lines->isEmpty()) {
            return redirect()->route('botiga.carret', $colla->shortname)
                ->with('error', 'El carret és buit.');
        }

        $member = $isMember ? auth('member')->user() : null;

        $order = Order::create([
            'colla_id'     => $colla->id,
            'casteller_id' => $member?->id,
            'buyer_name'   => $member?->alias,
            'buyer_email'  => $member?->email,
            'status'       => Order::STATUS_PENDING,
            'subtotal'     => $cart->subtotal($isMember),
            'tax_total'    => $cart->taxTotal($isMember),
            'total'        => $cart->total($isMember),
        ]);

        foreach ($lines as $line) {
            $variant = $line['variant'];

            $order->items()->create([
                'product_variant_id' => $variant->id,
                'product_name'       => $variant->product->name,
                'variant_name'       => $variant->name,
                'unit_price'         => $variant->priceFor($isMember),
                'tax_rate'           => $variant->tax_rate,
                'quantity'           => $line['quantity'],
            ]);
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $lineItems = $order->items->map(fn ($item) => [
            'price_data' => [
                'currency'     => 'eur',
                'product_data' => [
                    'name' => $item->variant_name !== 'Estàndard'
                        ? "{$item->product_name} ({$item->variant_name})"
                        : $item->product_name,
                ],
                'unit_amount' => (int) round($item->unitPriceWithTax() * 100),
            ],
            'quantity' => $item->quantity,
        ])->all();

        $session = StripeCheckoutSession::create([
            'mode'          => 'payment',
            'line_items'    => $lineItems,
            'customer_email' => $order->buyer_email,
            'success_url'   => route('botiga.checkout.success', [$colla->shortname, $order->id]) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'    => route('botiga.carret', $colla->shortname),
            'metadata'      => ['order_id' => (string) $order->id],
        ], ['stripe_account' => $colla->stripe_account_id]);

        $order->update(['stripe_checkout_session_id' => $session->id]);

        return redirect()->away($session->url);
    }

    public function success(Request $request, string $shortname, Order $order): View
    {
        $colla = Colla::where('shortname', $shortname)->firstOrFail();
        abort_unless($order->colla_id === $colla->id, 404);
        abort_unless($order->stripe_checkout_session_id === $request->query('session_id'), 404);

        if ($order->status === Order::STATUS_PENDING) {
            (new Cart($colla->id))->clear();
        }

        return view('botiga.confirmacio', [
            'colla' => $colla,
            'order' => $order,
        ]);
    }
}
