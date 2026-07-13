<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rondes · {{ $event->name }} — {{ $colla->name }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #0f172a; font-family: system-ui, sans-serif; min-height: 100vh; padding: 24px 16px 40px; color: #f1f5f9; }

        .header { text-align: center; margin-bottom: 32px; }
        .header .colla { font-size: 13px; font-weight: 600; color: #94a3b8; letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 6px; }
        .header .event-name { font-size: 22px; font-weight: 800; color: #f1f5f9; margin-bottom: 4px; }
        .header .event-date { font-size: 13px; color: #64748b; }

        .rondes-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
            max-width: 900px;
            margin: 0 auto;
        }

        .ronda-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 14px;
            padding: 18px 20px;
            width: 180px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            transition: border-color 0.15s, background 0.15s;
            position: relative;
        }
        .ronda-card:hover { background: #273549; border-color: #3b82f6; }

        .ronda-num {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #1d4ed8;
            color: white;
            font-size: 20px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .ronda-name {
            font-size: 14px;
            font-weight: 700;
            color: #e2e8f0;
            text-align: center;
            line-height: 1.3;
            word-break: break-word;
        }

        .ronda-type {
            font-size: 10px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .ronda-link {
            font-size: 11px;
            color: #3b82f6;
            text-decoration: none;
            margin-top: 2px;
        }
        .ronda-card:hover .ronda-link { text-decoration: underline; }

        .no-pinya {
            font-size: 10px;
            color: #475569;
            margin-top: 2px;
        }

        .empty { text-align: center; color: #475569; font-size: 15px; padding: 60px 0; }

        .footer { text-align: center; margin-top: 40px; font-size: 11px; color: #334155; }
    </style>
</head>
<body>
    <div class="header">
        <div class="colla">{{ $colla->name }}</div>
        <div class="event-name">{{ $event->name }}</div>
        <div class="event-date">
            {{ $event->start_date?->format('d/m/Y') }}
            @if($event->type === 2) · Actuació @elseif($event->type === 1) · Assaig @elseif($event->type === 3) · Activitat @endif
        </div>
    </div>

    @if($rondes->isEmpty())
        <div class="empty">Encara no hi ha rondes publicades per a aquest event.</div>
    @else
        <div class="rondes-grid">
            @foreach($rondes as $ronda)
                @if($ronda->public_token)
                    <a class="ronda-card" href="{{ route('display.pinya', [$colla->shortname, $ronda->public_token]) }}" target="_blank">
                @else
                    <div class="ronda-card">
                @endif
                        <div class="ronda-num">{{ $ronda->ronda_number }}</div>
                        <div class="ronda-name">{{ $ronda->name }}</div>
                        <div class="ronda-type">{{ strtoupper($ronda->castle_type) }}</div>
                        @if($ronda->public_token)
                            <span class="ronda-link">Veure pinya →</span>
                        @else
                            <span class="no-pinya">Sense URL pública</span>
                        @endif
                @if($ronda->public_token)
                    </a>
                @else
                    </div>
                @endif
            @endforeach
        </div>
    @endif

    <div class="footer">FemPinya 4</div>
</body>
</html>
