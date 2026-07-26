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
    window.__catSettings = @json($this->getCatSettings());
    window.__catCustom   = @json(auth()->user()?->colla?->board_settings['cat_custom'] ?? []);
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

        {{-- Obre plantilla existent --}}
        <div x-data="{ open: false }" style="position:relative;">
            <button type="button" @click="open = !open" title="Obre plantilla"
                    style="padding:5px 10px; border:1px solid #d1d5db; border-radius:7px;
                           background:#fff; font-size:14px; cursor:pointer; color:#6b7280;">
                📁
            </button>
            <div x-show="open" x-cloak @click.outside="open = false"
                 style="position:absolute; top:calc(100% + 6px); left:0; z-index:9999;
                        background:#fff; border:1px solid #e5e7eb; border-radius:10px;
                        box-shadow:0 6px 20px rgba(0,0,0,.12); min-width:240px; max-height:320px;
                        overflow-y:auto; padding:6px 0;">
                <div style="padding:6px 12px 4px; font-size:10px; font-weight:700;
                            text-transform:uppercase; letter-spacing:.08em; color:#9ca3af;">
                    Plantilles desades
                </div>
                @forelse($this->getTemplatesList() as $tpl)
                <button type="button"
                        wire:click="loadTemplate({{ $tpl['id'] }})"
                        @click="open = false"
                        style="width:100%; text-align:left; padding:6px 12px; border:none;
                               background:none; cursor:pointer; font-size:12px; color:#374151;
                               display:flex; justify-content:space-between; align-items:center;
                               transition:background .1s;"
                        onmouseover="this.style.background='#f5f3ff'"
                        onmouseout="this.style.background='none'">
                    <span>{{ $tpl['name'] }}</span>
                    <span style="font-size:10px; color:#9ca3af; margin-left:8px;">{{ $tpl['count'] }} pos.</span>
                </button>
                @empty
                <div style="padding:10px 12px; font-size:11px; color:#9ca3af;">
                    Cap plantilla desada
                </div>
                @endforelse
            </div>
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
                        ['section'=>'Pinya',      'id'=>'rengle',     'label'=>'Baix',       'color'=>'#e0e7ff', 'shape'=>'rect'],
                        ['section'=>null,          'id'=>'agulla',     'label'=>'Agulla',     'color'=>'#c7d2fe', 'shape'=>'rect'],
                        ['section'=>null,          'id'=>'contrafort', 'label'=>'Contrafort', 'color'=>'#fef3c7', 'shape'=>'rect'],
                        ['section'=>null,          'id'=>'cordo',      'label'=>'Cordó',      'color'=>'#f1f5f9', 'shape'=>'rect'],
                        ['section'=>null,          'id'=>'mans',       'label'=>'Mans',       'color'=>'#bfdbfe', 'shape'=>'rect'],
                        ['section'=>null,          'id'=>'porta',      'label'=>'Crossa',     'color'=>'#ffedd5', 'shape'=>'rect'],
                        ['section'=>null,          'id'=>'vent',       'label'=>'Vent',       'color'=>'#bbf7d0', 'shape'=>'ellipse'],
                        ['section'=>'Tronc',       'id'=>'segon',      'label'=>'Segon',      'color'=>'#fce7f3', 'shape'=>'rect'],
                        ['section'=>null,          'id'=>'terc',       'label'=>'Terç',       'color'=>'#fbcfe8', 'shape'=>'rect'],
                        ['section'=>null,          'id'=>'quart',      'label'=>'Quart+',     'color'=>'#f9a8d4', 'shape'=>'rect'],
                        ['section'=>'Pom de dalt', 'id'=>'dos',        'label'=>'Dos',        'color'=>'#fef9c3', 'shape'=>'rect'],
                        ['section'=>null,          'id'=>'acotxador',  'label'=>'Acotxador',  'color'=>'#fef08a', 'shape'=>'rect'],
                        ['section'=>null,          'id'=>'enxaneta',   'label'=>'Enxaneta',   'color'=>'#fde047', 'shape'=>'circle'],
                        ['section'=>'Auxiliar',    'id'=>'etiqueta',   'label'=>'Etiqueta',   'color'=>'transparent', 'shape'=>'label'],
                    ];
                @endphp

                @php
                    $catSettings = $this->getCatSettings();
                    $shapes = ['rect'=>'Rect','rect-rounded'=>'Round','ellipse'=>'Oval','circle'=>'Circ','label'=>'Text'];
                @endphp
                <div id="te-cat-list">
                @foreach($cats as $cat)
                @if($cat['section'])
                <div style="font-size:8.5px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;
                            color:#9ca3af;padding:6px 8px 2px;margin-top:2px;">
                    {{ $cat['section'] }}
                </div>
                @endif
                @php
                    $cfg   = $catSettings[$cat['id']] ?? [];
                    $lbl   = is_array($cfg) ? ($cfg['label'] ?? $cat['label']) : ($cfg ?: $cat['label']);
                    $col   = is_array($cfg) ? ($cfg['color'] ?? $cat['color']) : $cat['color'];
                    $shp   = is_array($cfg) ? ($cfg['shape'] ?? 'rect') : 'rect';
                @endphp
                <div class="te-cat-row" data-cat-id="{{ $cat['id'] }}"
                     onclick="window.templateEditor.selectByGrup('{{ $cat['id'] }}')">
                    <input type="color" value="{{ $col === 'transparent' ? '#ffffff' : $col }}"
                           style="width:16px;height:16px;border:none;padding:0;cursor:pointer;border-radius:3px;"
                           onclick="event.stopPropagation()"
                           oninput="event.stopPropagation(); window.templateEditor.updateCatColor('{{ $cat['id'] }}', this.value)">
                    <span class="te-cat-lbl" contenteditable="true" spellcheck="false"
                          title="Clica per editar el nom"
                          onclick="event.stopPropagation()"
                          onblur="window.templateEditor.updateCatLabel('{{ $cat['id'] }}', this.textContent.trim())"
                          onkeydown="if(event.key==='Enter'){event.preventDefault();this.blur();}">{{ $lbl }}</span>
                    <select onclick="event.stopPropagation()"
                            onchange="event.stopPropagation(); window.templateEditor.updateCatShape('{{ $cat['id'] }}', this.value)"
                            style="font-size:9px;border:1px solid #e5e7eb;border-radius:3px;padding:1px 2px;
                                   background:#fff;color:#374151;max-width:44px;">
                        @foreach($shapes as $sv => $sl)
                        <option value="{{ $sv }}" {{ $shp === $sv ? 'selected' : '' }}>{{ $sl }}</option>
                        @endforeach
                    </select>
                    <span class="te-cat-num" id="te-n-{{ $cat['id'] }}">0</span>
                    <button type="button" class="te-btn-add" title="Afegir"
                            onclick="event.stopPropagation(); window.templateEditor.addPosition('{{ $cat['id'] }}')">+</button>
                </div>
                @endforeach
                <div id="te-cat-add-btn">
                    <button type="button"
                            onclick="window.templateEditor.addCustomCategory('Nova categoria','#e5e7eb','rect')"
                            style="margin:4px 8px 2px; padding:3px 8px; border:1px dashed #d1d5db;
                                   border-radius:5px; background:transparent; font-size:11px;
                                   cursor:pointer; color:#6b7280; width:calc(100% - 16px);">
                        + Categoria
                    </button>
                </div>
                </div>
            </div>

            {{-- Desa categories --}}
            <div style="border-top:1px solid #f3f4f6; padding:6px 8px; flex-shrink:0;">
                <button type="button"
                        onclick="
                            const settings = {};
                            const custom   = [];
                            document.querySelectorAll('#te-cat-list .te-cat-row').forEach(row => {
                                const id  = row.dataset.catId;
                                if (!id) return;
                                const lbl = row.querySelector('.te-cat-lbl')?.textContent?.trim() ?? '';
                                const col = row.querySelector('input[type=color]')?.value ?? '';
                                const shp = row.querySelector('select')?.value ?? 'rect';
                                if (id.startsWith('custom_')) custom.push({id, label:lbl, color:col, shape:shp});
                                else settings[id] = {label:lbl, color:col, shape:shp};
                            });
                            @this.saveCatSettings(settings, custom);
                        "
                        style="padding:4px 10px; border:1px solid #6d28d9; border-radius:6px;
                               background:#f5f3ff; font-size:11px; cursor:pointer;
                               color:#6d28d9; width:100%;">
                    💾 Desa categories
                </button>
            </div>

            {{-- Mides per defecte de les figures --}}
            <div style="border-top:1px solid #f3f4f6; padding:8px 8px 6px;">
                <div style="font-size:9px; font-weight:700; text-transform:uppercase;
                            letter-spacing:.08em; color:#9ca3af; margin-bottom:5px;">
                    Figures (px)
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:4px;">
                    <div>
                        <label style="font-size:9px; color:#6b7280; display:block; margin-bottom:1px;">A (amplada)</label>
                        <input type="number" wire:model.live="defaultW" class="te-input"
                               style="font-size:11px; padding:3px 6px;" min="5" max="300">
                    </div>
                    <div>
                        <label style="font-size:9px; color:#6b7280; display:block; margin-bottom:1px;">H (alçada)</label>
                        <input type="number" wire:model.live="defaultH" class="te-input"
                               style="font-size:11px; padding:3px 6px;" min="5" max="300">
                    </div>
                </div>
            </div>

            {{-- Construeix pas a pas --}}
            <div style="display:none; border-top:1px solid #f3f4f6; padding:8px 8px 4px;">
                <div style="font-size:9px; font-weight:700; text-transform:uppercase;
                            letter-spacing:.08em; color:#9ca3af; margin-bottom:6px;">
                    Construeix
                </div>
                @php
                $steps = [
                    1 => 'Baixos',
                    2 => 'Agulles',
                    3 => 'Contraforts',
                    4 => 'Croses',
                    5 => 'Porta croses',
                    6 => 'Mans',
                    7 => 'Vents',
                ];
                @endphp
                @foreach($steps as $s => $label)
                <button type="button" wire:click="addStep({{ $s }})"
                        class="te-action" style="font-size:11px; padding:3px 8px; margin-bottom:2px;">
                    {{ $s }}. {{ $label }}
                </button>
                @endforeach
                {{-- Cordons: botó + select Livewire --}}
                <div style="display:flex; align-items:center; gap:4px; margin-bottom:2px;">
                    <button type="button" wire:click="addCordonsWired()"
                            class="te-action" style="font-size:11px; padding:3px 8px; width:auto; flex-shrink:0;">
                        8. Cordons
                    </button>
                    <select wire:model.live="cordonsN"
                            style="font-size:11px; padding:2px 4px; border:1px solid #e5e7eb;
                                   border-radius:4px; background:#fff; color:#374151; flex:1; min-width:0;">
                        @for($i = 1; $i <= 9; $i++)
                            <option value="{{ $i }}">{{ $i }}</option>
                        @endfor
                    </select>
                </div>
            </div>

            <div style="padding:6px 8px 10px; border-top:1px solid #f3f4f6; display:flex; flex-direction:column; gap:4px;">
                <button type="button" onclick="window.templateEditor.clearCanvas()"
                        class="te-action" style="font-size:11px;">
                    🧹 Neteja canvas
                </button>
                <button type="button" onclick="window.templateEditor.deleteSelected()"
                        class="te-action te-del" style="font-size:11px;">
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

            <div id="te-props" style="display:none; flex-direction:column; gap:8px; padding:10px;">

                <div>
                    <label style="font-size:10px; color:#6b7280; display:block; margin-bottom:2px;">Nom</label>
                    <input type="text" id="te-nom" class="te-input"
                           oninput="window.templateEditor.updateNom(this.value)">
                </div>

                <div>
                    <label style="font-size:10px; color:#6b7280; display:block; margin-bottom:2px;">Categoria</label>
                    <select id="te-grup" class="te-select"
                            onchange="window.templateEditor.updateGrup(this.value)">
                        @foreach($cats as $cat)
                        <option value="{{ $cat['id'] }}">{{ $cat['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px;">
                    <div>
                        <label style="font-size:10px; color:#6b7280; display:block; margin-bottom:2px;">X</label>
                        <input type="number" id="te-x" class="te-input" step="0.5"
                               onchange="window.templateEditor.updateX(+this.value)">
                    </div>
                    <div>
                        <label style="font-size:10px; color:#6b7280; display:block; margin-bottom:2px;">Y</label>
                        <input type="number" id="te-y" class="te-input" step="0.5"
                               onchange="window.templateEditor.updateY(+this.value)">
                    </div>
                </div>

                <div>
                    <label style="font-size:10px; color:#6b7280; display:block; margin-bottom:2px;">Angle °</label>
                    <input type="number" id="te-angle" class="te-input" step="1"
                           onchange="window.templateEditor.updateAngle(+this.value)">
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px;">
                    <div>
                        <label style="font-size:10px; color:#6b7280; display:block; margin-bottom:2px;">A (px)</label>
                        <input type="number" id="te-w" class="te-input" step="1"
                               onchange="window.templateEditor.updateW(+this.value)">
                    </div>
                    <div>
                        <label style="font-size:10px; color:#6b7280; display:block; margin-bottom:2px;">H (px)</label>
                        <input type="number" id="te-h" class="te-input" step="1"
                               onchange="window.templateEditor.updateH(+this.value)">
                    </div>
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
