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
shipped.echarts.setPlatformAPI({createCanvas: () => surface(300, 300)});

for (const mode of ['symcon', 'ipsview']) {
    const width = 220, height = 230, inset = mode === 'symcon' ? 40 : 0;
    const chartElement = {clientWidth: width, clientHeight: height, setAttribute() {}};
    const motion = {matches: false, addEventListener(name, listener) { this.changed = listener; }};
    let native;
    const model = {theme: 'auto', title: 'Wetterstation', direction: 90, speed: 15.5, speedUnit: 'km/h',
        speedDecimals: 2, language: 'de', windLabel: 'Wind', labels: ['N', 'NO', 'O', 'SO', 'S', 'SW', 'W', 'NW'],
        style: {showRotor: true, showDegreeValue: true, animationEnabled: true, animationDuration: 0, animationDurationUpdate: 0}};
    const window = {
        echarts: {init() {
            native = shipped.echarts.init(surface(width, height), null, {renderer: 'canvas', width, height, ssr: true});
            return native;
        }},
        SYMC_VISUALIZATION: {state: {status: 'ready', chart: model}, mode, options: {tileHeaderInset: inset}},
        addEventListener() {}, matchMedia: () => motion, getComputedStyle: () => ({color: '#fff', getPropertyValue: () => '#303030'})
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
    } finally {
        native.dispose();
    }
}
console.log('Wind Gauge draws the compass and advances or stops the independent native leaf animation.');
