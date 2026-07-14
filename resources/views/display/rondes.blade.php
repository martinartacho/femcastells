<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rondes · {{ $event->name }} — {{ $colla->name }}</title>
    @if($colla->rondes_display === 'tabs')
        @vite('resources/js/board-editor.js')
        @php
            $rondesData = $rondes->map(fn($r) => [
                'ronda_number' => $r->ronda_number,
                'name'         => $r->name,
                'castle_type'  => $r->castle_type,
                'assignments'  => $r->assignments ?? [],
                'public_token' => $r->public_token,
            ])->values();
        @endphp
        <script>
            window.__boardPositions      = @json($boardPositions);
            window.__autoLoadAssignments = @json($rondes->first()?->assignments ?? []);
            window.__readOnly            = true;
            window.__rondesData          = @json($rondesData);
        </script>
    @endif
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #0f172a; font-family: system-ui, sans-serif; min-height: 100vh; color: #f1f5f9; }

        /* --- CAPÇALERA --- */
        .header { text-align: center; padding: 24px 16px 16px; }
        .header .colla      { font-size: 12px; font-weight: 600; color: #64748b; letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 5px; }
        .header .event-name { font-size: 20px; font-weight: 800; color: #f1f5f9; margin-bottom: 3px; }
        .header .event-date { font-size: 12px; color: #64748b; }

        /* --- MODE CARDS --- */
        .rondes-grid { display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; max-width: 900px; margin: 0 auto; padding: 16px; }
        .ronda-card {
            background: #1e293b; border: 1px solid #334155; border-radius: 14px;
            padding: 18px 20px; width: 180px; display: flex; flex-direction: column;
            align-items: center; gap: 10px; text-decoration: none;
            transition: border-color 0.15s, background 0.15s;
        }
        .ronda-card:hover { background: #273549; border-color: #3b82f6; }

        /* --- MODE TABS --- */
        .tabs-bar {
            display: flex; gap: 8px; overflow-x: auto; padding: 12px 16px;
            scrollbar-width: none; -ms-overflow-style: none;
            border-bottom: 1px solid #1e293b; background: #0f172a;
        }
        .tabs-bar::-webkit-scrollbar { display: none; }

        .tab-btn {
            flex-shrink: 0; display: flex; align-items: center; gap: 8px;
            padding: 10px 16px; border-radius: 10px; border: 1px solid #334155;
            background: #1e293b; color: #94a3b8; cursor: pointer;
            font-size: 13px; font-weight: 600; transition: all 0.15s; white-space: nowrap;
        }
        .tab-btn:hover { background: #273549; border-color: #3b82f6; color: #e2e8f0; }
        .tab-btn.active { background: #1d4ed8; border-color: #3b82f6; color: white; }

        .tab-num {
            width: 26px; height: 26px; border-radius: 50%; background: rgba(255,255,255,0.15);
            font-size: 13px; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .tab-btn.active .tab-num { background: rgba(255,255,255,0.25); }

        .tab-info { display: flex; flex-direction: column; text-align: left; }
        .tab-name { font-size: 13px; font-weight: 700; line-height: 1.2; }
        .tab-type { font-size: 10px; font-weight: 500; opacity: 0.65; text-transform: uppercase; letter-spacing: 0.05em; }

        .canvas-panel {
            padding: 16px; display: flex; flex-direction: column; align-items: center;
        }
        .canvas-label {
            font-size: 11px; color: #475569; margin-top: 8px; text-align: center;
        }
        .pinya-link {
            display: inline-block; margin-top: 10px; font-size: 12px; color: #3b82f6;
            text-decoration: underline; cursor: pointer;
        }

        /* --- COMUNS --- */
        .ronda-num {
            width: 44px; height: 44px; border-radius: 50%; background: #1d4ed8;
            color: white; font-size: 20px; font-weight: 800;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .ronda-name  { font-size: 14px; font-weight: 700; color: #e2e8f0; text-align: center; line-height: 1.3; word-break: break-word; }
        .ronda-type  { font-size: 10px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.06em; }
        .ronda-link  { font-size: 11px; color: #3b82f6; margin-top: 2px; }
        .ronda-card:hover .ronda-link { text-decoration: underline; }
        .no-pinya    { font-size: 10px; color: #475569; margin-top: 2px; }

        .empty  { text-align: center; color: #475569; font-size: 15px; padding: 60px 0; }
        .footer { text-align: center; padding: 24px 16px 16px; font-size: 11px; color: #1e293b; }
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

    @elseif($colla->rondes_display === 'tabs')
        {{-- MODE TABS --}}
        <div class="tabs-bar" id="tabs-bar">
            @foreach($rondes as $i => $ronda)
                <button class="tab-btn {{ $i === 0 ? 'active' : '' }}"
                    onclick="selectTab({{ $i }}, this)"
                    data-index="{{ $i }}">
                    <div class="tab-num">{{ $ronda->ronda_number }}</div>
                    <div class="tab-info">
                        <div class="tab-name">{{ $ronda->name }}</div>
                        <div class="tab-type">{{ strtoupper($ronda->castle_type) }}</div>
                    </div>
                </button>
            @endforeach
        </div>

        <div class="canvas-panel">
            <div id="board-canvas"></div>
            <div id="tab-label" class="canvas-label"></div>
            <a id="tab-pinya-link" class="pinya-link" style="display:none;" target="_blank">Veure pinya completa →</a>
        </div>

        <script>
        function selectTab(index, btn) {
            // Marca tab activa
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const ronda = window.__rondesData[index];
            if (!ronda) return;

            // Canvia tipologia si cal, després aplica assignacions
            if (window.boardEditor) {
                window.boardEditor.loadCastleType(ronda.castle_type);
                window.boardEditor.applyAssignments(ronda.assignments || {});
            }

            // Actualitza label i link
            document.getElementById('tab-label').textContent =
                'Ronda ' + ronda.ronda_number + ' · ' + ronda.name;

            const link = document.getElementById('tab-pinya-link');
            if (ronda.public_token) {
                link.href = '{{ url("/share/" . $colla->shortname . "/") }}' + ronda.public_token;
                link.style.display = 'inline-block';
            } else {
                link.style.display = 'none';
            }
        }

        // Inicialitza label de la primera ronda quan el canvas estigui llest
        document.addEventListener('DOMContentLoaded', () => {
            const first = window.__rondesData?.[0];
            if (first) {
                document.getElementById('tab-label').textContent =
                    'Ronda ' + first.ronda_number + ' · ' + first.name;
                const link = document.getElementById('tab-pinya-link');
                if (first.public_token) {
                    link.href = '{{ url("/share/" . $colla->shortname . "/") }}' + first.public_token;
                    link.style.display = 'inline-block';
                }
            }
        });
        </script>

    @else
        {{-- MODE CARDS --}}
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
