<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Colla;
use App\Models\Order;
use App\Services\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\Stripe;

class CheckoutController extends Controller
{
    public function store(Request $request, string $shortname): RedirectResponse
    {
        $colla = Colla::where('shortname', $shortname)->firstOrFail();
        abort_unless($colla->isShopEnabled(), 404);

        if ($colla->shopNeedsLogin()) {
            return redirect()->guest(route('filament.member.auth.login'));
        }

        $data = $request->validate([
            'payment_method' => ['required', 'in:' . Order::PAYMENT_METHOD_STRIPE . ',' . Order::PAYMENT_METHOD_BIZUM],
            'notes'          => ['nullable', 'string', 'max:1000'],
        ]);
        $paymentMethod = $data['payment_method'];

        if ($paymentMethod === Order::PAYMENT_METHOD_STRIPE) {
            abort_unless($colla->isStripeConnected(), 422, 'La colla encara no ha connectat el seu compte de pagament.');
        } else {
            abort_unless($colla->isBizumAvailable(), 422, 'La colla no accepta Bizum.');
        }

        $isMember = $colla->authenticatedMemberIsMine();
        $cart = new Cart($colla->id);
        $lines = $cart->lines($isMember);

        if ($lines->isEmpty()) {
            return redirect()->route('botiga.carret', $colla->shortname)
                ->with('error', 'El carret és buit.');
        }

        $member = $isMember ? auth('member')->user() : null;

        $order = Order::create([
            'colla_id'       => $colla->id,
            'casteller_id'   => $member?->id,
            'buyer_name'     => $member?->alias,
            'buyer_email'    => $member?->email,
            'notes'          => $data['notes'] ?? null,
            'status'         => Order::STATUS_PENDING,
            'payment_method' => $paymentMethod,
            'access_token'   => (string) Str::uuid(),
            'subtotal'       => $cart->subtotal($isMember),
            'tax_total'      => $cart->taxTotal($isMember),
            'total'          => $cart->total($isMember),
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

        if ($paymentMethod === Order::PAYMENT_METHOD_BIZUM) {
            (new Cart($colla->id))->clear();

            return redirect($this->successUrl($colla, $order));
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
            'mode'            => 'payment',
            'line_items'      => $lineItems,
            'customer_email'  => $order->buyer_email,
            'success_url'     => $this->successUrl($colla, $order) . '&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'      => route('botiga.carret', $colla->shortname),
            'metadata'        => ['order_id' => (string) $order->id],
        ], ['stripe_account' => $colla->stripe_account_id]);

        $order->update(['stripe_checkout_session_id' => $session->id]);

        return redirect()->away($session->url);
    }

    public function success(Request $request, string $shortname, Order $order): View
    {
        $this->authorizeOrderAccess($request, $shortname, $order);
        $colla = $order->colla;

        if ($order->status === Order::STATUS_PENDING && $order->isStripe()) {
            (new Cart($colla->id))->clear();
        }

        return view('botiga.confirmacio', [
            'colla' => $colla,
            'order' => $order,
        ]);
    }

    public function confirmSent(Request $request, string $shortname, Order $order): RedirectResponse
    {
        $this->authorizeOrderAccess($request, $shortname, $order);

        if ($order->status === Order::STATUS_PENDING) {
            $order->update(['buyer_confirmed_at' => now()]);
        }

        return redirect($this->successUrl($order->colla, $order));
    }

    public function cancel(Request $request, string $shortname, Order $order): RedirectResponse
    {
        $this->authorizeOrderAccess($request, $shortname, $order);

        if ($order->status === Order::STATUS_PENDING) {
            $order->update(['status' => Order::STATUS_CANCELLED]);
        }

        return redirect($this->successUrl($order->colla, $order));
    }

    private function authorizeOrderAccess(Request $request, string $shortname, Order $order): void
    {
        $colla = Colla::where('shortname', $shortname)->firstOrFail();
        abort_unless($order->colla_id === $colla->id, 404);
        abort_unless(
            filled($order->access_token) && hash_equals($order->access_token, (string) $request->query('token')),
            404
        );
    }

    private function successUrl(Colla $colla, Order $order): string
    {
        return route('botiga.checkout.success', [$colla->shortname, $order->id]) . '?token=' . $order->access_token;
    }
}
