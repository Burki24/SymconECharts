'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.join(__dirname, '..');
const source = fs.readFileSync(path.join(root, 'EChartsThemeRiver/visualization/app.js'), 'utf8');
const designSource = fs.readFileSync(path.join(root, 'libs/echarts-design.js'), 'utf8');
const palette = {background: '#101114', text: '#f4f5f7', muted: '#969aa2', border: '#737780', accent: '#55cbb5', seriesColors: ['#5070DD', '#B6D634']};
function render(model, mode = 'symcon', width = 900, height = 500, headerVisible = true) {
    const elements = Object.fromEntries(['chart', 'error', 'empty', 'warning'].map(name => [name,
        {hidden: name !== 'chart', textContent: '', clientWidth: width, clientHeight: height}]));
    let option;
    const events = {}, actions = [];
    const chart = {setOption: next => {option = next;}, clear: () => {}, resize: () => {}, dispose: () => {},
        getZr: () => ({on: (event, handler) => {events[event] = handler;}}),
        convertFromPixel: () => model.range.startTimestamp * 1000,
        dispatchAction: action => actions.push(action)};
    const window = {SYMC_VISUALIZATION: {mode, language: 'de', state: {status: 'ready', chart: model}, translations: {},
        options: {echartsThemes: {auto: palette, dark: palette}, tileHeaderVisible: headerVisible, adaptToBackground: mode === 'ipsview'}},
        echarts: {init: () => chart}, addEventListener: () => {}, getComputedStyle: () => ({color: ''})};
    const document = {body: {appendChild: () => {}}, createElement: () => ({style: {}, remove: () => {}}),
        getElementById: id => elements[id.replace('echarts-theme-river-', '')]};
    vm.runInNewContext(designSource, {window});
    vm.runInNewContext(source, {window, document});
    return {get option() {return option;}, ...elements, window, engine: chart, events, actions};
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
console.log('ThemeRiver options, time labels, footer spacing, pointers, gaps, identity, scaling and real ECharts geometry verified.');
