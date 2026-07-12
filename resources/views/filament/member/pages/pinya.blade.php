<x-filament-panels::page>
    @vite('resources/js/board-editor.js')

    @php
        $snapshot      = $this->getSnapshot();
        $myPosId       = $this->getMyPosition();
        $myPosName     = $this->getMyPositionName();
        $casteller     = auth('member')->user();
    @endphp

    <script>
        window.__boardPositions      = @json($this->getBoardPositions());
        window.__autoLoadAssignments = @json($snapshot?->assignments ?? []);
        window.__readOnly            = true;
        window.__highlightPosition   = @json($myPosId);
    </script>

    @if($snapshot)
        {{-- Capçalera --}}
        <div style="margin-bottom:16px;">
            @if($snapshot->event)
                <div style="font-size:18px; font-weight:700; color:#111827; dark:color:#f9fafb;">
                    {{ $snapshot->event->start_date?->format('d/m/Y') }} · {{ $snapshot->event->name }}
                </div>
            @endif
            <div style="font-size:13px; color:#6b7280; margin-top:2px;">
                {{ $snapshot->name }}
                @if($this->getTemplateName()) · {{ $this->getTemplateName() }} @endif
            </div>
        </div>

        {{-- Posició personal --}}
        @if($myPosId)
            <div style="background:#d1fae5; border:1px solid #6ee7b7; border-radius:10px; padding:12px 16px; margin-bottom:16px; display:flex; align-items:center; gap:12px;">
                <span style="font-size:28px;">📍</span>
                <div>
                    <div style="font-size:15px; font-weight:700; color:#065f46;">La teva posició</div>
                    <div style="font-size:20px; font-weight:800; color:#047857; font-family:monospace;">{{ $myPosName }}</div>
                </div>
            </div>
        @else
            <div style="background:#fef3c7; border:1px solid #fcd34d; border-radius:10px; padding:12px 16px; margin-bottom:16px;">
                <span style="font-size:13px; color:#92400e;">No tens cap posició assignada en aquesta pinya.</span>
            </div>
        @endif

        {{-- Canvas --}}
        <div wire:ignore style="background:#f9fafb; border-radius:10px; border:1px solid #e5e7eb; display:inline-flex; flex-direction:column; align-items:center; padding:16px;">
            <div id="board-canvas"></div>
            <div id="board-info" style="min-height:16px; font-size:11px; color:#6b7280; margin-top:4px;"></div>
        </div>

        <script>
            document.addEventListener('board-highlight', (e) => {
                const posId = e.detail?.posId;
                if (!posId) return;
                setTimeout(() => {
                    document.dispatchEvent(new CustomEvent('board-highlight-apply', { detail: { posId } }));
                }, 50);
            });
        </script>

    @else
        <div style="text-align:center; padding:60px 20px; color:#9ca3af;">
            <div style="font-size:48px; margin-bottom:12px;">📋</div>
            <div style="font-size:16px; font-weight:600;">Cap pinya projectada ara</div>
            <div style="font-size:13px; margin-top:4px;">La tècnica publicarà la pinya quan comenci l'assaig o actuació.</div>
        </div>
    @endif
</x-filament-panels::page>
