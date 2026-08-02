<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comanda · {{ $colla->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex flex-col">

    <header class="bg-white border-b border-gray-200">
        <div class="max-w-3xl mx-auto px-6 py-4 flex items-center justify-between">
            <span class="text-sm font-bold text-gray-800">{{ $colla->name }}</span>
        </div>
    </header>

    <main class="flex-1 max-w-3xl mx-auto w-full px-6 py-14 text-center">

        @if($order->status === \App\Models\Order::STATUS_PAID)
            <p class="text-5xl mb-4">✅</p>
            <h1 class="text-2xl font-extrabold text-gray-900 mb-2">Gràcies per la compra!</h1>
            <p class="text-gray-500 text-sm mb-8">Comanda {{ $order->formattedReferenceCode() }} confirmada i pagada.</p>

        @elseif($order->status === \App\Models\Order::STATUS_CANCELLED)
            <p class="text-5xl mb-4">🚫</p>
            <h1 class="text-2xl font-extrabold text-gray-900 mb-2">Comanda anul·lada</h1>
            <p class="text-gray-500 text-sm mb-8">Comanda {{ $order->formattedReferenceCode() }} — no s'ha processat cap pagament.</p>

        @elseif($order->isBizum())
            <p class="text-5xl mb-4">📲</p>
            <h1 class="text-2xl font-extrabold text-gray-900 mb-2">Completa el pagament amb Bizum</h1>

            @if($order->buyer_confirmed_at)
                <p class="text-gray-500 text-sm mb-6">
                    Comanda {{ $order->formattedReferenceCode() }} — has indicat que ja has enviat el Bizum.
                    Un admin de la colla ho verificarà i rebràs confirmació.
                </p>
            @else
                <p class="text-gray-500 text-sm mb-6">
                    Comanda {{ $order->formattedReferenceCode() }} — envia el Bizum i confirma't-ho aquí.
                </p>
            @endif

            <div class="bg-emerald-50 rounded-2xl p-6 text-left max-w-md mx-auto mb-8 space-y-1">
                <p class="text-sm text-emerald-800">
                    Envia <strong>{{ number_format((float) $order->total, 2) }} €</strong> per Bizum a la colla <strong>{{ $colla->name }}</strong>:
                </p>
                <p class="text-xl font-bold text-emerald-900">{{ $colla->bizum_phone }}</p>
                <p class="text-xs text-emerald-700 mt-2">Concepte: <strong>{{ $order->formattedReferenceCode() }}</strong></p>
            </div>

            @unless($order->buyer_confirmed_at)
                <div class="flex items-center justify-center gap-3 mb-8">
                    <form method="POST" action="{{ route('botiga.checkout.confirmar', [$colla->shortname, $order->id, 'token' => $order->access_token]) }}">
                        @csrf
                        <button type="submit" class="text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg px-5 py-2.5 transition">
                            Ja l'he enviat — Confirmar
                        </button>
                    </form>
                    <form method="POST" action="{{ route('botiga.checkout.anullar', [$colla->shortname, $order->id, 'token' => $order->access_token]) }}">
                        @csrf
                        <button type="submit" class="text-sm font-semibold text-gray-500 hover:text-red-600 border border-gray-300 rounded-lg px-5 py-2.5 transition">
                            Anul·lar
                        </button>
                    </form>
                </div>
            @endunless

        @else
            <p class="text-5xl mb-4">⏳</p>
            <h1 class="text-2xl font-extrabold text-gray-900 mb-2">Confirmant el pagament…</h1>
            <p class="text-gray-500 text-sm mb-8">
                Comanda {{ $order->formattedReferenceCode() }} — el pagament s'està processant. Rebràs confirmació en uns instants.
            </p>
        @endif

        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 text-left max-w-md mx-auto">
            @foreach($order->items as $item)
                <div class="flex justify-between text-sm py-1.5 border-b border-gray-50 last:border-0">
                    <span>{{ $item->quantity }}× {{ $item->product_name }}</span>
                    <span class="font-semibold">{{ number_format($item->lineTotal(), 2) }} €</span>
                </div>
            @endforeach
            <div class="flex justify-between text-base font-bold pt-3 mt-2 border-t border-gray-200">
                <span>Total</span>
                <span>{{ number_format((float) $order->total, 2) }} €</span>
            </div>
            @if($order->notes)
                <div class="mt-4 pt-3 border-t border-gray-100">
                    <p class="text-xs font-semibold text-gray-400 mb-1">Observacions</p>
                    <p class="text-sm text-gray-600">{{ $order->notes }}</p>
                </div>
            @endif
        </div>

        <a href="{{ route('botiga.index', $colla->shortname) }}" class="inline-block mt-8 text-sm font-semibold text-emerald-600 hover:text-emerald-700">
            ← Tornar a la botiga
        </a>

    </main>

</body>
</html>
