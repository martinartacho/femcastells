<x-filament-panels::page>
@vite('resources/js/template-editor.js')
<style>
    .fi-main-ctn { max-width:none !important; padding:0 0.75rem 0.75rem !important; }
    [x-cloak] { display:none !important; }
    .wz-step1 { display:flex; flex-direction:column; align-items:center; justify-content:center;
                height:calc(100svh - 9rem); gap:2.5rem; }
    .wz-card  { background:#fff; border:2px solid #e5e7eb; border-radius:14px; padding:16px 24px;
                cursor:pointer; text-align:center; transition:border-color .15s, background .15s; }
    .wz-card:hover, .wz-card.active { border-color:#6d28d9; background:#f5f3ff; }
    .wz-card .ico { font-size:2rem; margin-bottom:4px; }
    .wz-card .lbl { font-size:.85rem; font-weight:600; color:#374151; }
    .wz-pis  { padding:8px 14px; border-radius:8px; border:2px solid #e5e7eb; font-size:.9rem;
               font-weight:700; cursor:pointer; background:#fff; color:#374151; transition:.12s; }
    .wz-pis:hover, .wz-pis.active { border-color:#6d28d9; background:#f5f3ff; color:#6d28d9; }
    .wz-niv  { padding:8px 20px; border-radius:8px; border:2px solid #e5e7eb; font-size:.85rem;
               cursor:pointer; background:#fff; color:#374151; transition:.12s; }
    .wz-niv:hover, .wz-niv.active { border-color:#6d28d9; background:#f5f3ff; color:#6d28d9; }
    .te-cat-row { display:flex; align-items:center; gap:6px; padding:5px 8px; border-radius:6px;
                  cursor:pointer; transition:background .1s; }
    .te-cat-row:hover { background:#f9fafb; }
    .te-cat-dot { width:12px; height:12px; border-radius:3px; flex-shrink:0; border:1px solid rgba(0,0,0,.12); }
    .te-cat-lbl { font-size:11.5px; color:#374151; flex:1; }
    .te-cat-num { font-size:11px; color:#9ca3af; min-width:18px; text-align:right; }
    .te-btn-add { padding:1px 7px; border-radius:5px; border:1px solid #d1d5db; background:#f9fafb;
                  color:#374151; font-size:13px; cursor:pointer; line-height:1.4; }
    .te-btn-add:hover { background:#eff6ff; border-color:#bfdbfe; color:#2563eb; }
    .te-cat-lbl[contenteditable]:hover { background:#f3f4f6; border-radius:3px; }
    .te-cat-lbl[contenteditable]:focus { outline:1px solid #8b5cf6; background:#faf5ff;
                                         border-radius:3px; padding:0 2px; }
    .te-input { width:100%; border:1px solid #d1d5db; border-radius:6px; padding:4px 8px;
                font-size:12px; color:#374151; outline:none; }
    .te-input:focus { border-color:#8b5cf6; box-shadow:0 0 0 2px #ede9fe; }
    .te-select { width:100%; border:1px solid #d1d5db; border-radius:6px; padding:4px 6px;
                 font-size:12px; color:#374151; background:#fff; outline:none; }
    .te-action { padding:5px 10px; border-radius:7px; border:1px solid #d1d5db; background:#f9fafb;
                 font-size:12px; cursor:pointer; color:#374151; width:100%; margin-bottom:4px;
                 text-align:left; transition:.1s; }
    .te-action:hover { background:#eff6ff; border-color:#bfdbfe; color:#2563eb; }
    .te-del { background:#fef2f2; border-color:#fecaca; color:#b91c1c; }
    .te-del:hover { background:#fee2e2; }
</style>

{{-- ──────────────────────────────────────────────── PAS 1 ──────────────────── --}}
@if($step === 1)
<div class="wz-step1">

    {{-- Títol --}}
    <div style="text-align:center;">
        <div style="font-size:1.5rem; font-weight:700; color:#111827;">Nova plantilla</div>
        <div style="font-size:.9rem; color:#6b7280; margin-top:4px;">Tria el tipus de castell</div>
    </div>

    {{-- Família / Tipus --}}
    <div>
        <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.08em;
                    color:#9ca3af; text-align:center; margin-bottom:12px;">1. Tipus</div>
        <div style="display:flex; gap:10px; flex-wrap:wrap; justify-content:center;">
            @foreach($this->getFamilies() as $key => $info)
            <button type="button"
                    wire:click="$set('familia', '{{ $key }}')"
                    class="wz-card {{ $familia === $key ? 'active' : '' }}"
                    style="min-width:80px;">
                <div style="font-size:1.6rem; font-weight:800; color:#6d28d9; line-height:1.1; margin-bottom:2px;">
                    {{ $info['baixos'] }}
                </div>
                <div class="lbl">{{ $info['nom'] }}</div>
                @if($info['nota'])
                <div style="font-size:9px; color:#9ca3af; margin-top:2px;">{{ $info['nota'] }}</div>
                @else
                <div style="font-size:9px; color:#9ca3af; margin-top:2px;">{{ $info['baixos'] === 1 ? '1 baix' : $info['baixos'].' baixos' }}</div>
                @endif
            </button>
            @endforeach
        </div>
    </div>

    {{-- Pisos --}}
    <div>
        <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.08em;
                    color:#9ca3af; text-align:center; margin-bottom:12px;">2. Pisos</div>
        <div style="display:flex; gap:8px; justify-content:center;">
            @foreach(range(4, 10) as $p)
            <button type="button"
                    wire:click="$set('pisos', {{ $p }})"
                    class="wz-pis {{ $pisos === $p ? 'active' : '' }}">
                {{ $p }}
            </button>
            @endforeach
        </div>
    </div>

    {{-- Nivell --}}
    <div>
        <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.08em;
                    color:#9ca3af; text-align:center; margin-bottom:12px;">3. Vista</div>
        <div style="display:flex; gap:10px; justify-content:center; flex-wrap:wrap;">
            @foreach(['tronc' => 'Tronc', 'base' => 'Base', 'folre' => 'Amb folre', 'manilles' => 'Amb manilles'] as $k => $lbl)
            <button type="button"
                    wire:click="$set('nivell', '{{ $k }}')"
                    class="wz-niv {{ $nivell === $k ? 'active' : '' }}">
                {{ $lbl }}
            </button>
            @endforeach
        </div>
    </div>

    {{-- Resum + botó --}}
    <div style="text-align:center;">
        <div style="font-size:1.1rem; font-weight:600; color:#6d28d9; margin-bottom:16px;">
            {{ $this->getCastellName() }}
        </div>
        <button type="button" wire:click="generate"
                style="padding:12px 40px; background:#6d28d9; color:#fff; border:none; border-radius:10px;
                       font-size:1rem; font-weight:700; cursor:pointer; box-shadow:0 2px 8px rgba(109,40,217,.3);">
            Genera →
        </button>
    </div>
</div>

{{-- ──────────────────────────────────────────────── PAS 2 ──────────────────── --}}
@elseif($step === 2)

<script>
    window.__templatePositions = @json($this->getInitialPositions());
</script>

<div style="display:flex; flex-direction:column; height:calc(100svh - 8rem); overflow:hidden; gap:8px;">

    {{-- Topbar --}}
    <div style="display:flex; align-items:center; gap:10px; padding:6px 0; flex-shrink:0;">
        <button type="button" wire:click="back"
                style="padding:5px 12px; border-radius:7px; border:1px solid #d1d5db; background:#fff;
                       font-size:12px; cursor:pointer; color:#6b7280; display:flex; align-items:center; gap:5px;">
            ← Enrere
        </button>
        {{-- Nom de la plantilla --}}
        <div style="font-size:15px; font-weight:700; color:#111827; white-space:nowrap;">
            {{ $templateName }}
        </div>

        {{-- Selectors interactius --}}
        @php $selStyle = "padding:4px 8px; border:1px solid #d1d5db; border-radius:7px;
                          font-size:12px; color:#374151; background:#fff; outline:none; cursor:pointer;"; @endphp

        <select wire:model="familia" style="{{ $selStyle }}">
            @foreach($this->getFamilies() as $key => $info)
            <option value="{{ $key }}">{{ $info['nom'] }}</option>
            @endforeach
        </select>

        <select wire:model="pisos" style="{{ $selStyle }}">
            @foreach(range(4, 10) as $p)
            <option value="{{ $p }}">{{ $p }} pisos</option>
            @endforeach
        </select>

        <select wire:model="nivell" style="{{ $selStyle }}">
            @foreach(['tronc'=>'Tronc','base'=>'Base','folre'=>'Amb folre','manilles'=>'Amb manilles'] as $k=>$lbl)
            <option value="{{ $k }}">{{ $lbl }}</option>
            @endforeach
        </select>

        <button type="button" wire:click="regenerate"
                style="padding:5px 12px; border:1px solid #6d28d9; border-radius:7px;
                       background:#f5f3ff; color:#6d28d9; font-size:12px; font-weight:600;
                       cursor:pointer; white-space:nowrap;">
            ↺ Regenera
        </button>

        {{-- Desfés / Refés --}}
        <div style="display:flex; gap:4px;">
            <button type="button" onclick="window.templateEditor.undo()" title="Desfés (Ctrl+Z)"
                    style="padding:5px 10px; border:1px solid #d1d5db; border-radius:7px;
                           background:#fff; font-size:13px; cursor:pointer; color:#6b7280;">
                ↩
            </button>
            <button type="button" onclick="window.templateEditor.redo()" title="Refés (Ctrl+Y)"
                    style="padding:5px 10px; border:1px solid #d1d5db; border-radius:7px;
                           background:#fff; font-size:13px; cursor:pointer; color:#6b7280;">
                ↪
            </button>
        </div>

        <div style="margin-left:auto; display:flex; gap:8px; align-items:center;">
            <input id="te-final-name" type="text" value="{{ $templateName }}"
                   style="padding:5px 10px; border:1px solid #d1d5db; border-radius:7px;
                          font-size:13px; width:200px; color:#374151; outline:none;">
            <button type="button"
                    onclick="
                        const name = document.getElementById('te-final-name').value;
                        const pos  = window.templateEditor.exportPositions();
                        if (!pos.length) { alert('El canvas és buit'); return; }
                        @this.dispatch('template-save', { name, positionsJson: JSON.stringify(pos) });
                    "
                    style="padding:6px 18px; background:#059669; color:#fff; border:none; border-radius:8px;
                           font-size:13px; font-weight:600; cursor:pointer;">
                💾 Desa plantilla
            </button>
        </div>
    </div>

    {{-- Cos: panell categories + canvas + propietats --}}
    <div style="flex:1; display:flex; gap:8px; min-height:0; overflow:hidden;">

        {{-- ── Panell categories (esquerra) ── --}}
        <aside style="width:180px; flex-shrink:0; background:#fff; border:1px solid #e5e7eb;
                      border-radius:10px; display:flex; flex-direction:column; overflow:hidden;">

            <div style="padding:10px 12px 6px; border-bottom:1px solid #f3f4f6;">
                <div style="font-size:10px; font-weight:700; text-transform:uppercase;
                            letter-spacing:.08em; color:#9ca3af;">Categories</div>
            </div>

            <div style="flex:1; overflow-y:auto; padding:8px 6px;">

                <div style="font-size:9px; color:#9ca3af; text-align:center; margin-bottom:8px; line-height:1.4;">
                    Clic dret sobre una posició per alinear, separar o eliminar
                </div>

                @php
                    $cats = [
                        ['id'=>'agulla',    'label'=>'Agulla',     'color'=>'#c7d2fe'],
                        ['id'=>'rengle',    'label'=>'Rengle',     'color'=>'#fef9c3'],
                        ['id'=>'buida',     'label'=>'Buida',      'color'=>'#dcfce7'],
                        ['id'=>'plena',     'label'=>'Plena',      'color'=>'#fce7f3'],
                        ['id'=>'mans',      'label'=>'Mans',       'color'=>'#bfdbfe'],
                        ['id'=>'cordo',     'label'=>'Cordo',      'color'=>'#f1f5f9'],
                        ['id'=>'vent',      'label'=>'Vent',       'color'=>'#bbf7d0'],
                        ['id'=>'porta',     'label'=>'Porta',      'color'=>'#ffedd5'],
                        ['id'=>'contrafort','label'=>'Contrafort', 'color'=>'#fef3c7'],
                    ];
                @endphp

                @foreach($cats as $cat)
                <div class="te-cat-row"
                     onclick="window.templateEditor.selectByGrup('{{ $cat['id'] }}')">
                    <span class="te-cat-dot" style="background:{{ $cat['color'] }};"></span>
                    <span class="te-cat-lbl"
                          contenteditable="true"
                          spellcheck="false"
                          title="Clica per editar el nom"
                          onclick="event.stopPropagation()"
                          onblur="window.templateEditor.updateCatLabel('{{ $cat['id'] }}', this.textContent.trim())"
                          onkeydown="if(event.key==='Enter'){event.preventDefault();this.blur();}"
                    >{{ $cat['label'] }}</span>
                    <span class="te-cat-num" id="te-n-{{ $cat['id'] }}">0</span>
                    <button type="button" class="te-btn-add" title="Afegir"
                            onclick="event.stopPropagation(); window.templateEditor.addPosition('{{ $cat['id'] }}')">
                        +
                    </button>
                </div>
                @endforeach
            </div>

            <div style="padding:8px 8px 10px; border-top:1px solid #f3f4f6;">
                <button type="button" onclick="window.templateEditor.deleteSelected()"
                        class="te-action te-del">
                    🗑 Elimina selecció
                </button>
            </div>
        </aside>

        {{-- ── Canvas (centre) ── --}}
        <div wire:ignore style="flex:1; min-width:0; background:#f9fafb; border:1px solid #e5e7eb;
                                border-radius:10px; overflow:hidden; position:relative;"
             x-init="
                 (() => {
                     const tryInit = () => {
                         if (window.templateEditor) {
                             window.templateEditor.init(window.__templatePositions ?? []);
                         } else {
                             setTimeout(tryInit, 50);
                         }
                     };
                     $nextTick(tryInit);
                 })();
             ">
            <div id="template-canvas" style="width:100%; height:100%;"></div>

            {{-- Instruccions --}}
            <div style="position:absolute; bottom:10px; left:10px; font-size:10px; color:#9ca3af;
                        pointer-events:none; line-height:1.8;">
                Clic = selecció &nbsp;·&nbsp; Shift+clic = multi &nbsp;·&nbsp;
                Arrossega = mou &nbsp;·&nbsp; Roda = zoom &nbsp;·&nbsp; Supr = elimina
            </div>
        </div>

        {{-- ── Propietats (dreta) ── --}}
        <aside style="width:170px; flex-shrink:0; background:#fff; border:1px solid #e5e7eb;
                      border-radius:10px; display:flex; flex-direction:column; overflow:hidden;">

            <div style="padding:10px 12px 6px; border-bottom:1px solid #f3f4f6;">
                <div style="font-size:10px; font-weight:700; text-transform:uppercase;
                            letter-spacing:.08em; color:#9ca3af;">Posició seleccionada</div>
            </div>

            <div id="te-props" style="display:none; flex-direction:column; gap:10px; padding:12px;">

                <div>
                    <label style="font-size:10px; color:#6b7280; display:block; margin-bottom:3px;">Nom</label>
                    <input type="text" id="te-nom" class="te-input"
                           oninput="window.templateEditor.updateNom(this.value)">
                </div>

                <div>
                    <label style="font-size:10px; color:#6b7280; display:block; margin-bottom:3px;">Categoria</label>
                    <select id="te-grup" class="te-select"
                            onchange="window.templateEditor.updateGrup(this.value)">
                        @foreach($cats as $cat)
                        <option value="{{ $cat['id'] }}">{{ $cat['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label style="font-size:10px; color:#6b7280; display:block; margin-bottom:3px;">Angle</label>
                    <div id="te-angle" style="font-size:13px; font-weight:600; color:#374151;">—</div>
                </div>

            </div>

            <div id="te-props-empty" style="flex:1; display:flex; align-items:center; justify-content:center;
                                            padding:16px; text-align:center;">
                <div style="font-size:11px; color:#d1d5db;">Clica una posició per editar-la</div>
            </div>
        </aside>

    </div>{{-- fi body --}}
</div>

<script>
    // Amaguem el props-empty quan es mostra props
    const obs = new MutationObserver(() => {
        const p = document.getElementById('te-props');
        const e = document.getElementById('te-props-empty');
        if (p && e) e.style.display = p.style.display === 'none' ? 'flex' : 'none';
    });
    const props = document.getElementById('te-props');
    if (props) obs.observe(props, { attributes: true, attributeFilter: ['style'] });
</script>

@endif
</x-filament-panels::page>
