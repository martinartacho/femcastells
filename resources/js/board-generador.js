import Konva from 'konva';

const GRUP_COLORS = {
    baix:         '#37474f',
    nucli:        '#ffcdd2',
    nucli_D:      '#f8bbd0',
    nucli_E:      '#f8bbd0',
    lateral_D:    '#c8e6c9',
    lateral_E:    '#bbdefb',
    rengle:       '#fff9c4',
    falca_D:      '#d1c4e9',
    falca_E:      '#d1c4e9',
    angle_D:      '#ffe0b2',
    angle_E:      '#ffe0b2',
    Plena:        '#f8bbd0',
    Carregada:    '#bbdefb',
    Buida:        '#c8e6c9',
    Rengle:       '#fff9c4',
    Descarregada: '#d1c4e9',
    Una:          '#f8bbd0',
    Dues:         '#bbdefb',
    Tres:         '#c8e6c9',
    Quatre:       '#fff9c4',
    Cinc:         '#d1c4e9',
    Sis:          '#ffe0b2',
    Set:          '#b2dfdb',
};

const STROKE_NORMAL   = '#374151';
const STROKE_SELECTED = '#dc2626';
const STROKE_HOVER    = '#f59e0b';

let stage       = null;
let layer       = null;
let transformer = null;
let selectedId  = null;
let posShapes   = {};   // id → { group, ellipse, posData }

function grupColor(grup) {
    return GRUP_COLORS[grup] ?? '#e5e7eb';
}

function initGenerador(positions) {
    const container = document.getElementById('generador-canvas');
    if (!container) return;

    if (stage) {
        stage.destroy();
        stage = null; layer = null; transformer = null;
        selectedId = null; posShapes = {};
    }

    const containerW = container.offsetWidth  || 700;
    const containerH = container.offsetHeight || 520;

    const xs   = positions.map(p => p.cx);
    const ys   = positions.map(p => p.cy);
    const minX = Math.min(...xs) - 60;
    const maxX = Math.max(...xs) + 60;
    const minY = Math.min(...ys) - 60;
    const maxY = Math.max(...ys) + 60;
    const dataW = maxX - minX;
    const dataH = maxY - minY;
    const scale = Math.min(containerW / dataW, containerH / dataH, 1.8);

    stage = new Konva.Stage({ container: 'generador-canvas', width: containerW, height: containerH });

    // Fit stage so world coords == Konva coords (clean for export)
    stage.scale({ x: scale, y: scale });
    stage.position({
        x: -minX * scale + (containerW - dataW * scale) / 2,
        y: -minY * scale + (containerH - dataH * scale) / 2,
    });

    layer = new Konva.Layer();
    stage.add(layer);

    transformer = new Konva.Transformer({
        rotateEnabled:   true,
        borderStroke:    STROKE_SELECTED,
        anchorStroke:    STROKE_SELECTED,
        anchorFill:      '#fff',
        anchorSize:      8,
        enabledAnchors:  ['middle-left', 'middle-right', 'top-center', 'bottom-center'],
    });
    layer.add(transformer);

    positions.forEach(pos => {
        const group = new Konva.Group({
            x:        pos.cx,
            y:        pos.cy,
            rotation: pos.angle ?? 0,
            draggable: true,
        });

        const ellipse = new Konva.Ellipse({
            radiusX:     pos.w / 2,
            radiusY:     pos.h / 2,
            fill:        pos.fill ?? grupColor(pos.grup),
            stroke:      STROKE_NORMAL,
            strokeWidth: 0.8 / scale,
        });

        group.add(ellipse);
        layer.add(group);
        posShapes[pos.id] = { group, ellipse, posData: { ...pos } };

        group.on('mouseenter', () => {
            if (selectedId !== pos.id) {
                ellipse.stroke(STROKE_HOVER);
                ellipse.strokeWidth(1.5 / scale);
                layer.batchDraw();
            }
            const el = document.getElementById('generador-info');
            if (el) el.textContent = pos.nom ?? pos.grup ?? '';
        });

        group.on('mouseleave', () => {
            if (selectedId !== pos.id) {
                ellipse.stroke(STROKE_NORMAL);
                ellipse.strokeWidth(0.8 / scale);
                layer.batchDraw();
            }
            const el = document.getElementById('generador-info');
            if (el) el.textContent = '';
        });

        group.on('click tap', (e) => {
            e.cancelBubble = true;
            selectPos(pos.id);
        });
    });

    stage.on('click tap', () => deselectAll());

    layer.batchDraw();
}

function selectPos(id) {
    // Reset previous
    if (selectedId && posShapes[selectedId]) {
        const prev = posShapes[selectedId];
        const sc = stage?.scaleX() ?? 1;
        prev.ellipse.stroke(STROKE_NORMAL);
        prev.ellipse.strokeWidth(0.8 / sc);
    }

    selectedId = id;
    const { group, ellipse, posData } = posShapes[id];
    const sc = stage?.scaleX() ?? 1;
    ellipse.stroke(STROKE_SELECTED);
    ellipse.strokeWidth(2 / sc);
    transformer.nodes([group]);
    layer.batchDraw();

    updatePropsPanel(id, ellipse);
}

function deselectAll() {
    if (selectedId && posShapes[selectedId]) {
        const sc = stage?.scaleX() ?? 1;
        posShapes[selectedId].ellipse.stroke(STROKE_NORMAL);
        posShapes[selectedId].ellipse.strokeWidth(0.8 / sc);
    }
    selectedId = null;
    transformer.nodes([]);
    layer.batchDraw();
    hidePropsPanel();
}

function updatePropsPanel(id, ellipse) {
    const panel = document.getElementById('generador-props');
    if (!panel) return;
    panel.style.display = 'flex';

    const { posData } = posShapes[id];
    const nomEl   = document.getElementById('prop-nom');
    const grupEl  = document.getElementById('prop-grup');
    const colorEl = document.getElementById('prop-color');

    if (nomEl)   nomEl.textContent  = posData.nom  ?? '—';
    if (grupEl)  grupEl.textContent = posData.grup ?? '—';
    if (colorEl) {
        colorEl.value    = ellipse.fill();
        colorEl.oninput  = () => {
            ellipse.fill(colorEl.value);
            posShapes[id].posData.fill = colorEl.value;
            // Propagate to all same grup if checkbox checked
            const allGrup = document.getElementById('prop-all-grup');
            if (allGrup?.checked && posData.grup) {
                Object.values(posShapes).forEach(({ ellipse: e, posData: pd }) => {
                    if (pd.grup === posData.grup) {
                        e.fill(colorEl.value);
                        pd.fill = colorEl.value;
                    }
                });
            }
            layer.batchDraw();
        };
    }
}

function hidePropsPanel() {
    const panel = document.getElementById('generador-props');
    if (panel) panel.style.display = 'none';
}

function getPositions() {
    return Object.entries(posShapes).map(([, { group, ellipse, posData }]) => ({
        ...posData,
        cx:    parseFloat(group.x().toFixed(2)),
        cy:    parseFloat(group.y().toFixed(2)),
        w:     parseFloat((ellipse.radiusX() * 2 * group.scaleX()).toFixed(2)),
        h:     parseFloat((ellipse.radiusY() * 2 * group.scaleY()).toFixed(2)),
        angle: parseFloat(group.rotation().toFixed(2)),
    }));
}

function addWheelZoom() {
    if (!stage) return;
    stage.on('wheel', (e) => {
        e.evt.preventDefault();
        const by      = 1.12;
        const old     = stage.scaleX();
        const ptr     = stage.getPointerPosition();
        const next    = e.evt.deltaY < 0 ? old * by : old / by;
        const clamped = Math.min(Math.max(next, 0.1), 6);
        stage.scale({ x: clamped, y: clamped });
        stage.position({
            x: ptr.x - (ptr.x - stage.x()) * (clamped / old),
            y: ptr.y - (ptr.y - stage.y()) * (clamped / old),
        });
        layer.batchDraw();
    });
}

window.boardGenerador = {
    init(positions) {
        initGenerador(positions);
        addWheelZoom();
    },
    getPositions,
    deselectAll,
};
