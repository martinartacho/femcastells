<x-filament-panels::page>
    @php
        $event  = $this->getEvent();
        $rondes = $this->getRondes();
        $colla  = filament()->getTenant();
        $events = $this->getEvents();
    @endphp

    @if($rondes->isNotEmpty())
        @php
            $rondesData = $rondes->map(fn($r) => [
                'ronda_number' => $r->ronda_number,
                'name'         => $r->name,
                'castle_type'  => $r->castle_type,
                'assignments'  => $r->assignments ?? [],
                'public_token' => $r->public_token,
            ])->values();
        @endphp
        @vite('resources/js/board-editor.js')
        <script>
            window.__boardPositions      = @json($this->getBoardPositions());
            window.__autoLoadAssignments = @json($rondes->first()?->assignments ?? []);
            window.__readOnly            = true;
            window.__rondesData          = @json($rondesData);
        </script>
    @endif

    {{-- Selector d'event (si n'hi ha més d'un amb rondes) --}}
    @if($events->count() > 1)
        <div style="display:flex; gap:8px; align-items:center; margin-bottom:16px; flex-wrap:wrap;">
            <span style="font-size:12px; color:#6b7280; flex-shrink:0;">Event:</span>
            @foreach($events as $ev)
                <button
                    wire:click="$set('eventId', {{ $ev->id }})"
                    style="font-size:12px; padding:4px 12px; border-radius:20px; border:1px solid {{ ($this->eventId ?? $event?->id) == $ev->id ? '#16a34a' : '#d1d5db' }}; background:{{ ($this->eventId ?? $event?->id) == $ev->id ? '#f0fdf4' : 'white' }}; color:{{ ($this->eventId ?? $event?->id) == $ev->id ? '#15803d' : '#374151' }}; cursor:pointer; font-weight:{{ ($this->eventId ?? $event?->id) == $ev->id ? '700' : '400' }}; white-space:nowrap;">
                    {{ $ev->start_date?->format('d/m') }} · {{ Str::limit($ev->name, 20) }}
                </button>
            @endforeach
        </div>
    @endif

    @if(!$event || $rondes->isEmpty())
        <div style="text-align:center; padding:60px 20px; color:#9ca3af;">
            <div style="font-size:36px; margin-bottom:12px;">🏰</div>
            <div style="font-size:15px; font-weight:600; color:#6b7280; margin-bottom:6px;">Sense rondes publicades</div>
            <div style="font-size:13px;">La tècnica publicarà les rondes quan estiguin llestes.</div>
        </div>
    @else
        {{-- Capçalera de l'event --}}
        <div style="margin-bottom:20px;">
            <div style="font-size:18px; font-weight:700; color:#111827;">
                {{ $event->start_date?->format('d/m/Y') }} · {{ $event->name }}
            </div>
            <div style="font-size:12px; color:#9ca3af; margin-top:2px;">
                {{ $rondes->count() }} {{ $rondes->count() === 1 ? 'ronda' : 'rondes' }}
                @if($colla->rondes_display === 'tabs') · Clica una pestanya per veure la pinya @endif
            </div>
        </div>

        @if($colla->rondes_display === 'tabs')
            {{-- MODE TABS --}}
            <div style="display:flex; gap:8px; overflow-x:auto; padding-bottom:4px; scrollbar-width:none; margin-bottom:16px;">
                @foreach($rondes as $i => $ronda)
                    <button
                        onclick="selectMemberTab({{ $i }}, this)"
                        style="flex-shrink:0; display:flex; align-items:center; gap:8px; padding:10px 16px; border-radius:10px; border:1px solid {{ $i === 0 ? '#16a34a' : '#e5e7eb' }}; background:{{ $i === 0 ? '#f0fdf4' : 'white' }}; color:{{ $i === 0 ? '#15803d' : '#6b7280' }}; cursor:pointer; font-size:13px; font-weight:600; white-space:nowrap; transition:all 0.15s;"
                        data-index="{{ $i }}"
                        class="member-tab {{ $i === 0 ? 'active-tab' : '' }}">
                        <span style="width:26px; height:26px; border-radius:50%; background:{{ $i === 0 ? '#16a34a' : '#e5e7eb' }}; color:{{ $i === 0 ? 'white' : '#6b7280' }}; font-size:13px; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0;" class="tab-badge">
                            {{ $ronda->ronda_number }}
                        </span>
                        <div style="text-align:left;">
                            <div style="font-size:13px; font-weight:700; line-height:1.2;">{{ $ronda->name }}</div>
                            <div style="font-size:10px; opacity:0.6; text-transform:uppercase; letter-spacing:0.05em;">{{ strtoupper($ronda->castle_type) }}</div>
                        </div>
                    </button>
                @endforeach
            </div>

            <div id="member-canvas-wrap" style="background:#f9fafb; border-radius:10px; border:1px solid #e5e7eb; padding:12px; display:flex; flex-direction:column; align-items:center;">
                <div id="board-canvas"></div>
                <div id="member-tab-label" style="font-size:12px; color:#6b7280; margin-top:6px;"></div>
            </div>

            <script>
            function selectMemberTab(index, btn) {
                document.querySelectorAll('.member-tab').forEach(b => {
                    b.style.borderColor = '#e5e7eb';
                    b.style.background  = 'white';
                    b.style.color       = '#6b7280';
                    b.querySelector('.tab-badge').style.background = '#e5e7eb';
                    b.querySelector('.tab-badge').style.color      = '#6b7280';
                });
                btn.style.borderColor = '#16a34a';
                btn.style.background  = '#f0fdf4';
                btn.style.color       = '#15803d';
                btn.querySelector('.tab-badge').style.background = '#16a34a';
                btn.querySelector('.tab-badge').style.color      = 'white';

                const ronda = window.__rondesData[index];
                if (!ronda || !window.boardEditor) return;

                window.boardEditor.loadCastleType(ronda.castle_type);
                window.boardEditor.applyAssignments(ronda.assignments || {});
                document.getElementById('member-tab-label').textContent =
                    'Ronda ' + ronda.ronda_number + ' · ' + ronda.name;
            }

            document.addEventListener('DOMContentLoaded', () => {
                const first = window.__rondesData?.[0];
                if (first) {
                    document.getElementById('member-tab-label').textContent =
                        'Ronda ' + first.ronda_number + ' · ' + first.name;
                }
            });
            </script>

        @else
            {{-- MODE CARDS --}}
            <div style="display:flex; flex-wrap:wrap; gap:12px;">
                @foreach($rondes as $ronda)
                    @if($ronda->public_token)
                        <a href="{{ route('display.pinya', [$colla->shortname, $ronda->public_token]) }}" target="_blank"
                            style="text-decoration:none; display:flex; flex-direction:column; align-items:center; gap:8px; background:white; border:1px solid #e5e7eb; border-radius:12px; padding:16px; width:160px; transition:border-color 0.15s;"
                            onmouseover="this.style.borderColor='#16a34a'" onmouseout="this.style.borderColor='#e5e7eb'">
                    @else
                        <div style="display:flex; flex-direction:column; align-items:center; gap:8px; background:white; border:1px solid #e5e7eb; border-radius:12px; padding:16px; width:160px;">
                    @endif
                            <div style="width:40px; height:40px; border-radius:50%; background:#16a34a; color:white; font-size:18px; font-weight:800; display:flex; align-items:center; justify-content:center;">
                                {{ $ronda->ronda_number }}
                            </div>
                            <div style="font-size:13px; font-weight:700; color:#111827; text-align:center; line-height:1.3;">{{ $ronda->name }}</div>
                            <div style="font-size:10px; color:#9ca3af; text-transform:uppercase; letter-spacing:0.05em;">{{ strtoupper($ronda->castle_type) }}</div>
                            @if($ronda->public_token)
                                <span style="font-size:11px; color:#16a34a; margin-top:2px;">Veure pinya →</span>
                            @endif
                    @if($ronda->public_token)
                        </a>
                    @else
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    @endif
</x-filament-panels::page>
