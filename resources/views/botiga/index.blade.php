<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Botiga · {{ $colla->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex flex-col">

    <header class="bg-white border-b border-gray-200">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between gap-4">
            <span class="text-sm font-bold text-gray-800">{{ $colla->name }}</span>
            <div class="flex items-center gap-4">
                <span class="text-xs text-gray-400 hidden sm:inline">
                    @if($isMember)
                        Preus de soci aplicats
                    @else
                        Preus públics — <a href="{{ route('filament.member.auth.login') }}" class="text-emerald-600 hover:text-emerald-700 font-semibold">Inicia sessió</a> per veure preus de soci
                    @endif
                </span>
                <a href="{{ route('botiga.carret', $colla->shortname) }}"
                   class="text-sm font-semibold text-emerald-600 hover:text-emerald-700 flex items-center gap-1">
                    🛒 Carret
                    @if($cartCount > 0)
                        <span class="text-xs font-bold bg-emerald-600 text-white rounded-full min-w-[1.25rem] h-5 px-1 flex items-center justify-center">
                            {{ $cartCount }}
                        </span>
                    @endif
                </a>
            </div>
        </div>
    </header>

    @if(session('success'))
        <div class="max-w-6xl mx-auto w-full px-6 pt-4">
            <div class="bg-emerald-50 text-emerald-700 text-sm font-semibold px-4 py-2 rounded-lg">
                {{ session('success') }}
            </div>
        </div>
    @endif

    <main class="flex-1 max-w-6xl mx-auto w-full px-6 py-14">

        <div class="mb-10">
            <p class="text-xs font-semibold uppercase tracking-widest text-emerald-500 mb-2">Botiga</p>
            <h1 class="text-3xl font-extrabold text-gray-900 mb-2">{{ $colla->name }}</h1>
            <p class="text-gray-500 text-sm">Productes oficials de la colla.</p>
        </div>

        @if($products->isEmpty())
            <p class="text-gray-400 text-sm">Encara no hi ha productes disponibles.</p>
        @else
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($products as $product)
                @php
                    $prices = $product->variants->map(fn ($v) => $v->priceFor($isMember));
                    $minPrice = $prices->min();
                    $maxPrice = $prices->max();
                @endphp
                <article class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
                    <div class="aspect-square bg-gray-100 p-3">
                        @if($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}"
                                 alt="{{ $product->name }}"
                                 loading="lazy"
                                 class="w-full h-full object-contain">
                        @endif
                    </div>
                    <div class="p-3 flex flex-col gap-1 flex-1">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <h2 class="text-sm font-bold text-gray-900">{{ $product->name }}</h2>
                            @if($product->members_only)
                                <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-violet-100 text-violet-700">Socis</span>
                            @endif
                        </div>
                        <div class="mt-auto pt-1 text-sm font-semibold text-gray-900">
                            @if($minPrice === $maxPrice)
                                {{ number_format((float) $minPrice, 2) }} €
                            @else
                                {{ number_format((float) $minPrice, 2) }}–{{ number_format((float) $maxPrice, 2) }} €
                            @endif
                        </div>

                        @if($product->variants->isNotEmpty())
                        <form method="POST" action="{{ route('botiga.carret.afegir', $colla->shortname) }}" class="pt-2">
                            @csrf
                            @if($product->variants->count() === 1)
                                <input type="hidden" name="variant_id" value="{{ $product->variants->first()->id }}">
                            @else
                                <select name="variant_id" class="w-full text-xs border-gray-300 rounded-lg mb-1.5">
                                    @foreach($product->variants as $variant)
                                        <option value="{{ $variant->id }}">
                                            {{ $variant->name }} — {{ number_format($variant->priceFor($isMember), 2) }} €
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                            <button type="submit"
                                    class="w-full text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg py-1.5 transition">
                                Afegir al carret
                            </button>
                        </form>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
        @endif

    </main>

    <footer class="border-t border-gray-200 py-6 text-center text-xs text-gray-400">
        {{ $colla->name }} · FemPinya
    </footer>

</body>
</html>
