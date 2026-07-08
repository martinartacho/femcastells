<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ request('label') === 'sistema' ? 'Novetats' : 'Notícies' }} · FemPinya</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex flex-col">

    <header class="bg-white border-b border-gray-200">
        <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="{{ route('noticies.index', ['label' => 'sistema']) }}"
               class="text-sm text-gray-500 hover:text-gray-700 transition">Novetats →</a>
            <span class="text-sm font-bold text-gray-800">FemPinya</span>
            <a href="{{ url('/admin') }}"
               class="text-sm font-semibold text-red-600 hover:text-red-700 transition">Panel →</a>
        </div>
    </header>

    <main class="flex-1 max-w-3xl mx-auto w-full px-6 py-14">

        <div class="mb-10">
            @if(request('label') === 'sistema')
            <p class="text-xs font-semibold uppercase tracking-widest text-red-500 mb-2">Changelog</p>
            <h1 class="text-3xl font-extrabold text-gray-900 mb-2">Novetats de la plataforma</h1>
            <p class="text-gray-500 text-sm">Historial de versions i canvis rellevants de FemPinya.</p>
            @else
            <p class="text-xs font-semibold uppercase tracking-widest text-red-500 mb-2">Plataforma</p>
            <h1 class="text-3xl font-extrabold text-gray-900 mb-2">Notícies</h1>
            <p class="text-gray-500 text-sm">Tot el que millora a FemPinya, explicat sense tecnicismes.</p>
            @endif
        </div>

        {{-- Filtre per label --}}
        @php
            $labelActiu = request('label', 'tots');
            $colors = [
                'tots'       => 'bg-gray-900 text-white',
                'general'    => 'bg-gray-600 text-white',
                'pissarra'   => 'bg-red-600 text-white',
                'castellers' => 'bg-amber-500 text-white',
                'events'     => 'bg-emerald-600 text-white',
                'admin'      => 'bg-violet-600 text-white',
                'sistema'    => 'bg-blue-600 text-white',
            ];
            $colorsInactiu = [
                'tots'       => 'bg-white text-gray-600 border border-gray-300 hover:bg-gray-50',
                'general'    => 'bg-white text-gray-600 border border-gray-300 hover:bg-gray-50',
                'pissarra'   => 'bg-white text-red-600 border border-red-200 hover:bg-red-50',
                'castellers' => 'bg-white text-amber-600 border border-amber-200 hover:bg-amber-50',
                'events'     => 'bg-white text-emerald-600 border border-emerald-200 hover:bg-emerald-50',
                'admin'      => 'bg-white text-violet-600 border border-violet-200 hover:bg-violet-50',
                'sistema'    => 'bg-white text-blue-600 border border-blue-200 hover:bg-blue-50',
            ];
            $filtres = ['tots' => 'Totes'] + $categories;
        @endphp
        <div class="flex flex-wrap gap-2 mb-10">
            @foreach($filtres as $clau => $etiqueta)
            <a href="{{ route('noticies.index', $clau !== 'tots' ? ['label' => $clau] : []) }}"
               class="text-xs font-semibold px-4 py-1.5 rounded-full transition
                      {{ $labelActiu === $clau ? ($colors[$clau] ?? $colors['general']) : ($colorsInactiu[$clau] ?? $colorsInactiu['general']) }}">
                {{ $etiqueta }}
            </a>
            @endforeach
        </div>

        @php
            $noticiesFiltrades = $labelActiu === 'tots'
                ? $noticies
                : $noticies->filter(fn($n) => in_array($labelActiu, $n->labels ?? []));
            $badgeColors = [
                'general'    => 'bg-gray-100 text-gray-600',
                'pissarra'   => 'bg-red-100 text-red-700',
                'castellers' => 'bg-amber-100 text-amber-700',
                'events'     => 'bg-emerald-100 text-emerald-700',
                'admin'      => 'bg-violet-100 text-violet-700',
                'sistema'    => 'bg-blue-100 text-blue-700',
            ];
        @endphp

        @if($noticiesFiltrades->isEmpty())
            <p class="text-gray-400 text-sm">No hi ha notícies en aquesta categoria.</p>
        @else
        <div class="flex flex-col gap-8">
            @foreach($noticiesFiltrades as $noticia)
            <article class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8">
                <div class="flex items-center gap-3 mb-4 flex-wrap">
                    @foreach($noticia->labels ?? [] as $label)
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full {{ $badgeColors[$label] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ $categories[$label] ?? $label }}
                    </span>
                    @endforeach
                    @if($noticia->version)
                    <span class="text-xs text-gray-400 font-mono">{{ $noticia->version }}</span>
                    @endif
                    <span class="text-xs text-gray-400 ml-auto">
                        {{ $noticia->published_at->translatedFormat('d \d\e F \d\e Y') }}
                    </span>
                </div>
                <h2 class="text-xl font-bold text-gray-900 mb-2">{{ $noticia->title }}</h2>
                @if($noticia->summary)
                <p class="text-gray-500 text-sm mb-4 leading-relaxed">{{ $noticia->summary }}</p>
                @endif
                <div class="prose prose-sm max-w-none text-gray-700">
                    {!! $noticia->body !!}
                </div>
            </article>
            @endforeach
        </div>
        @endif

    </main>

    <footer class="border-t border-gray-200 py-6 text-center text-xs text-gray-400">
        FemPinya · <a href="{{ route('noticies.index', ['label' => 'sistema']) }}" class="hover:text-gray-600">Novetats</a>
    </footer>

</body>
</html>
