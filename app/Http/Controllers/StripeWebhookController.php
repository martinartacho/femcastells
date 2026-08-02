<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature'),
                config('services.stripe.connect_webhook_secret'),
            );
        } catch (SignatureVerificationException|\UnexpectedValueException $e) {
            Log::warning('[StripeWebhook] signatura invàlida', ['message' => $e->getMessage()]);

            return response('Invalid signature', 400);
        }

        Log::info('[StripeWebhook] event rebut', ['type' => $event->type, 'id' => $event->id]);

        match ($event->type) {
            'checkout.session.completed',
            'checkout.session.async_payment_succeeded' => $this->markPaid($event),
            'checkout.session.async_payment_failed' => $this->markFailed($event),
            default => null,
        };

        return response('OK', 200);
    }

    private function markPaid(Event $event): void
    {
        $session = $event->data->object;
        $orderId = $session->metadata->order_id ?? null;

        $order = $orderId ? Order::find($orderId) : null;

        if (!$order) {
            Log::warning('[StripeWebhook] comanda no trobada', ['order_id' => $orderId]);
            return;
        }

        if ($order->status === Order::STATUS_PAID) {
            return;
        }

        $order->update([
            'status'                    => Order::STATUS_PAID,
            'paid_at'                   => now(),
            'stripe_payment_intent_id'  => $session->payment_intent,
        ]);

        Log::info('[StripeWebhook] comanda marcada pagada', ['order_id' => $order->id]);
    }

    private function markFailed(Event $event): void
    {
        $session = $event->data->object;
        $orderId = $session->metadata->order_id ?? null;

        $order = $orderId ? Order::find($orderId) : null;

        if ($order && $order->status === Order::STATUS_PENDING) {
            $order->update(['status' => Order::STATUS_CANCELLED]);
        }
    }
}
