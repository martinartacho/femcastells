<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novetats · FemPinya</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex flex-col">

    <header class="bg-white border-b border-gray-200">
        <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="{{ route('noticies.index') }}"
               class="text-sm text-gray-500 hover:text-gray-700 transition">← Notícies</a>
            <span class="text-sm font-bold text-gray-800">FemPinya</span>
            <a href="{{ url('/admin') }}"
               class="text-sm font-semibold text-red-600 hover:text-red-700 transition">Panel →</a>
        </div>
    </header>

    <main class="flex-1 max-w-3xl mx-auto w-full px-6 py-14">

        <div class="mb-12">
            <p class="text-xs font-semibold uppercase tracking-widest text-red-500 mb-2">Changelog</p>
            <h1 class="text-3xl font-extrabold text-gray-900 mb-2">Novetats de la plataforma</h1>
            <p class="text-gray-500 text-sm">Historial de versions i canvis rellevants de FemPinya.</p>
        </div>

        {{-- ── Releases dinàmiques (BD, label=sistema) ── --}}
        @if($releases->isNotEmpty())
        <section class="mb-16">
            <div class="flex flex-col gap-8">
                @foreach($releases as $r)
                <article class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8">
                    <div class="flex items-center gap-3 mb-4">
                        @if($r->version)
                        <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-700 font-mono">
                            v{{ $r->version }}
                        </span>
                        @endif
                        <span class="text-xs text-gray-400">
                            {{ $r->published_at->translatedFormat('d \d\e F \d\e Y') }}
                        </span>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 mb-2">{{ $r->title }}</h2>
                    @if($r->summary)
                    <p class="text-gray-500 text-sm mb-4 leading-relaxed">{{ $r->summary }}</p>
                    @endif
                    <div class="prose prose-sm max-w-none text-gray-700">
                        {!! $r->body !!}
                    </div>
                </article>
                @endforeach
            </div>
        </section>
        @endif

        {{-- ── Release notes estàtiques ── --}}
        <section>
            <h2 class="text-sm font-semibold uppercase tracking-widest text-gray-400 mb-8">Versions anteriors</h2>

            <div class="flex flex-col gap-6">

                <article class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-gray-100 text-gray-600 font-mono">v1.0.0</span>
                        <span class="text-xs text-gray-400">Juliol 2026</span>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-red-100 text-red-700 ml-auto">Llançament</span>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 mb-3">Primera versió pública</h2>
                    <ul class="text-sm text-gray-600 space-y-1 list-disc list-inside">
                        <li>Gestió de colles i castellers</li>
                        <li>Calendari d'esdeveniments i assistència</li>
                        <li>Pissarra de pinya amb editor Konva</li>
                        <li>Generador de pinyes automàtic</li>
                        <li>Portal del membre</li>
                        <li>Multi-tenant per colla</li>
                    </ul>
                </article>

            </div>
        </section>

    </main>

    <footer class="border-t border-gray-200 py-6 text-center text-xs text-gray-400">
        FemPinya · <a href="{{ route('noticies.index') }}" class="hover:text-gray-600">Notícies</a>
    </footer>

</body>
</html>
