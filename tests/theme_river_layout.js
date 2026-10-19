'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const zlib = require('node:zlib');
const root = path.join(__dirname, '..');
const source = fs.readFileSync(path.join(root, 'EChartsThemeRiver/visualization/app.js'), 'utf8');
const designSource = fs.readFileSync(path.join(root, 'libs/echarts-design.js'), 'utf8');
const selectionSource = fs.readFileSync(path.join(root, 'libs/echarts-series-selection.js'), 'utf8');
const startupSource = fs.readFileSync(path.join(root, 'libs/echarts-startup.js'), 'utf8');
const palette = {background: '#101114', text: '#f4f5f7', muted: '#969aa2', border: '#737780', accent: '#55cbb5', seriesColors: ['#5070DD', '#B6D634']};
function render(model, mode = 'symcon', width = 900, height = 500, headerVisible = true, createEngine = null, startup = null) {
    const elements = Object.fromEntries(['chart', 'error', 'empty', 'warning'].map(name => [name,
        {hidden: name !== 'chart', textContent: '', clientWidth: width, clientHeight: height}]));
    let option;
    let resolveStartup, startupJSON;
    let resizeCallback;
    const events = {}, actions = [];
    const chart = createEngine ? createEngine() : {setOption: next => {option = next;}, clear: () => {}, resize: () => {}, dispose: () => {},
        on: (event, handler) => {events[event] = handler;},
        getOption: () => ({...option, legend: [option.legend]}),
        getZr: () => ({on: (event, handler) => {events[event] = handler;}}),
        convertFromPixel: () => model.range.startTimestamp * 1000,
        dispatchAction: action => actions.push(action)};
    const window = {SYMC_VISUALIZATION: {mode, language: 'de', state: {status: 'ready', chart: model}, translations: {},
        options: {echartsThemes: {auto: palette, dark: palette}, tileHeaderVisible: headerVisible, adaptToBackground: mode === 'ipsview'}},
        echarts: {init: () => chart}, addEventListener: (event, handler) => {events[event] = handler;}, getComputedStyle: () => ({color: ''}),
        ResizeObserver: createEngine ? class {constructor(callback) {resizeCallback = callback;} observe() {}} : undefined};
    if (startup === true) {
        startupJSON = JSON.stringify(window.SYMC_VISUALIZATION.state);
        window.SYMC_VISUALIZATION.options.compressedStateGzipBase64 = zlib.gzipSync(startupJSON).toString('base64');
        window.SYMC_VISUALIZATION.state = null;
        window.atob = value => Buffer.from(value, 'base64').toString('binary');
        window.Blob = class {constructor(parts) {this.bytes = parts[0];}
            stream() {return {pipeThrough: () => this.bytes};}};
        window.DecompressionStream = class {constructor(format) {assert.equal(format, 'gzip');}};
        window.Response = class {constructor(bytes) {startupJSON = zlib.gunzipSync(Buffer.from(bytes)).toString('utf8');}
            text() {return new Promise(resolve => {resolveStartup = resolve;});}};
    } else if (startup) {Object.assign(window, startup);}
    if (createEngine) {
        const setOption = chart.setOption.bind(chart);
        chart.setOption = (next, ...args) => {option = next; setOption(next, ...args);};
        const nativeResize = chart.resize.bind(chart);
        // SSR has no DOM from which to measure the new dimensions. Supply that genuine boundary explicitly.
        chart.resize = options => nativeResize(options || {width: elements.chart.clientWidth, height: elements.chart.clientHeight});
    }
    const document = {body: {appendChild: () => {}}, createElement: () => ({style: {}, remove: () => {}}),
        getElementById: id => elements[id.replace('echarts-theme-river-', '')]};
    vm.runInNewContext(designSource, {window});
    vm.runInNewContext(selectionSource, {window});
    vm.runInNewContext(startupSource, {window});
    vm.runInNewContext(source, {window, document, ResizeObserver: window.ResizeObserver});
    return {get option() {return option;}, ...elements, window, engine: chart, events, actions,
        resolveStartup: () => resolveStartup(startupJSON),
        resize: (nextWidth, nextHeight) => {elements.chart.clientWidth = nextWidth; elements.chart.clientHeight = nextHeight; resizeCallback();}};
}
const start = 1792144800;
const row = (index, values) => ({startTimestamp: start + index * 3600, endTimestamp: start + (index + 1) * 3600 - 1,
    label: '2026-10-16 12:00 +02:00', values});
const model = {family: 'themeRiver', theme: 'dark', range: {startTimestamp: start, endTimestamp: start + 5 * 3600 - 1, timezone: 'Europe/Berlin'},
    themeRiver: {title: 'Archivleistung', aggregation: 'hour', unit: 'W', missingIntervals: 1,
        sources: [{id: 'variable-1', label: '<Same>', unit: 'W', decimals: 1, color: '#ABCDEF'},
            {id: 'variable-2', label: '<Same>', unit: 'W', decimals: 1, color: ''}],
        rows: [row(0, [10, 20]), row(1, [20, 10]), row(2, null), row(3, [1, 2]), row(4, [0, 0])],
        style: {showLegend: true, showTooltip: true, showLabels: true, showAxisLabels: true, showAxisTicks: true,
            bandOpacityPercent: 85, borderWidth: 2, borderColor: '#123456', titleFontSizePercent: 100, animationEnabled: false}}};
const tile = render(model);
const consumptionModel = {...model, themeRiver: {...model.themeRiver, metric: 'consumption', title: 'Interval energy', unit: 'kWh',
    sources: model.themeRiver.sources.map(source => ({...source, unit: 'kWh', decimals: 3})),
    rows: [row(0, [0.125, 0.375]), row(1, [0.25, 0.5]), row(2, null), row(3, [0.1, 0.2]), row(4, [0, 0])]}};
const consumption = render(consumptionModel);
assert.match(consumption.option.aria.label.description, /Completed interval consumption/,
    'Counter consumption must never be described as an interval mean.');
assert.doesNotMatch(consumption.option.aria.label.description, /mean/i);
assert.match(tile.option.aria.label.description, /Completed interval means/, 'A legacy model without metric retains average semantics.');
const designed = render({...model, themeRiver: {...model.themeRiver, style: {...model.themeRiver.style,
    bandHeightPercent: 50, borderType: 'dotted', shadowBlur: 8, shadowColor: '#123456', shadowOffsetX: 3, shadowOffsetY: -4,
    emphasisEnabled: false, emphasisFocus: 'none', blurOpacityPercent: 20, showAxisLine: false, axisLineWidth: 3,
    axisLineType: 'dashed', showGrid: true, gridLineWidth: 2, gridLineType: 'dotted', gridColor: '#334455', gridOpacityPercent: 35,
    axisLabelMargin: 22, timeLabelFormat: 'dateTime', legendPosition: 'top', legendAlignment: 'center',
    legendIcon: 'diamond', legendItemWidth: 30, legendItemHeight: 20, legendItemGap: 16,
    fontFamily: 'Georgia, serif', titleBold: false, axisLabelBold: true, labelBold: true, legendBold: true,
    titleItalic: true, axisLabelItalic: true, labelItalic: true, legendItalic: true}}});
assert.equal(designed.option.series[0].boundaryGap[0], '25%');
assert.equal(designed.option.series[0].itemStyle.borderType, 'dotted');
assert.equal(designed.option.series[0].itemStyle.shadowBlur, 8);
assert.equal(designed.option.series[0].itemStyle.shadowColor, '#123456');
assert.equal(designed.option.series[0].itemStyle.shadowOffsetX, 3);
assert.equal(designed.option.series[0].itemStyle.shadowOffsetY, -4);
assert.equal(designed.option.series[0].emphasis.disabled, true);
assert.equal(designed.option.series[0].emphasis.focus, 'none');
assert.equal(designed.option.series[0].blur.itemStyle.opacity, 0.17);
assert.equal(designed.option.singleAxis.axisLine.show, false);
assert.equal(designed.option.singleAxis.axisLine.lineStyle.width, 3);
assert.equal(designed.option.singleAxis.axisLine.lineStyle.type, 'dashed');
assert.equal(designed.option.singleAxis.axisLabel.margin, 22);
assert.match(designed.option.singleAxis.axisLabel.formatter(start * 1000), /16\.10\.\n\d{2}:\d{2}/);
assert.equal(designed.option.singleAxis.splitLine.show, true);
assert.equal(designed.option.singleAxis.splitLine.lineStyle.opacity, 0.35);
assert.equal(designed.option.legend.icon, 'diamond');
assert.equal(designed.option.legend.itemWidth, 30);
assert.equal(designed.option.legend.itemHeight, 20);
assert.equal(designed.option.legend.itemGap, 16);
assert.equal(designed.option.legend.left, 'center');
assert.equal(designed.option.title.textStyle.fontWeight, 'normal');
for (const text of [designed.option.singleAxis.axisLabel, designed.option.legend.textStyle, designed.option.graphic[0].style]) {
    assert.equal(text.fontFamily, 'Georgia, serif');
    assert.equal(text.fontWeight, 'bold');
    assert.equal(text.fontStyle, 'italic');
}
assert.equal(tile.option.series.length, 2, 'A gap must split ThemeRiver into independent contiguous sections.');
assert.equal(tile.option.series[0].type, 'themeRiver');
assert.equal(tile.option.series[0].itemStyle.opacity, 0.85);
assert.equal(tile.option.series[0].itemStyle.borderColor, '#123456');
assert.equal(tile.option.legend.data.length, 2);
assert.notEqual(tile.option.legend.data[0].name, tile.option.legend.data[1].name);
assert.equal(tile.option.legend.formatter('variable-1'), '<Same>');
assert.equal(tile.option.legend.formatter('variable-2'), '<Same>');
assert.ok(tile.option.title.top >= 58);
assert.ok(tile.option.singleAxis.top > tile.option.title.top + tile.option.title.textStyle.fontSize);
assert.equal(tile.option.animation, false);
assert.equal(tile.option.series[0].label.show, false, 'Native ThemeRiver labels ignore formatter and must not leak source IDs.');
assert.ok(tile.option.graphic.length > 0 && tile.option.graphic.every(label => label.style.text === '<Same>'));
assert.equal(tile.warning.hidden, false);
assert.match(tile.option.tooltip.formatter({data: tile.option.series[0].data[0]}), /&lt;Same&gt;: 10 W/);
assert.doesNotMatch(tile.option.tooltip.formatter({data: tile.option.series[0].data[0]}), /<Same>|variable-1/);
assert.equal(tile.option.tooltip.formatter({data: [0, 0, '', 2]}), '', 'A missing slot cannot yield a fabricated tooltip.');
tile.engine.convertFromPixel = () => (start + 2.5 * 3600) * 1000;
tile.events.mousemove({offsetX: 300, offsetY: 200});
assert.equal(tile.actions.at(-1).type, 'hideTip', 'Actual pointer gaps must hide tooltips even if ECharts would snap to a neighboring point.');
tile.engine.convertFromPixel = () => (start + 3.5 * 3600) * 1000;
tile.events.mousemove({offsetX: 500, offsetY: 200});
assert.equal(tile.actions.at(-1).type, 'showTip');
assert.match(tile.option.tooltip.formatter({data: tile.option.series[0].data[0]}), /&lt;Same&gt;: 1 W/);
tile.window.handleMessage({status: 'error', error: 'The ThemeRiver values could not be loaded.'});
assert.equal(tile.chart.hidden, false);
tile.window.handleMessage({status: 'error', error: 'Configure valid ThemeRiver archive variables.'});
assert.equal(tile.error.hidden, false);
tile.window.handleMessage({status: 'ready', chart: model});
assert.equal(tile.chart.hidden, false);
const empty = render({...model, themeRiver: {...model.themeRiver, rows: [row(0, null)]}});
assert.equal(empty.option, undefined);
assert.equal(empty.empty.hidden, false);
const zero = render({...model, themeRiver: {...model.themeRiver, rows: [row(0, [0, 0])]}});
assert.ok(zero.option.series.every(series => series.data.length === 0), 'All-zero segments cannot enter native ThemeRiver division by zero or invent epsilon values.');
assert.equal(zero.empty.hidden, false);
assert.equal(zero.chart.hidden, false, 'Real zero values keep the time axis and explanatory zero-state.');
const transparent = render({...model, themeRiver: {...model.themeRiver,
    style: {...model.themeRiver.style, bandOpacityPercent: 0, borderWidth: 0, showLabels: false, showAxisLabels: false, showAxisTicks: false, showLegend: false}}}, 'ipsview', 320);
assert.equal(transparent.option.backgroundColor, 'transparent');
assert.equal(transparent.option.series[0].itemStyle.opacity, 0);
assert.equal(transparent.option.series[0].label.show, false);
assert.equal(transparent.option.singleAxis.axisLabel.show, false);
assert.equal(transparent.option.singleAxis.axisTick.show, false);
assert.equal(transparent.option.legend.show, false);

const echarts = require(path.join(root, '.tools/echarts-runtime/node_modules/echarts'));
for (const mode of ['symcon', 'ipsview']) {
    for (const width of [320, 900]) {
        for (const legendPosition of ['top', 'bottom']) {
            for (const legendAlignment of ['left', 'center', 'right']) {
                for (const timeLabelFormat of ['auto', 'date', 'dateTime']) {
                    const option = render({...model, themeRiver: {...model.themeRiver, style: {...model.themeRiver.style,
                        legendPosition, legendAlignment, timeLabelFormat, axisLabelMargin: 30, fontFamily: 'monospace',
                        axisFontSizePercent: 200, legendFontSizePercent: 200, legendBold: true, axisLabelBold: true,
                        legendItemWidth: 40, legendItemHeight: 30, legendItemGap: 30, bandHeightPercent: 100, showGrid: true}}}, mode, width).option;
                    const engine = echarts.init(null, null, {renderer: 'svg', ssr: true, width, height: 500});
                    engine.setOption(option);
                    const rect = name => {
                        const view = engine.getViewOfComponentModel(engine.getModel().getComponent(name));
                        // The scroll content group includes clipped off-page entries; its background is the visible box.
                        const target = name === 'legend' ? view.group.children().find(child => child.type === 'rect') : view.group;
                        const bounds = target.getBoundingRect().clone();
                        bounds.applyTransform(target.getComputedTransform());
                        return bounds;
                    };
                    const legend = rect('legend'), title = rect('title'), axis = rect('singleAxis');
                    if (legendPosition === 'top') {
                        assert.ok(legend.y >= title.y + title.height + 10, 'Top legend must clear the chart title.');
                        assert.ok(option.singleAxis.top >= legend.y + legend.height + 14, 'Top legend must clear the band plot.');
                    } else {
                        assert.ok(legend.y >= axis.y + axis.height + 18, 'Two-line dates must not intrude into the bottom legend.');
                    }
                    assert.ok(legend.x >= 0 && legend.x + legend.width <= width + 1, 'Large scroll legend stays inside the viewport: ' + JSON.stringify({mode, width, legendPosition, legendAlignment, timeLabelFormat, legend}));
                    if (width === 900) {
                        if (legendAlignment === 'left') {assert.ok(legend.x < 20);}
                        if (legendAlignment === 'center') {assert.ok(Math.abs(legend.x + legend.width / 2 - width / 2) < 2);}
                        if (legendAlignment === 'right') {assert.ok(legend.x + legend.width > width - 20);}
                    }
                    const svg = engine.renderToSVGString();
                    assert.ok(!svg.includes('NaN') && !svg.includes('Infinity'));
                    assert.equal(engine.getModel().getSeries()[0].getData().count(), option.series[0].data.length);
                    engine.dispose();
                }
            }
        }
    }
}
const styledEngine = echarts.init(null, null, {renderer: 'svg', ssr: true, width: 900, height: 500});
styledEngine.setOption(designed.option);
const styledPolygon = styledEngine.getModel().getSeries()[0].getData().getItemGraphicEl(0);
assert.equal(styledPolygon.style.shadowBlur, 8, 'Shadow options must reach the actual native band geometry.');
assert.ok(styledPolygon.style.lineDash.length > 0, 'Dotted borders must produce actual dash geometry.');
assert.equal(styledPolygon.states.blur.style.opacity, 0.17);
styledEngine.dispose();
const zeroEngine = echarts.init(null, null, {renderer: 'svg', ssr: true, width: 900, height: 500});
zeroEngine.setOption(zero.option);
assert.equal(zeroEngine.getModel().getSeriesByName('variable-1').length, 1);
assert.ok(!zeroEngine.renderToSVGString().includes('NaN'));
zeroEngine.dispose();
for (const mode of ['symcon', 'ipsview']) {
    for (const width of [320, 900]) {
        const rendered = render(model, mode, width);
        const chart = echarts.init(null, null, {renderer: 'svg', ssr: true, width, height: 500});
        chart.setOption(rendered.option);
        const series = chart.getModel().getSeries();
        assert.equal(series.length, 2);
        let firstUnitHeight;
        for (let i = 0; i < series.length; i++) {
            const data = series[i].getData();
            assert.equal(data.count(), rendered.option.series[i].data.length, 'No native zero filling is allowed within complete segments.');
            const positions = Array.from({length: data.count()}, (_, index) => data.getItemLayout(index));
            assert.ok(positions.every(p => Number.isFinite(p.x) && Number.isFinite(p.y) && Number.isFinite(p.y0)));
            const item = positions[0];
            const normalized = rendered.option.series[i].data[0][1];
            if (i === 0) {firstUnitHeight = item.y / normalized;}
            else {assert.ok(Math.abs(item.y / normalized - firstUnitHeight) < 1e-9, 'All runs must preserve one value-to-pixel scale.');}
            const lower = i === 0 ? start : start + 3 * 3600;
            const upper = i === 0 ? start + 2 * 3600 : start + 5 * 3600;
            const lo = chart.convertToPixel({singleAxisIndex: 0}, lower * 1000)[0];
            const hi = chart.convertToPixel({singleAxisIndex: 0}, upper * 1000)[0];
            assert.ok(positions.every(p => p.x >= lo - 1e-6 && p.x <= hi + 1e-6), 'Segment points must never extend across archive gaps.');
            assert.equal(data.getName(0), 'variable-1');
            assert.equal(data.getItemVisual(0, 'style').fill, '#ABCDEF');
        }
        const svg = chart.renderToSVGString();
        assert.ok(svg.includes('<path') && !svg.includes('NaN') && !svg.includes('Infinity'));
        assert.ok(!/>variable-[12]</.test(svg), 'Actual engine output must use display names, never technical IDs.');
        const repeatedOption = render(model, mode, width).option;
        chart.setOption(repeatedOption, true);
        assert.ok(!chart.renderToSVGString().includes('NaN'), 'Rebuilding options after resize must not reuse ECharts-mutated boundaryGap values.');
        chart.dispose();
    }
}
const enormous = render({...model, themeRiver: {...model.themeRiver, rows: [row(0, [1e308, 1e308]), row(1, [0, 1e308])]}});
assert.ok(enormous.option.series[0].data.every(point => Number.isFinite(point[1])));
const longRange = render({...model, range: {...model.range, endTimestamp: start + 30 * 86400}});
assert.equal(longRange.option.singleAxis.axisLabel.formatter(start * 1000), '16.10.', 'Multi-day windows use a single calendar date without redundant midnight times.');
assert.match(tile.option.singleAxis.axisLabel.formatter(start * 1000), /^\d{2}:\d{2}$/, 'Short hourly windows retain clock times.');
for (const hours of [23, 24, 25]) {
    const daily = render({...model, range: {...model.range, endTimestamp: start + hours * 3600 - 1},
        themeRiver: {...model.themeRiver, aggregation: 'day'}}).option.singleAxis;
    assert.equal(daily.axisLabel.formatter(start * 1000), '16.10.');
    assert.equal(daily.axisLabel.showMinLabel, true, 'One completed day must retain its start date even across DST.');
    assert.equal(daily.axisLabel.showMaxLabel, false);
}
for (const days of [7, 30]) {
    for (const mode of ['symcon', 'ipsview']) {
        for (const width of [320, 630, 2048]) {
            for (const percent of [50, 100, 200]) {
                for (const showLegend of [true, false]) {
                    const output = render({...model, range: {...model.range, endTimestamp: start + days * 86400 - 1},
                        themeRiver: {...model.themeRiver, missingIntervals: 0, style: {...model.themeRiver.style,
                            axisFontSizePercent: percent, legendFontSizePercent: percent, showLegend}}}, mode, width);
                    const option = output.option;
                    assert.equal(option.singleAxis.minInterval, 86400000, 'Date-only labels need at least daily ticks.');
                    const engine = echarts.init(null, null, {renderer: 'svg', ssr: true, width, height: 500});
                    engine.setOption(option);
                    const axisView = engine.getViewOfComponentModel(engine.getModel().getComponent('singleAxis'));
                    const axisRect = axisView.group.getBoundingRect().clone();
                    axisRect.applyTransform(axisView.group.getComputedTransform());
                    if (showLegend) {
                        const legendView = engine.getViewOfComponentModel(engine.getModel().getComponent('legend'));
                        const legendRect = legendView.group.getBoundingRect().clone();
                        legendRect.applyTransform(legendView.group.getComputedTransform());
                        assert.ok(legendRect.y - axisRect.y - axisRect.height >= 18,
                            `Axis and legend need breathing room: ${mode}/${width}/${percent}/${days}: ${JSON.stringify({axisRect, legendRect})}.`);
                        assert.ok(legendRect.y + legendRect.height <= 500, 'Legend stays inside the viewport.');
                    }
                    assert.ok(axisRect.y + axisRect.height <= 486, 'Axis labels stay inside the viewport even without a legend.');
                    assert.equal(option.singleAxis.axisLabel.showMinLabel, false);
                    assert.equal(option.singleAxis.axisLabel.showMaxLabel, false);
                    // ECharts adds arbitrary range endpoints; they are hidden to avoid duplicate partial days.
                    const ticks = engine.getModel().getComponent('singleAxis').axis.scale.getTicks().slice(1, -1);
                    const dates = ticks.map(tick => option.singleAxis.axisLabel.formatter(tick.value));
                    assert.equal(new Set(dates).size, dates.length, 'Date-only time ticks must not repeat a calendar date: ' + JSON.stringify(ticks.map((t, i) => [new Date(t.value).toISOString(), dates[i]])));
                    engine.dispose();
                }
            }
        }
    }
}
const fragmented = render({...model, range: {...model.range, endTimestamp: start + 720 * 3600 - 1}, themeRiver: {...model.themeRiver,
    missingIntervals: 360, rows: Array.from({length: 720}, (_, i) => row(i, i % 2 ? null : [1, 2]))}});
assert.equal(fragmented.option.series.length, 360);
const fragmentedEngine = echarts.init(null, null, {renderer: 'svg', ssr: true, width: 900, height: 500});
fragmentedEngine.setOption(fragmented.option);
assert.ok(!fragmentedEngine.renderToSVGString().includes('NaN'), 'The maximal fragmented window must remain finite in the real engine.');
fragmentedEngine.dispose();
const sixteen = {...model, range: {...model.range, endTimestamp: start + 720 * 3600 - 1}, themeRiver: {...model.themeRiver,
    enableBandSelection: true, missingIntervals: 2,
    sources: Array.from({length: 16}, (_, i) => ({id: 'variable-' + (10000 + i), label: i < 2 ? '<Same>' : 'Source ' + i,
        unit: 'W', decimals: 2, initiallyVisible: i !== 1, color: i === 0 ? '#ABCDEF' : ''})),
    rows: Array.from({length: 720}, (_, i) => row(i, i === 200 || i === 500 ? null : Array.from({length: 16}, (_, j) => (i % 10 + 1) * (j + 1))))}};
const originalSixteen = JSON.stringify(sixteen);
function assertNativeFinite(engine) {
    assert.doesNotMatch(engine.renderToSVGString(), /NaN|Infinity|>variable-\d+</);
}
for (const mode of ['symcon', 'ipsview']) {
    const engine = echarts.init(null, null, {renderer: 'svg', ssr: true, width: 400, height: 500});
    const output = render(sixteen, mode, 400, 500, true, () => engine);
    assert.equal(output.option.legend.data.length, 16);
    assert.equal(output.option.legend.selectedMode, true);
    assert.equal(output.option.legend.selected['variable-10001'], false);
    assert.equal(output.option.series.filter(s => s.data.length).length, 3, 'Original null rows split three runs despite the hidden source.');
    assert.ok(output.option.series.filter(s => s.data.length).every(s => s.data.length % 15 === 0));
    assert.match(output.option.tooltip.formatter({data: output.option.series[0].data[0]}), /&lt;Same&gt;: 2 W/,
        'The tooltip retains hidden original values and units.');
    assertNativeFinite(engine);
    engine.dispatchAction({type: 'legendToggleSelect', name: 'variable-10000'});
    assert.equal(output.option.legend.selected['variable-10000'], false);
    assert.equal(output.option.legend.selected['variable-10001'], false, 'Same labels do not merge independent source identities.');
    engine.dispatchAction({type: 'legendScroll', scrollDataIndex: 8});
    const reversed = {...sixteen, themeRiver: {...sixteen.themeRiver,
        sources: [...sixteen.themeRiver.sources].reverse(),
        rows: sixteen.themeRiver.rows.map(r => ({...r, values: r.values && [...r.values].reverse()}))}};
    output.window.handleMessage({status: 'ready', chart: reversed});
    assert.equal(output.option.legend.selected['variable-10000'], false);
    assert.equal(output.option.legend.scrollDataIndex, 7, 'Legend scroll follows its source ID after reorder.');
    let unitScale;
    for (const series of engine.getModel().getSeries().filter(s => s.getData().count())) {
        const data = series.getData();
        const point = data.getItemLayout(0);
        const value = output.option.series.find(s => s.id === series.id).data[0][1];
        if (unitScale === undefined) {unitScale = point.y / value;}
        else {assert.ok(Math.abs(point.y / value - unitScale) < 1e-9, 'Selected bands retain a common scale over all gap-separated runs.');}
        assert.ok(Array.from({length: data.count()}, (_, i) => data.getItemLayout(i)).every(p => Number.isFinite(p.x) && Number.isFinite(p.y)));
    }
    engine.dispatchAction({type: 'legendAllSelect'});
    assert.ok(Object.values(output.option.legend.selected).every(Boolean));
    engine.dispatchAction({type: 'legendInverseSelect'});
    assert.ok(Object.values(output.option.legend.selected).every(v => v === false));
    assert.equal(output.empty.textContent, 'No bands selected.');
    assert.equal(output.empty.hidden, false);
    assert.ok(output.option.series.every(s => s.data.length === 0));
    assertNativeFinite(engine);
    engine.dispatchAction({type: 'legendToggleSelect', name: 'variable-10001'});
    assert.equal(output.empty.hidden, true, 'An all-hidden band remains selectable through its empty legend series.');
    assert.ok(output.option.series.filter(s => s.data.length).every(s => s.data.every(p => p[2] === 'variable-10001')));
    output.resize(650, 500);
    assert.equal(engine.getWidth(), 650);
    assert.equal(output.option.legend.selected['variable-10001'], true, 'ResizeObserver preserves selection without needing a new full state.');
    assert.equal(output.option.legend.selected['variable-10000'], false);
    output.window.handleMessage({status: 'ready', chart: reversed});
    assert.equal(output.option.legend.selected['variable-10001'], true);
    assert.equal(output.option.legend.selected['variable-10000'], false);
    const removed = {...reversed, themeRiver: {...reversed.themeRiver, sources: reversed.themeRiver.sources.slice(1),
        rows: reversed.themeRiver.rows.map(r => ({...r, values: r.values && r.values.slice(1)}))}};
    output.window.handleMessage({status: 'ready', chart: removed});
    assert.equal(Object.keys(output.option.legend.selected).length, 15);
    assert.equal(output.option.legend.selected['variable-10015'], undefined);
    const disabled = {...sixteen, themeRiver: {...sixteen.themeRiver, enableBandSelection: false}};
    output.window.handleMessage({status: 'ready', chart: disabled});
    assert.equal(output.option.legend.selectedMode, false);
    assert.ok(Object.values(output.option.legend.selected).every(Boolean), 'Disabling optional selection restores every source, ignoring initial flags.');
    assert.ok(output.option.series.every(s => s.data.length % 16 === 0));
    assertNativeFinite(engine);
    engine.dispose();
}
const allInitiallyHidden = render({...sixteen, themeRiver: {...sixteen.themeRiver,
    sources: sixteen.themeRiver.sources.map(s => ({...s, initiallyVisible: false})), style: {...sixteen.themeRiver.style, showLegend: false}}});
assert.equal(allInitiallyHidden.option.legend.show, true, 'Enabled selection keeps a visible way to restore hidden bands.');
assert.equal(allInitiallyHidden.empty.textContent, 'No bands selected.');
const zeroSelected = render({...model, themeRiver: {...model.themeRiver, enableBandSelection: true,
    rows: [row(0, [0, 100])], sources: model.themeRiver.sources.map((s, i) => ({...s, initiallyVisible: i === 0}))}});
assert.equal(zeroSelected.empty.textContent, 'All selected interval means are zero.');
assert.equal(zeroSelected.empty.hidden, false);
for (const mode of ['symcon', 'ipsview']) {
    const engine = echarts.init(null, null, {renderer: 'svg', ssr: true, width: 420, height: 500});
    const input = {...consumptionModel, themeRiver: {...consumptionModel.themeRiver, enableBandSelection: true,
        sources: consumptionModel.themeRiver.sources.map((source, index) => ({...source, initiallyVisible: index === 0}))}};
    const snapshot = JSON.stringify(input);
    const output = render(input, mode, 420, 500, true, () => engine);
    assert.match(output.option.aria.label.description, /Completed interval consumption/);
    assert.equal(output.option.series.filter(series => series.data.length).length, 2,
        'A hidden counter source cannot heal the shared consumption gap.');
    const tooltip = output.option.tooltip.formatter({data: output.option.series[0].data[0]});
    assert.match(tooltip, /&lt;Same&gt;: 0,125 kWh/);
    assert.match(tooltip, /&lt;Same&gt;: 0,375 kWh/, 'Hidden sources retain unmodified original counter consumption in the tooltip.');
    assert.doesNotMatch(tooltip, /mean|average|Mittelwert|variable-/i);
    assert.equal(output.option.tooltip.formatter({data: [0, 0, '', 2]}), '');
    assertNativeFinite(engine);
    engine.dispatchAction({type: 'legendAllSelect'});
    engine.dispatchAction({type: 'legendInverseSelect'});
    assert.equal(output.empty.textContent, 'No bands selected.');
    engine.dispatchAction({type: 'legendToggleSelect', name: input.themeRiver.sources[0].id});
    const selectedZeroConsumption = {...input, themeRiver: {...input.themeRiver, rows: [row(0, [0, 0.375])]}};
    output.window.handleMessage({status: 'ready', chart: selectedZeroConsumption});
    assert.equal(output.empty.hidden, false);
    assert.equal(output.empty.textContent, 'Consumption is zero in all selected bands.');
    const allZeroConsumption = {...input, themeRiver: {...input.themeRiver, rows: [row(0, [0, 0])]}};
    output.window.handleMessage({status: 'ready', chart: allZeroConsumption});
    assert.equal(output.empty.hidden, false);
    assert.equal(output.empty.textContent, 'Consumption is zero in all completed intervals.');
    assertNativeFinite(engine);
    assert.equal(JSON.stringify(input), snapshot, 'Consumption rendering never rewrites source values, units, gaps or selection defaults.');
    engine.dispose();
}
assert.equal(JSON.stringify(sixteen), originalSixteen, 'Selection cannot mutate any full original model values or gaps.');
const manyGaps = render({...sixteen, themeRiver: {...sixteen.themeRiver,
    missingIntervals: 360, rows: sixteen.themeRiver.rows.map((r, i) => ({...r,
        values: i % 2 ? null : Array.from({length: 16}, (_, j) => j + 1)}))}});
assert.equal(manyGaps.option.series.filter(s => s.data.length).length, 360);
const nativeManyGaps = echarts.init(null, null, {renderer: 'svg', ssr: true, width: 400, height: 500});
nativeManyGaps.setOption(manyGaps.option);
for (const series of nativeManyGaps.getModel().getSeries().filter(s => s.getData().count())) {
    assert.equal(series.getData().count(), 45, 'Each isolated interval contains three endpoints per visible source, with no native zero filling.');
}
assertNativeFinite(nativeManyGaps);
nativeManyGaps.dispose();
async function verifyCompressedStartup() {
    const delayed = render(sixteen, 'symcon', 400, 500, true, null, true);
    assert.equal(delayed.option, undefined);
    delayed.resolveStartup();
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(delayed.option.legend.data.length, 16);
    const superseded = render(sixteen, 'ipsview', 400, 500, true, null, true);
    superseded.window.handleMessage({status: 'ready', chart: model});
    superseded.resolveStartup();
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(superseded.option.legend.data.length, 2, 'A delayed compressed startup never overwrites a newer full state.');
    const unloaded = render(sixteen, 'symcon', 400, 500, true, null, true);
    unloaded.events.beforeunload();
    unloaded.resolveStartup();
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(unloaded.option, undefined, 'Unloading cancels asynchronous startup.');
}
verifyCompressedStartup().then(() => console.log('ThemeRiver options, 16-band selection, identity, refresh, scaling, gaps, compressed startup and real ECharts geometry verified.'))
    .catch(error => {console.error(error); process.exitCode = 1;});
