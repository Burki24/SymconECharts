'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const read = file => fs.readFileSync(path.join(__dirname, '..', file), 'utf8');

// Run the delivered CanvasRenderer and its animation engine; only the drawing surface is substituted.
const shipped = {};
vm.runInNewContext(read('libs/echarts/6.1.0/echarts.gauge.min.js'), {
    window: shipped, self: {}, navigator: {userAgent: 'Wind renderer test'}, console, setTimeout, clearTimeout
});
function surface(width, height) {
    const canvas = {nodeName: 'CANVAS', style: {}, width, height, setAttribute() {}};
    const context = new Proxy({canvas, getLineDash: () => [], measureText: text => ({width: String(text).length * 7}),
        createLinearGradient: () => ({addColorStop() {}})}, {
        get: (target, key) => key in target ? target[key] : () => {}
    });
    canvas.getContext = () => context;
    return canvas;
}
shipped.echarts.setPlatformAPI({createCanvas: () => surface(300, 300),
    loadImage: src => ({src, width: 100, height: 100})});

for (const mode of ['symcon', 'ipsview']) {
    const width = 220, height = 230, inset = mode === 'symcon' ? 40 : 0;
    const chartElement = {clientWidth: width, clientHeight: height, setAttribute() {}};
    const motion = {matches: false, addEventListener(name, listener) { this.changed = listener; }};
    let native, resize;
    const model = {theme: 'auto', title: 'Wetterstation', direction: 90, speed: 15.5, speedUnit: 'km/h',
        speedDecimals: 2, language: 'de', windLabel: 'Wind', labels: ['N', 'NO', 'O', 'SO', 'S', 'SW', 'W', 'NW'],
        style: {showRotor: true, showDegreeValue: true, animationEnabled: true, animationDuration: 0, animationDurationUpdate: 0}};
    const window = {
        echarts: {init() {
            native = shipped.echarts.init(surface(width, height), null, {renderer: 'canvas', width, height, ssr: true});
            return native;
        }},
        SYMC_VISUALIZATION: {state: {status: 'ready', chart: model}, mode, options: {tileHeaderInset: inset}},
        addEventListener(name, fn) { if (name === 'resize') { resize = fn; } },
        matchMedia: () => motion, getComputedStyle: () => ({color: '#fff', getPropertyValue: () => '#303030'})
    };
    const document = {getElementById: id => id.endsWith('-chart') ? chartElement : {}};
    vm.runInNewContext(read('libs/echarts-design.js'), {window, document});
    vm.runInNewContext(read('EChartsGaugeWind/visualization/app.js'), {window, document});
    function elements() { return native.getZr().storage.getDisplayList(true); }
    function leaf() { return elements().find(element => element.type === 'polygon'); }
    function rotation() { return leaf().animators.find(animator => animator.scope === 'keyframe'); }
    function update(fields) {
        Object.assign(model, fields);
        window.handleMessage(JSON.stringify({status: 'ready', chart: model}));
    }
    function series() { return native.getModel().getSeries()[0]; }
    try {
        const gauge = series();
        assert.equal(gauge.subType, 'gauge');
        assert.deepEqual([gauge.get('min'), gauge.get('max'), gauge.get('clockwise')], [0, 360, true]);
        assert.ok(gauge.get('center')[1] - gauge.get('radius') > inset);
        const drawn = elements().filter(e => e.type === 'tspan').map(e => e.style.text);
        assert.ok(drawn.includes('Wetterstation'));
        assert.ok(drawn.includes('Wind: 15,50 km/h · O 90°'));
        assert.equal(drawn.filter(text => text === 'N').length, 1);
        for (const label of model.labels) { assert.ok(drawn.includes(label), 'Missing ' + label); }
        const pointerAngle = gauge.getData().getItemGraphicEl(0).rotation;
        assert.ok(Math.abs(Math.sin(pointerAngle) + 1) < 1e-6, 'East pointer rotation: ' + pointerAngle);

        const rotor = leaf(), animator = rotation(), clip = animator.getClip();
        const duration = animator.getMaxTime();
        clip.step(100, 0);
        clip.step(100 + duration / 4, duration / 4);
        assert.ok(Math.abs(rotor.rotation + Math.PI / 2) < 1e-5);
        update({direction: 315});
        assert.equal(leaf(), rotor, 'An ordinary wind direction update preserves the leaf and its animation phase.');
        assert.equal(rotation(), animator);
        clip.step(100 + duration, duration * 0.75);
        clip.step(100 + duration * 1.5, duration / 2);
        assert.ok(Math.abs(rotor.rotation + Math.PI) < 1e-5);

        update({speed: 45});
        assert.ok(rotation().getMaxTime() < duration);
        motion.matches = true;
        motion.changed();
        assert.ok(leaf());
        assert.equal(rotation(), undefined);
        motion.matches = false;
        motion.changed();
        assert.ok(rotation());
        assert.equal(series().getData().get('value', 0), 315);
        for (const speed of [0, null]) {
            update({speed});
            assert.ok(leaf());
            assert.equal(rotation(), undefined);
            const header = elements().filter(e => e.type === 'tspan').map(e => e.style.text);
            assert.ok(header.includes(speed === null ? 'NW 315°' : 'Wind: 0,00 km/h · NW 315°'));
        }
        update({speed: 50, style: {...model.style, animationEnabled: false}});
        assert.equal(rotation(), undefined);
        update({style: {...model.style, showRotor: false}});
        assert.equal(leaf(), undefined);

        update({direction: 359, style: {...model.style, animationEnabled: true, animationDurationUpdate: 600}});
        for (const direction of [1, 359]) {
            update({direction});
            const pointer = series().getData().getItemGraphicEl(0);
            assert.equal(pointer.animators.length, 0, 'Crossing north must never animate through south.');
            const expected = -direction * Math.PI / 180;
            assert.ok(Math.abs(Math.sin(pointer.rotation) - Math.sin(expected)) < 1e-6);
            assert.ok(Math.abs(Math.cos(pointer.rotation) - Math.cos(expected)) < 1e-6);
        }
        update({direction: 350});
        assert.ok(series().getData().getItemGraphicEl(0).animators.length > 0, 'Ordinary changes retain pointer transitions.');

        let turn;
        const timeline = native.getZr().animation;
        const animate = timeline.animate.bind(timeline);
        timeline.animate = function (target) {
            turn = animate(target);
            return turn;
        };
        function assertNeedleUp() {
            const pointer = series().getData().getItemGraphicEl(0);
            assert.ok(Math.abs(Math.cos(pointer.rotation) - 1) < 1e-6, 'The needle must stay at twelve o’clock.');
            assert.equal(pointer.animators.length, 0);
        }
        function labelPosition(label) {
            const labelEl = elements().find(el => el.type === 'tspan' && el.style.text === label).parent;
            assert.equal(labelEl.rotation, 0, 'Labels must remain upright.');
            return [labelEl.style.x, labelEl.style.y];
        }
        function assertTopLabel(label) {
            const [x, y] = labelPosition(label);
            const [cx, cy] = series().get('center');
            assert.ok(Math.abs(x - cx) < 0.001 && y < cy, label + ' must be centered above the hub.');
            assertNeedleUp();
        }
        update({direction: 135, speed: 0, style: {...model.style, rotateCompass: true, animationEasingUpdate: 'linear'}});
        assertTopLabel('SO');
        assert.equal(turn, undefined, 'Initial orientation must be immediate.');
        update({direction: 90, speed: null});
        const firstTurn = turn;
        const moving = turn.getClip();
        moving.step(0, 0);
        moving.step(300, 300);
        assert.equal(series().get('startAngle'), 90 + 112.5);
        assertNeedleUp();
        update({speed: 12, style: {...model.style, showRotor: true}});
        assert.ok(rotation(), 'The speed rotor can run independently of the compass.');
        assert.equal(turn, firstTurn, 'A speed-only update must not restart the dial.');
        moving.step(600, 300);
        assertTopLabel('O');
        update({direction: 359, style: {...model.style, animationEnabled: false}});
        update({direction: 1, style: {...model.style, animationEnabled: true}});
        const crossing = turn.getClip();
        crossing.step(1000, 0);
        crossing.step(1300, 300);
        assertTopLabel('N');
        motion.matches = true;
        motion.changed();
        assert.equal(turn.getClip(), null, 'Reduced motion stops an active dial transition.');
        assert.equal(series().get('startAngle'), 91);
        update({direction: 270, speed: null});
        assertTopLabel('W');
        update({style: {...model.style, rotateCompass: false}});
        assert.equal(series().get('startAngle'), 90);
        assert.ok(Math.abs(Math.sin(series().getData().getItemGraphicEl(0).rotation) - 1) < 1e-6);

        const svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><text x="90" y="50">O</text></svg>';
        const compassImage = 'data:image/svg+xml;base64,' + Buffer.from(svg).toString('base64');
        const image = () => elements().find(el => el.type === 'image');
        function assertImageOrientation(bearing) {
            const rose = image();
            assert.ok(rose && rose.style.image === compassImage);
            const [cx, cy] = series().get('center');
            const box = rose.getBoundingRect();
            assert.ok(box.width > 0 && box.width === box.height);
            const center = rose.transformCoordToGlobal(box.x + box.width / 2, box.y + box.height / 2);
            const north = rose.transformCoordToGlobal(box.x + box.width / 2, box.y);
            const rad = bearing * Math.PI / 180;
            assert.ok(Math.hypot(center[0] - cx, center[1] - cy) < 1e-6, 'SVG pivots around the hub.');
            assert.ok(Math.abs(north[0] - cx + box.width / 2 * Math.sin(rad)) < 1e-6
                && Math.abs(north[1] - cy + box.height / 2 * Math.cos(rad)) < 1e-6, 'SVG north at bearing ' + bearing);
            for (const name of ['axisLine', 'axisTick', 'splitLine', 'axisLabel']) {
                assert.equal(series().get(name).show, false);
            }
        }
        update({direction: 90, speed: 0, style: {...model.style, compassImage}});
        assertImageOrientation(0);
        assert.ok(Math.abs(Math.sin(series().getData().getItemGraphicEl(0).rotation) + 1) < 1e-6);
        update({style: {...model.style, rotateCompass: true}});
        assertImageOrientation(90);
        assertNeedleUp();
        update({direction: 359});
        motion.matches = false;
        motion.changed();
        update({direction: 1});
        const imageTurn = turn.getClip();
        imageTurn.step(0, 0);
        imageTurn.step(300, 300);
        assertImageOrientation(0);
        assertNeedleUp();
        imageTurn.step(600, 300);
        assertImageOrientation(1);
        const originalWidth = image().style.width;
        chartElement.clientWidth = 140;
        chartElement.clientHeight = 160;
        resize();
        assertImageOrientation(1);
        assertNeedleUp();
        assert.ok(image().style.width < originalWidth && series().get('center')[0] === 70);
        assert.equal(leaf().z > image().z, true, 'The optional speed rotor must stay above the imported artwork.');
        assert.ok(elements().every(el => el.type !== 'tspan' || !model.labels.includes(el.style.text)));
        update({style: {...model.style, compassImage: ''}});
        assert.equal(image(), undefined, 'Returning to the standard rose removes the SVG.');
        assert.equal(series().get('axisLabel').show, true);
    } finally {
        native.dispose();
    }
}
console.log('Wind Gauge renders standard and SVG compasses, preserves their pivots, and animates bearing and rotor independently.');
