import Konva from 'konva';
import positions3d6 from './boards/3d6.json';

const CASTLE_POSITIONS = {
    '3d6': positions3d6,
};

const SCALE = 0.9;
const EMPTY_FILL = '#e5e7eb';
const ASSIGNED_FILL = '#86efac';
const SELECTED_FILL = '#fde68a';
const STROKE = '#374151';

let selectedCasteller = null;
let layer = null;
let stage = null;
let positionGroups = {};
let assignments = {};

function initBoard(type = '3d6') {
    const container = document.getElementById('board-canvas');
    if (!container) return;

    if (stage) {
        stage.destroy();
        stage = null;
        layer = null;
        positionGroups = {};
        assignments = {};
    }

    const positions = CASTLE_POSITIONS[type] ?? positions3d6;
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
            offsetX: 0,
            offsetY: 0,
        });

        const rect = new Konva.Rect({
            x: -pos.w / 2,
            y: -pos.h / 2,
            width: pos.w,
            height: pos.h,
            fill: EMPTY_FILL,
            stroke: STROKE,
            strokeWidth: 0.8,
            cornerRadius: 1,
        });

        // Rects "tall" (h > w): text along long axis with -90° rotation
        const isTall = pos.h > pos.w;
        const textW = isTall ? pos.h : pos.w;
        const textH = isTall ? pos.w : pos.h;
        const baseRot = isTall ? -90 : 0;

        // Detect upside-down: if effective screen angle is in (90°, 270°), flip 180°
        const screenAngle = ((pos.angle + baseRot) % 360 + 360) % 360;
        const flip = screenAngle > 90 && screenAngle < 270;
        const textRot = baseRot + (flip ? 180 : 0);

        const label = new Konva.Text({
            x: 0,
            y: 0,
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
        positionGroups[pos.id] = { group, rect, label, pos };

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
}

function unassignPosition(posId) {
    const alias = assignments[posId];
    delete assignments[posId];
    const { rect, label } = positionGroups[posId];
    rect.fill(EMPTY_FILL);
    label.text('');
    layer.draw();
    if (alias) setListVisible(alias, true);
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
        if (assignments && Object.values(assignments).includes(alias)) return;
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
};

document.addEventListener('DOMContentLoaded', () => initBoard());
