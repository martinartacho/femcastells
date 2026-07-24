import Konva from 'konva';

const CASTLE_POSITIONS = window.__boardPositions ?? {};
const READ_ONLY        = window.__readOnly ?? false;
const HIGHLIGHT_ALIAS  = (window.__highlightAlias ?? '').trim();

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

// Role-based colors: generador categories + motor PHP + FemPinya imports
const GRUP_COLORS = {
    agulla:     '#1a237e',
    tronc:      '#e65100',
    contrafort: '#f9a825',
    laterals:   '#2e7d32',
    mitjans:    '#1565c0',
    folre:      '#6a1b9a',
    manilles:   '#c62828',
    baixos:     '#90a4ae',
    baix:      '#37474f',
    nucli:     '#ffcdd2',
    nucli_D:   '#f8bbd0',
    nucli_E:   '#f8bbd0',
    lateral_D: '#c8e6c9',
    lateral_E: '#bbdefb',
    rengle:    '#fff9c4',
    falca_D:   '#d1c4e9',
    falca_E:   '#d1c4e9',
    angle_D:   '#ffe0b2',
    angle_E:   '#ffe0b2',
    Plena:        '#f8bbd0',
    Carregada:    '#bbdefb',
    Buida:        '#c8e6c9',
    Rengle:       '#fff9c4',
    Descarregada: '#d1c4e9',
    Una:    '#f8bbd0',
    Dues:   '#bbdefb',
    Tres:   '#c8e6c9',
    Quatre: '#fff9c4',
    Cinc:   '#d1c4e9',
    Sis:    '#ffe0b2',
    Set:    '#b2dfdb',
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
    return Math.max(4.5, Math.min(9, byWidth, byHeight));
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

function updateCounter() {
    const el = document.getElementById('board-counter');
    if (el) el.textContent = Object.keys(assignments).length + '/' + Object.keys(positionGroups).length;
}

function initBoard(type = '3d6') {
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

    const positions = CASTLE_POSITIONS[type] ?? Object.values(CASTLE_POSITIONS)[0] ?? [];
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
        const group = new Konva.Group({
            x: pos.cx - minX + 40,
            y: pos.cy - minY + 40,
            rotation: pos.angle,
        });
        group.setAttr('posId', pos.id);

        const emptyFill = zoneColor(pos, centroid);

        const rect = new Konva.Ellipse({
            radiusX: pos.w / 2,
            radiusY: pos.h / 2,
            fill: emptyFill,
            stroke: STROKE,
            strokeWidth: 0.8,
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
            if (info && pos.nom) info.textContent = pos.nom;
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

function assignCasteller(posId, alias) {
    snapshotToHistory();
    // Si el casteller ja estava en una altra posició, el movem sense re-snapshot
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
    getAssignments: () => ({ ...assignments }),
    applyAssignments(json) {
        suppressPush();
        clearBoardInternal();
        const data = typeof json === 'string' ? JSON.parse(json) : json;
        Object.entries(data).forEach(([posId, alias]) => {
            if (positionGroups[posId]) assignCasteller(posId, alias);
        });
        suppressPop();
        assignmentHistory.length = 0;  // Nova base — no té sentit fer undo aquí
        markSaved();
    },
    loadCastleType(type) {
        initBoard(type);
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
    initBoard();
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
