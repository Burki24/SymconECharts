'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const {execFileSync} = require('node:child_process');
const root = path.join(__dirname, '..');
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
const echarts = require(path.join(root, '.tools/echarts-runtime/node_modules/echarts'));
function renderer(file, runtime) {
    const window = {SYMC_VISUALIZATION: {options: {locale: 'de-DE'}}};
    const context = vm.createContext({window, navigator: {userAgent: 'Node.js'}, console, setTimeout, clearTimeout});
    vm.runInContext(read('libs/echarts/6.1.0/echarts.' + runtime + '.min.js'), context);
    vm.runInContext(read('libs/jslive/format.js'), context);
    vm.runInContext(read(file), context);
    return window;
}
function labels() {
    const w = renderer('EChartsPie/JSLiveRenderer.js', 'pie'), f = w.SYMC_JSLIVE_FORMAT;
    const c = {datalabels_fontColor: 0x112233, datalabels_fontSize: 12, datalabels_borderWidth: 2,
        datalabels_borderRadius: 3, datalabels_align: 'center', datalabels_anchoring: 'center', data_precision: 1,
        rotation_length: 180, rotation_start: -90, type: 'doughnut', __animate: false};
    const inherited = {datalabels_enable: true, datalabels_FontColor: -1,
        datalabels_BackgroundColor: -1, datalabels_BackgroundColor_Alpha: 0.01,
        datalabels_BorderColor: -1, datalabels_BorderColor_Alpha: 0.01,
        BackgroundColor: 0x334455, BackgroundColor_Alpha: 0.35, BorderColor: 0x667788, BorderColor_Alpha: 0.6};
    const label = f.dataLabel(c, inherited);
    assert.equal(label.backgroundColor, 'rgba(51,68,85,0.35)', 'F04: inherit the series fill and its alpha.');
    assert.equal(label.borderColor, 'rgba(102,119,136,0.6)', 'F04: inherit the series border and its alpha.');
    const explicit = {...inherited, datalabels_BackgroundColor: 0xff0000, datalabels_BackgroundColor_Alpha: 0,
        datalabels_BorderColor: 0xabcdef, datalabels_BorderColor_Alpha: 0.4};
    assert.equal(f.dataLabel(c, explicit).backgroundColor, 'rgba(255,0,0,0)', 'An explicit transparent fill is not inheritance.');
    assert.equal(f.dataLabel(c, explicit).borderColor, 'rgba(171,205,239,0.4)');
    const item = (key, value, color, border) => ({key, variableID: key.length, label: key, value, originalValue: value,
        native: {parameters: {DIGITS: 1, SUFFIX: ' W'}}, settings: {BackgroundColor: color, BackgroundColor_Alpha: 0.5,
            BorderColor: border, BorderColor_Alpha: 0.8, BorderWidth: 1}});
    const state = {settings: c, background: 'transparent', data: {rings: [
        {key: 'outer', settings: {...inherited, cutout: 50, Title: 'Outer'}, items: [item('a', 3, 0x010203, 0x040506), item('bb', 1, 0x112233, 0x445566), item('zero', 0, 0, 0)]},
        {key: 'inner', settings: {...explicit, cutout: 50, Title: 'Inner'}, items: [item('ccc', 1, 0x998877, 0x665544)]}
    ]}};
    const before = JSON.stringify(state), option = w.SYMC_JSLIVE_OPTION(state, 620, 500);
    // The pinned SVG painter exposes the actual label rectangles; browser checks
    // separately exercise the shipped Canvas-only family bundle.
    const chart = echarts.init(null, null, {renderer: 'svg', ssr: true, width: 620, height: 500});
    try {
        chart.setOption(option, true);
        const data = chart.getModel().getSeriesByIndex(0).getData();
        for (const [i, background, border] of [[0, 'rgba(1,2,3,0.5)', 'rgba(4,5,6,0.8)'], [1, 'rgba(17,34,51,0.5)', 'rgba(68,85,102,0.8)']]) {
            const actual = data.getItemModel(i).getModel('label');
            assert.equal(actual.get('backgroundColor'), background, 'F04: resolve the color per segment, not per ring.');
            assert.equal(actual.get('borderColor'), border);
            assert.equal(actual.get('borderWidth'), 2);
            assert.equal(actual.get('borderRadius'), 3);
        }
        assert.equal(data.getItemModel(2).getModel('label').get('show'), false, 'Zero segments must not leave empty label boxes.');
        const inner = chart.getModel().getSeriesByIndex(1).getData().getItemModel(0).getModel('label');
        assert.equal(inner.get('backgroundColor'), 'rgba(255,0,0,0)');
        assert.equal(inner.get('borderColor'), 'rgba(171,205,239,0.4)');
        assert.match(chart.renderToSVGString(), /stroke="rgb\(4,5,6\)"/, 'The native renderer must actually draw the label border.');
        chart.dispatchAction({type: 'legendUnSelect', name: 'variable:1'});
        const visible = chart.getModel().getSeriesByIndex(0).getData();
        const remaining = visible.getItemGraphicEl(visible.indexOfRawIndex(1)).getTextContent();
        const box = remaining.getBoundingRect(), point = remaining.transformCoordToGlobal(box.x + box.width / 2, box.y + box.height / 2);
        const ring = option.series[0];
        assert.ok(Math.abs(point[0] - ring.center[0]) < 0.1, 'F04: labels must follow the native sector after legend filtering.');
        assert.ok(Math.abs(point[1] - (ring.center[1] - (ring.radius[0] + ring.radius[1]) / 2)) < 0.1);
        chart.dispatchAction({type: 'legendSelect', name: 'variable:1'});
        const restored = chart.getModel().getSeriesByIndex(0).getData().getItemGraphicEl(0).getTextContent();
        const bounds = restored.getBoundingRect(), restoredPoint = restored.transformCoordToGlobal(bounds.x + bounds.width / 2, bounds.y + bounds.height / 2);
        const angle = -112.5 * Math.PI / 180, radius = (ring.radius[0] + ring.radius[1]) / 2;
        assert.ok(Math.abs(restoredPoint[0] - ring.center[0] - Math.cos(angle) * radius) < 0.1, 'Restoring the legend must restore the label angle.');
        assert.ok(Math.abs(restoredPoint[1] - ring.center[1] - Math.sin(angle) * radius) < 0.1);
        const zeroed = JSON.parse(JSON.stringify(state)); zeroed.data.rings[0].items[0].value = 0;
        chart.setOption(w.SYMC_JSLIVE_OPTION(zeroed, 620, 500), false);
        assert.equal(chart.getModel().getSeriesByIndex(0).getData().getItemGraphicEl(0).getTextContent().ignore, true, 'A live zero update must remove its label box.');
        assert.equal(JSON.stringify(state), before, 'Label rendering must preserve source values and saved designs.');
    } finally { chart.dispose(); }
    for (const anchor of ['start', 'center', 'end']) {
        const anchored = JSON.parse(JSON.stringify(state)); anchored.settings.datalabels_anchoring = anchor;
        const series = w.SYMC_JSLIVE_OPTION(anchored, 620, 500).series[0];
        assert.equal(typeof series.labelLayout, 'function', 'F04: retain the radial label anchor.');
        const p = series.labelLayout({dataIndex: 0, labelRect: {width: 20, height: 10}});
        const expected = anchor === 'start' ? series.radius[0] : anchor === 'end' ? series.radius[1] : (series.radius[0] + series.radius[1]) / 2;
        assert.ok(Math.abs(Math.hypot(p.x - series.center[0], p.y - series.center[1]) - expected) < 0.01);
        assert.equal(series.data[0].label.position, 'inside');
        const actual = echarts.init(null, null, {renderer: 'svg', ssr: true, width: 620, height: 500});
        try {
            actual.setOption(w.SYMC_JSLIVE_OPTION(anchored, 620, 500), true);
            const text = actual.getModel().getSeriesByIndex(0).getData().getItemGraphicEl(0).getTextContent();
            const box = text.getBoundingRect(), point = text.transformCoordToGlobal(box.x + box.width / 2, box.y + box.height / 2);
            assert.ok(Math.abs(Math.hypot(point[0] - series.center[0], point[1] - series.center[1]) - expected) < 0.1, 'Actual native label anchor ' + anchor);
        } finally { actual.dispose(); }
    }
    for (const align of ['start', 'end', 'top', 'bottom', 'left', 'right']) {
        const aligned = JSON.parse(JSON.stringify(state)); aligned.settings.datalabels_align = align;
        const base = w.SYMC_JSLIVE_OPTION(state, 620, 500).series[0].labelLayout({dataIndex: 0, labelRect: {width: 20, height: 10}});
        const series = w.SYMC_JSLIVE_OPTION(aligned, 620, 500).series[0];
        const point = series.labelLayout({dataIndex: 0, labelRect: {width: 20, height: 10}});
        if (align === 'top' || align === 'bottom') {
            assert.equal(point.x, base.x); assert.equal(point.y - base.y, align === 'top' ? -9 : 9);
        } else if (align === 'left' || align === 'right') {
            assert.equal(point.y, base.y); assert.equal(point.x - base.x, align === 'left' ? -14 : 14);
        } else {
            const dot = (point.x - base.x) * (base.x - series.center[0]) + (point.y - base.y) * (base.y - series.center[1]);
            assert.ok(align === 'start' ? dot < 0 : dot > 0, 'Radial label alignment ' + align);
        }
    }
    console.log('JSLive F04: native segment/series label fills, borders, alpha, multiple rings and unchanged models passed.');
}

function gauges() {
    const w = renderer('EChartsGaugeSingle/JSLiveRenderer.js', 'gauge');
    const states = JSON.parse(execFileSync(process.env.PHP_BINARY || 'php', ['tests/jslive_models.php', '--emit'], {cwd: root, encoding: 'utf8', maxBuffer: 8388608}));
    const input = JSON.parse(JSON.stringify(states[14].chart));
    Object.assign(input.settings, {title_display: true, title_text: 'Title', title_fontSize: 24, title_fontFamily: 'Arial',
        plate_unit: 'W', plate_unit_fontSize: 22, plate_unit_fontFamily: 'Arial', ticks_fontSize: 20, ticks_fontFamily: 'Arial',
        valuebox_fontSize: 26, valuebox_fontFamily: 'Arial', valuebox_display: true, plate_display: true,
        min: 0, max: 100, precision: 0, Ticks: [20, 40, 60, 80].map(Value => ({Value})),
        ticks_exactTicks: false, ticks_minorTicks: 5, Highlights: [], progressbar_display: true, progressbar_barWidth: 10,
        needle_display: true, needle_Type: 'arrow', needle_width: 4, needle_start: 5, needle_end: 85,
        radial_startAngle: 45, radial_ticksAngle: 270, animation_target: 'needle', __animate: false});
    input.data.value = 25;
    const close = (actual, expected, why) => assert.ok(Math.abs(actual - expected) < 0.01, why + ': ' + actual + ' vs ' + expected);
    const get = (o, id) => { const found = o.graphic.find(x => x.id === id); assert.ok(found, 'Missing ' + id); return found; };
    // Frozen public-template / Canvas Gauges 2.1.7 reference measurements at a
    // 620x500 viewport. Radial fonts use radius/200, not diameter/200. Vertical
    // canvas width is height/2; its scale uses 85% of that width.
    for (const template of ['CanvasGauges-Radial', 'CanvasGauges-Compass', 'CanvasGauges-Linear', 'CanvasGauges-Linear(vertical)']) {
        const state = JSON.parse(JSON.stringify(input)); state.settings.template = template;
        state.settings.linear_tickSide = state.settings.linear_numberSide = state.settings.linear_needleSide = 'both';
        const before = JSON.stringify(state), o = w.SYMC_JSLIVE_OPTION(state, 620, 500), plate = get(o, 'plate');
        const linear = template.includes('Linear'), vertical = template.includes('(vertical)');
        if (!linear) {
            close(get(o, 'title').style.fontSize, 28.8, 'F03 radial title font');
            close(get(o, 'number:0').style.fontSize, 24, 'F03 radial scale font');
            close(get(o, 'unit').style.fontSize, 26.4, 'F03 radial unit font');
            close(get(o, 'title').style.y, 248 - 240 / 4.25, 'F03 radial title position');
            assert.equal(plate.style.fill.type, 'radial', 'The radial plate gradient must not be vertical.');
            if (template === 'CanvasGauges-Radial') {
                close(get(o, 'value').style.fontSize, 31.2, 'F03 radial value font');
                assert.equal(get(o, 'needle').shape.points.length, 6, 'Preserve the six-sided tapered arrow.');
            }
        } else if (vertical) {
            close(plate.shape.width, 240, 'F03 vertical plate width');
            close(plate.shape.height, 480, 'F03 vertical plate height');
            close(get(o, 'title').style.fontSize, 24.48, 'F03 vertical title uses the effective scale width');
            close(get(o, 'number:left:0').style.fontSize, 20.4, 'F03 vertical scale font');
            close(get(o, 'value').style.fontSize, 31.2, 'F03 vertical value uses the canvas width');
            close(get(w.SYMC_JSLIVE_OPTION(state, 1200, 700), 'title').style.x, 601, 'F03 source rounds the vertical scale origin before the title');
        } else {
            close(plate.shape.width, 600, 'F03 horizontal plate width');
            close(plate.shape.height, 150, 'F03 horizontal plate height');
            close(get(o, 'title').style.fontSize, 12.48, 'F03 horizontal reserved-title/unit scale width');
            assert.equal(o.graphic.some(x => x.id === 'value'), false, 'The horizontal source template has no value box.');
        }
        assert.equal(JSON.stringify(state), before, 'Gauge rendering must preserve settings, references and raw values.');
        const chart = echarts.init(null, null, {renderer: 'svg', ssr: true, width: 620, height: 500});
        try { chart.setOption(o, true); assert.doesNotMatch(chart.renderToSVGString(), /NaN|Infinity/); } finally { chart.dispose(); }
    }
    // Keep duplicate configured boundary ticks: the source template inserts its
    // min/max as well. The correction must not "clean up" this saved design.
    const duplicate = JSON.parse(JSON.stringify(input));
    duplicate.settings.template = 'CanvasGauges-Radial'; duplicate.settings.Ticks = [{Value: 0}, {Value: 100}];
    assert.equal(w.SYMC_JSLIVE_OPTION(duplicate, 620, 500).graphic.filter(x => /^tick:\d+$/.test(x.id)).length, 4);
    const plateMode = JSON.parse(JSON.stringify(input));
    plateMode.settings.template = 'CanvasGauges-Compass'; plateMode.settings.animation_target = 'plate';
    close(get(w.SYMC_JSLIVE_OPTION(plateMode, 620, 500), 'title').style.y, 181.4494117647, 'F03 rotating plate uses the source alphabetic title baseline');
    plateMode.settings.ticks_exactTicks = true;
    const exactCompass = w.SYMC_JSLIVE_OPTION(plateMode, 620, 500);
    assert.equal(exactCompass.graphic.some(x => /^number:/.test(x.id)), false, 'Named compass labels have no numeric exact positions in the source.');
    assert.equal(exactCompass.graphic.filter(x => /^minor:/.test(x.id)).length, 17, 'The source exact compass uses 22-degree minor steps.');
    assert.doesNotMatch(JSON.stringify(exactCompass), /NaN/);
    const inheritedUnit = JSON.parse(JSON.stringify(input));
    inheritedUnit.settings.template = 'CanvasGauges-Radial'; inheritedUnit.settings.plate_unit_fontFamily = '';
    close(get(w.SYMC_JSLIVE_OPTION(inheritedUnit, 620, 500), 'unit').style.fontSize, 24, 'Empty radial unit font inherits the active number font.');
    inheritedUnit.settings.template = 'CanvasGauges-Linear'; inheritedUnit.settings.title_fontFamily = 'Georgia';
    assert.equal(get(w.SYMC_JSLIVE_OPTION(inheritedUnit, 620, 500), 'unit').style.fontFamily, 'Georgia', 'Empty linear unit font inherits the active title family.');
    inheritedUnit.settings.template = 'CanvasGauges-Compass'; inheritedUnit.settings.animation_target = 'plate';
    close(get(w.SYMC_JSLIVE_OPTION(inheritedUnit, 620, 500), 'unit').style.fontSize, 10, 'A cached rotating plate starts with the canvas default font.');
    const frozen = JSON.parse(read('tests/fixtures/jslive-gauge-geometry.json'));
    assert.equal(frozen.cases.length, 24);
    let referenceChecks = 0, fontChecks = 0;
    for (const ref of frozen.cases) {
        const state = states[ref.case - 1].chart, before = JSON.stringify(state);
        const option = w.SYMC_JSLIVE_OPTION(state, ref.width, ref.height);
        for (const [id, expected] of Object.entries(ref.texts)) {
            const actual = get(option, id).style;
            assert.equal(actual.fontFamily, expected.family, 'Frozen source font family ' + id); ++fontChecks;
            close(actual.fontSize, expected.fontSize, 'Frozen source font ' + id);
            assert.ok(Math.abs(actual.x - expected.x) <= 0.6, 'Frozen source x ' + id);
            const baseline = actual.y + (expected.baseline === 'alphabetic' ? actual.fontSize * 0.35 : 0);
            assert.ok(Math.abs(baseline - expected.baselineY) <= 0.6, 'Frozen source baseline ' + id);
            referenceChecks += 3;
        }
        ref.absent.forEach(id => assert.equal(option.graphic.some(g => g.id === id), false, 'Source does not paint ' + id));
        if (ref.needle) {
            const actual = get(option, ref.needle.id), points = actual.type === 'line'
                ? [[actual.shape.x1, actual.shape.y1], [actual.shape.x2, actual.shape.y2]] : actual.shape.points;
            assert.equal(points.length, ref.needle.points.length);
            points.forEach((point, i) => { point.forEach((coordinate, j) => {
                assert.ok(Math.abs(coordinate - ref.needle.points[i][j]) <= 0.6, 'Frozen source needle case ' + ref.case);
                ++referenceChecks;
            }); });
        }
        assert.equal(JSON.stringify(state), before);
    }
    assert.equal(referenceChecks, 459);
    assert.equal(fontChecks, 81);
    console.log('JSLive F03: 459 frozen original-engine measurements, 81 font families, template dimensions, plate, needle and unchanged models passed.');
}

if (process.argv[2] !== '--gauge') { labels(); }
if (process.argv[2] !== '--labels') { gauges(); }
