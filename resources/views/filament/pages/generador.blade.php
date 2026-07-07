<x-filament-panels::page>

    {{-- ── Amagar el sidebar de Filament en aquesta pàgina ── --}}
    <style>
        .fi-main-ctn { max-width: none !important; padding: 0 0.75rem 0.75rem !important; }
        [x-cloak] { display: none !important; }
    </style>

    <div x-data="{
            topbarOpen: true,
            sidebarOpen: false,
            zoom: 1.0,
            toggleTopbar() {
                this.topbarOpen = !this.topbarOpen;
                const tb = document.querySelector('.fi-topbar-ctn');
                if (tb) tb.style.display = this.topbarOpen ? '' : 'none';
            },
            toggleSidebar() {
                this.sidebarOpen = !this.sidebarOpen;
                const sb = document.querySelector('.fi-sidebar');
                if (sb) sb.style.display = this.sidebarOpen ? '' : 'none';
                document.documentElement.style.setProperty('--sidebar-width', this.sidebarOpen ? '14rem' : '0px');
            }
         }"
         :style="`display:flex; gap:12px; height:calc(100svh - ${topbarOpen ? '8rem' : '3.5rem'}); overflow:hidden;`"
         x-init="
             const sb = document.querySelector('.fi-sidebar');
             if (sb) sb.style.display = 'none';
             document.documentElement.style.setProperty('--sidebar-width', '0px');
             $watch('topbarOpen', () => {});
         "
    >

        @if($templateId)
        {{-- ══════════════════════════════════════════════
             MODE EDITOR — sidebar propietats + canvas Konva
        ══════════════════════════════════════════════ --}}

        {{-- Sidebar lean: només propietats de la posició seleccionada --}}
        <aside style="width:200px; flex-shrink:0; overflow-y:auto; background:#fff;
                      border-radius:12px; border:1px solid #e5e7eb;
                      display:flex; flex-direction:column; box-shadow:0 1px 3px rgba(0,0,0,.06);">

            <div style="padding:10px 14px 8px; border-bottom:1px solid #f3f4f6;">
                <button type="button" x-data
                        x-on:click="if(confirm('Sortir de l\'editor? Els canvis no desats es perdran.')) $wire.call('closeTemplate')"
                        style="display:flex; align-items:center; gap:5px; border:none; background:none;
                               cursor:pointer; color:#6b7280; font-size:11px; padding:0;">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:13px;height:13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Generador
                </button>
            </div>

            <div style="padding:12px 14px; display:flex; flex-direction:column; gap:10px; flex:1;">
                <div style="font-size:9.5px; font-weight:700; color:#b0b7c3; text-transform:uppercase; letter-spacing:.1em;">Posició seleccionada</div>

                {{-- Propietats (omplert per JS) --}}
                <div id="generador-props" style="display:none; flex-direction:column; gap:8px;">
                    <div style="font-size:11px; color:#374151; font-weight:600;" id="prop-nom"></div>
                    <div style="font-size:10px; color:#9ca3af;" id="prop-grup"></div>
                    <div>
                        <label style="font-size:10px; color:#6b7280; display:block; margin-bottom:3px;">Color</label>
                        <input type="color" id="prop-color" style="width:100%; height:28px; border-radius:6px; border:1px solid #d1d5db; cursor:pointer; padding:1px;">
                    </div>
                    <label style="display:flex; align-items:center; gap:6px; font-size:10px; color:#6b7280; cursor:pointer;">
                        <input type="checkbox" id="prop-all-grup" style="cursor:pointer;">
                        Aplica a tot el grup
                    </label>
                </div>

                <div id="generador-info" style="font-size:11px; color:#9ca3af; min-height:18px; font-style:italic;"></div>
            </div>
        </aside>

        {{-- Àrea principal Editor --}}
        <div style="flex:1; display:flex; flex-direction:column; gap:10px; min-width:0; overflow:hidden;">

            {{-- Topbar + hamburger (fixed) --}}
            <div style="position:fixed; top:0; left:50%; transform:translateX(-50%); z-index:9999; line-height:1;">
                <button @click="toggleTopbar()" type="button"
                        style="background:#fff; border:1px solid #e5e7eb; border-top:none;
                               border-radius:0 0 8px 8px; padding:3px 16px 5px;
                               cursor:pointer; box-shadow:0 2px 4px rgba(0,0,0,.08);
                               display:inline-flex; align-items:center; gap:6px;
                               font-size:11px; color:#9ca3af; font-family:sans-serif;">
                    <span x-text="topbarOpen ? '▲' : '▼'" style="font-size:9px; line-height:1;"></span>
                    <span x-text="topbarOpen ? 'Amaga barra' : 'Mostra barra'"></span>
                </button>
            </div>
            <button @click="toggleSidebar()" type="button"
                    :style="`position:fixed; top:50%; transform:translateY(-50%); z-index:9999;
                             left:${sidebarOpen ? '14rem' : '0px'}; transition:left .2s;
                             background:#fff; border:1px solid #e5e7eb; border-left:none;
                             border-radius:0 8px 8px 0; padding:10px 5px; cursor:pointer;
                             box-shadow:2px 0 4px rgba(0,0,0,.08);
                             display:flex; flex-direction:column; align-items:center; gap:3px;`">
                <span style="display:block; width:13px; height:1.5px; background:#9ca3af; border-radius:1px;"></span>
                <span style="display:block; width:13px; height:1.5px; background:#9ca3af; border-radius:1px;"></span>
                <span style="display:block; width:13px; height:1.5px; background:#9ca3af; border-radius:1px;"></span>
            </button>

            {{-- Header: nom + capes en una línia + desar --}}
            <div style="flex-shrink:0; background:#fff; border-radius:12px; border:1px solid #e5e7eb;
                        padding:8px 16px; display:flex; align-items:center; gap:12px;
                        box-shadow:0 1px 3px rgba(0,0,0,.06);">
                <span style="font-size:14px; font-weight:700; color:#111827; white-space:nowrap;">
                    {{ $templateName }}
                </span>
                <span style="color:#d1d5db; font-size:16px; flex-shrink:0;">|</span>
                <span style="font-size:10px; font-weight:700; color:#b0b7c3; text-transform:uppercase; letter-spacing:.08em; white-space:nowrap;">Capa</span>
                <div style="display:flex; gap:3px;">
                    @foreach(['pinya' => 'Pinya', 'folre' => 'Folre', 'manilles' => 'Manilles', 'tronc' => 'Tronc'] as $l => $ln)
                        <button wire:click="$set('editorLayer', '{{ $l }}')" type="button"
                                style="padding:4px 10px; border-radius:6px; font-size:11px; cursor:pointer;
                                       border:1px solid {{ $editorLayer === $l ? '#dc2626' : '#e5e7eb' }};
                                       background:{{ $editorLayer === $l ? '#dc2626' : 'transparent' }};
                                       color:{{ $editorLayer === $l ? '#fff' : '#6b7280' }};
                                       font-weight:{{ $editorLayer === $l ? '700' : '400' }};">
                            {{ $ln }}
                        </button>
                    @endforeach
                </div>
                <div style="margin-left:auto;">
                    <button type="button" x-data
                            x-on:click="
                        const positions = window.boardGenerador?.getPositions() ?? [];
                        if (positions.length === 0) { alert('El canvas no té posicions. Recarrega la pàgina.'); return; }
                        $wire.call('saveTemplateChanges', JSON.stringify(positions));
                    "
                            style="padding:6px 16px; border-radius:8px; border:none;
                                   background:#dc2626; color:#fff; font-size:11px; cursor:pointer;
                                   font-weight:700; letter-spacing:.04em;">
                        Desar canvis
                    </button>
                </div>
            </div>

            {{-- Canvas Konva --}}
            {{-- wire:key → re-crea el canvas quan canvia la plantilla --}}
            {{-- wire:ignore → evita que Livewire morphegi el canvas durant un desar --}}
            <div wire:key="canvas-{{ $templateId }}"
                 wire:ignore
                 style="flex:1; background:#f9fafb; border-radius:12px; border:1px solid #e5e7eb;
                        overflow:hidden; position:relative; box-shadow:0 1px 3px rgba(0,0,0,.06);">
                <div id="generador-canvas"
                     style="width:100%; height:100%;"
                     data-positions="{{ $this->getPositionsJson() }}"
                     x-data
                     x-init="
                         const pos = JSON.parse($el.dataset.positions || '[]');
                         (function tryInit() {
                             const el = document.getElementById('generador-canvas');
                             if (!el || !window.boardGenerador || el.offsetWidth === 0) {
                                 requestAnimationFrame(tryInit); return;
                             }
                             window.boardGenerador.init(pos);
                         })();
                     "></div>
            </div>
        </div>

        @else
        {{-- ══════════════════════════════════════════════
             SIDEBAR ESQUERRE — paràmetres del generador
        ══════════════════════════════════════════════ --}}
        <aside style="width:224px; flex-shrink:0; overflow-y:auto; background:#fff;
                      border-radius:12px; border:1px solid #e5e7eb;
                      display:flex; flex-direction:column; box-shadow:0 1px 3px rgba(0,0,0,.06);">

            {{-- Capçalera --}}
            <div style="padding:14px 16px 10px; border-bottom:1px solid #f3f4f6;">
                <div style="font-weight:700; font-size:13px; color:#111827;">Generador de Pinya</div>
                <div style="font-size:11px; color:#9ca3af; margin-top:2px;">Dibuix automàtic per castell</div>
            </div>

            <div style="padding:14px 16px; display:flex; flex-direction:column; gap:14px; flex:1;">

                {{-- ── 1. SELECCIONA EL CASTELL ── --}}
                <div>
                    <div style="font-size:9.5px; font-weight:700; color:#b0b7c3; text-transform:uppercase; letter-spacing:.1em; margin-bottom:8px;">
                        1. Selecciona el castell
                    </div>

                    <label style="display:block; font-size:11px; color:#6b7280; margin-bottom:3px;">Família</label>
                    <select wire:model.live="familia"
                            style="width:100%; border:1px solid #d1d5db; border-radius:8px; background:#fff; color:#111827; padding:5px 8px; font-size:12px;">
                        @foreach($this->getFamilies() as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>

                    <label style="display:block; font-size:11px; color:#6b7280; margin-top:8px; margin-bottom:3px;">Castell</label>
                    <select wire:model.live="pisos"
                            style="width:100%; border:1px solid #d1d5db; border-radius:8px; background:#fff; color:#111827; padding:5px 8px; font-size:12px;">
                        @foreach(range(4, 10) as $p)
                            @php
                                $fam = $this->getFamilies()[$familia] ?? ucfirst($familia);
                                $suf = match($nivell) {
                                    'folre'    => ' amb folre',
                                    'manilles' => ' amb folre i manilles',
                                    default    => '',
                                };
                            @endphp
                            <option value="{{ $p }}">{{ $fam }} de {{ $p }}{{ $suf }}</option>
                        @endforeach
                    </select>

                    <label style="display:block; font-size:11px; color:#6b7280; margin-top:8px; margin-bottom:3px;">Nivell de pinya</label>
                    <select wire:model.live="nivell"
                            style="width:100%; border:1px solid #d1d5db; border-radius:8px; background:#fff; color:#111827; padding:5px 8px; font-size:12px;">
                        @foreach($this->getNivellOptions() as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <hr style="border:none; border-top:1px solid #f3f4f6;">

                {{-- ── 2. PARÀMETRES DE LA PINYA ── --}}
                <div>
                    <div style="font-size:9.5px; font-weight:700; color:#b0b7c3; text-transform:uppercase; letter-spacing:.1em; margin-bottom:8px;">
                        2. Paràmetres de la pinya
                    </div>

                    <label style="display:block; font-size:11px; color:#6b7280; margin-bottom:3px;">Forma dels cercles</label>
                    <select wire:model.live="shape"
                            style="width:100%; border:1px solid #d1d5db; border-radius:8px; background:#fff; color:#111827; padding:5px 8px; font-size:12px;">
                        @foreach($this->getShapeOptions() as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>

                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:10px;">
                        <label style="font-size:11px; color:#6b7280;">Mida</label>
                        <span style="font-size:11px; font-family:monospace; color:#9ca3af;">{{ $mida }}%</span>
                    </div>
                    <input type="range" wire:model.live.debounce.200ms="mida"
                           min="50" max="200" step="5"
                           style="width:100%; margin-top:4px; accent-color:#dc2626;">

                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:8px;">
                        <label style="font-size:11px; color:#6b7280;">Densitat</label>
                        <span style="font-size:11px; font-family:monospace; color:#9ca3af;">{{ $densitat }}%</span>
                    </div>
                    <input type="range" wire:model.live.debounce.200ms="densitat"
                           min="60" max="140" step="5"
                           style="width:100%; margin-top:4px; accent-color:#dc2626;">

                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:10px;">
                        <label style="font-size:11px; color:#6b7280;">Mostrar peus</label>
                        <button wire:click="$toggle('mostrarPeus')" type="button"
                                style="position:relative; display:inline-flex; height:20px; width:36px;
                                       border-radius:10px; border:none; cursor:pointer;
                                       background:{{ $mostrarPeus ? '#dc2626' : '#d1d5db' }};
                                       padding:0; transition:background .15s;">
                            <span style="position:absolute; top:2px; height:16px; width:16px; border-radius:50%;
                                         background:white; box-shadow:0 1px 3px rgba(0,0,0,.2);
                                         transition:left .15s; left:{{ $mostrarPeus ? '18px' : '2px' }};"></span>
                        </button>
                    </div>
                </div>

                <hr style="border:none; border-top:1px solid #f3f4f6;">

                {{-- ── 3. VISUALITZACIÓ ── --}}
                <div>
                    <div style="font-size:9.5px; font-weight:700; color:#b0b7c3; text-transform:uppercase; letter-spacing:.1em; margin-bottom:8px;">
                        3. Visualització
                    </div>

                    <label style="display:block; font-size:11px; color:#6b7280; margin-bottom:5px;">Vista</label>
                    <div style="display:flex; gap:4px;">
                        @foreach(['superior' => 'Planta', 'frontal' => 'Tronc', '3d' => '3D'] as $v => $icon)
                            <button wire:click="$set('vista', '{{ $v }}')" type="button" title="{{ ucfirst($v) }}"
                                    style="flex:1; padding:6px 0; border-radius:8px; font-size:15px; cursor:pointer;
                                           border:1px solid {{ $vista === $v ? '#dc2626' : '#d1d5db' }};
                                           background:{{ $vista === $v ? '#dc2626' : '#fff' }};
                                           color:{{ $vista === $v ? '#fff' : '#6b7280' }};
                                           transition:all .15s;">{{ $icon }}</button>
                        @endforeach
                    </div>

                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:10px;">
                        <label style="font-size:11px; color:#6b7280;">Noms posicions</label>
                        <button wire:click="$toggle('nomsPos')" type="button"
                                style="position:relative; display:inline-flex; height:20px; width:36px;
                                       border-radius:10px; border:none; cursor:pointer;
                                       background:{{ $nomsPos ? '#dc2626' : '#d1d5db' }};
                                       padding:0; transition:background .15s;">
                            <span style="position:absolute; top:2px; height:16px; width:16px; border-radius:50%;
                                         background:white; box-shadow:0 1px 3px rgba(0,0,0,.2);
                                         left:{{ $nomsPos ? '18px' : '2px' }};"></span>
                        </button>
                    </div>
                </div>

                {{-- ── Botó principal ── --}}
                <div style="margin-top:auto; padding-top:4px;">
                    <button wire:click="$refresh" type="button"
                            style="width:100%; display:flex; align-items:center; justify-content:center; gap:8px;
                                   background:#dc2626; color:#fff; font-weight:700; padding:10px 16px;
                                   border-radius:10px; border:none; cursor:pointer; font-size:11px;
                                   text-transform:uppercase; letter-spacing:.08em; box-shadow:0 2px 6px rgba(220,38,38,.3);">
                        <svg xmlns="http://www.w3.org/2000/svg" style="width:13px;height:13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M4 9a9 9 0 0115-3.46M20 15a9 9 0 01-15 3.46"/>
                        </svg>
                        Dibuixar pinya
                    </button>
                </div>

                <hr style="border:none; border-top:1px solid #f3f4f6;">

                {{-- ── Llegenda ── --}}
                <div style="padding-bottom:8px;">
                    <div style="font-size:9.5px; font-weight:700; color:#b0b7c3; text-transform:uppercase; letter-spacing:.1em; margin-bottom:8px;">
                        Llegenda posicions
                    </div>
                    @php
                        $legendCats = ['folre', 'laterals', 'manilles', 'mitjans', 'tronc', 'baixos', 'contrafort', 'agulla'];
                    @endphp
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:5px 10px;">
                        @foreach($legendCats as $cat)
                            <div style="display:flex; align-items:center; gap:5px;">
                                <span style="width:10px; height:10px; border-radius:50%; flex-shrink:0;
                                             background:{{ $this->catColor($cat) }};"></span>
                                <span style="font-size:10.5px; color:#374151; line-height:1.3;">{{ $this->catLabel($cat) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>
        </aside>

        {{-- ══════════════════════════════════════════════
             ÀREA PRINCIPAL — canvas Konva + taula
        ══════════════════════════════════════════════ --}}
        <div style="flex:1; display:flex; flex-direction:column; gap:10px; min-width:0; overflow:hidden;">


            {{-- Capçalera: nom casteller + botons desar --}}
            <div style="flex-shrink:0; background:#fff; border-radius:12px; border:1px solid #e5e7eb;
                        padding:8px 16px; display:flex; align-items:center; justify-content:space-between;
                        box-shadow:0 1px 3px rgba(0,0,0,.06); gap:10px;">
                <div style="display:flex; align-items:center; gap:10px; min-width:0;">
                    <span style="font-size:15px; font-weight:700; color:#111827; white-space:nowrap;">
                        {{ $templateName ?: $this->getCastellName() }}
                    </span>
                    @if($templateId)
                        <span style="font-size:10px; background:#dcfce7; color:#166534; padding:2px 8px; border-radius:999px; white-space:nowrap;">
                            Plantilla activa
                        </span>
                    @endif
                </div>
                <div style="display:flex; gap:6px; flex-shrink:0; align-items:center;">

                    {{-- Obrir plantilla (dropdown) --}}
                    <div x-data="{ open: false }" style="position:relative;">
                        <button @click="open = !open" type="button"
                                style="padding:5px 10px; border-radius:8px; border:1px solid #d1d5db;
                                       background:#fff; color:#374151; font-size:11px; cursor:pointer;
                                       display:flex; align-items:center; gap:5px;">
                            <svg xmlns="http://www.w3.org/2000/svg" style="width:13px;height:13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                            </svg>
                            Obrir
                        </button>
                        <div x-show="open" x-cloak @click.outside="open = false"
                             style="position:absolute; top:calc(100% + 4px); right:0; z-index:200;
                                    background:#fff; border:1px solid #e5e7eb; border-radius:10px;
                                    box-shadow:0 6px 20px rgba(0,0,0,.12); min-width:220px; max-height:320px;
                                    overflow-y:auto;">
                            <div style="padding:8px 12px 6px; font-size:9.5px; font-weight:700; color:#9ca3af;
                                        text-transform:uppercase; letter-spacing:.08em; border-bottom:1px solid #f3f4f6;">
                                Plantilles desades
                            </div>
                            @forelse($this->getAvailableTemplates() as $t)
                                <button wire:click="loadTemplate({{ $t->id }})" @click="open = false" type="button"
                                        style="display:flex; align-items:center; justify-content:space-between;
                                               width:100%; text-align:left; padding:9px 14px; border:none;
                                               background:{{ $templateId === $t->id ? '#fef2f2' : 'transparent' }};
                                               cursor:pointer; font-size:12px; color:#111827;
                                               border-bottom:1px solid #f9fafb;">
                                    <span>{{ $t->name }}</span>
                                    @if($templateId === $t->id)
                                        <span style="font-size:10px; color:#dc2626; font-weight:700;">✓</span>
                                    @endif
                                </button>
                            @empty
                                <div style="padding:14px; font-size:12px; color:#9ca3af; text-align:center;">
                                    Cap plantilla desada
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Desar versió (si ja hi ha plantilla) --}}
                    @if($templateId)
                        <button type="button"
                                wire:click="saveVersion"
                                style="padding:5px 12px; border-radius:8px; border:1px solid #d1d5db;
                                       background:#fff; color:#374151; font-size:11px; cursor:pointer; font-weight:600;">
                            + Versió
                        </button>
                    @endif
                    {{-- Desar com a plantilla nova --}}
                    <button type="button"
                            x-data x-on:click="
                                const nom = prompt('Nom de la plantilla:', '{{ $templateName ?: $this->getCastellName() }}');
                                if (nom) $wire.call('saveAsTemplate', nom);
                            "
                            style="padding:5px 14px; border-radius:8px; border:none;
                                   background:#dc2626; color:#fff; font-size:11px; cursor:pointer;
                                   font-weight:700; letter-spacing:.04em;">
                        Desar plantilla
                    </button>
                </div>
            </div>

            {{-- Botó toggle topbar --}}
            <div style="position:fixed; top:0; left:50%; transform:translateX(-50%); z-index:9999; line-height:1;">
                <button @click="toggleTopbar()" type="button"
                        style="background:#fff; border:1px solid #e5e7eb; border-top:none;
                               border-radius:0 0 8px 8px; padding:3px 16px 5px;
                               cursor:pointer; box-shadow:0 2px 4px rgba(0,0,0,.08);
                               display:inline-flex; align-items:center; gap:6px;
                               font-size:11px; color:#9ca3af; font-family:sans-serif;">
                    <span x-text="topbarOpen ? '▲' : '▼'" style="font-size:9px; line-height:1;"></span>
                    <span x-text="topbarOpen ? 'Amaga barra' : 'Mostra barra'"></span>
                </button>
            </div>

            {{-- Botó toggle sidebar principal --}}
            <button @click="toggleSidebar()" type="button"
                    :style="`position:fixed; top:50%; transform:translateY(-50%); z-index:9999;
                             left:${sidebarOpen ? '14rem' : '0px'}; transition:left .2s;
                             background:#fff; border:1px solid #e5e7eb; border-left:none;
                             border-radius:0 8px 8px 0; padding:10px 5px; cursor:pointer;
                             box-shadow:2px 0 4px rgba(0,0,0,.08);
                             display:flex; flex-direction:column; align-items:center; gap:3px;`"
                    title="Menú de navegació">
                <span style="display:block; width:13px; height:1.5px; background:#9ca3af; border-radius:1px;"></span>
                <span style="display:block; width:13px; height:1.5px; background:#9ca3af; border-radius:1px;"></span>
                <span style="display:block; width:13px; height:1.5px; background:#9ca3af; border-radius:1px;"></span>
            </button>

            {{-- Canvas + panells laterals --}}
            <div style="flex:1; display:flex; gap:10px; min-height:0; overflow:hidden;">

                {{-- Canvas Konva --}}
                <div style="flex:1; background:#f9fafb; border-radius:12px; border:1px solid #e5e7eb;
                            overflow:hidden; position:relative; box-shadow:0 1px 3px rgba(0,0,0,.06);">

                    {{-- Vista: Planta | Editor | Tronc | 3D --}}
                    <div style="position:absolute; top:10px; left:10px; z-index:10;
                                display:flex; gap:4px; background:rgba(255,255,255,.92);
                                border:1px solid #e5e7eb; border-radius:8px; padding:3px;">
                        @foreach(['superior' => 'Planta', 'frontal' => 'Tronc', '3d' => '3D'] as $v => $icon)
                            <button wire:click="$set('vista', '{{ $v }}')" type="button"
                                    style="padding:4px 10px; border-radius:5px; font-size:11px; cursor:pointer;
                                           border:none; font-weight:600;
                                           background:{{ $vista === $v ? '#dc2626' : 'transparent' }};
                                           color:{{ $vista === $v ? '#fff' : '#6b7280' }};">{{ $icon }}</button>
                        @endforeach
                    </div>

                    @if($vista === 'superior')
                        {{-- Planta: SVG concèntric abstracte --}}
                        <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; padding:12px;"
                             :style="`transform: scale(${zoom}); transform-origin: center center; transition: transform .15s;`">
                            {!! $this->getGeneratedSvg() !!}
                        </div>
                        {{-- Zoom botons --}}
                        <div style="position:absolute; bottom:10px; right:10px; z-index:10;
                                    display:flex; gap:3px; background:rgba(255,255,255,.92);
                                    border:1px solid #e5e7eb; border-radius:8px; padding:3px;">
                            <button @click="zoom = Math.min(4, +(zoom+0.25).toFixed(2))" type="button"
                                    style="width:26px;height:26px;display:flex;align-items:center;justify-content:center;
                                           border-radius:5px;border:none;background:transparent;cursor:pointer;font-size:16px;font-weight:700;color:#374151;">+</button>
                            <button @click="zoom = 1.0" type="button"
                                    style="height:26px;padding:0 6px;border-radius:5px;border:none;background:transparent;
                                           cursor:pointer;font-size:10px;font-family:monospace;color:#6b7280;font-weight:600;"
                                    x-text="zoom.toFixed(1)+'×'"></button>
                            <button @click="zoom = Math.max(0.25, +(zoom-0.25).toFixed(2))" type="button"
                                    style="width:26px;height:26px;display:flex;align-items:center;justify-content:center;
                                           border-radius:5px;border:none;background:transparent;cursor:pointer;font-size:16px;font-weight:700;color:#374151;">−</button>
                        </div>

                    @elseif($vista === 'frontal')
                        <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; padding:12px; overflow:auto;">
                            {!! $this->getTroncSvg() !!}
                        </div>
                    @else
                        <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; flex-direction:column; color:#9ca3af;">
                            <div style="font-size:36px; opacity:.2; margin-bottom:8px;">◈</div>
                            <p style="font-size:13px;">Vista 3D pendent d'implementar</p>
                        </div>
                    @endif
                </div>

                {{-- Panell dret: propietats + distribució --}}
                <div style="width:200px; flex-shrink:0; display:flex; flex-direction:column; gap:10px; overflow-y:auto;">

                    {{-- Propietats de la posició seleccionada --}}
                    <div id="generador-props"
                         style="display:none; flex-direction:column; gap:8px;
                                background:#fff; border-radius:12px; border:1px solid #e5e7eb;
                                padding:14px 16px; box-shadow:0 1px 3px rgba(0,0,0,.06);">
                        <div style="font-size:9.5px; font-weight:700; color:#b0b7c3; text-transform:uppercase; letter-spacing:.1em;">
                            Posició
                        </div>
                        <div style="font-size:11px; color:#374151; font-weight:600;" id="prop-nom"></div>
                        <div style="font-size:10px; color:#9ca3af;" id="prop-grup"></div>
                        <div>
                            <label style="font-size:10px; color:#6b7280; display:block; margin-bottom:3px;">Color</label>
                            <input type="color" id="prop-color"
                                   style="width:100%; height:28px; border-radius:6px; border:1px solid #d1d5db; cursor:pointer; padding:1px;">
                        </div>
                        <label style="display:flex; align-items:center; gap:6px; font-size:10px; color:#6b7280; cursor:pointer;">
                            <input type="checkbox" id="prop-all-grup" style="cursor:pointer;">
                            Aplica a tot el grup
                        </label>
                    </div>

                    {{-- Distribució --}}
                    <div style="background:#fff; border-radius:12px; border:1px solid #e5e7eb;
                                padding:14px 16px; box-shadow:0 1px 3px rgba(0,0,0,.06); flex:1;">
                        <div style="font-size:9.5px; font-weight:700; color:#b0b7c3; text-transform:uppercase;
                                    letter-spacing:.1em; margin-bottom:10px;">Distribució</div>
                        <table style="width:100%; border-collapse:collapse;">
                            <tbody>
                                <tr style="border-bottom:1px solid #f9fafb;">
                                    <td style="padding:4px 0;">
                                        <div style="display:flex; align-items:center; gap:5px;">
                                            <span style="width:7px; height:7px; border-radius:50%; background:#1a237e; flex-shrink:0;"></span>
                                            <span style="font-size:10px; color:#6b7280;">Agulla</span>
                                        </div>
                                    </td>
                                    <td style="padding:4px 0; text-align:right; font-weight:700; font-family:monospace; font-size:12px; color:#111827;">1</td>
                                </tr>
                                @foreach($this->getDistribution() as $cat => $count)
                                    <tr style="border-bottom:1px solid #f9fafb;">
                                        <td style="padding:4px 0;">
                                            <div style="display:flex; align-items:center; gap:5px;">
                                                <span style="width:7px; height:7px; border-radius:50%; background:{{ $this->catColor($cat) }}; flex-shrink:0;"></span>
                                                <span style="font-size:10px; color:#6b7280;">{{ $this->catLabel($cat) }}</span>
                                            </div>
                                        </td>
                                        <td style="padding:4px 0; text-align:right; font-weight:700; font-family:monospace; font-size:12px; color:#111827;">{{ $count }}</td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td style="padding-top:8px; font-weight:700; font-size:10px; color:#111827; text-transform:uppercase;">Total</td>
                                    <td style="padding-top:8px; text-align:right; font-weight:700; font-size:18px; font-family:monospace; color:#dc2626;">{{ $this->getTotalCastellers() }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
        @endif
    </div>

    @vite('resources/js/board-generador.js')

</x-filament-panels::page>
