<x-filament-panels::page>
    @vite('resources/js/board-editor.js')
    <script>window.__boardPositions = @json($this->getCastlePositions());</script>
    <script>window.__castellersData = @json($this->getCastellerPhysicalData());</script>
    <script>window.__heightAlertCm = @json((int) ($colla->board_settings['height_alert_cm'] ?? 15));</script>
    <script>window.__autoLoadAssignments = @json($this->getAutoLoadAssignments() ?? []);</script>
    <script>window.__initialCastleType = @json($this->castleType);</script>
    <script>window.__castleViews = @json($this->getCastleViews());</script>
    @php $colla = filament()->getTenant(); @endphp
    <script>
        window.__showLogo  = @json((bool) ($colla?->show_logo_in_pinya));
        window.__logoUrl   = @json($colla?->logo ? asset('storage/' . $colla->logo) : null);
    </script>

    <style>
        body.board-fullscreen aside,
        body.board-fullscreen header,
        body.board-fullscreen .fi-topbar,
        body.board-fullscreen .fi-sidebar,
        body.board-fullscreen .fi-sidebar-nav {
            display: none !important;
        }
        body.board-fullscreen .fi-main-ctn,
        body.board-fullscreen .fi-main,
        body.board-fullscreen main {
            padding: 0 !important;
            margin: 0 !important;
            max-width: 100% !important;
            width: 100% !important;
        }
        body.board-fullscreen #board-wrapper {
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: #f9fafb;
            padding: 8px;
            display: flex;
            flex-direction: column;
        }
    </style>

    @php
        $selectedEventName = null;
        if ($this->selectedEventId) {
            $ev = \App\Models\Event::find($this->selectedEventId);
            $selectedEventName = $ev ? $ev->start_date?->format('d/m') . ' · ' . Str::limit($ev->name, 22) : null;
        }
        $currentTemplateName = $this->getCastleTypes()->firstWhere('key', $this->castleType)?->name ?? $this->castleType;
        $collaShortname = auth()->user()?->colla?->shortname ?? '';
    @endphp

    <div id="board-wrapper"
         x-data="{ showSave: false, saveName: '', showCantar: false, showEventPicker: false, eventSearch: '', showImport: false, showShare: false, showProjector: false, showRename: false, renameId: null, renameName: '', showEventPinyes: false, showPublish: false, caSearch: '', caFilter: 'all' }"
         @board-apply.window="boardEditor.applyAssignments($event.detail.assignments)"
         @board-type-changed.window="boardEditor.loadCastleType($event.detail.type)"
         style="display:flex; flex-direction:column; height:calc(100svh - 8rem); overflow:hidden;">

        {{-- Toolbar — una fila, icones + tooltip --}}
        @php
            $btnBase   = 'font-size:14px; padding:4px 8px; border-radius:6px; border:1px solid; cursor:pointer; line-height:1; display:inline-flex; align-items:center; justify-content:center;';
            $btnGray   = $btnBase . ' background:#f3f4f6; border-color:#d1d5db; color:#374151;';
            $btnBlue   = $btnBase . ' background:#eff6ff; border-color:#bfdbfe; color:#2563eb;';
            $btnGreen  = $btnBase . ' background:#d1fae5; border-color:#6ee7b7; color:#065f46; font-weight:600;';
            $btnPurple = $btnBase . ' background:#f5f3ff; border-color:#c4b5fd; color:#6d28d9;';
            $btnDark   = $btnBase . ' background:' . (auth()->user()->colla->projected_snapshot_id ? '#064e3b' : '#1f2937') . '; border-color:' . (auth()->user()->colla->projected_snapshot_id ? '#059669' : '#374151') . '; color:' . (auth()->user()->colla->projected_snapshot_id ? '#d1fae5' : '#9ca3af') . ';';
            $sep = '<span style="width:1px; height:22px; background:#d1d5db; margin:0 4px; flex-shrink:0; display:inline-block;"></span>';
        @endphp
        <div style="display:flex; align-items:center; gap:4px; margin-bottom:8px; flex-wrap:wrap; flex-shrink:0;">

            {{-- G1: Context --}}
            <button @click="showEventPicker = true; eventSearch = ''"
                title="{{ $selectedEventName ? $selectedEventName : 'Selecciona un event' }}"
                style="{{ $btnGray }} {{ $this->selectedEventId ? 'color:#1d4ed8; border-color:#bfdbfe; background:#eff6ff;' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:15px;height:15px"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776"/></svg>
            </button>
            <select wire:model.live="castleType"
                style="font-size:11px; padding:4px 6px; border-radius:6px; border:1px solid {{ $this->castleType ? '#d1d5db' : '#f59e0b' }}; background:{{ $this->castleType ? 'white' : '#fffbeb' }}; color:#374151; max-width:120px;">
                <option value="" disabled @selected(!$this->castleType)>— Tria plantilla —</option>
                @foreach($this->getCastleTypes() as $template)
                    <option value="{{ $template->key }}">{{ $template->name }}</option>
                @endforeach
            </select>

            {!! $sep !!}

            {{-- G2: Pinyes --}}
            <button @click="showEventPinyes = true" title="Pinyes d'aquest event" style="{{ $btnBlue }}">🏰</button>

            {!! $sep !!}

            {{-- G3: Edita --}}
            <button onclick="boardEditor.clearBoard()" title="Neteja la pissarra" style="{{ $btnGray }}">🧹</button>
            <button id="btn-undo" onclick="boardEditor.undo()" title="Desfés l'última assignació (Ctrl+Z)" disabled style="{{ $btnGray }} opacity:0.35;">↩</button>
            <span id="board-dirty" style="display:none; font-size:10px; color:#d97706; background:#fef3c7; border:1px solid #fcd34d; border-radius:4px; padding:2px 5px; white-space:nowrap;">●</span>

            {!! $sep !!}

            {{-- G4: Desa --}}
            @if($this->currentSnapshotId)
                <button @click="$dispatch('board-overwrite', {assignmentsJson: JSON.stringify(boardEditor.getAssignments()), dirtyViewsJson: JSON.stringify(boardEditor.getDirtyViews())})"
                    title="Desa (sobreescriu): {{ $this->currentSnapshotName }}" style="{{ $btnGreen }}">💾</button>
                <button @click="saveName = '{{ addslashes($currentTemplateName) }}'; showSave = true"
                    title="Desa com a nova pinya" style="{{ $btnGray }}">💾<sup style="font-size:9px; line-height:0;">+</sup></button>
            @else
                <button @click="saveName = '{{ addslashes($currentTemplateName) }}'; showSave = true"
                    title="Desa nova pinya" style="{{ $btnGreen }}">💾</button>
            @endif

            {!! $sep !!}

            {{-- G4b: Genera proposta --}}
            @if($this->selectedEventId && $this->castleType)
                <button wire:click="generateProposal" wire:loading.attr="disabled"
                    title="Genera proposta automàtica d'assignació basada en assistència i dades físiques"
                    style="{{ $btnGray }} gap:4px;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:15px;height:15px"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/></svg>
                    <span style="font-size:11px;">Auto</span>
                </button>
            @endif

            {!! $sep !!}

            {{-- G5: Publicar (dropdown) + Pantalla completa --}}
            <div style="position:relative; display:inline-flex;">
                <button @click="showPublish = !showPublish" @click.outside="showPublish = false"
                    title="Publicar / Exportar"
                    style="{{ $btnPurple }} gap:4px;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:15px;height:15px"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:10px;height:10px;opacity:0.7"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </button>
                <div x-show="showPublish" x-cloak @click.outside="showPublish = false"
                    style="position:absolute; top:calc(100% + 4px); right:0; background:white; border:1px solid #e5e7eb; border-radius:8px; box-shadow:0 8px 24px rgba(0,0,0,0.12); z-index:500; min-width:180px; padding:4px; white-space:nowrap;">
                    <button onclick="boardEditor.exportPng('pissarra-{{ $this->castleType }}.png')" @click="showPublish = false"
                        style="display:flex; align-items:center; gap:8px; width:100%; text-align:left; font-size:12px; padding:7px 10px; border-radius:6px; border:none; background:transparent; cursor:pointer; color:#374151;"
                        onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">
                        📷 <span>Exporta PNG</span>
                    </button>
                    @if($this->currentSnapshotId)
                        <button @click="showCantar = true; showPublish = false"
                            style="display:flex; align-items:center; gap:8px; width:100%; text-align:left; font-size:12px; padding:7px 10px; border-radius:6px; border:none; background:transparent; cursor:pointer; color:#374151;"
                            onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">
                            🎤 <span>Canta la pinya</span>
                        </button>
                    @endif
                    <button @click="showProjector = true; showPublish = false"
                        style="display:flex; align-items:center; gap:8px; width:100%; text-align:left; font-size:12px; padding:7px 10px; border-radius:6px; border:none; background:transparent; cursor:pointer; color:{{ auth()->user()->colla->projected_snapshot_id ? '#059669' : '#374151' }}; font-weight:{{ auth()->user()->colla->projected_snapshot_id ? '600' : 'normal' }};"
                        onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">
                        📡 <span>Projector{{ auth()->user()->colla->projected_snapshot_id ? ' (actiu)' : '' }}</span>
                    </button>
                </div>
            </div>
            <button id="btn-fullscreen" onclick="toggleFullscreen()" title="Pantalla completa" style="{{ $btnBlue }}">⛶</button>

        </div>

        {{-- Modal selecció d'event --}}
        <div x-show="showEventPicker" x-cloak
            @keydown.escape.window="showEventPicker = false"
            style="position:fixed; inset:0; z-index:10000; background:rgba(0,0,0,0.4); display:flex; align-items:center; justify-content:center; padding:16px;">
            <div style="background:white; border-radius:12px; width:420px; max-height:70vh; display:flex; flex-direction:column; box-shadow:0 20px 60px rgba(0,0,0,0.25);">
                <div style="padding:14px 16px; border-bottom:1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center; flex-shrink:0;">
                    <div style="font-size:14px; font-weight:700; color:#111827;">Selecciona un event</div>
                    <button @click="showEventPicker = false"
                        style="font-size:13px; padding:2px 8px; border-radius:6px; background:#f3f4f6; border:1px solid #d1d5db; cursor:pointer; color:#6b7280;">✕</button>
                </div>
                <div style="padding:10px 12px; border-bottom:1px solid #f3f4f6; flex-shrink:0;">
                    <input x-model="eventSearch" type="text" placeholder="Cerca event..."
                        x-ref="eventSearchInput"
                        x-init="$watch('showEventPicker', v => v && $nextTick(() => $refs.eventSearchInput.focus()))"
                        style="width:100%; font-size:12px; padding:6px 10px; border:1px solid #d1d5db; border-radius:6px; box-sizing:border-box;">
                </div>
                <div style="overflow-y:auto; flex:1; padding:8px;">
                    {{-- Opció "sense event" --}}
                    <div x-show="!eventSearch || 'sense event'.includes(eventSearch.toLowerCase())"
                        @click="$wire.set('selectedEventId', null); showEventPicker = false"
                        style="padding:7px 10px; border-radius:6px; cursor:pointer; font-size:12px; color:#6b7280; display:flex; align-items:center; gap:8px; margin-bottom:2px;"
                        @mouseenter="$el.style.background='#f9fafb'" @mouseleave="$el.style.background=''">
                        <span style="opacity:0.5;">📅</span> Sense event
                    </div>
                    @php $events = $this->getUpcomingEvents(); $prevHasType = null; @endphp
                    @foreach($events as $event)
                        @php
                            $hasType = ($event->type_count ?? 0) > 0;
                            $label   = $event->start_date?->format('d/m/Y') . ' · ' . $event->name;
                            $type    = match($event->type) { 2 => '🏟', 3 => '🎯', default => '🔁' };
                        @endphp
                        @if($prevHasType === true && !$hasType)
                            <div style="font-size:10px; color:#9ca3af; padding:6px 10px 2px; text-transform:uppercase; letter-spacing:0.05em;">Altres events</div>
                        @endif
                        @php $prevHasType = $hasType; @endphp
                        <div
                            x-show="!eventSearch || '{{ strtolower($label) }}'.includes(eventSearch.toLowerCase())"
                            @click="$wire.set('selectedEventId', {{ $event->id }}); showEventPicker = false"
                            style="padding:7px 10px; border-radius:6px; cursor:pointer; font-size:12px; display:flex; align-items:center; gap:8px; margin-bottom:2px; border:1px solid {{ $hasType ? '#bfdbfe' : 'transparent' }}; background:{{ $hasType ? '#eff6ff' : '' }};"
                            @mouseenter="$el.style.background='{{ $hasType ? '#dbeafe' : '#f9fafb' }}'" @mouseleave="$el.style.background='{{ $hasType ? '#eff6ff' : '' }}'">
                            <span>{{ $type }}</span>
                            <div style="flex:1; overflow:hidden;">
                                <div style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:{{ $this->selectedEventId == $event->id ? '#1d4ed8' : '#111827' }}; font-weight:{{ $this->selectedEventId == $event->id ? '600' : '400' }};">
                                    {{ $event->name }}
                                </div>
                                <div style="font-size:10px; color:#9ca3af;">{{ $event->start_date?->format('d/m/Y') }}</div>
                            </div>
                            @if($hasType)
                                <span style="font-size:10px; background:#dbeafe; color:#1e40af; border-radius:10px; padding:1px 6px; white-space:nowrap;">{{ $event->type_count }} pinya/es</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Modal importar pinya d'un altre event --}}
        <div x-show="showImport" x-cloak
            @keydown.escape.window="showImport = false"
            style="position:fixed; inset:0; z-index:10000; background:rgba(0,0,0,0.4); display:flex; align-items:center; justify-content:center; padding:16px;">
            <div style="background:white; border-radius:12px; width:400px; max-height:70vh; display:flex; flex-direction:column; box-shadow:0 20px 60px rgba(0,0,0,0.25);">
                <div style="padding:14px 16px; border-bottom:1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center; flex-shrink:0;">
                    <div>
                        <div style="font-size:14px; font-weight:700; color:#111827;">Importa posicions d'una altra pinya</div>
                        <div style="font-size:11px; color:#6b7280; margin-top:2px;">Les posicions es copiaran al canvas actual. L'event no canvia.</div>
                    </div>
                    <button @click="showImport = false"
                        style="font-size:13px; padding:2px 8px; border-radius:6px; background:#f3f4f6; border:1px solid #d1d5db; cursor:pointer; color:#6b7280; flex-shrink:0;">✕</button>
                </div>
                <div style="overflow-y:auto; flex:1; padding:10px;">
                    @forelse($this->getAllCollaSnapshots() as $snap)
                        <div style="border:1px solid #e5e7eb; border-radius:8px; padding:8px 10px; margin-bottom:8px; background:white; display:flex; justify-content:space-between; align-items:center; gap:8px;">
                            <div style="flex:1; overflow:hidden;">
                                <div style="font-size:12px; font-weight:600; color:#111827; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                    @if($snap->is_primary) ⭐ @endif{{ $snap->name }}
                                </div>
                                @if($snap->event)
                                    <div style="font-size:11px; color:#6b7280; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                        {{ $snap->event->start_date?->format('d/m/Y') }} · {{ $snap->event->name }}
                                    </div>
                                @else
                                    <div style="font-size:11px; color:#9ca3af;">Sense event</div>
                                @endif
                                <div style="font-size:10px; color:#9ca3af;">{{ $snap->created_at->format('d/m H:i') }}</div>
                            </div>
                            <button @click="$wire.loadSnapshot({{ $snap->id }}); showImport = false"
                                style="font-size:11px; padding:4px 10px; border-radius:6px; background:#f0fdf4; border:1px solid #86efac; color:#166534; cursor:pointer; white-space:nowrap; flex-shrink:0;">
                                ↓ Importa
                            </button>
                        </div>
                    @empty
                        <p style="font-size:12px; color:#9ca3af; text-align:center; padding:24px 0;">No hi ha pinyes d'altres events.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Modal Compartir --}}
        <div x-show="showShare" x-cloak
            @keydown.escape.window="showShare = false"
            style="position:fixed; inset:0; z-index:10000; background:rgba(0,0,0,0.4); display:flex; align-items:center; justify-content:center; padding:16px;">
            <div style="background:white; border-radius:12px; padding:20px; width:380px; box-shadow:0 10px 40px rgba(0,0,0,0.2);">
                <div style="font-size:14px; font-weight:600; color:#111827; margin-bottom:4px;">📤 Compartir pinya</div>
                @if($this->shareSnapshotName)
                    <div style="font-size:11px; color:#6b7280; margin-bottom:12px;">{{ $this->shareSnapshotName }}</div>
                @endif
                @if($this->shareUrl)
                    <div style="display:flex; gap:6px; align-items:center; margin-bottom:12px;">
                        <input type="text" readonly value="{{ $this->shareUrl }}"
                            style="flex:1; font-size:11px; padding:6px 8px; border:1px solid #d1d5db; border-radius:6px; background:#f9fafb; color:#374151;"
                            onclick="this.select()">
                        <button onclick="(function(btn, text){ if(navigator.clipboard){ navigator.clipboard.writeText(text).then(()=>{btn.textContent='✓'; setTimeout(()=>btn.textContent='Copia',2000)}).catch(()=>{}); } else { var ta=document.createElement('textarea'); ta.value=text; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta); btn.textContent='✓'; setTimeout(()=>btn.textContent='Copia',2000); } })(this, '{{ $this->shareUrl }}')"
                            style="font-size:11px; padding:6px 10px; border-radius:6px; background:#eff6ff; border:1px solid #bfdbfe; color:#2563eb; cursor:pointer; white-space:nowrap; flex-shrink:0;">
                            Copia
                        </button>
                    </div>
                    <div style="text-align:center; margin-bottom:12px;">
                        <a href="{{ $this->shareUrl }}" target="_blank"
                            style="font-size:11px; color:#6d28d9; text-decoration:underline;">
                            Obrir en una nova pestanya →
                        </a>
                    </div>
                    <p style="font-size:11px; color:#9ca3af;">Qualsevol persona amb aquest enllaç pot veure la pinya sense necessitat de compte.</p>
                @endif
                <div style="display:flex; justify-content:flex-end; margin-top:14px;">
                    <button @click="showShare = false"
                        style="font-size:12px; padding:5px 14px; border-radius:6px; background:#f3f4f6; border:1px solid #d1d5db; cursor:pointer;">
                        Tanca
                    </button>
                </div>
            </div>
        </div>

        {{-- Modal Projector --}}
        <div x-show="showProjector" x-cloak
            @keydown.escape.window="showProjector = false"
            style="position:fixed; inset:0; z-index:10000; background:rgba(0,0,0,0.5); display:flex; align-items:center; justify-content:center; padding:16px;">
            <div style="background:#111827; border-radius:12px; padding:20px; width:400px; box-shadow:0 10px 40px rgba(0,0,0,0.5); border:1px solid #374151;">
                <div style="font-size:14px; font-weight:600; color:#f9fafb; margin-bottom:4px;">📡 URL del Projector</div>
                <div style="font-size:11px; color:#6b7280; margin-bottom:14px;">
                    URL permanent de la colla. Apunta la TV o Raspberry Pi aquí. Mostra sempre la pinya que estigui projectada.
                </div>
                <div style="display:flex; gap:6px; align-items:center; margin-bottom:12px;">
                    <input type="text" readonly value="{{ $this->getProjectorUrl() }}"
                        style="flex:1; font-size:10px; padding:6px 8px; border:1px solid #374151; border-radius:6px; background:#1f2937; color:#d1d5db;"
                        onclick="this.select()">
                    <button onclick="(function(btn, text){ if(navigator.clipboard){ navigator.clipboard.writeText(text).then(()=>{btn.textContent='✓'; setTimeout(()=>btn.textContent='Copia',2000)}).catch(()=>{}); } else { var ta=document.createElement('textarea'); ta.value=text; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta); btn.textContent='✓'; setTimeout(()=>btn.textContent='Copia',2000); } })(this, '{{ $this->getProjectorUrl() }}')"
                        style="font-size:11px; padding:6px 10px; border-radius:6px; background:#1d4ed8; border:none; color:white; cursor:pointer; white-space:nowrap; flex-shrink:0;">
                        Copia
                    </button>
                </div>
                <div style="text-align:center; margin-bottom:14px;">
                    <a href="{{ $this->getProjectorUrl() }}" target="_blank"
                        style="font-size:11px; color:#60a5fa; text-decoration:underline;">
                        Obrir pantalla del projector →
                    </a>
                </div>
                @php $projectedId = auth()->user()->colla->projected_snapshot_id; @endphp
                @if($projectedId)
                    <div style="background:#064e3b; border:1px solid #059669; border-radius:8px; padding:8px 12px; font-size:12px; color:#d1fae5; margin-bottom:12px; display:flex; justify-content:space-between; align-items:center; gap:8px;">
                        <span>📡 Projectant ara</span>
                        <button wire:click="unproject" @click="showProjector = false"
                            style="font-size:10px; padding:3px 8px; border-radius:4px; background:#7f1d1d; border:1px solid #ef4444; color:#fca5a5; cursor:pointer; white-space:nowrap;">
                            Atura
                        </button>
                    </div>
                @else
                    <div style="background:#1f2937; border:1px solid #374151; border-radius:8px; padding:8px 12px; font-size:12px; color:#6b7280; margin-bottom:12px;">
                        Cap pinya projectada. Usa el botó 📡 a cada pinya per projectar-la.
                    </div>
                @endif
                <div style="display:flex; justify-content:flex-end;">
                    <button @click="showProjector = false"
                        style="font-size:12px; padding:5px 14px; border-radius:6px; background:#374151; border:1px solid #4b5563; color:#d1d5db; cursor:pointer;">
                        Tanca
                    </button>
                </div>
            </div>
        </div>

        {{-- Diàleg Desa --}}
        <div x-show="showSave" x-cloak
            style="position:fixed; inset:0; z-index:10000; background:rgba(0,0,0,0.4); display:flex; align-items:center; justify-content:center;">
            <div style="background:white; border-radius:12px; padding:20px; width:320px; box-shadow:0 10px 40px rgba(0,0,0,0.2);">
                <div style="font-size:14px; font-weight:600; color:#111827; margin-bottom:12px;">Desar pissarra</div>

                @if(!$this->selectedEventId)
                    <p style="font-size:12px; color:#92400e; background:#fef3c7; border-radius:6px; padding:8px; margin-bottom:12px;">
                        Sense event seleccionat. La pissarra es desarà sense vincle a cap event.
                    </p>
                @endif

                <label style="font-size:12px; color:#374151; display:block; margin-bottom:4px;">Nom (ex: "Assaig 1", "Versió definitiva")</label>
                <input x-model="saveName" type="text" maxlength="100"
                    placeholder="Nom de la pissarra..."
                    @keydown.enter="if(saveName.trim()) { $dispatch('board-save', {name: saveName.trim(), assignmentsJson: JSON.stringify(boardEditor.getAssignments())}); showSave = false; }"
                    style="width:100%; font-size:12px; padding:6px 10px; border:1px solid #d1d5db; border-radius:6px; margin-bottom:12px; box-sizing:border-box;"
                    x-ref="saveNameInput"
                    x-init="$watch('showSave', v => v && $nextTick(() => $refs.saveNameInput.focus()))">

                <div style="display:flex; gap:8px; justify-content:flex-end;">
                    <button @click="showSave = false"
                        style="font-size:12px; padding:5px 14px; border-radius:6px; background:#f3f4f6; border:1px solid #d1d5db; cursor:pointer;">
                        Cancel·la
                    </button>
                    <button
                        @click="if(saveName.trim()) { $dispatch('board-save', {name: saveName.trim(), assignmentsJson: JSON.stringify(boardEditor.getAssignments())}); showSave = false; }"
                        style="font-size:12px; padding:5px 14px; border-radius:6px; background:#059669; border:none; color:white; cursor:pointer; font-weight:600;">
                        Desa
                    </button>
                </div>
            </div>
        </div>

        {{-- Modal Reanomena snapshot --}}
        <div x-show="showRename" x-cloak
            @keydown.escape.window="showRename = false"
            style="position:fixed; inset:0; z-index:10000; background:rgba(0,0,0,0.4); display:flex; align-items:center; justify-content:center;">
            <div style="background:white; border-radius:12px; padding:20px; width:320px; box-shadow:0 10px 40px rgba(0,0,0,0.2);">
                <div style="font-size:14px; font-weight:600; color:#111827; margin-bottom:12px;">✏ Reanomena la pinya</div>
                <input x-model="renameName" type="text" maxlength="100"
                    @keydown.enter="if(renameName.trim()) { $wire.renameSnapshot(renameId, renameName.trim()); showRename = false; }"
                    style="width:100%; font-size:12px; padding:6px 10px; border:1px solid #d1d5db; border-radius:6px; margin-bottom:12px; box-sizing:border-box;"
                    x-ref="renameInput"
                    x-init="$watch('showRename', v => v && $nextTick(() => { $refs.renameInput.focus(); $refs.renameInput.select(); }))">
                <div style="display:flex; gap:8px; justify-content:flex-end;">
                    <button @click="showRename = false"
                        style="font-size:12px; padding:5px 14px; border-radius:6px; background:#f3f4f6; border:1px solid #d1d5db; cursor:pointer;">
                        Cancel·la
                    </button>
                    <button @click="if(renameName.trim()) { $wire.renameSnapshot(renameId, renameName.trim()); showRename = false; }"
                        style="font-size:12px; padding:5px 14px; border-radius:6px; background:#059669; border:none; color:white; cursor:pointer; font-weight:600;">
                        Desa
                    </button>
                </div>
            </div>
        </div>

        {{-- Modal Pinyes de l'event --}}
        <div x-show="showEventPinyes" x-cloak
            @keydown.escape.window="showEventPinyes = false"
            style="position:fixed; inset:0; z-index:10000; background:rgba(0,0,0,0.5); display:flex; align-items:center; justify-content:center; padding:16px;">
            <div style="background:white; border-radius:12px; padding:20px; width:520px; max-height:85vh; display:flex; flex-direction:column; box-shadow:0 20px 60px rgba(0,0,0,0.3);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-shrink:0;">
                    <div style="font-size:15px; font-weight:700; color:#111827;">🏰 Pinyes d'aquest event</div>
                    <button @click="showEventPinyes = false"
                        style="font-size:14px; padding:2px 8px; border-radius:6px; background:#f3f4f6; border:1px solid #d1d5db; cursor:pointer; color:#6b7280;">✕</button>
                </div>
                <div style="overflow-y:auto; flex:1;">
                    @php
                        $allSnaps = $this->getAllEventSnapshots();
                        $grouped  = $allSnaps->groupBy('castle_type');
                        $projectedId = auth()->user()->colla->projected_snapshot_id;
                    @endphp
                    @if($allSnaps->isEmpty())
                        <p style="font-size:13px; color:#9ca3af; text-align:center; padding:24px 0;">
                            Sense pinyes desades per aquest event.
                        </p>
                    @else
                        @foreach($grouped as $type => $snaps)
                            <div style="font-size:10px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.05em; margin-top:10px; margin-bottom:6px; padding-bottom:3px; border-bottom:1px solid #e5e7eb;">
                                {{ $type }}
                            </div>
                            @foreach($snaps as $snap)
                                <div style="border:1px solid {{ $snap->is_primary ? '#fbbf24' : '#e5e7eb' }}; border-radius:8px; padding:8px; margin-bottom:6px; background:{{ $snap->is_primary ? '#fffbeb' : 'white' }};">
                                    <div style="display:flex; align-items:center; gap:6px; margin-bottom:6px;">
                                        @if($snap->ronda_number)
                                            <span style="flex-shrink:0; font-size:10px; font-weight:700; background:#1d4ed8; color:white; border-radius:10px; padding:1px 6px;">R{{ $snap->ronda_number }}</span>
                                        @endif
                                        <div style="font-size:12px; font-weight:600; color:#111827; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; flex:1;" title="{{ $snap->name }}">
                                            @if($snap->is_primary) ⭐ @endif{{ $snap->name }}
                                        </div>
                                        <div style="font-size:10px; color:#9ca3af; flex-shrink:0;">{{ $snap->created_at->format('d/m H:i') }}</div>
                                    </div>
                                    <div style="display:flex; gap:4px; flex-wrap:wrap;">
                                        <button wire:click="loadSnapshot({{ $snap->id }})" @click="showEventPinyes = false"
                                            style="font-size:10px; font-weight:600; padding:3px 10px; border-radius:4px; background:#eff6ff; border:1px solid #bfdbfe; color:#2563eb; cursor:pointer;">
                                            Carrega
                                        </button>
                                        @if(!$snap->is_primary)
                                            <button wire:click="setPrimary({{ $snap->id }})"
                                                title="Marca com a pissarra oficial"
                                                style="font-size:10px; padding:3px 6px; border-radius:4px; background:#fef3c7; border:1px solid #fcd34d; color:#92400e; cursor:pointer;">⭐</button>
                                        @endif
                                        <button wire:click="projectSnapshot({{ $snap->id }})"
                                            title="{{ $projectedId === $snap->id ? 'Projectant ara' : 'Projectar aquesta pinya' }}"
                                            style="font-size:10px; padding:3px 6px; border-radius:4px; background:{{ $projectedId === $snap->id ? '#064e3b' : '#f3f4f6' }}; border:1px solid {{ $projectedId === $snap->id ? '#059669' : '#d1d5db' }}; color:{{ $projectedId === $snap->id ? '#d1fae5' : '#374151' }}; cursor:pointer;">📡</button>
                                        <button @click="showEventPinyes = false; renameId = {{ $snap->id }}; renameName = '{{ addslashes($snap->name) }}'; showRename = true"
                                            title="Reanomena"
                                            style="font-size:10px; padding:3px 6px; border-radius:4px; background:#f3f4f6; border:1px solid #d1d5db; color:#374151; cursor:pointer;">✏</button>
                                        <button @click="$wire.generateShareUrl({{ $snap->id }}); showEventPinyes = false; showShare = true"
                                            title="Compartir / URL pública"
                                            style="font-size:10px; padding:3px 6px; border-radius:4px; background:#f5f3ff; border:1px solid #c4b5fd; color:#6d28d9; cursor:pointer;">📤</button>
                                        <button wire:click="deleteSnapshot({{ $snap->id }})"
                                            wire:confirm="Eliminar '{{ addslashes($snap->name) }}'?"
                                            style="font-size:10px; padding:3px 6px; border-radius:4px; background:#fef2f2; border:1px solid #fecaca; color:#dc2626; cursor:pointer;">✕</button>
                                    </div>
                                </div>
                            @endforeach
                        @endforeach
                    @endif
                </div>
                {{-- Peu: importar d'altra pinya --}}
                <div style="padding-top:12px; border-top:1px solid #e5e7eb; flex-shrink:0; margin-top:8px;">
                    <button @click="showEventPinyes = false; showImport = true"
                        style="width:100%; font-size:11px; padding:5px 8px; border-radius:6px; background:#f0fdf4; border:1px solid #86efac; color:#166534; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:5px;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:12px;height:12px"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                        Importa d'altra pinya
                    </button>
                </div>
            </div>
        </div>

        {{-- Modal Canta la pinya --}}
        <div x-show="showCantar" x-cloak
            style="position:fixed; inset:0; z-index:10000; background:rgba(0,0,0,0.5); display:flex; align-items:center; justify-content:center; padding:16px;">
            <div style="background:white; border-radius:12px; padding:20px; width:480px; max-height:80vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,0.3);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                    <div style="font-size:15px; font-weight:700; color:#111827;">
                        🎤 Canta la pinya
                        @if($this->currentSnapshotName)
                            <span style="font-size:12px; font-weight:400; color:#6b7280; margin-left:6px;">— {{ $this->currentSnapshotName }}</span>
                        @endif
                    </div>
                    <button @click="showCantar = false"
                        style="font-size:14px; padding:2px 8px; border-radius:6px; background:#f3f4f6; border:1px solid #d1d5db; cursor:pointer; color:#6b7280;">✕</button>
                </div>

                @php $lines = $this->getCantarLines(); $currentGrup = null; @endphp

                @if(empty($lines))
                    <p style="font-size:13px; color:#9ca3af; text-align:center; padding:20px 0;">Carrega una pissarra primer.</p>
                @else
                    @foreach($lines as $line)
                        @if($line['grup'] !== $currentGrup)
                            @php $currentGrup = $line['grup']; @endphp
                            <div style="font-size:10px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.05em; margin-top:10px; margin-bottom:4px; padding-bottom:2px; border-bottom:1px solid #e5e7eb;">
                                {{ $currentGrup }}
                            </div>
                        @endif
                        <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px; padding:3px 0;">
                            <span style="color:#6b7280;">{{ $line['nom'] }}</span>
                            <span style="font-weight:600; color:#111827; font-family:monospace;">{{ $line['alias'] }}</span>
                        </div>
                    @endforeach

                    <div style="margin-top:16px; padding-top:12px; border-top:1px solid #e5e7eb; text-align:right;">
                        <button onclick="window.print()"
                            style="font-size:12px; padding:5px 14px; border-radius:6px; background:#f3f4f6; border:1px solid #d1d5db; cursor:pointer; color:#374151;">
                            🖨️ Imprimeix
                        </button>
                    </div>
                @endif
            </div>
        </div>

        {{-- Layout 3 columnes --}}
        <div id="board-cols" style="display:flex; flex-direction:row; gap:10px; flex:1; min-height:0;">

            {{-- ESQUERRA — Llista castellers --}}
            <div style="width:180px; min-width:180px; display:flex; flex-direction:column; background:#f9fafb; border-radius:10px; border:1px solid #e5e7eb; overflow:hidden;">

                {{-- Capçalera --}}
                <div style="padding:6px 8px 4px; border-bottom:1px solid #e5e7eb; background:#f3f4f6; flex-shrink:0;">
                    <div style="font-size:12px; font-weight:600; color:#374151; margin-bottom:4px;">Castellers actius</div>

                    {{-- Cercador --}}
                    <input x-model="caSearch" type="text" placeholder="Cerca nom..."
                        style="width:100%; font-size:11px; padding:3px 7px; border:1px solid #d1d5db; border-radius:5px; background:white; box-sizing:border-box; margin-bottom:4px; color:#374151;"
                        @input="caSearch = $event.target.value">

                    {{-- Filtres d'assistència --}}
                    @if($this->selectedEventId)
                    <div style="display:flex; gap:3px; flex-wrap:wrap;">
                        <button @click="caFilter='all'"
                            :style="caFilter==='all' ? 'background:#374151;color:white;' : 'background:white;color:#6b7280;'"
                            style="font-size:10px; padding:2px 6px; border-radius:4px; border:1px solid #d1d5db; cursor:pointer; font-weight:600;">
                            Tots
                        </button>
                        <button @click="caFilter='ok'"
                            :style="caFilter==='ok' ? 'background:#16a34a;color:white;border-color:#16a34a;' : 'background:white;color:#16a34a;border-color:#86efac;'"
                            style="font-size:11px; padding:2px 6px; border-radius:4px; border:1px solid; cursor:pointer; font-weight:700;"
                            title="Assistiran">✓</button>
                        <button @click="caFilter='nok'"
                            :style="caFilter==='nok' ? 'background:#dc2626;color:white;border-color:#dc2626;' : 'background:white;color:#dc2626;border-color:#fca5a5;'"
                            style="font-size:11px; padding:2px 6px; border-radius:4px; border:1px solid; cursor:pointer; font-weight:700;"
                            title="No assistiran">✗</button>
                        <button @click="caFilter='pendent'"
                            :style="caFilter==='pendent' ? 'background:#d97706;color:white;border-color:#d97706;' : 'background:white;color:#d97706;border-color:#fcd34d;'"
                            style="font-size:11px; padding:2px 6px; border-radius:4px; border:1px solid; cursor:pointer; font-weight:700;"
                            title="Pendent de resposta">?</button>
                    </div>
                    @endif
                </div>

                {{-- Llista --}}
                <div style="overflow-y:auto; flex:1; padding:6px;">
                    @foreach($this->getCastellers() as $casteller)
                        @php
                            $as  = $casteller->attendance_status ?? null;
                            $att = $as ?? 'pendent';
                            [$ic, $bg] = match($as) {
                                'ok'  => ['✓', '#16a34a'],
                                'nok' => ['✗', '#dc2626'],
                                default => ['?', '#d97706'],
                            };
                        @endphp
                        <button
                            data-alias="{{ $casteller->alias }}"
                            data-attendance="{{ $att }}"
                            draggable="true"
                            onclick="boardEditor.selectCasteller('{{ $casteller->alias }}')"
                            x-show="(caFilter === 'all' || caFilter === '{{ $att }}') && '{{ strtolower($casteller->alias) }}'.includes(caSearch.toLowerCase())"
                            style="display:flex; align-items:center; gap:5px; width:100%; text-align:left; font-size:11px; font-family:monospace; padding:3px 6px; margin-bottom:2px; border-radius:4px; border:1px solid #e5e7eb; background:white; cursor:grab;">
                            @if($this->selectedEventId)
                                <span style="flex-shrink:0; width:15px; height:15px; border-radius:50%; background:{{ $bg }}; color:white; font-size:9px; font-family:sans-serif; font-weight:700; display:inline-flex; align-items:center; justify-content:center;">{{ $ic }}</span>
                            @endif
                            <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $casteller->alias }}</span>
                        </button>
                    @endforeach
                </div>

                {{-- Panell Assignats (JS-controlat, wire:ignore) --}}
                <div wire:ignore id="board-assigned-panel"
                     style="display:none; border-top:1px solid #e5e7eb; flex-shrink:0; background:#fafafa;">
                    <div style="padding:4px 8px 2px; font-size:10px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:0.05em; display:flex; justify-content:space-between; align-items:baseline;">
                        <span id="board-assigned-title">Assignats</span>
                        <span id="board-assigned-stats" style="font-weight:400; font-size:9px; color:#9ca3af; letter-spacing:0;"></span>
                    </div>
                </div>

                {{-- Peu --}}
                <div style="padding:5px 6px; border-top:1px solid #e5e7eb; background:#f3f4f6; font-size:10px; color:#9ca3af; flex-shrink:0; line-height:1.6;">
                    Clic → selecciona · Clic posició → assigna · Doble clic → desassigna
                </div>
            </div>

            {{-- CENTRE — Canvas --}}
            <div style="flex:1; display:flex; flex-direction:column; min-height:0; background:#f9fafb; border-radius:10px; border:1px solid #e5e7eb; overflow:hidden;">

                {{-- Capçalera: event + pinya activa (fora de wire:ignore, s'actualitza en cada re-render) --}}
                <div style="padding:4px 12px; border-bottom:1px solid #e5e7eb; font-size:11px; color:#9ca3af; flex-shrink:0; min-height:28px; display:flex; align-items:center; gap:6px; overflow:hidden;">
                    {{-- Esquerra: event + nom pinya --}}
                    <div style="display:flex; align-items:center; gap:6px; flex:1; overflow:hidden; min-width:0;">
                        @if($selectedEventName)
                            <span style="color:#6b7280; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $selectedEventName }}</span>
                        @endif
                        @if($selectedEventName && $this->currentSnapshotName)
                            <span style="color:#d1d5db; flex-shrink:0;">·</span>
                        @endif
                        @if($this->currentSnapshotName)
                            <span style="font-weight:600; color:#2563eb; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $this->currentSnapshotName }}</span>
                        @endif
                        @if(!$selectedEventName && !$this->currentSnapshotName)
                            <span style="font-style:italic; white-space:nowrap;">Sense event seleccionat</span>
                        @endif
                    </div>
                    {{-- Dreta: selector vistes (JS-controlat), ID registre, comptador --}}
                    <div style="display:flex; align-items:center; gap:4px; flex-shrink:0;">
                        <div id="board-view-switcher" wire:ignore style="display:none; gap:2px; align-items:center;"></div>
                        @if($this->currentSnapshotId)
                            <span style="font-size:10px; font-family:monospace; color:#9ca3af; background:#f3f4f6; border:1px solid #e5e7eb; border-radius:4px; padding:1px 5px; white-space:nowrap;">#{{ $this->currentSnapshotId }}</span>
                        @endif
                        <span id="board-counter" style="font-size:10px; font-family:monospace; color:#6b7280; background:#f3f4f6; border:1px solid #e5e7eb; border-radius:4px; padding:1px 6px; white-space:nowrap;">0/0</span>
                    </div>
                </div>

                {{-- Canvas Konva (wire:ignore evita que Livewire destrueixi el Konva en re-renders) --}}
                <div wire:ignore style="flex:1; overflow:hidden; position:relative; min-height:0;">
                    <div id="board-canvas"></div>
                    <div id="board-info" style="position:absolute; bottom:38px; left:8px; font-size:11px; color:#6b7280; pointer-events:none;"></div>

                    {{-- Avís de bloqueig cross-vista (controlat per JS) --}}
                    <div id="board-warning"
                         style="display:none; position:absolute; top:50px; left:50%; transform:translateX(-50%); z-index:30; background:#fef2f2; border:1px solid #fca5a5; border-radius:8px; padding:10px 16px; font-size:12px; color:#991b1b; font-weight:500; box-shadow:0 4px 12px rgba(0,0,0,.12); white-space:nowrap; align-items:center; gap:8px;">
                        🚫 <span></span>
                    </div>

                    {{-- Empty state: visible fins que es tria plantilla (controlat per JS) --}}
                    <div id="board-empty-state"
                         style="position:absolute; inset:0; background:#f9fafb; display:flex; flex-direction:column; align-items:center; justify-content:center; z-index:20; gap:20px; pointer-events:none;">
                        <div style="font-size:52px; line-height:1;">🏰</div>
                        <div style="text-align:center; max-width:300px;">
                            <div style="font-size:15px; font-weight:600; color:#374151; margin-bottom:6px;">Cap plantilla seleccionada</div>
                            <div style="font-size:12px; color:#6b7280; line-height:1.5;">Tria un tipus de castell del menú de dalt per preparar la pinya.</div>
                        </div>
                        <div style="display:flex; align-items:center; gap:6px; font-size:11px; color:#9ca3af; background:white; border:1px solid #e5e7eb; border-radius:8px; padding:8px 14px;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18"/></svg>
                            <span>Menú «Tria plantilla» a la barra superior</span>
                        </div>
                    </div>

                    {{-- Zoom controls --}}
                    <div style="position:absolute; bottom:10px; right:10px; z-index:10; display:flex; gap:2px; background:rgba(255,255,255,.95); border:1px solid #e5e7eb; border-radius:8px; padding:3px; box-shadow:0 2px 8px rgba(0,0,0,.08);">
                        <button onclick="boardEditor.zoomIn()" type="button"
                            style="width:26px;height:26px;display:flex;align-items:center;justify-content:center;border-radius:5px;border:none;background:transparent;cursor:pointer;font-size:16px;font-weight:700;color:#374151;" title="Ampliar">+</button>
                        <button onclick="boardEditor.zoomReset()" type="button" id="board-zoom"
                            style="height:26px;padding:0 6px;border-radius:5px;border:none;background:transparent;cursor:pointer;font-size:10px;font-family:monospace;color:#6b7280;font-weight:600;min-width:36px;" title="Restablir zoom">100%</button>
                        <button onclick="boardEditor.zoomOut()" type="button"
                            style="width:26px;height:26px;display:flex;align-items:center;justify-content:center;border-radius:5px;border:none;background:transparent;cursor:pointer;font-size:16px;font-weight:700;color:#374151;" title="Reduir">−</button>
                    </div>
                </div>

            </div>


        </div>
    </div>

    <script>
        function toggleFullscreen() {
            const isFs = document.body.classList.toggle('board-fullscreen');
            document.getElementById('btn-fullscreen').textContent = isFs ? '✕ Surt' : '⛶ Pantalla completa';
        }
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                document.body.classList.remove('board-fullscreen');
                document.getElementById('btn-fullscreen').textContent = '⛶ Pantalla completa';
            }
        });

        // Després de cada re-render de Livewire, re-amaguem els castellers ja assignats al canvas
        document.addEventListener('livewire:updated', () => {
            if (typeof boardEditor === 'undefined') return;
            const assigned = Object.values(boardEditor.getAssignments());
            assigned.forEach(alias => {
                document.querySelectorAll(`[data-alias="${alias}"]`).forEach(el => {
                    el.style.display = 'none';
                });
            });
        });
    </script>
</x-filament-panels::page>
