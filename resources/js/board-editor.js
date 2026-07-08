import Konva from 'konva';

const CASTLE_POSITIONS = window.__boardPositions ?? {};

const SCALE = 0.9;
const ASSIGNED_FILL = '#86efac';
const HOVER_FILL    = '#fde68a';
const STROKE = '#374151';

// Zone colors — fallback when pos.grup is absent (distance-based approximation)
const ZONE_COLORS = {
    tronc:  '#bfdbfe',
    folre:  '#fde68a',
    pinya:  '#e5e7eb',
};

// Role-based colors: generador categories + motor PHP + FemPinya imports
const GRUP_COLORS = {
    // Generador abstracte (categories)
    agulla:     '#1a237e',
    tronc:      '#e65100',
    contrafort: '#f9a825',
    laterals:   '#2e7d32',
    mitjans:    '#1565c0',
    folre:      '#6a1b9a',
    manilles:   '#c62828',
    baixos:     '#90a4ae',
    // Rols estructurals (PinyaMotor)
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
    // Baixos del Cinc de 6 (dades reals FemPinya)
    Plena:        '#f8bbd0',
    Carregada:    '#bbdefb',
    Buida:        '#c8e6c9',
    Rengle:       '#fff9c4',
    Descarregada: '#d1c4e9',
    // Baixos del Set de X (dades reals FemPinya)
    Una:    '#f8bbd0',
    Dues:   '#bbdefb',
    Tres:   '#c8e6c9',
    Quatre: '#fff9c4',
    Cinc:   '#d1c4e9',
    Sis:    '#ffe0b2',
    Set:    '#b2dfdb',
};

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

function updateCounter() {
    const el = document.getElementById('board-counter');
    if (el) el.textContent = Object.keys(assignments).length + '/' + Object.keys(positionGroups).length;
}

function initBoard(type = '3d6') {
    const container = document.getElementById('board-canvas');
    if (!container) return;

    if (stage) {
        stage.destroy();
        stage = null;
        layer = null;
        positionGroups = {};
        assignments = {};
        dragOverPosId = null;
    }

    const positions = CASTLE_POSITIONS[type] ?? Object.values(CASTLE_POSITIONS)[0] ?? [];
    const centroid  = computeCentroid(positions);
    const xs = positions.map(p => p.cx);
    const ys = positions.map(p => p.cy);
    const minX = Math.min(...xs), maxX = Math.max(...xs);
    const minY = Math.min(...ys), maxY = Math.max(...ys);
    const svgW = maxX - minX + 80;
    const svgH = maxY - minY + 80;

    stage = new Konva.Stage({
        container: 'board-canvas',
        width: Math.round(svgW * SCALE),
        height: Math.round(svgH * SCALE),
        scaleX: SCALE,
        scaleY: SCALE,
    });

    layer = new Konva.Layer();
    stage.add(layer);

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
            ellipsis: true,
            listening: false,
        });

        group.add(rect);
        group.add(label);
        layer.add(group);
        positionGroups[pos.id] = { group, rect, label, pos, emptyFill };

        group.on('mouseenter', () => {
            const info = document.getElementById('board-info');
            if (info && pos.nom) info.textContent = pos.nom;
        });
        group.on('mouseleave', () => {
            const info = document.getElementById('board-info');
            if (info) info.textContent = '';
        });

        group.on('click tap', () => {
            if (!selectedCasteller) return;
            assignCasteller(pos.id, selectedCasteller);
            deselectCasteller();
        });

        group.on('dblclick dbltap', () => {
            unassignPosition(pos.id);
        });
    });

    layer.draw();
    updateCounter();
    setupDragDrop();
}

function getPosIdAtPoint(clientX, clientY) {
    const canvasEl = stage.container().querySelector('canvas');
    if (!canvasEl) return null;
    const rect = canvasEl.getBoundingClientRect();
    const x = (clientX - rect.left) / SCALE;
    const y = (clientY - rect.top) / SCALE;
    const shape = layer.getIntersection({ x, y });
    if (!shape) return null;
    const group = shape.getParent();
    return group?.getAttr('posId') ?? null;
}

function setHoverRect(posId, hover) {
    if (!posId || !positionGroups[posId]) return;
    const { rect } = positionGroups[posId];
    if (hover) {
        rect.fill(HOVER_FILL);
    } else {
        rect.fill(assignments[posId] ? ASSIGNED_FILL : (positionGroups[posId]?.emptyFill ?? '#e5e7eb'));
    }
    layer.draw();
}

function setupDragDrop() {
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
    const prev = Object.entries(assignments).find(([, v]) => v === alias);
    if (prev) unassignPosition(prev[0]);

    assignments[posId] = alias;
    const { rect, label } = positionGroups[posId];
    rect.fill(ASSIGNED_FILL);
    label.text(alias);
    layer.draw();
    setListVisible(alias, false);
    updateCounter();
}

function unassignPosition(posId) {
    const alias = assignments[posId];
    delete assignments[posId];
    const { rect, label, emptyFill } = positionGroups[posId];
    rect.fill(emptyFill);
    label.text('');
    layer.draw();
    if (alias) setListVisible(alias, true);
    updateCounter();
}

function deselectCasteller() {
    if (selectedCasteller) {
        document.querySelectorAll(`[data-alias="${selectedCasteller}"]`).forEach(el => {
            el.classList.remove('ring-2', 'ring-yellow-400', 'bg-yellow-50');
        });
    }
    selectedCasteller = null;
}

function clearBoard() {
    Object.keys(assignments).forEach(posId => unassignPosition(posId));
}

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
    getAssignments: () => ({ ...assignments }),
    applyAssignments(json) {
        clearBoard();
        const data = typeof json === 'string' ? JSON.parse(json) : json;
        Object.entries(data).forEach(([posId, alias]) => {
            if (positionGroups[posId]) assignCasteller(posId, alias);
        });
    },
    loadCastleType(type) {
        initBoard(type);
    },
    exportPng(filename = 'pissarra.png') {
        if (!stage) return;
        const url = stage.toDataURL({ mimeType: 'image/png', pixelRatio: 2 });
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.click();
    },
};

document.addEventListener('DOMContentLoaded', () => initBoard());
