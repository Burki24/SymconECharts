'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.join(__dirname, '..');
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
const width = 620, height = 500;

function near(actual, expected, tolerance, message) {
    assert.ok(Math.abs(actual - expected) <= tolerance, message + ': ' + actual + ' vs ' + expected);
}

// Observe the real shipped CanvasRenderer. Only the drawing surface is replaced;
// expected lengths and directions below come from analytic geometry, not ECharts.
function surface() {
    const strokes = [];
    let x = 0, y = 0, sx = 0, sy = 0, length = 0, dash = [];
    const line = (a, b) => { length += Math.hypot(a - x, b - y); x = a; y = b; };
    const canvas = {nodeName: 'CANVAS', style: {}, width, height, setAttribute() {}};
    const ctx = new Proxy({canvas,
        beginPath() { length = 0; x = y = sx = sy = 0; },
        moveTo(a, b) { x = sx = a; y = sy = b; },
        lineTo: line,
        closePath() { line(sx, sy); },
        rect(a, b, w, h) { this.moveTo(a, b); line(a + w, b); line(a + w, b + h); line(a, b + h); this.closePath(); },
        bezierCurveTo(a, b, c, d, e, f) {
            const ox = x, oy = y;
            for (let i = 1; i <= 256; i++) {
                const t = i / 256, u = 1 - t;
                line(u ** 3 * ox + 3 * u * u * t * a + 3 * u * t * t * c + t ** 3 * e,
                    u ** 3 * oy + 3 * u * u * t * b + 3 * u * t * t * d + t ** 3 * f);
            }
        },
        quadraticCurveTo(a, b, c, d) {
            const ox = x, oy = y;
            for (let i = 1; i <= 256; i++) {
                const t = i / 256, u = 1 - t;
                line(u * u * ox + 2 * u * t * a + t * t * c, u * u * oy + 2 * u * t * b + t * t * d);
            }
        },
        arc(cx, cy, r, start, end, ccw) {
            let delta = end - start;
            if (ccw && delta > 0) { delta -= 2 * Math.PI; }
            if (!ccw && delta < 0) { delta += 2 * Math.PI; }
            length += r * Math.abs(delta);
            x = cx + r * Math.cos(end); y = cy + r * Math.sin(end);
        },
        ellipse(cx, cy, rx, ry, rotation, start, end, ccw) {
            let delta = end - start;
            if (ccw && delta > 0) { delta -= 2 * Math.PI; }
            if (!ccw && delta < 0) { delta += 2 * Math.PI; }
            for (let i = 1; i <= 4096; i++) {
                const a = start + delta * i / 4096;
                line(cx + rx * Math.cos(a) * Math.cos(rotation) - ry * Math.sin(a) * Math.sin(rotation),
                    cy + rx * Math.cos(a) * Math.sin(rotation) + ry * Math.sin(a) * Math.cos(rotation));
            }
        },
        stroke() { strokes.push({color: this.strokeStyle, length, x, y, dash: Array.from(dash)}); },
        setLineDash(value) { dash = value; },
        getLineDash() { return dash; },
        measureText: text => ({width: String(text).length * 8}),
        createLinearGradient: () => ({addColorStop() {}}),
        createRadialGradient: () => ({addColorStop() {}})
    }, {get: (target, key) => key in target ? target[key] : () => {}});
    canvas.getContext = () => ctx;
    return {canvas, strokes};
}

function renderer(runtime, file) {
    const window = {SYMC_VISUALIZATION: {options: {locale: 'de-DE'}}};
    const context = vm.createContext({window, navigator: {userAgent: 'Node.js'}, console, setTimeout, clearTimeout});
    vm.runInContext(read('libs/echarts/6.1.0/echarts.' + runtime + '.min.js'), context);
    vm.runInContext(read('libs/jslive/format.js'), context);
    vm.runInContext(read(file), context);
    window.echarts.setPlatformAPI({createCanvas: () => surface().canvas});
    return window;
}

function progressTests() {
    const w = renderer('gauge', 'EChartsProgress/ProgressRenderer.js');
    const settings = {shape_preset: 'circle', shape_path: '', shape_svg: '', Type: 'stroke', stroke_dir: 'normal',
        stroke_color: 0x112233, stroke_trailColor: 0xdddddd, stroke_width: 5, stroke_trailWidth: 5,
        stroke_lincap: 'butt', stroke_Dash1: 0, stroke_Dash2: 0, style_fontDisplay: false, __animate: false};
    const circleEnd = t => [50 + 40 * Math.sin(2 * Math.PI * t), 50 - 40 * Math.cos(2 * Math.PI * t)];
    const capsuleEnd = t => {
        let distance = (140 + 10 * Math.PI) * t;
        if (distance <= 70) { return [15 + distance, 5]; }
        distance -= 70;
        if (distance <= 5 * Math.PI) { return [85 + 5 * Math.sin(distance / 5), 10 - 5 * Math.cos(distance / 5)]; }
        distance -= 5 * Math.PI;
        if (distance <= 70) { return [85 - distance, 15]; }
        distance -= 70;
        return [15 - 5 * Math.sin(distance / 5), 10 + 5 * Math.cos(distance / 5)];
    };
    // Independent numerical circumference for a rotated 2:1 ellipse.
    let ellipseLength = 0;
    for (let i = 0; i < 20000; i++) {
        const a = (i + 0.5) * 2 * Math.PI / 20000;
        ellipseLength += Math.hypot(40 * Math.sin(a), 20 * Math.cos(a)) * 2 * Math.PI / 20000;
    }
    const ellipseStart = [50 + 20 * Math.sqrt(3), 70], ellipseOpposite = [50 - 20 * Math.sqrt(3), 30];
    const ellipsePath = flag => 'M' + ellipseStart.join(' ') + 'A40 20 30 0 ' + flag + ' ' + ellipseOpposite.join(' ')
        + 'A40 20 30 0 ' + flag + ' ' + ellipseStart.join(' ');
    const cases = [
        ['circle', '', 80 * Math.PI, circleEnd], ['bubble', '', 80 * Math.PI, circleEnd],
        ['energy', '', 140 + 10 * Math.PI, capsuleEnd], ['stripe', '', 140 + 10 * Math.PI, capsuleEnd],
        ['fan', '', 40 * Math.PI], ['line', '', 80],
        ['line', 'M0 0L30 40L60 0', 100],
        ['line', 'M50 10A40 40 0 0 1 50 90A40 40 0 0 1 50 10', 80 * Math.PI, circleEnd],
        ['line', 'M10 50A40 40 0 0 0 90 50A40 40 0 0 0 10 50', 80 * Math.PI],
        ['line', 'M0 0L100 0L100 100L0 100Z', 400],
        ['line', ellipsePath(1), ellipseLength], ['line', ellipsePath(0), ellipseLength]
    ];
    let checked = 0;
    for (const [preset, customPath, perimeter, endpoint] of cases) {
        const drawing = surface();
        const chart = w.echarts.init(drawing.canvas, null, {renderer: 'canvas', ssr: true, width, height});
        try {
            // Reuse the same real chart to catch stale path-length caches on updates.
            for (const ratio of [0.862, 0, 0.25, 0.5, 1, 0.862]) {
                const state = {settings: {...settings, shape_preset: preset, shape_path: customPath},
                    data: {value: ratio * 100, displayValue: ratio * 100, ratio, effective: {}}, background: 'transparent'};
                const before = JSON.stringify(state);
                const option = w.SYMC_JSLIVE_OPTION(state, width, height);
                option.animation = false;
                drawing.strokes.length = 0;
                chart.setOption(option, false); chart.getZr().flush();
                const foreground = drawing.strokes.filter(s => s.color === 'rgba(17,34,51,1)').at(-1);
                const actual = foreground ? foreground.length : 0;
                near(actual, perimeter * ratio, perimeter * 0.002,
                    'F01 ' + preset + ' ' + customPath + ' ratio ' + ratio + ': painted path length');
                if (endpoint && ratio > 0) {
                    const point = endpoint(ratio);
                    near(foreground.x, point[0], 0.15, 'F01 painted endpoint x');
                    near(foreground.y, point[1], 0.15, 'F01 painted endpoint y');
                }
                assert.equal(JSON.stringify(state), before, 'Rendering must not change imported values or designs.');
                ++checked;
            }
        } finally { chart.dispose(); }
    }
    for (const direction of ['normal', 'reverse']) {
        const drawing = surface();
        const chart = w.echarts.init(drawing.canvas, null, {renderer: 'canvas', ssr: true, width, height});
        try {
            for (const ratio of [0, 0.25, 0.5, 0.862, 1]) {
                const state = {settings: {...settings, shape_path: cases[7][1], stroke_dir: direction,
                    stroke_Dash1: direction === 'normal' ? 2 : 0, stroke_Dash2: direction === 'normal' ? 1 : 0},
                data: {value: 100 * ratio, displayValue: 100 * ratio, ratio, effective: {}}, background: 'transparent'};
                drawing.strokes.length = 0;
                chart.setOption(w.SYMC_JSLIVE_OPTION(state, width, height), false); chart.getZr().flush();
                const stroke = drawing.strokes.filter(s => s.color === 'rgba(17,34,51,1)').at(-1);
                if (direction === 'normal') {
                    if (ratio > 0) {
                        assert.deepEqual(stroke.dash, [2, 1], 'Custom arc dash pattern must remain active.');
                        near(stroke.length, 80 * Math.PI * ratio, 0.5, 'Dashed partial arc length');
                    }
                } else {
                    near(stroke.length, 80 * Math.PI, 0.5, 'Reverse mode keeps the full underlying path.');
                    near(stroke.dash[1], 80 * Math.PI * (1 - ratio), 0.01, 'Reverse unpainted prefix');
                    near(stroke.dash[2], 80 * Math.PI * ratio, 0.01, 'Reverse painted suffix');
                }
                ++checked;
            }
        } finally { chart.dispose(); }
    }
    console.log('JSLive F01: ' + checked + ' real Canvas path lengths/updates passed.');
}

function radarTests() {
    const w = renderer('radar', 'EChartsRadarHistory/RadarHistoryRenderer.js');
    const settings = {title_display: false, legend_display: false, tooltips_enabled: true, axes_display: true,
        axes_pointLabels_display: true, axes_pointLabels_fontSize: 12, axes_angleLines_display: true,
        axes_angleLines_lineWidth: 1, axes_gridLines_display: true, axes_gridLines_lineWidth: 1,
        axes_ticks_fontSize: 12, data_precision: 0, __animate: false};
    for (const labels of [['N', 'O', 'S', 'W'], ['N', 'NO', 'O', 'SO', 'S', 'SW', 'W', 'NW'],
        ['Frost', 'Kühl', 'Mild'], ['0', '3', '17', '100', 'Sonderklasse']]) {
        const values = labels.map((_, i) => (i + 1) * 7);
        const state = {settings, background: 'transparent', data: {labels: values, alias: labels,
            series: [{key: 'series:one', label: 'Original order', values, native: {parameters: {DIGITS: 0}},
                settings: {BackgroundColor: -1, BorderColor: 0x112233, BorderWidth: 1, PointRadius: 3}}]}};
        const before = JSON.stringify(state);
        const option = w.SYMC_JSLIVE_OPTION(state, width, height);
        option.animation = false;
        const chart = w.echarts.init(surface().canvas, null, {renderer: 'canvas', ssr: true, width, height});
        try {
            chart.setOption(option, true);
            const points = chart.getModel().getSeriesByIndex(0).getData().getItemLayout(0);
            const center = option.radar.center, radius = option.radar.radius;
            const maximum = Math.max(...values) * 1.05;
            labels.forEach((label, i) => {
                const angle = 2 * Math.PI * i / labels.length;
                const r = radius * values[i] / maximum;
                near(points[i][0], center[0] + r * Math.sin(angle), 0.01, 'F02 clockwise x: ' + label);
                near(points[i][1], center[1] - r * Math.cos(angle), 0.01, 'F02 clockwise y: ' + label);
            });
            const tooltip = option.tooltip.formatter({data: {id: 'series:one'}});
            let previous = -1;
            labels.forEach((label, i) => {
                const position = tooltip.indexOf(label + ': ' + values[i]);
                assert.ok(position > previous, 'Tooltip must retain the original label/value order.');
                previous = position;
            });
            assert.equal(JSON.stringify(state), before, 'Radar must not reorder the imported model.');
        } finally { chart.dispose(); }
    }
    console.log('JSLive F02: clockwise compass/custom-class coordinates and unchanged tooltip/model passed.');
}

const choice = process.argv[2];
if (choice !== '--radar') { progressTests(); }
if (choice !== '--progress') { radarTests(); }
