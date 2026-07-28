import Konva from 'konva';

const CASTLE_POSITIONS  = window.__boardPositions ?? {};
const CASTLE_VIEWS      = window.__castleViews ?? {};
const READ_ONLY         = window.__readOnly ?? false;
const HIGHLIGHT_ALIAS   = (window.__highlightAlias ?? '').trim();
const CASTELLERS_DATA   = window.__castellersData ?? {};

// Posicions de "pom de dalt" (enxaneta + acotxadors): no mostrem pes/alçada
const POM_GRUPS = new Set(['enxaneta', 'acotxador']);
const HEIGHT_ALERT_CM = window.__heightAlertCm ?? 15;

const SCALE = 0.9;
const HOVER_FILL      = '#fde68a';
const STROKE          = '#374151';
const HIGHLIGHT_STROKE = '#f97316';
const BASE_FONT  = 7;
const CHAR_RATIO = 0.68;
const ATTENDANCE_STROKE = { ok: '#16a34a', nok: '#dc2626', pendent: '#d97706' };

// Zone colors — fallback when pos.grup is absent (distance-based approximation)
const ZONE_COLORS = {
    tronc:  '#bfdbfe',
    folre:  '#fde68a',
    pinya:  '#e5e7eb',
};

// Colors per grup — sincronitzats amb POSICIONS de template-editor.js
const GRUP_COLORS = {
    // Categories wizard
    rengle:     '#e0e7ff',
    agulla:     '#c7d2fe',
    contrafort: '#fef3c7',
    cordo:      '#f1f5f9',
    mans:       '#bfdbfe',
    porta:      '#ffedd5',
    vent:       '#bbf7d0',
    segon:      '#fce7f3',
    terc:       '#fbcfe8',
    quart:      '#f9a8d4',
    dos:        '#fef9c3',
    acotxador:  '#fef08a',
    enxaneta:   '#fde047',
    buida:      '#c7d2fe',
    plena:      '#a5b4fc',
    // Categories generador (backward compat)
    tronc:      '#fed7aa',
    baixos:     '#cbd5e1',
    laterals:   '#dbeafe',
    mitjans:    '#fef3c7',
    folre:      '#d1fae5',
    manilles:   '#fce7f3',
    // Motor PHP antic
    baix:       '#e0e7ff',
    nucli:      '#fce7f3',
    nucli_D:    '#fbcfe8',
    nucli_E:    '#fbcfe8',
    lateral_D:  '#bbf7d0',
    lateral_E:  '#bfdbfe',
    falca_D:    '#ede9fe',
    falca_E:    '#ede9fe',
    angle_D:    '#ffedd5',
    angle_E:    '#ffedd5',
    // SVG import (noms capitalitzats)
    Rengle:       '#e0e7ff',
    Plena:        '#a5b4fc',
    Buida:        '#c7d2fe',
    Carregada:    '#bfdbfe',
    Descarregada: '#ede9fe',
    Una:    '#fce7f3',
    Dues:   '#bfdbfe',
    Tres:   '#bbf7d0',
    Quatre: '#fef9c3',
    Cinc:   '#ede9fe',
    Sis:    '#ffedd5',
    Set:    '#ccfbf1',
};

// --- Historial (undo) ---
const assignmentHistory = [];
const MAX_HISTORY = 30;
let suppressDepth = 0;   // >0 = no afegir a l'historial
let lastSavedJson = '{}';

function suppressPush() { suppressDepth++; }
function suppressPop()  { suppressDepth = Math.max(0, suppressDepth - 1); }

function snapshotToHistory() {
    if (suppressDepth > 0) return;
    assignmentHistory.push({ ...assignments });
    if (assignmentHistory.length > MAX_HISTORY) assignmentHistory.shift();
}

function undo() {
    if (assignmentHistory.length === 0) return;
    const prev = assignmentHistory.pop();
    suppressPush();
    clearBoardInternal();
    Object.entries(prev).forEach(([posId, alias]) => {
        if (positionGroups[posId]) assignCasteller(posId, alias);
    });
    suppressPop();
    layer?.batchDraw();
    updateCounter();
    updateUI();
}

function isDirty() {
    return Object.keys(assignments).length > 0 && JSON.stringify(assignments) !== lastSavedJson;
}

function markSaved() {
    lastSavedJson = JSON.stringify(assignments);
    updateUI();
}

function updateUI() {
    // Botó undo
    const undoBtn = document.getElementById('btn-undo');
    if (undoBtn) {
        undoBtn.disabled = assignmentHistory.length === 0;
        undoBtn.style.opacity = assignmentHistory.length === 0 ? '0.35' : '1';
    }
    // Indicador "no desat"
    const dirty = document.getElementById('board-dirty');
    if (dirty) dirty.style.display = isDirty() ? '' : 'none';
}

function fitFontSize(alias, textW, textH) {
    if (!alias) return BASE_FONT;
    const byWidth  = (textW * 0.88) / (alias.length * CHAR_RATIO);
    const byHeight = textH * 0.72;
    return Math.max(4.5, Math.min(byWidth, byHeight));
}

// --- Helpers --
function computeCentroid(positions) {
    const n = positions.length;
    return {
        cx: positions.reduce((s, p) => s + p.cx, 0) / n,
        cy: positions.reduce((s, p) => s + p.cy, 0) / n,
    };
}

function zoneColor(pos, centroid) {
    if (pos.grup !== undefined && GRUP_COLORS[pos.grup] !== undefined) {
        return GRUP_COLORS[pos.grup];
    }
    const d = Math.sqrt((pos.cx - centroid.cx) ** 2 + (pos.cy - centroid.cy) ** 2);
    if (d < 110) return ZONE_COLORS.tronc;
    if (d < 165) return ZONE_COLORS.folre;
    return ZONE_COLORS.pinya;
}

let selectedCasteller = null;
let layer = null;
let stage = null;
let positionGroups = {};
let assignments = {};
let dragOverPosId = null;
let fitScale = SCALE;
let fitCenterX = 0;
let fitCenterY = 0;
let currentCastleType = '';
let currentViewName   = 'base';
let viewAssignments   = {};   // { viewName: { posId: alias } } per a vistes no actives
let dirtyViews        = new Set();  // vistes modificades des del darrer carregat

const VIEW_LABELS = { base: 'Base', tronc: 'Tronc', folre: 'Folre', manilles: 'Manilles' };

function buildPosToViewMap(type) {
    const views = CASTLE_VIEWS[type] ?? {};
    const map = {};
    Object.entries(views).forEach(([viewName, positions]) => {
        (positions ?? []).forEach(p => { map[p.id] = viewName; });
    });
    return map;
}

function updateViewSwitcher(type) {
    const switcher = document.getElementById('board-view-switcher');
    const globalCounter = document.getElementById('board-counter');
    if (!switcher) return;
    const views = CASTLE_VIEWS[type] ?? {};
    const names = Object.keys(views).filter(v => Array.isArray(views[v]) && views[v].length > 0);
    if (names.length <= 1) {
        switcher.style.display = 'none';
        if (globalCounter) globalCounter.style.display = '';
        return;
    }
    switcher.style.display = 'flex';
    if (globalCounter) globalCounter.style.display = 'none';
    switcher.innerHTML = names.map(v => {
        const active = v === currentViewName;
        const assigned = v === currentViewName
            ? Object.keys(assignments).length
            : Object.keys(viewAssignments[v] ?? {}).length;
        const total = (views[v] ?? []).filter(p => p.shape !== 'image' && p.shape !== 'label').length;
        return `<button onclick="window.__loadView('${v}')" style="font-size:11px;padding:3px 10px;border-radius:5px;border:2px solid ${active ? '#2563eb' : '#d1d5db'};background:${active ? '#2563eb' : 'white'};color:${active ? 'white' : '#6b7280'};cursor:pointer;font-weight:${active ? '700' : '400'};display:inline-flex;align-items:center;gap:4px;transition:none;">${VIEW_LABELS[v] ?? v}<span style="font-size:9px;font-family:monospace;opacity:${active ? '0.85' : '0.65'};">${assigned}/${total}</span></button>`;
    }).join('');
}

// Retorna "75kg 158cm" per a un alias, o '' si no hi ha dades
function physText(alias) {
    const d = CASTELLERS_DATA[alias];
    if (!d) return '';
    const parts = [];
    if (d.w)  parts.push(d.w + 'kg');
    if (d.sh) parts.push(d.sh + 'cm');
    else if (d.h) parts.push(d.h + 'cm');
    return parts.join(' ');
}

// Alçada mitjana de la vista actual (excloent pom de dalt)
function getViewHeightAvg() {
    const heights = Object.entries(assignments)
        .filter(([posId]) => {
            const pg = positionGroups[posId];
            return pg && !POM_GRUPS.has(pg.pos.grup);
        })
        .map(([, alias]) => CASTELLERS_DATA[alias]?.sh ?? CASTELLERS_DATA[alias]?.h)
        .filter(Boolean);
    if (heights.length < 2) return null;
    return heights.reduce((s, h) => s + h, 0) / heights.length;
}


// Calcula stats (pes total, rang alçada espatlla) d'un conjunt d'assignacions
function calcStats(asns) {
    const entries = Object.entries(asns)
        .filter(([posId]) => {
            const pg = positionGroups[posId];
            return !pg || !POM_GRUPS.has(pg.pos.grup);
        });
    const physData = entries.map(([, alias]) => CASTELLERS_DATA[alias]).filter(Boolean);
    if (!physData.length) return null;
    const weights  = physData.map(d => d.w).filter(Boolean);
    const heights  = physData.map(d => d.sh ?? d.h).filter(Boolean);
    return {
        count:       entries.length,
        totalWeight: weights.length ? weights.reduce((s, w) => s + w, 0) : null,
        minH:        heights.length ? Math.min(...heights) : null,
        maxH:        heights.length ? Math.max(...heights) : null,
    };
}

// Actualitza el panell de resum estadístic a la columna esquerra
function updateAssignedList() {
    const panel  = document.getElementById('board-assigned-panel');
    const title  = document.getElementById('board-assigned-title');
    const statEl = document.getElementById('board-assigned-stats');
    if (!panel) return;

    const total    = Object.keys(positionGroups).length;
    const assigned = Object.keys(assignments).length;

    if (!assigned) { panel.style.display = 'none'; return; }
    panel.style.display = '';

    if (title) title.textContent = `Assignats ${assigned}/${total}`;

    // Stats de la vista actual (excloent pom de dalt)
    const st = calcStats(assignments);
    if (statEl && st) {
        const parts = [];
        if (st.totalWeight) parts.push('⚖ ' + st.totalWeight + 'kg');
        if (st.minH && st.maxH) {
            const rang = st.maxH - st.minH;
            const warn = rang > 18 ? ' ⚠' : '';
            parts.push('🧍 ' + (st.minH === st.maxH ? st.minH : st.minH + '–' + st.maxH) + 'cm' + warn);
        }
        statEl.textContent = parts.join(' · ');
    } else if (statEl) {
        statEl.textContent = '';
    }

}

window.__loadView = function loadView(viewName) {
    if (viewName === currentViewName || !stage) return;
    // Desa les assignacions de la vista actual
    viewAssignments[currentViewName] = { ...assignments };
    // Extreu les de la nova vista ABANS de canviar currentViewName
    // (si no, assignCasteller les trobaria a viewAssignments i es bloquejarien)
    const toRestore = { ...(viewAssignments[viewName] ?? {}) };
    delete viewAssignments[viewName];
    currentViewName = viewName;
    initBoard(currentCastleType, viewName);
    suppressPush();
    Object.entries(toRestore).forEach(([posId, alias]) => {
        if (positionGroups[posId]) assignCasteller(posId, alias);
    });
    suppressPop();
    assignmentHistory.length = 0;
    markSaved();
};

function updateCounter() {
    const el = document.getElementById('board-counter');
    if (el) el.textContent = Object.keys(assignments).length + '/' + Object.keys(positionGroups).length;
    updateViewSwitcher(currentCastleType);
    updateAssignedList();
}

function initBoard(type = '', viewName = 'base') {
    const emptyState = document.getElementById('board-empty-state');
    if (!type) {
        if (emptyState) emptyState.style.display = 'flex';
        updateViewSwitcher('');
        return;
    }
    if (emptyState) emptyState.style.display = 'none';

    currentCastleType = type;
    currentViewName   = viewName;

    const container = document.getElementById('board-canvas');
    if (!container) return;
    const wrapper = container.parentElement;

    if (stage) {
        stage.destroy();
        stage = null;
        layer = null;
        positionGroups = {};
        assignments = {};
        dragOverPosId = null;
    }

    // Reset historial en canviar de plantilla
    assignmentHistory.length = 0;
    lastSavedJson = '{}';

    // Preferim el format multi-vista si existeix; fallback al pla
    const views = CASTLE_VIEWS[type] ?? {};
    const positions = views[viewName] ?? CASTLE_POSITIONS[type] ?? [];

    updateViewSwitcher(type);
    const centroid  = computeCentroid(positions);
    const xs = positions.map(p => p.cx);
    const ys = positions.map(p => p.cy);
    const minX = Math.min(...xs), maxX = Math.max(...xs);
    const minY = Math.min(...ys), maxY = Math.max(...ys);
    const svgW = maxX - minX + 80;
    const svgH = maxY - minY + 80;

    // Stage ocupa tot el contenidor disponible
    const availW = Math.max(300, wrapper.clientWidth);
    const availH = Math.max(300, wrapper.clientHeight);

    // Escala per encabir el contingut amb marge del 5%
    fitScale   = Math.min(availW / svgW, availH / svgH) * 0.95;
    fitCenterX = (availW - svgW * fitScale) / 2;
    fitCenterY = (availH - svgH * fitScale) / 2;

    stage = new Konva.Stage({
        container: 'board-canvas',
        width:  availW,
        height: availH,
        scaleX: fitScale,
        scaleY: fitScale,
        x: fitCenterX,
        y: fitCenterY,
    });

    layer = new Konva.Layer();
    stage.add(layer);

    // Zoom amb roda del ratolí centrat al punter
    stage.on('wheel', (e) => {
        e.evt.preventDefault();
        const scaleBy = 1.1;
        const old = stage.scaleX();
        const pointer = stage.getPointerPosition();
        const origin = {
            x: (pointer.x - stage.x()) / old,
            y: (pointer.y - stage.y()) / old,
        };
        const newScale = Math.max(0.25, Math.min(5.0, e.evt.deltaY < 0 ? old * scaleBy : old / scaleBy));
        stage.scale({ x: newScale, y: newScale });
        stage.position({ x: pointer.x - origin.x * newScale, y: pointer.y - origin.y * newScale });
        updateZoomDisplay();
    });

    // Pan arrossegant (sense conflicte amb click: Konva diferencia drag de click)
    stage.draggable(true);
    updateZoomDisplay();

    positions.forEach(pos => {
        // Imatge informativa — no assignable, renderitzar a sota de tot
        if (pos.shape === 'image') {
            if (!pos.imageData) return;
            const imgEl = new window.Image();
            imgEl.onload = () => {
                const g = new Konva.Group({
                    x: pos.cx - minX + 40, y: pos.cy - minY + 40,
                    rotation: pos.angle, listening: false,
                });
                g.add(new Konva.Image({
                    image: imgEl, x: -pos.w / 2, y: -pos.h / 2,
                    width: pos.w, height: pos.h, opacity: 0.85,
                }));
                layer.add(g);
                g.moveToBottom();
                layer.batchDraw();
            };
            imgEl.src = pos.imageData;
            return;
        }

        // Etiqueta — text estàtic, no assignable
        if (pos.shape === 'label') {
            const g = new Konva.Group({
                x: pos.cx - minX + 40, y: pos.cy - minY + 40,
                rotation: pos.angle, listening: false,
            });
            g.add(new Konva.Text({
                x: -pos.w / 2, y: -pos.h / 2,
                width: pos.w, height: pos.h,
                text: pos.nom ?? '',
                fontSize: Math.max(8, Math.min(14, pos.h * 0.7)),
                fontFamily: 'sans-serif', fontStyle: 'bold',
                fill: '#374151', align: 'center', verticalAlign: 'middle',
            }));
            layer.add(g);
            return;
        }

        const group = new Konva.Group({
            x: pos.cx - minX + 40,
            y: pos.cy - minY + 40,
            rotation: pos.angle,
        });
        group.setAttr('posId', pos.id);

        const emptyFill = zoneColor(pos, centroid);

        const rect = pos.shape === 'rect'
            ? new Konva.Rect({
                x: -pos.w / 2, y: -pos.h / 2,
                width: pos.w, height: pos.h,
                fill: emptyFill, stroke: STROKE, strokeWidth: 0.8, cornerRadius: 2,
            })
            : new Konva.Ellipse({
                radiusX: pos.w / 2, radiusY: pos.h / 2,
                fill: emptyFill, stroke: STROKE, strokeWidth: 0.8,
            });

        const isTall = pos.h > pos.w;
        const textW = isTall ? pos.h * 0.75 : pos.w * 0.82;
        const textH = isTall ? pos.w * 0.82 : pos.h;
        const baseRot = isTall ? -90 : 0;
        const screenAngle = ((pos.angle + baseRot) % 360 + 360) % 360;
        const flip = screenAngle > 90 && screenAngle < 270;
        const textRot = baseRot + (flip ? 180 : 0);

        const label = new Konva.Text({
            x: 0, y: 0,
            offsetX: textW / 2,
            offsetY: textH / 2,
            width: textW,
            height: textH,
            rotation: textRot,
            text: '',
            fontSize: 7,
            fontFamily: 'sans-serif',
            fontStyle: 'bold',
            fill: '#111827',
            align: 'center',
            verticalAlign: 'middle',
            wrap: 'none',
            ellipsis: false,
            listening: false,
        });

        group.add(rect);
        group.add(label);
        layer.add(group);
        positionGroups[pos.id] = { group, rect, label, pos, emptyFill, textW, textH };

        group.on('mouseenter', () => {
            const info = document.getElementById('board-info');
            if (info) {
                const alias = assignments[pos.id];
                const parts = [];
                if (pos.nom) parts.push(pos.nom);
                if (alias) {
                    parts.push(alias);
                    const pt = !POM_GRUPS.has(pos.grup) ? physText(alias) : '';
                    if (pt) parts.push(pt);
                }
                info.textContent = parts.join(' · ');
            }
        });
        group.on('mouseleave', () => {
            const info = document.getElementById('board-info');
            if (info) info.textContent = '';
        });

        if (!READ_ONLY) {
            group.on('click tap', () => {
                if (!selectedCasteller) return;
                assignCasteller(pos.id, selectedCasteller);
                deselectCasteller();
            });

            group.on('dblclick dbltap', () => {
                unassignPosition(pos.id);
            });
        }
    });

    layer.draw();
    updateCounter();
    updateUI();
    setupDragDrop();

    // Logo de la colla (si activat a Configuració)
    if (window.__showLogo && window.__logoUrl) {
        const logoEl = new window.Image();
        logoEl.onload = () => {
            const maxSize = 80;
            const scale = Math.min(1, maxSize / logoEl.naturalWidth, maxSize / logoEl.naturalHeight);
            const lw = logoEl.naturalWidth  * scale;
            const lh = logoEl.naturalHeight * scale;
            const margin = 12;
            const logoImg = new Konva.Image({
                image:    logoEl,
                x:        stage.width()  - lw  - margin,
                y:        stage.height() - lh  - margin,
                width:    lw,
                height:   lh,
                opacity:  0.75,
                listening: false,
            });
            layer.add(logoImg);
            logoImg.moveToBottom();
            layer.batchDraw();
        };
        logoEl.src = window.__logoUrl;
    }
}

function getPosIdAtPoint(clientX, clientY) {
    if (!stage) return null;
    const canvasEl = stage.container().querySelector('canvas');
    if (!canvasEl) return null;
    const rect = canvasEl.getBoundingClientRect();
    const x = (clientX - rect.left - stage.x()) / stage.scaleX();
    const y = (clientY - rect.top  - stage.y()) / stage.scaleY();
    const shape = layer.getIntersection({ x, y });
    if (!shape) return null;
    const group = shape.getParent();
    return group?.getAttr('posId') ?? null;
}

function updateZoomDisplay() {
    const el = document.getElementById('board-zoom');
    if (el && stage) el.textContent = Math.round(stage.scaleX() / fitScale * 100) + '%';
}

function attendanceStrokeForAlias(alias) {
    const btn = document.querySelector(`[data-alias="${CSS.escape(alias)}"]`);
    const status = btn?.dataset?.attendance ?? 'pendent';
    return ATTENDANCE_STROKE[status] ?? STROKE;
}

function setHoverRect(posId, hover) {
    if (!posId || !positionGroups[posId]) return;
    const { rect, emptyFill } = positionGroups[posId];
    rect.fill(hover ? HOVER_FILL : emptyFill);
    layer.draw();
}

function setupDragDrop() {
    if (READ_ONLY) return;
    const wrapper = document.getElementById('board-canvas').parentElement;

    document.addEventListener('dragstart', (e) => {
        const btn = e.target.closest('[data-alias]');
        if (!btn) return;
        e.dataTransfer.setData('text/plain', btn.dataset.alias);
        e.dataTransfer.effectAllowed = 'move';
    });

    wrapper.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        const posId = getPosIdAtPoint(e.clientX, e.clientY);
        if (posId !== dragOverPosId) {
            setHoverRect(dragOverPosId, false);
            dragOverPosId = posId;
            setHoverRect(dragOverPosId, true);
        }
    });

    wrapper.addEventListener('dragleave', () => {
        setHoverRect(dragOverPosId, false);
        dragOverPosId = null;
    });

    wrapper.addEventListener('drop', (e) => {
        e.preventDefault();
        const alias = e.dataTransfer.getData('text/plain');
        const posId = getPosIdAtPoint(e.clientX, e.clientY);
        setHoverRect(dragOverPosId, false);
        dragOverPosId = null;
        if (alias && posId) {
            deselectCasteller();
            assignCasteller(posId, alias);
        }
    });
}

function setListVisible(alias, visible) {
    document.querySelectorAll(`[data-alias="${alias}"]`).forEach(el => {
        el.style.display = visible ? '' : 'none';
    });
}

function showBoardWarning(msg) {
    const el = document.getElementById('board-warning');
    if (!el) return;
    const span = el.querySelector('span');
    if (span) span.textContent = msg;
    el.style.display = 'flex';
    clearTimeout(el._timer);
    el._timer = setTimeout(() => { el.style.display = 'none'; }, 4000);
}

function assignCasteller(posId, alias) {
    // Comprovació cross-vista: si el casteller és en una altra vista, bloquejar
    const crossEntry = Object.entries(viewAssignments).find(([, asns]) =>
        Object.values(asns).includes(alias)
    );
    if (crossEntry) {
        const viewLabel = VIEW_LABELS[crossEntry[0]] ?? crossEntry[0];
        showBoardWarning(`${alias} ja és al ${viewLabel}. Allibera'l primer des d'aquella vista.`);
        return;
    }
    dirtyViews.add(currentViewName);

    snapshotToHistory();
    // Si el casteller ja estava en una altra posició de la vista activa, el movem
    const prev = Object.entries(assignments).find(([, v]) => v === alias);
    if (prev) {
        suppressPush();
        unassignPosition(prev[0]);
        suppressPop();
    }
    assignments[posId] = alias;
    const { rect, label, textW, textH } = positionGroups[posId];
    if (!READ_ONLY) {
        rect.stroke(attendanceStrokeForAlias(alias));
        rect.strokeWidth(2);
    } else if (HIGHLIGHT_ALIAS && alias === HIGHLIGHT_ALIAS) {
        rect.stroke(HIGHLIGHT_STROKE);
        rect.strokeWidth(3);
    } else {
        rect.stroke(STROKE);
        rect.strokeWidth(0.8);
    }
    label.text(alias);
    label.fontSize(fitFontSize(alias, textW, textH));
    layer.draw();
    setListVisible(alias, false);
    updateCounter();
    updateUI();
}

function unassignPosition(posId) {
    snapshotToHistory();
    dirtyViews.add(currentViewName);
    const alias = assignments[posId];
    delete assignments[posId];
    const { rect, label, emptyFill } = positionGroups[posId];
    if (!READ_ONLY) {
        rect.stroke(STROKE);
        rect.strokeWidth(0.8);
    }
    label.text('');
    label.fontSize(BASE_FONT);
    layer.draw();
    if (alias) setListVisible(alias, true);
    updateCounter();
    updateUI();
}

function deselectCasteller() {
    if (selectedCasteller) {
        document.querySelectorAll(`[data-alias="${selectedCasteller}"]`).forEach(el => {
            el.classList.remove('ring-2', 'ring-yellow-400', 'bg-yellow-50');
        });
    }
    selectedCasteller = null;
}

// Buit intern el canvas sense tocar l'historial
function clearBoardInternal() {
    Object.keys({ ...assignments }).forEach(posId => {
        const alias = assignments[posId];
        delete assignments[posId];
        const pg = positionGroups[posId];
        if (pg) {
            pg.rect.fill(pg.emptyFill);
            pg.label.text('');
            if (alias) setListVisible(alias, true);
        }
    });
    layer?.batchDraw();
    updateCounter();
}

function clearBoard() {
    snapshotToHistory();
    suppressPush();
    clearBoardInternal();
    suppressPop();
    updateUI();
}

// Escoltadors d'events de desar — marca l'estat com a desat
document.addEventListener('board-save',      () => markSaved());
document.addEventListener('board-overwrite', () => markSaved());

// Ctrl+Z / Cmd+Z
document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'z' && !e.shiftKey) {
        e.preventDefault();
        undo();
    }
});

// Avís si hi ha canvis no desats al tancar/recarregar
window.addEventListener('beforeunload', (e) => {
    if (isDirty()) {
        e.preventDefault();
    }
});

window.boardEditor = {
    selectCasteller(alias) {
        deselectCasteller();
        if (Object.values(assignments).includes(alias)) return;
        selectedCasteller = alias;
        document.querySelectorAll(`[data-alias="${alias}"]`).forEach(el => {
            el.classList.add('ring-2', 'ring-yellow-400', 'bg-yellow-50');
        });
    },
    clearBoard,
    undo,
    getAssignments: () => {
        // Fusiona totes les vistes: les altres des de viewAssignments, l'activa des del canvas
        const all = {};
        Object.entries(viewAssignments).forEach(([v, asn]) => {
            if (v !== currentViewName) Object.assign(all, asn);
        });
        Object.assign(all, assignments);  // vista activa (canvas) sempre guanya
        return all;
    },
    getDirtyViews: () => {
        const dirty = new Set(dirtyViews);
        if (Object.keys(assignments).length > 0) dirty.add(currentViewName);
        return [...dirty];
    },
    applyAssignments(json) {
        suppressPush();
        clearBoardInternal();
        const data = typeof json === 'string' ? JSON.parse(json) : json;
        viewAssignments = {};
        dirtyViews      = new Set();

        const posToView = buildPosToViewMap(currentCastleType);
        if (Object.keys(posToView).length > 0) {
            // Template multi-vista: distribueix les assignacions per vista
            Object.entries(data).forEach(([posId, alias]) => {
                const v = posToView[posId] ?? currentViewName;
                viewAssignments[v] ??= {};
                viewAssignments[v][posId] = alias;
            });
            // Aplica al canvas les de la vista activa (treient-les de viewAssignments per evitar autobloqueig)
            const currentToRestore = { ...(viewAssignments[currentViewName] ?? {}) };
            delete viewAssignments[currentViewName];
            Object.entries(currentToRestore).forEach(([posId, alias]) => {
                if (positionGroups[posId]) assignCasteller(posId, alias);
            });
        } else {
            // Format antic (una sola vista)
            Object.entries(data).forEach(([posId, alias]) => {
                if (positionGroups[posId]) assignCasteller(posId, alias);
            });
        }

        suppressPop();
        assignmentHistory.length = 0;
        markSaved();
    },
    loadCastleType(type) {
        currentViewName = 'base';
        viewAssignments = {};
        dirtyViews      = new Set();
        initBoard(type, 'base');
    },
    zoomIn()  { if (!stage) return; const s = Math.min(5.0, stage.scaleX() * 1.25); stage.scale({ x: s, y: s }); updateZoomDisplay(); },
    zoomOut() { if (!stage) return; const s = Math.max(0.25, stage.scaleX() / 1.25); stage.scale({ x: s, y: s }); updateZoomDisplay(); },
    zoomReset() { if (!stage) return; stage.scale({ x: fitScale, y: fitScale }); stage.position({ x: fitCenterX, y: fitCenterY }); updateZoomDisplay(); },
    exportPng(filename = 'pissarra.png') {
        if (!stage) return;
        const url = stage.toDataURL({ mimeType: 'image/png', pixelRatio: 2 });
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.click();
    },
};

document.addEventListener('DOMContentLoaded', () => {
    initBoard(window.__initialCastleType || '');
    if (window.__autoLoadAssignments && Object.keys(window.__autoLoadAssignments).length > 0) {
        window.boardEditor.applyAssignments(window.__autoLoadAssignments);
    }

    // Redimensiona el stage si canvia el contenidor (ex: pantalla completa)
    const wrapper = document.getElementById('board-canvas')?.parentElement;
    if (wrapper && typeof ResizeObserver !== 'undefined') {
        let resizeTimer;
        new ResizeObserver(() => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                if (!stage) return;
                const w = Math.max(300, wrapper.clientWidth);
                const h = Math.max(300, wrapper.clientHeight);
                stage.width(w);
                stage.height(h);
            }, 100);
        }).observe(wrapper);
    }
});

// Ressaltar una posició concreta (Pissarra Personal)
document.addEventListener('board-highlight', (e) => {
    const posId = e.detail?.posId;
    if (!posId || !positionGroups[posId] || !layer) return;
    const { rect } = positionGroups[posId];
    rect.stroke('#fbbf24');
    rect.strokeWidth(3);
    layer.draw();
});
