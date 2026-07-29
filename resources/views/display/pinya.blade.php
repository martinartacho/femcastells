<!DOCTYPE html>
<html lang="ca" style="height:100%;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $snapshot->name }} — {{ $colla->name }}</title>
    @vite('resources/js/board-editor.js')
    <script>
        window.__boardPositions      = @json($boardPositions);
        window.__castleViews         = @json($castleViews);
        window.__initialCastleType   = @json($snapshot->castle_type ?? '');
        window.__autoLoadAssignments = @json($snapshot->assignments ?? []);
        window.__readOnly            = true;
        window.__highlightAlias      = @json(request('alias') ?? '');
    </script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; overflow: hidden; font-family: sans-serif; background: #f9fafb; }
        body { display: flex; flex-direction: column; }
        .header { padding: 10px 16px 8px; flex-shrink: 0; border-bottom: 1px solid #e5e7eb; }
        .colla  { font-size: 18px; font-weight: 700; color: #111827; }
        .event  { font-size: 12px; color: #6b7280; margin-top: 2px; }
        .snap   { font-size: 11px; color: #9ca3af; margin-top: 2px; }
        #board-wrapper { flex: 1; min-height: 0; overflow: hidden; }
    </style>
</head>
<body>
    <div class="header">
        <div class="colla">{{ $colla->name }}</div>
        @if($snapshot->event)
            <div class="event">
                {{ $snapshot->event->start_date?->format('d/m/Y') }}
                · {{ $snapshot->event->name }}
            </div>
        @endif
        <div class="snap">
            {{ $snapshot->name }}
            @if($template) · {{ $template->name }} @endif
        </div>
    </div>

    <div id="board-view-switcher" style="display:none; gap:4px; align-items:center; padding:6px 16px; border-bottom:1px solid #e5e7eb; background:#fff; flex-shrink:0;"></div>

    <div id="board-wrapper">
        <div id="board-canvas"></div>
    </div>
</body>
</html>
