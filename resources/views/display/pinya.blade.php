<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $snapshot->name }} — {{ $colla->name }}</title>
    @vite('resources/js/board-editor.js')
    <script>
        window.__boardPositions    = @json($boardPositions);
        window.__autoLoadAssignments = @json($snapshot->assignments ?? []);
        window.__readOnly          = true;
    </script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #f9fafb; font-family: sans-serif; padding: 20px; }
        .header { margin-bottom: 16px; }
        .colla  { font-size: 20px; font-weight: 700; color: #111827; }
        .event  { font-size: 13px; color: #6b7280; margin-top: 2px; }
        .snap   { font-size: 12px; color: #9ca3af; margin-top: 2px; }
        .canvas-wrap { display: inline-block; background: white; border-radius: 10px;
                       border: 1px solid #e5e7eb; padding: 12px; }
        .footer { margin-top: 14px; font-size: 11px; color: #d1d5db; }
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

    <div class="canvas-wrap">
        <div id="board-canvas"></div>
    </div>

    <div class="footer">FemPinya 4</div>
</body>
</html>
