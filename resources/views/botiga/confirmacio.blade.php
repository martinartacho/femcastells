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
            <p class="text-gray-500 text-sm mb-8">Comanda #{{ $order->id }} confirmada i pagada.</p>
        @else
            <p class="text-5xl mb-4">⏳</p>
            <h1 class="text-2xl font-extrabold text-gray-900 mb-2">Confirmant el pagament…</h1>
            <p class="text-gray-500 text-sm mb-8">
                Comanda #{{ $order->id }} — el pagament s'està processant. Rebràs confirmació en uns instants.
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
        </div>

        <a href="{{ route('botiga.index', $colla->shortname) }}" class="inline-block mt-8 text-sm font-semibold text-emerald-600 hover:text-emerald-700">
            ← Tornar a la botiga
        </a>

    </main>

</body>
</html>
