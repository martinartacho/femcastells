<x-filament-panels::page>
    @vite('resources/js/board-editor.js')
    <script>window.__boardPositions = @json($this->getCastlePositions());</script>

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

    <div id="board-wrapper"
         x-data="{ showSave: false, saveName: '', showCantar: false }"
         @board-apply.window="boardEditor.applyAssignments($event.detail.assignments)"
         @board-type-changed.window="boardEditor.loadCastleType($event.detail.type)">

        {{-- Toolbar --}}
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px; flex-wrap:wrap;">

            {{-- Selector d'event --}}
            <select wire:model.live="selectedEventId"
                style="font-size:12px; padding:4px 8px; border-radius:6px; border:1px solid #d1d5db; background:white; color:#374151; flex:1; min-width:0;">
                <option value="">— Sense event —</option>
                @foreach($this->getUpcomingEvents() as $event)
                    <option value="{{ $event->id }}">
                        {{ $event->start_date?->format('d/m') }} · {{ Str::limit($event->name, 28) }}
                    </option>
                @endforeach
            </select>

            {{-- Selector tipus de castell --}}
            <select wire:model.live="castleType"
                style="font-size:12px; padding:4px 8px; border-radius:6px; border:1px solid #d1d5db; background:white; color:#374151; width:110px;">
                @foreach($this->getCastleTypes() as $template)
                    <option value="{{ $template->key }}">{{ $template->name }}</option>
                @endforeach
            </select>

            <span id="board-counter" style="font-size:11px; color:#6b7280; white-space:nowrap;">0/0</span>

            <button onclick="boardEditor.clearBoard()"
                style="font-size:11px; padding:4px 10px; border-radius:6px; background:#f3f4f6; border:1px solid #d1d5db; cursor:pointer; white-space:nowrap;">
                Neteja
            </button>

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

                {{-- Secció 2: totes les plantilles de la colla --}}
                <div style="padding:8px; border-bottom:1px solid #e5e7eb; background:#f3f4f6; flex-shrink:0;">
                    <div style="font-size:12px; font-weight:600; color:#374151;">Totes les plantilles</div>
                    <div style="font-size:10px; color:#6b7280;">Altres events · {{ $this->getAllCollaSnapshots()->count() }}</div>
                </div>
                <div style="overflow-y:auto; flex:1; padding:6px;">
                    @forelse($this->getAllCollaSnapshots() as $snap)
                        <div style="border:1px solid #e5e7eb; border-radius:6px; padding:6px; margin-bottom:6px; background:white;">
                            <div style="font-size:11px; font-weight:600; color:#111827; margin-bottom:1px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $snap->name }}">
                                {{ $snap->name }}
                            </div>
                            @if($snap->event)
                                <div style="font-size:10px; color:#6b7280; margin-bottom:1px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $snap->event->name }}">
                                    {{ $snap->event->start_date?->format('d/m') }} · {{ Str::limit($snap->event->name, 18) }}
                                </div>
                            @else
                                <div style="font-size:10px; color:#9ca3af; margin-bottom:1px;">Sense event</div>
                            @endif
                            <div style="font-size:10px; color:#9ca3af; margin-bottom:6px;">{{ $snap->created_at->format('d/m H:i') }}</div>
                            <div style="display:flex; gap:4px;">
                                <button wire:click="loadSnapshot({{ $snap->id }})"
                                    style="flex:1; font-size:10px; padding:3px 6px; border-radius:4px; background:#f5f3ff; border:1px solid #c4b5fd; color:#6d28d9; cursor:pointer;">
                                    Carrega
                                </button>
                                <button wire:click="deleteSnapshot({{ $snap->id }})"
                                    wire:confirm="Eliminar '{{ addslashes($snap->name) }}'?"
                                    style="font-size:10px; padding:3px 6px; border-radius:4px; background:#fef2f2; border:1px solid #fecaca; color:#dc2626; cursor:pointer;">
                                    ✕
                                </button>
                            </div>
                        </div>
                    @empty
                        <p style="font-size:11px; color:#9ca3af; text-align:center; padding:12px 8px;">
                            Sense plantilles d'altres events.
                        </p>
                    @endforelse
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
