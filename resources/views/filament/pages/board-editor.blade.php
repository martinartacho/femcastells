<x-filament-panels::page>
    @vite('resources/js/board-editor.js')
    <script>window.__boardPositions = @json($this->getCastlePositions());</script>
    <script>window.__autoLoadAssignments = @json($this->getAutoLoadAssignments() ?? []);</script>

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
         x-data="{ showSave: false, saveName: '', showCantar: false, showEventPicker: false, eventSearch: '', showImport: false }"
         @board-apply.window="boardEditor.applyAssignments($event.detail.assignments)"
         @board-type-changed.window="boardEditor.loadCastleType($event.detail.type)">

        {{-- Toolbar --}}
        <div style="display:flex; align-items:center; gap:6px; margin-bottom:8px; flex-wrap:wrap;">

            {{-- Botó selector d'event (icona carpeta oberta) --}}
            <button @click="showEventPicker = true; eventSearch = ''"
                title="{{ $selectedEventName ?? 'Obrir events' }}"
                style="font-size:11px; padding:4px 10px; border-radius:6px; border:1px solid #d1d5db; background:white; color:{{ $this->selectedEventId ? '#1d4ed8' : '#6b7280' }}; cursor:pointer; white-space:nowrap; max-width:200px; overflow:hidden; text-overflow:ellipsis; display:flex; align-items:center; gap:5px;">
                {{-- Folder open --}}
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:13px;height:13px;flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776"/></svg>
                <span>{{ $selectedEventName ?? 'Obrir event' }}</span>
            </button>

            {{-- Selector tipus de castell (compacte) + botó nova plantilla + botó importar pinya --}}
            <div style="display:flex; align-items:center; gap:4px;">
                <select wire:model.live="castleType"
                    style="font-size:11px; padding:3px 6px; border-radius:6px; border:1px solid #d1d5db; background:white; color:#374151; max-width:100px;">
                    @foreach($this->getCastleTypes() as $template)
                        <option value="{{ $template->key }}">{{ $template->name }}</option>
                    @endforeach
                </select>
                <a href="/admin/{{ $collaShortname }}/templates/create"
                    title="Nova plantilla de castell"
                    style="font-size:13px; font-weight:700; padding:2px 7px; border-radius:6px; background:#f3f4f6; border:1px solid #d1d5db; color:#374151; cursor:pointer; text-decoration:none; line-height:1.4;">+</a>
                <button @click="showImport = true"
                    title="Importar posicions d'una altra pinya"
                    style="padding:3px 7px; border-radius:6px; background:#f0fdf4; border:1px solid #86efac; color:#166534; cursor:pointer; display:flex; align-items:center;">
                    {{-- Arrow-down-tray (import) --}}
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:13px;height:13px"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                </button>
            </div>

            <span id="board-counter" style="font-size:11px; color:#6b7280; white-space:nowrap;">0/0</span>

            <button onclick="boardEditor.clearBoard()"
                style="font-size:11px; padding:4px 10px; border-radius:6px; background:#f3f4f6; border:1px solid #d1d5db; cursor:pointer; white-space:nowrap;">
                Neteja
            </button>

            <button id="btn-undo" onclick="boardEditor.undo()"
                title="Desfés l'última assignació (Ctrl+Z)"
                disabled
                style="font-size:11px; padding:4px 10px; border-radius:6px; background:#f3f4f6; border:1px solid #d1d5db; cursor:pointer; white-space:nowrap; opacity:0.35;">
                ↩ Desfés
            </button>

            <span id="board-dirty" style="display:none; font-size:10px; color:#d97706; background:#fef3c7; border:1px solid #fcd34d; border-radius:4px; padding:2px 6px; white-space:nowrap;">● No desat</span>

            @if($this->currentSnapshotId)
                {{-- Snapshot actiu: Desa (sobreescriu) + Desa com... (nou) --}}
                <button
                    @click="$dispatch('board-overwrite', {assignmentsJson: JSON.stringify(boardEditor.getAssignments())})"
                    title="Sobreescriu: {{ $this->currentSnapshotName }}"
                    style="font-size:11px; padding:4px 10px; border-radius:6px; background:#d1fae5; border:1px solid #6ee7b7; color:#065f46; cursor:pointer; font-weight:600; white-space:nowrap;">
                    💾 Desa
                </button>
                <button @click="saveName = ''; showSave = true"
                    style="font-size:11px; padding:4px 10px; border-radius:6px; background:#f3f4f6; border:1px solid #d1d5db; color:#374151; cursor:pointer; white-space:nowrap;">
                    💾 Desa com...
                </button>
            @else
                {{-- Sense snapshot: Desa obre el diàleg de nom --}}
                <button @click="saveName = ''; showSave = true"
                    style="font-size:11px; padding:4px 10px; border-radius:6px; background:#d1fae5; border:1px solid #6ee7b7; color:#065f46; cursor:pointer; font-weight:600; white-space:nowrap;">
                    💾 Desa
                </button>
            @endif

            <button onclick="boardEditor.exportPng('pissarra-{{ $this->castleType }}.png')"
                title="Descarrega com a imatge PNG"
                style="font-size:11px; padding:4px 10px; border-radius:6px; background:#f5f3ff; border:1px solid #c4b5fd; color:#6d28d9; cursor:pointer; white-space:nowrap;">
                📷 PNG
            </button>

            @if($this->currentSnapshotId)
                <button @click="showCantar = true"
                    title="Veure llista d'assignacions per cantar la pinya"
                    style="font-size:11px; padding:4px 10px; border-radius:6px; background:#f0fdf4; border:1px solid #86efac; color:#166534; cursor:pointer; white-space:nowrap;">
                    🎤 Canta la pinya
                </button>
            @endif

            <button id="btn-fullscreen" onclick="toggleFullscreen()"
                style="font-size:11px; padding:4px 10px; border-radius:6px; background:#eff6ff; border:1px solid #bfdbfe; color:#2563eb; cursor:pointer; white-space:nowrap;">
                ⛶ Pantalla completa
            </button>
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
                <div style="padding:8px; border-bottom:1px solid #e5e7eb; background:#f3f4f6; flex-shrink:0;">
                    <div style="font-size:12px; font-weight:600; color:#374151;">Castellers actius</div>
                    <div style="font-size:11px; color:#6b7280;">{{ $this->getCastellers()->count() }} castellers</div>
                </div>
                <div style="overflow-y:auto; flex:1; padding:6px;">
                    @foreach($this->getCastellers() as $casteller)
                        @php
                            $as = $casteller->attendance_status ?? null;
                            [$ic, $bg] = match($as) {
                                'ok'  => ['✓', '#16a34a'],
                                'nok' => ['✗', '#dc2626'],
                                default => ['?', '#d97706'],
                            };
                        @endphp
                        <button
                            data-alias="{{ $casteller->alias }}"
                            draggable="true"
                            onclick="boardEditor.selectCasteller('{{ $casteller->alias }}')"
                            style="display:flex; align-items:center; gap:5px; width:100%; text-align:left; font-size:11px; font-family:monospace; padding:3px 6px; margin-bottom:2px; border-radius:4px; border:1px solid #e5e7eb; background:white; cursor:grab;">
                            @if($this->selectedEventId)
                                <span style="flex-shrink:0; width:15px; height:15px; border-radius:50%; background:{{ $bg }}; color:white; font-size:9px; font-family:sans-serif; font-weight:700; display:inline-flex; align-items:center; justify-content:center;">{{ $ic }}</span>
                            @endif
                            <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $casteller->alias }}</span>
                        </button>
                    @endforeach
                </div>
                <div style="padding:6px; border-top:1px solid #e5e7eb; background:#f3f4f6; font-size:10px; color:#6b7280; flex-shrink:0;">
                    Clic → selecciona<br>
                    Clic posició → assigna<br>
                    Doble clic → desassigna
                </div>
            </div>

            {{-- CENTRE — Canvas (wire:ignore evita que Livewire destrueixi el Konva en re-renders) --}}
            <div wire:ignore style="flex:1; background:#f9fafb; border-radius:10px; border:1px solid #e5e7eb; overflow:auto; display:flex; flex-direction:column; align-items:center; padding:12px;">
                <div id="board-canvas"></div>
                <div id="board-info" style="min-height:18px; font-size:11px; color:#6b7280; margin-top:4px;"></div>
            </div>

            {{-- DRETA — Snapshots desats --}}
            <div style="width:190px; min-width:190px; display:flex; flex-direction:column; background:#f9fafb; border-radius:10px; border:1px solid #e5e7eb; overflow:hidden;">

                {{-- Secció 1: pissarres d'aquest event --}}
                <div style="padding:8px; border-bottom:1px solid #e5e7eb; background:#f3f4f6; flex-shrink:0;">
                    <div style="font-size:12px; font-weight:600; color:#374151;">Aquest event</div>
                    @if($this->selectedEventId)
                        <div style="font-size:10px; color:#6b7280;">{{ $this->getSnapshots()->count() }} versions</div>
                    @else
                        <div style="font-size:10px; color:#9ca3af;">Selecciona un event</div>
                    @endif
                </div>
                <div style="overflow-y:auto; max-height:240px; padding:6px; border-bottom:2px solid #e5e7eb;">
                    @forelse($this->getSnapshots() as $snap)
                        <div style="border:1px solid {{ $snap->is_primary ? '#fbbf24' : '#e5e7eb' }}; border-radius:6px; padding:6px; margin-bottom:6px; background:{{ $snap->is_primary ? '#fffbeb' : 'white' }};">
                            <div style="font-size:11px; font-weight:600; color:#111827; margin-bottom:2px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $snap->name }}">
                                @if($snap->is_primary) ⭐ @endif{{ $snap->name }}
                            </div>
                            <div style="font-size:10px; color:#9ca3af; margin-bottom:6px;">
                                {{ $snap->created_at->format('d/m H:i') }}
                            </div>
                            <div style="display:flex; gap:4px; flex-wrap:wrap;">
                                <button wire:click="loadSnapshot({{ $snap->id }})"
                                    style="flex:1; font-size:10px; padding:3px 6px; border-radius:4px; background:#eff6ff; border:1px solid #bfdbfe; color:#2563eb; cursor:pointer;">
                                    Carrega
                                </button>
                                @if(!$snap->is_primary)
                                    <button wire:click="setPrimary({{ $snap->id }})"
                                        title="Marca com a pissarra oficial d'aquest event"
                                        style="font-size:10px; padding:3px 6px; border-radius:4px; background:#fef3c7; border:1px solid #fcd34d; color:#92400e; cursor:pointer;">
                                        ⭐
                                    </button>
                                @endif
                                <button wire:click="deleteSnapshot({{ $snap->id }})"
                                    wire:confirm="Eliminar '{{ addslashes($snap->name) }}'?"
                                    style="font-size:10px; padding:3px 6px; border-radius:4px; background:#fef2f2; border:1px solid #fecaca; color:#dc2626; cursor:pointer;">
                                    ✕
                                </button>
                            </div>
                        </div>
                    @empty
                        @if($this->selectedEventId)
                            <p style="font-size:11px; color:#9ca3af; text-align:center; padding:12px 8px;">
                                Sense pissarres per aquest event.
                            </p>
                        @endif
                    @endforelse
                </div>

                {{-- Peu del sidebar: accés ràpid a importar --}}
                <div style="padding:8px; background:#f3f4f6; border-top:1px solid #e5e7eb; flex-shrink:0;">
                    <button @click="showImport = true"
                        style="width:100%; font-size:11px; padding:4px 8px; border-radius:6px; background:#f0fdf4; border:1px solid #86efac; color:#166534; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:5px;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:12px;height:12px"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                        Importa d'altra pinya
                    </button>
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
