<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $colla->name }} — Pissarra</title>
    @vite('resources/js/board-editor.js')
    <script>
        window.__boardPositions       = @json($boardPositions);
        window.__castleViews          = @json($castleViews);
        window.__initialCastleType    = @json($snapshot?->castle_type ?? '');
        window.__autoLoadAssignments  = @json($snapshot?->assignments ?? []);
        window.__readOnly             = true;
        window.__highlightPosition    = @json($highlightPosition);
    </script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #111827; color: #f9fafb; font-family: sans-serif; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 24px; }

        .header { text-align: center; margin-bottom: 20px; }
        .colla-name { font-size: 28px; font-weight: 700; color: #f9fafb; letter-spacing: 0.02em; }
        .event-name { font-size: 15px; color: #9ca3af; margin-top: 4px; }
        .snap-name  { font-size: 13px; color: #6b7280; margin-top: 2px; }

        .canvas-wrap { background: #1f2937; border-radius: 16px; padding: 20px; border: 1px solid #374151; }

        .no-pinya { font-size: 20px; color: #6b7280; text-align: center; padding: 60px 40px; }
        .no-pinya span { display: block; font-size: 48px; margin-bottom: 12px; }

        .personal-badge { margin-top: 16px; background: #065f46; border: 1px solid #059669; border-radius: 8px; padding: 8px 20px; text-align: center; font-size: 14px; color: #d1fae5; }
        .personal-badge strong { font-size: 16px; display: block; margin-bottom: 2px; }

        .footer { margin-top: 16px; font-size: 11px; color: #374151; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="colla-name">{{ $colla->name }}</div>
        @if($snapshot?->event)
            <div class="event-name">
                {{ $snapshot->event->start_date?->format('d/m/Y') }}
                · {{ $snapshot->event->name }}
            </div>
        @endif
        @if($snapshot)
            <div class="snap-name">{{ $snapshot->name }}@if($template) · {{ $template->name }}@endif</div>
        @endif
    </div>

    @if($snapshot)
        <div class="canvas-wrap">
            <div id="board-view-switcher" style="display:none; gap:4px; align-items:center; justify-content:center; margin-bottom:12px;"></div>
            <div id="board-canvas"></div>
        </div>

        @if($casteller && $highlightPosition)
            <div class="personal-badge">
                <strong>{{ $casteller->alias }}</strong>
                La teva posició és destacada al mapa
            </div>
        @elseif($casteller && !$highlightPosition)
            <div class="personal-badge" style="background:#1f2937; border-color:#374151; color:#6b7280;">
                {{ $casteller->alias }} — sense posició assignada en aquesta pinya
            </div>
        @endif
    @else
        <div class="no-pinya">
            <span>📋</span>
            Cap pinya projectada ara mateix
        </div>
    @endif

    <div class="footer">FemPinya 4</div>

    @if($snapshot)
    <script>
        // Ressaltar la posició personal (fons groc)
        document.addEventListener('DOMContentLoaded', () => {
            const highlight = window.__highlightPosition;
            if (!highlight || typeof Konva === 'undefined') return;
            // La posició ressaltada s'aplica després que el canvas estigui inicialitzat
            // board-editor.js crida applyAssignments en DOMContentLoaded — afegim un segon listener
            setTimeout(() => {
                // Accés directe al stage de Konva per ressaltar una forma
                // Ho fem via un event custom que board-editor.js pot escoltar
                document.dispatchEvent(new CustomEvent('board-highlight', { detail: { posId: highlight } }));
            }, 200);
        });
    </script>
    @endif

    {{-- Auto-refresh cada 30 segons per recollir canvis de projecció --}}
    <script>
        setTimeout(() => location.reload(), 30000);
    </script>
</body>
</html>
