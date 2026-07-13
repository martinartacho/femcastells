<x-filament-panels::page>
    @php
        $event   = $this->getEvent();
        $rondes  = $this->getRondes();
        $disps   = $this->getDisponibles();
        $collaShortname = auth()->user()?->colla?->shortname ?? '';
    @endphp

    {{-- Capçalera: selector d'event --}}
    <div style="display:flex; align-items:center; gap:10px; margin-bottom:16px; flex-wrap:wrap;">
        <div style="font-size:13px; font-weight:600; color:#374151;">
            @if($event)
                📅 {{ $event->start_date?->format('d/m/Y') }} · {{ $event->name }}
            @else
                Selecciona un event per gestionar les rondes
            @endif
        </div>
        <a href="/admin/{{ $collaShortname }}/events"
            style="font-size:11px; padding:4px 10px; border-radius:6px; background:#f3f4f6; border:1px solid #d1d5db; color:#374151; text-decoration:none; white-space:nowrap;">
            ← Events
        </a>
        @if($event)
            <a href="/admin/{{ $collaShortname }}/pissarra?selectedEventId={{ $this->eventId }}&autoLoad=1"
                style="font-size:11px; padding:4px 10px; border-radius:6px; background:#eff6ff; border:1px solid #bfdbfe; color:#2563eb; text-decoration:none; white-space:nowrap;">
                🎨 Pissarra d'aquest event
            </a>
        @endif
    </div>

    @if(!$event)
        <div style="text-align:center; padding:40px; color:#9ca3af; font-size:14px;">
            Accedeix a les rondes des de la taula d'<a href="/admin/{{ $collaShortname }}/events" style="color:#2563eb;">Events</a>.
        </div>
    @else
        {{-- Layout dos panells --}}
        <div style="display:flex; gap:16px; align-items:flex-start;">

            {{-- PANELL ESQUERRE — Rondes assignades --}}
            <div style="flex:1; background:#f9fafb; border-radius:10px; border:1px solid #e5e7eb; overflow:hidden;">
                <div style="padding:10px 14px; background:#f3f4f6; border-bottom:1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <div style="font-size:13px; font-weight:700; color:#111827;">Rondes d'aquest event</div>
                        <div style="font-size:11px; color:#6b7280;">{{ $rondes->count() }} rondes assignades</div>
                    </div>
                    <div style="font-size:10px; color:#9ca3af; text-align:right;">
                        ▲▼ ordena · 📡 projecta
                    </div>
                </div>
                <div style="padding:10px;">
                    @forelse($rondes as $snap)
                        <div style="border:1px solid #bfdbfe; border-radius:8px; padding:10px 12px; margin-bottom:8px; background:white; display:flex; align-items:center; gap:10px;">
                            <div style="flex-shrink:0; width:32px; height:32px; border-radius:50%; background:#1d4ed8; color:white; font-size:13px; font-weight:700; display:flex; align-items:center; justify-content:center;">
                                {{ $snap->ronda_number }}
                            </div>
                            <div style="flex:1; overflow:hidden;">
                                <div style="font-size:12px; font-weight:600; color:#111827; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                    {{ $snap->name }}
                                </div>
                                <div style="font-size:10px; color:#9ca3af;">{{ strtoupper($snap->castle_type) }}</div>
                            </div>
                            <div style="display:flex; gap:4px; flex-shrink:0;">
                                @if($snap->ronda_number > 1)
                                    <button wire:click="moveUp({{ $snap->id }})"
                                        title="Puja una posició"
                                        style="font-size:12px; padding:4px 8px; border-radius:4px; background:#f3f4f6; border:1px solid #d1d5db; cursor:pointer;">▲</button>
                                @else
                                    <button disabled style="font-size:12px; padding:4px 8px; border-radius:4px; background:#f9fafb; border:1px solid #e5e7eb; cursor:default; color:#d1d5db;">▲</button>
                                @endif
                                @if(!$loop->last)
                                    <button wire:click="moveDown({{ $snap->id }})"
                                        title="Baixa una posició"
                                        style="font-size:12px; padding:4px 8px; border-radius:4px; background:#f3f4f6; border:1px solid #d1d5db; cursor:pointer;">▼</button>
                                @else
                                    <button disabled style="font-size:12px; padding:4px 8px; border-radius:4px; background:#f9fafb; border:1px solid #e5e7eb; cursor:default; color:#d1d5db;">▼</button>
                                @endif
                                <button wire:click="projectRonda({{ $snap->id }})"
                                    title="Projecta aquesta pinya al display"
                                    style="font-size:12px; padding:4px 8px; border-radius:4px; background:#064e3b; border:1px solid #059669; color:#d1fae5; cursor:pointer;">📡</button>
                                <button wire:click="removeFromRonda({{ $snap->id }})"
                                    wire:confirm="Treure '{{ addslashes($snap->name) }}' de les rondes?"
                                    title="Treu de rondes"
                                    style="font-size:12px; padding:4px 8px; border-radius:4px; background:#fef2f2; border:1px solid #fecaca; color:#dc2626; cursor:pointer;">✕</button>
                            </div>
                        </div>
                    @empty
                        <div style="text-align:center; padding:32px 16px; color:#9ca3af; font-size:13px;">
                            Sense rondes. Afegeix pinyes disponibles amb ←
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- PANELL DRET — Pinyes disponibles --}}
            <div style="width:260px; flex-shrink:0; background:#f9fafb; border-radius:10px; border:1px solid #e5e7eb; overflow:hidden;">
                <div style="padding:10px 14px; background:#f3f4f6; border-bottom:1px solid #e5e7eb;">
                    <div style="font-size:13px; font-weight:700; color:#111827;">Pinyes disponibles</div>
                    <div style="font-size:11px; color:#6b7280;">{{ $disps->count() }} versions sense ronda</div>
                </div>
                <div style="padding:10px;">
                    @forelse($disps as $snap)
                        <div style="border:1px solid #e5e7eb; border-radius:8px; padding:8px 10px; margin-bottom:8px; background:white; display:flex; align-items:center; gap:8px;">
                            <div style="flex:1; overflow:hidden;">
                                <div style="font-size:12px; font-weight:600; color:#111827; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                    @if($snap->is_primary) ⭐ @endif{{ $snap->name }}
                                </div>
                                <div style="font-size:10px; color:#9ca3af;">{{ strtoupper($snap->castle_type) }}</div>
                            </div>
                            <button wire:click="assignToRonda({{ $snap->id }})"
                                title="Afegir a rondes"
                                style="flex-shrink:0; font-size:11px; padding:4px 10px; border-radius:6px; background:#eff6ff; border:1px solid #bfdbfe; color:#1d4ed8; cursor:pointer; white-space:nowrap;">
                                ← Afegeix
                            </button>
                        </div>
                    @empty
                        <div style="text-align:center; padding:32px 16px; color:#9ca3af; font-size:12px;">
                            Totes les pinyes estan assignades a rondes.
                        </div>
                    @endforelse

                    @if($disps->isEmpty() && $rondes->isEmpty())
                        <div style="text-align:center; padding:16px; color:#9ca3af; font-size:12px;">
                            No hi ha pinyes desades per aquest event.<br>
                            <a href="/admin/{{ $collaShortname }}/pissarra?selectedEventId={{ $this->eventId }}"
                                style="color:#2563eb; text-decoration:underline;">Crea'n una a la pissarra.</a>
                        </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- Peu: URL projector --}}
        @if($rondes->isNotEmpty())
            <div style="margin-top:16px; padding:10px 14px; background:#111827; border-radius:8px; font-size:11px; color:#6b7280; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <span style="color:#9ca3af;">📡 URL Projector:</span>
                <a href="{{ $this->getProjectorUrl() }}" target="_blank"
                    style="color:#60a5fa; text-decoration:underline; word-break:break-all;">{{ $this->getProjectorUrl() }}</a>
            </div>
        @endif
    @endif
</x-filament-panels::page>
