<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carret · {{ $colla->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex flex-col">

    <header class="bg-white border-b border-gray-200">
        <div class="max-w-3xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="{{ route('botiga.index', $colla->shortname) }}"
               class="text-sm text-gray-500 hover:text-gray-700 transition">← Seguir comprant</a>
            <span class="text-sm font-bold text-gray-800">{{ $colla->name }}</span>
        </div>
    </header>

    <main class="flex-1 max-w-3xl mx-auto w-full px-6 py-14">

        <h1 class="text-2xl font-extrabold text-gray-900 mb-8">El teu carret</h1>

        @if(session('error'))
            <div class="mb-6 bg-red-50 text-red-700 text-sm font-semibold px-4 py-2 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        @if($lines->isEmpty())
            <p class="text-gray-400 text-sm">El carret és buit.
                <a href="{{ route('botiga.index', $colla->shortname) }}" class="text-emerald-600 font-semibold">Anar a la botiga →</a>
            </p>
        @else
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm divide-y divide-gray-100">
            @foreach($lines as $line)
                @php $variant = $line['variant']; @endphp
                <div class="flex items-center gap-4 p-5">
                    @if($variant->product->image)
                        <img src="{{ asset('storage/' . $variant->product->image) }}"
                             alt="{{ $variant->product->name }}"
                             class="w-16 h-16 object-contain bg-gray-50 rounded-lg flex-shrink-0">
                    @endif

                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-gray-900 truncate">{{ $variant->product->name }}</p>
                        @if($variant->name !== 'Estàndard')
                            <p class="text-xs text-gray-400">{{ $variant->name }}</p>
                        @endif
                        <p class="text-sm text-gray-500">{{ number_format($variant->priceFor($isMember), 2) }} € / unitat</p>
                    </div>

                    <form method="POST" action="{{ route('botiga.carret.actualitzar', $colla->shortname) }}" class="flex items-center gap-2">
                        @csrf
                        <input type="hidden" name="variant_id" value="{{ $variant->id }}">
                        <input type="number" name="quantity" value="{{ $line['quantity'] }}" min="1" max="99"
                               onchange="this.form.submit()"
                               class="w-16 text-sm border-gray-300 rounded-lg text-center">
                    </form>

                    <p class="w-20 text-right text-sm font-semibold text-gray-900">
                        {{ number_format($variant->priceFor($isMember) * $line['quantity'], 2) }} €
                    </p>

                    <form method="POST" action="{{ route('botiga.carret.eliminar', [$colla->shortname, $variant->id]) }}">
                        @csrf
                        <button type="submit" class="text-gray-400 hover:text-red-600 transition" title="Eliminar">✕</button>
                    </form>
                </div>
            @endforeach
        </div>

        <div class="mt-8 bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-2">
            <div class="flex justify-between text-sm text-gray-500">
                <span>Subtotal</span>
                <span>{{ number_format($subtotal, 2) }} €</span>
            </div>
            <div class="flex justify-between text-sm text-gray-500">
                <span>IVA</span>
                <span>{{ number_format($taxTotal, 2) }} €</span>
            </div>
            <div class="flex justify-between text-lg font-bold text-gray-900 pt-2 border-t border-gray-100">
                <span>Total</span>
                <span>{{ number_format($total, 2) }} €</span>
            </div>

            @if($stripeReady)
                <form method="POST" action="{{ route('botiga.checkout', $colla->shortname) }}">
                    @csrf
                    <button type="submit"
                            class="w-full mt-4 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg py-3 transition">
                        Pagar amb Stripe
                    </button>
                </form>
            @else
                <p class="mt-4 text-xs text-center text-gray-400">
                    La colla encara no ha connectat el seu compte de pagament — el checkout no està disponible.
                </p>
            @endif
        </div>
        @endif

    </main>

    <footer class="border-t border-gray-200 py-6 text-center text-xs text-gray-400">
        {{ $colla->name }} · FemPinya
    </footer>

</body>
</html>
