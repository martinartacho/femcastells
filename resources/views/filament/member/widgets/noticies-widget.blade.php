<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Notícies</x-slot>
        <x-slot name="headerEnd">
            <a href="{{ route('noticies.index') }}" target="_blank"
               style="font-size:.75rem;color:var(--color-primary-600,#e11d48);text-decoration:none;font-weight:500;">
                Veure totes →
            </a>
        </x-slot>

        @php
            $unread = $this->getUnreadNoticies();
            $read   = $this->getReadNoticies();
            $labelBg = [
                'general'    => '#f3f4f6', 'sistema'    => '#dbeafe',
                'pissarra'   => '#fee2e2', 'castellers' => '#fef3c7',
                'events'     => '#d1fae5', 'admin'      => '#ede9fe',
            ];
            $labelFg = [
                'general'    => '#4b5563', 'sistema'    => '#1d4ed8',
                'pissarra'   => '#b91c1c', 'castellers' => '#92400e',
                'events'     => '#065f46', 'admin'      => '#5b21b6',
            ];
            $labelNames = \App\Models\Noticia::LABELS;
        @endphp

        @if($unread->isEmpty() && $read->isEmpty())
            <p style="font-size:.875rem;color:#9ca3af;padding:.5rem 0;">No hi ha notícies publicades.</p>
        @endif

        {{-- Notícies no llegides — format card --}}
        @foreach($unread as $n)
        <div style="border:1px solid #e5e7eb;border-radius:.75rem;background:#f9fafb;padding:1.25rem;margin-bottom:1rem;">
            <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:.75rem;">
                @foreach($n->labels ?? [] as $label)
                <span style="font-size:.7rem;font-weight:600;padding:.2rem .6rem;border-radius:9999px;background:{{ $labelBg[$label] ?? $labelBg['general'] }};color:{{ $labelFg[$label] ?? $labelFg['general'] }};">
                    {{ $labelNames[$label] ?? $label }}
                </span>
                @endforeach
                @if($n->version)
                <span style="font-size:.7rem;font-family:monospace;color:#9ca3af;">{{ $n->version }}</span>
                @endif
                <span style="font-size:.7rem;color:#9ca3af;margin-left:auto;">
                    {{ $n->published_at->translatedFormat('d M Y') }}
                </span>
            </div>
            <h3 style="font-size:1rem;font-weight:700;color:#111827;margin:0 0 .25rem;">{{ $n->title }}</h3>
            @if($n->summary)
            <p style="font-size:.875rem;color:#6b7280;margin:0 0 .75rem;line-height:1.5;">{{ $n->summary }}</p>
            @endif
            <div style="font-size:.875rem;color:#374151;line-height:1.6;">
                {!! $n->body !!}
            </div>
        </div>
        @endforeach

        {{-- Notícies anteriors — llista simple --}}
        @if($read->isNotEmpty())
        <div style="border-top:1px solid #f3f4f6;padding-top:1rem;margin-top:.5rem;">
            <p style="font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af;margin-bottom:.75rem;">
                Notícies anteriors
            </p>
            <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:.5rem;">
                @foreach($read as $n)
                <li style="display:flex;align-items:center;gap:.5rem;font-size:.875rem;">
                    <span style="width:.4rem;height:.4rem;border-radius:9999px;background:#d1d5db;flex-shrink:0;"></span>
                    @if($n->is_public)
                    <a href="{{ route('noticies.index') }}"
                       style="color:#6b7280;text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        {{ $n->title }}
                    </a>
                    @else
                    <span style="color:#9ca3af;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $n->title }}</span>
                    @endif
                    <span style="font-size:.7rem;color:#d1d5db;margin-left:auto;white-space:nowrap;">
                        {{ $n->published_at->translatedFormat('d M') }}
                    </span>
                </li>
                @endforeach
            </ul>
        </div>
        @endif

    </x-filament::section>
</x-filament-widgets::widget>
