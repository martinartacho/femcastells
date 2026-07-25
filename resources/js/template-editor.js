import Konva from 'konva';

// ── Categories del domini casteller ──────────────────────────────────────────
export const CATEGORIES = [
    { id: 'agulla',     label: 'Agulla',     color: '#c7d2fe', text: '#1e3a8a', shape: 'rect',    w: 30, h: 14 },
    { id: 'rengle',     label: 'Rengle',     color: '#fef9c3', text: '#713f12', shape: 'rect',    w: 34, h: 15 },
    { id: 'buida',      label: 'Buida',      color: '#dcfce7', text: '#14532d', shape: 'rect',    w: 34, h: 15 },
    { id: 'plena',      label: 'Plena',      color: '#fce7f3', text: '#831843', shape: 'rect',    w: 34, h: 15 },
    { id: 'mans',       label: 'Mans',       color: '#bfdbfe', text: '#1e40af', shape: 'rect',    w: 34, h: 15 },
    { id: 'cordo',      label: 'Cordo',      color: '#f1f5f9', text: '#475569', shape: 'rect',    w: 34, h: 15 },
    { id: 'vent',       label: 'Vent',       color: '#bbf7d0', text: '#064e3b', shape: 'ellipse', w: 30, h: 19 },
    { id: 'porta',      label: 'Porta',      color: '#ffedd5', text: '#7c2d12', shape: 'rect',    w: 34, h: 15 },
    { id: 'contrafort', label: 'Contrafort', color: '#fef3c7', text: '#78350f', shape: 'rect',    w: 34, h: 15 },
];
const CAT = Object.fromEntries(CATEGORIES.map(c => [c.id, c]));

// ── Estat ────────────────────────────────────────────────────────────────────
let stage = null;
let guideLayer = null;
let layer = null;
let transformer = null;
let posCounter = 0;
let rulerXNodes = [];
let rulerYNodes = [];
let history = [];
let historyIdx = -1;

// ── Init (idempotent) ────────────────────────────────────────────────────────
function initEditor(initialPositions = []) {
    const container = document.getElementById('template-canvas');
    if (!container) return;
    const wrapper = container.parentElement;

    // Destruïm l'escena anterior si n'hi havia
    if (stage) { stage.destroy(); stage = null; guideLayer = null; layer = null; transformer = null; rulerXNodes = []; rulerYNodes = []; history = []; historyIdx = -1; }

    const W = Math.max(500, wrapper.clientWidth);
    const H = Math.max(400, wrapper.clientHeight);

    stage = new Konva.Stage({ container: 'template-canvas', width: W, height: H });

    guideLayer = new Konva.Layer({ listening: false });
    stage.add(guideLayer);

    layer = new Konva.Layer();
    stage.add(layer);

    initGuides();

    transformer = new Konva.Transformer({
        rotateEnabled: true,
        resizeEnabled: true,
        keepRatio: false,
        boundBoxFunc: (old, box) => (box.width < 8 || box.height < 5 ? old : box),
    });
    layer.add(transformer);

    // Encabim el contingut inicial
    if (initialPositions.length > 0) {
        const xs = initialPositions.map(p => p.cx);
        const ys = initialPositions.map(p => p.cy);
        const minX = Math.min(...xs), maxX = Math.max(...xs);
        const minY = Math.min(...ys), maxY = Math.max(...ys);
        const scale = Math.min(W / (maxX - minX + 80), H / (maxY - minY + 80)) * 0.88;
        const ox = (W - (maxX - minX) * scale) / 2 - minX * scale;
        const oy = (H - (maxY - minY) * scale) / 2 - minY * scale;
        stage.scale({ x: scale, y: scale });
        stage.position({ x: ox, y: oy });
    } else {
        // Canvas buit: centrem el punt 0,0
        stage.position({ x: W / 2, y: H / 2 });
    }

    initialPositions.forEach(pos => {
        posCounter = Math.max(posCounter, parseInt(String(pos.id ?? '0').replace(/\D/g, '') || '0'));
        addNode(pos);
    });

    // Clic al fons → deseleccionar
    stage.on('click tap', e => {
        if (e.target === stage) { transformer.nodes([]); showProps(null); layer.draw(); }
    });

    // Zoom amb roda
    stage.on('wheel', e => {
        e.evt.preventDefault();
        const old = stage.scaleX();
        const ptr = stage.getPointerPosition();
        const o = { x: (ptr.x - stage.x()) / old, y: (ptr.y - stage.y()) / old };
        const s = Math.max(0.05, Math.min(20, e.evt.deltaY < 0 ? old * 1.12 : old / 1.12));
        stage.scale({ x: s, y: s });
        stage.position({ x: ptr.x - o.x * s, y: ptr.y - o.y * s });
        refreshGuideLabels();
    });

    stage.on('dragmove', refreshGuideLabels);

    stage.draggable(true);

    // Tecla Supr per eliminar selecció
    document.addEventListener('keydown', kbHandler);

    // ResizeObserver
    if (typeof ResizeObserver !== 'undefined') {
        let t;
        new ResizeObserver(() => { clearTimeout(t); t = setTimeout(() => {
            if (stage) { stage.width(wrapper.clientWidth); stage.height(wrapper.clientHeight); }
        }, 80); }).observe(wrapper);
    }

    initGuideLabels();
    saveHistory(); // estat inicial per poder desfés
    layer.draw();
    refreshCounts();
}

// ── Historial (Ctrl+Z / Ctrl+Y) ──────────────────────────────────────────────
function saveHistory() {
    history.splice(historyIdx + 1);
    history.push(JSON.stringify(exportPositions()));
    if (history.length > 60) history.shift(); else historyIdx++;
}

function restoreSnapshot(positions) {
    layer.getChildren(n => n.getAttr?.('posId')).forEach(n => n.destroy());
    transformer.nodes([]);
    posCounter = 0;
    positions.forEach(pos => {
        posCounter = Math.max(posCounter, parseInt(String(pos.id ?? '0').replace(/\D/g, '') || '0'));
        addNode(pos);
    });
    showProps(null);
    refreshCounts();
    layer.draw();
}

function undoHistory() {
    if (historyIdx <= 0) return;
    historyIdx--;
    restoreSnapshot(JSON.parse(history[historyIdx]));
}

function redoHistory() {
    if (historyIdx >= history.length - 1) return;
    historyIdx++;
    restoreSnapshot(JSON.parse(history[historyIdx]));
}

// ── Distribució amb separació ─────────────────────────────────────────────────
function distributeH(gap = 5) {
    const nodes = transformer.nodes().slice().sort((a, b) => a.x() - b.x());
    if (nodes.length < 2) return;
    saveHistory();
    let x = nodes[0].x();
    nodes.forEach(n => { n.x(x); x += (n.getAttr('posW') ?? 34) + gap; });
    layer.draw();
}

function distributeV(gap = 5) {
    const nodes = transformer.nodes().slice().sort((a, b) => a.y() - b.y());
    if (nodes.length < 2) return;
    saveHistory();
    let y = nodes[0].y();
    nodes.forEach(n => { n.y(y); y += (n.getAttr('posH') ?? 15) + gap; });
    layer.draw();
}

// ── Menú contextual ───────────────────────────────────────────────────────────
let _ctxMenu = null;

function getCtxMenu() {
    if (_ctxMenu) return _ctxMenu;
    _ctxMenu = document.createElement('div');
    _ctxMenu.id = 'te-ctx';
    _ctxMenu.style.cssText = [
        'display:none', 'position:fixed', 'z-index:99999',
        'background:#fff', 'border:1px solid #e5e7eb', 'border-radius:10px',
        'box-shadow:0 6px 20px rgba(0,0,0,.14)', 'padding:6px 0',
        'min-width:230px', 'font-family:sans-serif', 'user-select:none',
    ].join(';');
    document.body.appendChild(_ctxMenu);
    document.addEventListener('mousedown', e => {
        if (!_ctxMenu.contains(e.target)) hideCtxMenu();
    });
    return _ctxMenu;
}

function hideCtxMenu() { if (_ctxMenu) _ctxMenu.style.display = 'none'; }

function showContextMenu(clientX, clientY) {
    const menu = getCtxMenu();
    const sel  = transformer.nodes();
    const n    = sel.length;

    const row = (html) => `<div style="padding:3px 12px;">${html}</div>`;
    const sep = () => `<div style="height:1px;background:#f3f4f6;margin:4px 0;"></div>`;
    const btn = (label, fn, danger = false) => {
        const id = `ctx-btn-${Math.random().toString(36).slice(2)}`;
        setTimeout(() => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('click', () => { fn(); hideCtxMenu(); });
        }, 0);
        const col = danger ? 'color:#b91c1c;background:#fef2f2;border-color:#fecaca;'
                           : 'color:#374151;background:#fff;border-color:#e5e7eb;';
        return `<div style="padding:2px 8px;">
            <button id="${id}" style="width:100%;padding:4px 8px;border:1px solid;border-radius:6px;
                cursor:pointer;font-size:11px;text-align:left;${col}">${label}</button></div>`;
    };

    const hoursHtml = Array.from({length:12},(_,i)=>{
        const h = i + 1;
        const id = `ctx-h${h}`;
        setTimeout(() => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('click', () => { alignToHour(h); hideCtxMenu(); });
        }, 0);
        return `<button id="${id}" title="${h}h"
            style="padding:2px 0;font-size:9px;font-weight:600;border:1px solid #e5e7eb;
                   border-radius:3px;background:#fff;cursor:pointer;color:#6b7280;">${h}</button>`;
    }).join('');

    const degId  = `ctx-deg-${Date.now()}`;
    const applyId = `ctx-apply-${Date.now()}`;
    setTimeout(() => {
        const el = document.getElementById(applyId);
        if (el) el.addEventListener('click', () => {
            const v = +(document.getElementById(degId)?.value ?? 0);
            alignToAngle(v); hideCtxMenu();
        });
        const di = document.getElementById(degId);
        if (di) di.addEventListener('keydown', e => {
            if (e.key === 'Enter') { alignToAngle(+di.value); hideCtxMenu(); }
        });
    }, 0);

    let spacing = '';
    if (n >= 2) {
        const sepId = `ctx-sep-${Date.now()}`;
        const hId   = `ctx-dh-${Date.now()}`;
        const vId   = `ctx-dv-${Date.now()}`;
        setTimeout(() => {
            const sh = document.getElementById(hId);
            const sv = document.getElementById(vId);
            if (sh) sh.addEventListener('click', () => { distributeH(+(document.getElementById(sepId)?.value??5)); hideCtxMenu(); });
            if (sv) sv.addEventListener('click', () => { distributeV(+(document.getElementById(sepId)?.value??5)); hideCtxMenu(); });
        }, 0);
        spacing = sep() + row(`
            <div style="font-size:9px;font-weight:700;text-transform:uppercase;color:#9ca3af;margin-bottom:4px;">
                Separació entre figures
            </div>
            <div style="display:flex;gap:4px;align-items:center;margin-bottom:4px;">
                <input id="${sepId}" type="number" value="5" min="0" max="500"
                       style="width:52px;border:1px solid #d1d5db;border-radius:5px;padding:3px 5px;font-size:11px;">
                <span style="font-size:10px;color:#9ca3af;">px</span>
            </div>
            <div style="display:flex;gap:4px;">
                <button id="${hId}" style="flex:1;padding:3px 4px;border:1px solid #d1d5db;border-radius:5px;
                    background:#fff;font-size:10px;cursor:pointer;color:#374151;">↔ Horitzontal</button>
                <button id="${vId}" style="flex:1;padding:3px 4px;border:1px solid #d1d5db;border-radius:5px;
                    background:#fff;font-size:10px;cursor:pointer;color:#374151;">↕ Vertical</button>
            </div>
        `);
    }

    menu.innerHTML = `
        ${row(`<div style="font-size:9px;font-weight:700;text-transform:uppercase;color:#9ca3af;">
            ${n} posició${n!==1?'ns':''} seleccionad${n!==1?'es':'a'}
        </div>`)}
        ${sep()}
        ${row(`
            <div style="font-size:9px;font-weight:700;text-transform:uppercase;color:#9ca3af;margin-bottom:4px;">
                Alinea — hora del rellotge
            </div>
            <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:2px;margin-bottom:6px;">
                ${hoursHtml}
            </div>
            <div style="display:flex;gap:4px;align-items:center;">
                <input id="${degId}" type="number" value="0" min="-360" max="360"
                       style="width:52px;border:1px solid #d1d5db;border-radius:5px;padding:3px 5px;font-size:11px;">
                <span style="font-size:10px;color:#9ca3af;">°</span>
                <button id="${applyId}" style="flex:1;padding:3px 6px;border:1px solid #d1d5db;border-radius:5px;
                    background:#fff;font-size:11px;cursor:pointer;color:#374151;">Aplica</button>
            </div>
        `)}
        ${btn('🔄 Radial (des del centre)', radialAlign)}
        ${spacing}
        ${sep()}
        ${btn('🗑 Elimina selecció', deleteSelected, true)}
    `;

    const W = window.innerWidth, H = window.innerHeight;
    menu.style.display = 'block';
    const mw = menu.offsetWidth  || 240;
    const mh = menu.offsetHeight || 300;
    menu.style.left = Math.min(clientX, W - mw - 8) + 'px';
    menu.style.top  = Math.min(clientY, H - mh - 8) + 'px';
}

// ── Capa de guies (grid, centre, radials) ────────────────────────────────────
const GUIDE_EXTENT = 2000;
const GUIDE_MAJOR  = 100; // etiqueta cada 100px
const GUIDE_MINOR  = 50;  // línia cada 50px

function initGuides() {
    if (!guideLayer) return;
    guideLayer.destroyChildren();

    // Grid lleuger
    const GRID_COLOR = '#e5e7eb';
    for (let x = -GUIDE_EXTENT; x <= GUIDE_EXTENT; x += GUIDE_MINOR) {
        guideLayer.add(new Konva.Line({
            points: [x, -GUIDE_EXTENT, x, GUIDE_EXTENT],
            stroke: GRID_COLOR, strokeWidth: 0.5, listening: false,
            name: 'grid-static',
        }));
    }
    for (let y = -GUIDE_EXTENT; y <= GUIDE_EXTENT; y += GUIDE_MINOR) {
        guideLayer.add(new Konva.Line({
            points: [-GUIDE_EXTENT, y, GUIDE_EXTENT, y],
            stroke: GRID_COLOR, strokeWidth: 0.5, listening: false,
            name: 'grid-static',
        }));
    }

    // Eixos del centre
    const AXIS_COLOR = '#9ca3af';
    guideLayer.add(new Konva.Line({
        points: [-GUIDE_EXTENT, 0, GUIDE_EXTENT, 0],
        stroke: AXIS_COLOR, strokeWidth: 1, listening: false, name: 'grid-static',
    }));
    guideLayer.add(new Konva.Line({
        points: [0, -GUIDE_EXTENT, 0, GUIDE_EXTENT],
        stroke: AXIS_COLOR, strokeWidth: 1, listening: false, name: 'grid-static',
    }));
    guideLayer.add(new Konva.Circle({
        x: 0, y: 0, radius: 4, fill: AXIS_COLOR, listening: false, name: 'grid-static',
    }));
    guideLayer.add(new Konva.Text({
        x: 6, y: -15, text: '0,0', fontSize: 10,
        fill: AXIS_COLOR, listening: false, name: 'grid-static',
    }));

    // Radials (12 hores del rellotge analògic)
    const RADIAL_COLOR = '#6ee7b7';
    const RADIAL_R = 700;
    for (let i = 0; i < 12; i++) {
        const ang = (i / 12) * Math.PI * 2 - Math.PI / 2;
        guideLayer.add(new Konva.Line({
            points: [0, 0, Math.cos(ang) * RADIAL_R, Math.sin(ang) * RADIAL_R],
            stroke: RADIAL_COLOR, strokeWidth: 0.8, dash: [8, 5],
            listening: false, name: 'grid-static',
        }));
    }

    guideLayer.batchDraw();
}

// Etiquetes de les regles — es reposicionen en zoom/pan per quedar als marges
function initGuideLabels() {
    if (!guideLayer) return;

    rulerXNodes = [];
    rulerYNodes = [];

    for (let x = -GUIDE_EXTENT; x <= GUIDE_EXTENT; x += GUIDE_MAJOR) {
        if (x === 0) continue;
        const t = new Konva.Text({
            text: String(x), fontSize: 10, fill: '#9ca3af',
            listening: false, name: 'ruler-label',
        });
        guideLayer.add(t);
        rulerXNodes.push({ val: x, node: t });
    }

    for (let y = -GUIDE_EXTENT; y <= GUIDE_EXTENT; y += GUIDE_MAJOR) {
        if (y === 0) continue;
        const t = new Konva.Text({
            text: String(y), fontSize: 10, fill: '#9ca3af',
            listening: false, name: 'ruler-label',
        });
        guideLayer.add(t);
        rulerYNodes.push({ val: y, node: t });
    }

    refreshGuideLabels();
}

function refreshGuideLabels() {
    if (!stage || !guideLayer) return;
    const sc  = stage.scaleX();
    const pos = stage.position();
    const sz  = Math.max(7, Math.min(14, 10 / sc)); // font size en world coords

    // Vora superior i esquerra en world coords
    const topWorld  = (-pos.y / sc) + 4 / sc;
    const leftWorld = (-pos.x / sc) + 4 / sc;

    rulerXNodes.forEach(({ val, node }) => {
        node.x(val + 2);
        node.y(topWorld);
        node.fontSize(sz);
    });
    rulerYNodes.forEach(({ val, node }) => {
        node.x(leftWorld);
        node.y(val - sz - 2);
        node.fontSize(sz);
    });

    guideLayer.batchDraw();
}

function kbHandler(e) {
    const active = document.activeElement?.tagName ?? '';
    if (['INPUT','TEXTAREA','SELECT'].includes(active)) return;
    if (e.ctrlKey && (e.key === 'z' || e.key === 'Z')) { e.preventDefault(); undoHistory(); return; }
    if (e.ctrlKey && (e.key === 'y' || e.key === 'Y')) { e.preventDefault(); redoHistory(); return; }
    if (e.key === 'Delete' || e.key === 'Backspace') deleteSelected();
}

// ── Crear node Konva per a una posició ───────────────────────────────────────
function addNode(pos) {
    const cat   = CAT[pos.grup] ?? CAT['cordo'];
    const w     = pos.w ?? cat.w;
    const h     = pos.h ?? cat.h;
    const shape = pos.shape ?? cat.shape;

    const group = new Konva.Group({
        x: pos.cx ?? 0,
        y: pos.cy ?? 0,
        rotation: pos.angle ?? 0,
        draggable: true,
    });
    group.setAttr('posId',    pos.id    ?? `pos_${++posCounter}`);
    group.setAttr('posGrup',  pos.grup  ?? 'cordo');
    group.setAttr('posNom',   pos.nom   ?? cat.label);
    group.setAttr('posShape', shape);
    group.setAttr('posW', w);
    group.setAttr('posH', h);

    const fill = cat.color;
    const tc   = cat.text;
    const stroke = '#374151';

    let body;
    if (shape === 'ellipse') {
        body = new Konva.Ellipse({ radiusX: w / 2, radiusY: h / 2, fill, stroke, strokeWidth: 0.8 });
    } else {
        body = new Konva.Rect({ x: -w / 2, y: -h / 2, width: w, height: h,
                                fill, stroke, strokeWidth: 0.8, cornerRadius: 2 });
    }

    const label = new Konva.Text({
        x: -w / 2, y: -h / 2, width: w, height: h,
        text: pos.nom ?? cat.label,
        fontSize: Math.max(6, Math.min(12, h * 0.55)),
        fontFamily: 'sans-serif', fill: tc,
        align: 'center', verticalAlign: 'middle',
        listening: false, wrap: 'none', ellipsis: true,
    });

    group.add(body, label);
    layer.add(group);

    // Selecció
    group.on('click tap', e => {
        e.cancelBubble = true;
        const shift = e.evt.shiftKey;
        const nodes = transformer.nodes().slice();
        if (!shift) {
            transformer.nodes([group]);
        } else {
            const i = nodes.indexOf(group);
            i >= 0 ? nodes.splice(i, 1) : nodes.push(group);
            transformer.nodes(nodes);
        }
        showProps(transformer.nodes().length === 1 ? group : null);
        layer.draw();
    });

    group.on('dragstart', () => { saveHistory(); group.moveToTop(); });
    group.on('dragend',   () => showProps(group));
    group.on('transform transformend', () => showProps(group));
    group.on('contextmenu', e => {
        e.evt.preventDefault();
        if (!transformer.nodes().includes(group)) {
            transformer.nodes([group]); showProps(group); layer.draw();
        }
        showContextMenu(e.evt.clientX, e.evt.clientY);
    });

    return group;
}

// ── Panell de propietats ─────────────────────────────────────────────────────
function showProps(group) {
    const panel = document.getElementById('te-props');
    if (!panel) return;
    if (!group) { panel.style.display = 'none'; return; }

    panel.style.display = 'flex';
    const nomEl   = document.getElementById('te-nom');
    const grupEl  = document.getElementById('te-grup');
    const angleEl = document.getElementById('te-angle');
    if (nomEl)   nomEl.value    = group.getAttr('posNom') ?? '';
    if (grupEl)  grupEl.value   = group.getAttr('posGrup') ?? '';
    if (angleEl) angleEl.value = Math.round(group.rotation() * 10) / 10;
    const xEl = document.getElementById('te-x');
    const yEl = document.getElementById('te-y');
    if (xEl) xEl.value = Math.round(group.x() * 10) / 10;
    if (yEl) yEl.value = Math.round(group.y() * 10) / 10;
    const wEl = document.getElementById('te-w');
    const hEl = document.getElementById('te-h');
    if (wEl) wEl.value = Math.round(group.getAttr('posW') * (group.scaleX?.() ?? 1) * 10) / 10;
    if (hEl) hEl.value = Math.round(group.getAttr('posH') * (group.scaleY?.() ?? 1) * 10) / 10;
}

function updateX(x) {
    const nodes = transformer.nodes();
    if (nodes.length !== 1) return;
    nodes[0].x(x); layer.draw();
}

function updateY(y) {
    const nodes = transformer.nodes();
    if (nodes.length !== 1) return;
    nodes[0].y(y); layer.draw();
}

function updateAngle(deg) {
    const nodes = transformer.nodes();
    if (nodes.length !== 1) return;
    nodes[0].rotation(+deg); layer.draw();
}

function updateW(w) {
    const nodes = transformer.nodes();
    if (nodes.length !== 1) return;
    const n = nodes[0];
    const shape = n.findOne('Rect') ?? n.findOne('Ellipse');
    if (!shape) return;
    const curH = n.getAttr('posH') ?? 15;
    shape.x(-w / 2); shape.width(w);
    const text = n.findOne('Text');
    if (text) { text.x(-w / 2); text.width(w); text.fontSize(Math.max(6, Math.min(12, curH * 0.55))); }
    n.setAttr('posW', w);
    layer.draw();
}

function updateH(h) {
    const nodes = transformer.nodes();
    if (nodes.length !== 1) return;
    const n = nodes[0];
    const shape = n.findOne('Rect') ?? n.findOne('Ellipse');
    if (!shape) return;
    const curW = n.getAttr('posW') ?? 34;
    shape.y(-h / 2); shape.height(h);
    const text = n.findOne('Text');
    if (text) { text.y(-h / 2); text.height(h); text.fontSize(Math.max(6, Math.min(12, h * 0.55))); }
    n.setAttr('posH', h);
    layer.draw();
}

// ── Actualitzar comptadors de categories ─────────────────────────────────────
function refreshCounts() {
    if (!layer) return;
    const counts = {};
    layer.getChildren(n => n.getAttr?.('posId')).forEach(g => {
        const k = g.getAttr('posGrup') ?? '';
        counts[k] = (counts[k] ?? 0) + 1;
    });
    CATEGORIES.forEach(cat => {
        const el = document.getElementById(`te-n-${cat.id}`);
        if (el) el.textContent = counts[cat.id] ?? 0;
    });
}

// ── API pública ──────────────────────────────────────────────────────────────
function addPosition(grupId) {
    if (!stage || !layer) return;
    const cat = CAT[grupId]; if (!cat) return;

    const nodes = layer.getChildren(n => n.getAttr?.('posGrup') === grupId);
    const num   = nodes.length + 1;
    const nom   = ['agulla','rengle'].includes(grupId) ? cat.label : cat.label + ' ' + num;

    // Posicionar: apilat sota l'última del grup, o al marge superior esquerra si és el primer
    let cx, cy;
    if (nodes.length > 0) {
        const maxY = Math.max(...nodes.map(n => n.y()));
        const ref  = nodes.find(n => n.y() === maxY);
        cx = ref.x();
        cy = maxY + (cat.h + 3);
    } else {
        const sc = stage.scaleX();
        const sp = stage.position();
        cx = (-sp.x + 40) / sc;
        cy = (-sp.y + 40) / sc;
    }

    const node = addNode({ id: `pos_${++posCounter}`, cx, cy, w: cat.w, h: cat.h,
                           angle: 0, grup: grupId, nom, shape: cat.shape });
    saveHistory();
    transformer.nodes([node]);
    showProps(node);
    refreshCounts();
    layer.draw();
}

function selectByGrup(grupId) {
    if (!layer) return;
    const nodes = layer.getChildren(n => n.getAttr?.('posGrup') === grupId);
    transformer.nodes(nodes);
    showProps(null);
    layer.draw();
}

function deleteSelected() {
    if (!transformer) return;
    const nodes = transformer.nodes();
    if (nodes.length === 0) return;
    saveHistory();
    transformer.nodes([]);
    nodes.forEach(n => n.destroy());
    showProps(null);
    refreshCounts();
    layer.draw();
}

function radialAlign() {
    if (!layer) return;
    const targets = transformer.nodes().length > 0
        ? transformer.nodes()
        : layer.getChildren(n => n.getAttr?.('posId'));
    if (targets.length === 0) return;
    saveHistory();

    // Centre de referència = 0,0 (o centroide si no hi ha 0,0 accessible)
    const cx = 0, cy = 0;
    targets.forEach(node => {
        const ang = Math.atan2(node.y() - cy, node.x() - cx) * 180 / Math.PI;
        node.rotation(ang); // eix llarg apunta cap enfora del centre
    });
    layer.draw();
}

function alignToAngle(deg) {
    if (!layer) return;
    const targets = transformer.nodes().length > 0
        ? transformer.nodes()
        : layer.getChildren(n => n.getAttr?.('posId'));
    if (!targets.length) return;
    saveHistory();
    targets.forEach(node => node.rotation(deg));
    layer.draw();
    if (transformer.nodes().length === 1) showProps(transformer.nodes()[0]);
}

function alignToHour(h) {
    // h=12 → -90° (amunt), h=3 → 0° (dreta), h=6 → 90° (avall), h=9 → 180° (esquerra)
    alignToAngle(h * 30 - 90);
}

function updateNom(nom) {
    const nodes = transformer.nodes();
    if (nodes.length !== 1) return;
    const node = nodes[0];
    node.setAttr('posNom', nom);
    const lbl = node.findOne('Text');
    if (lbl) { lbl.text(nom); layer.draw(); }
}

function updateGrup(grupId) {
    const nodes = transformer.nodes(); if (!nodes.length) return;
    const cat = CAT[grupId]; if (!cat) return;
    nodes.forEach(node => {
        node.setAttr('posGrup', grupId);
        const body = node.findOne('Rect') ?? node.findOne('Ellipse');
        if (body) body.fill(cat.color);
        const lbl = node.findOne('Text');
        if (lbl) lbl.fill(cat.text);
    });
    refreshCounts();
    layer.draw();
}

function updateCatLabel(id, label) {
    const cat = CAT[id];
    if (!cat || !label) return;
    cat.label = label;
    // Actualitza el text del selector de la dreta
    const sel = document.getElementById('te-grup');
    if (sel) {
        const opt = Array.from(sel.options).find(o => o.value === id);
        if (opt) opt.textContent = label;
    }
}

function exportPositions() {
    if (!layer) return [];
    return layer.getChildren(n => n.getAttr?.('posId')).map(node => ({
        id:    node.getAttr('posId'),
        cx:    Math.round(node.x() * 10) / 10,
        cy:    Math.round(node.y() * 10) / 10,
        w:     Math.round(node.getAttr('posW') * (node.scaleX?.() ?? 1) * 10) / 10,
        h:     Math.round(node.getAttr('posH') * (node.scaleY?.() ?? 1) * 10) / 10,
        angle: Math.round(node.rotation() * 10) / 10,
        grup:  node.getAttr('posGrup') ?? '',
        nom:   node.getAttr('posNom')  ?? '',
        shape: node.getAttr('posShape') ?? 'rect',
    }));
}

// AFEGEIX posicions al canvas sense netejar l'escena
function addPositions(newPositions) {
    if (!stage || !layer) return;
    newPositions.forEach(pos => {
        posCounter = Math.max(posCounter, parseInt(String(pos.id ?? '0').replace(/\D/g, '') || '0'));
        addNode(pos);
    });
    saveHistory();
    refreshCounts();
    layer.draw();
}

function clearCanvas() {
    if (!layer) return;
    layer.getChildren(n => n.getAttr?.('posId')).forEach(n => n.destroy());
    transformer.nodes([]);
    posCounter = 0;
    showProps(null);
    saveHistory();
    refreshCounts();
    layer.draw();
}

// Livewire dispatch → re-inicialitza el canvas amb noves posicions
window.addEventListener('template-regenerate', e => {
    const pos = e.detail?.positions ?? e.detail?.[0]?.positions ?? [];
    if (window.templateEditor) window.templateEditor.init(pos);
});

// Livewire dispatch → afegeix posicions sense netejar
window.addEventListener('template-add-positions', e => {
    const pos = e.detail?.positions ?? e.detail?.[0]?.positions ?? [];
    if (window.templateEditor) window.templateEditor.addPositions(pos);
});

// ── Exposem l'API global ──────────────────────────────────────────────────────
window.templateEditor = {
    init: initEditor,
    CATEGORIES,
    addPosition,
    selectByGrup,
    deleteSelected,
    radialAlign,
    alignToAngle,
    alignToHour,
    distributeH,
    distributeV,
    undo: undoHistory,
    redo: redoHistory,
    updateNom,
    updateCatLabel,
    updateGrup,
    exportPositions,
    refreshCounts,
    addPositions,
    clearCanvas,
    updateX,
    updateY,
    updateAngle,
    updateW,
    updateH,
};
