'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '..', 'EChartsHeatmap', 'visualization', 'app.js'), 'utf8');
const designSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-design.js'), 'utf8');
const palette = {
    background: '#101114', text: '#f4f5f7', muted: '#969aa2', border: '#999999',
    track: '#34363b', accent: '#55cbb5'
};

function render(model, width = 800, height = 420, mode = 'symcon') {
    const chartElement = { hidden: false, clientWidth: width, clientHeight: height };
    const warningElement = { hidden: true, textContent: '' };
    const emptyElement = { hidden: true, textContent: '' };
    const errorElement = { hidden: true, textContent: '' };
    let option;
    let chartWidth = width;
    let chartHeight = height;
    const listeners = {};
    const chart = {
        setOption: next => { option = next; }, clear: () => {}, dispose: () => {},
        resize: () => { chartWidth = chartElement.clientWidth; chartHeight = chartElement.clientHeight; },
        getWidth: () => chartWidth, getHeight: () => chartHeight
    };
    const window = {
        SYMC_VISUALIZATION: {
            mode, state: { status: 'ready', chart: model }, translations: {},
            options: { echartsThemes: { auto: palette, dark: palette }, tileHeaderVisible: true }
        },
        echarts: { init: () => chart },
        addEventListener: (name, listener) => { listeners[name] = listener; },
        getComputedStyle: () => ({ color: '' })
    };
    const document = {
        body: { appendChild: () => {} },
        createElement: () => ({ style: {}, remove: () => {} }),
        getElementById: id => ({
            'echarts-heatmap-chart': chartElement,
            'echarts-heatmap-warning': warningElement,
            'echarts-heatmap-empty': emptyElement,
            'echarts-heatmap-error': errorElement
        })[id]
    };
    vm.runInNewContext(designSource, { window });
    vm.runInNewContext(source, { window, document });
    return { get option() { return option; }, window, chartElement, warningElement, emptyElement, errorElement,
        resize: () => listeners.resize() };
}

const model = {
    theme: 'dark', heatmap: { title: 'Weekly climate', unit: '°C', decimals: 1,
        style: { showColorScale: true, showValues: true, lowColor: '#123456', highColor: '#fedcba' } },
    cells: [
        { hour: 0, weekday: 0, value: 0, count: 1 },
        { hour: 8, weekday: 6, value: 20, count: 4 }
    ], truncated: false
};
const wide = render(model, 1400, 520);
assert.equal(wide.option.series[0].type, 'heatmap');
assert.equal(wide.option.series[0].data.length, 2, 'No missing weekday/hour cells may be invented.');
assert.deepEqual(Array.from(wide.option.series[0].data[0].value), [0, 0, 0],
    'A measured zero must not be treated as an absent cell.');
assert.equal(wide.option.yAxis.inverse, true, 'Monday must appear at the top.');
assert.equal(wide.option.xAxis.data.length, 24);
assert.equal(wide.option.visualMap.orient, 'horizontal');
assert.equal(wide.option.visualMap.itemWidth, 12);
assert.deepEqual(Array.from(wide.option.visualMap.text, label => label.replace(',', '.')), ['20.0 °C', '0.0 °C'],
    'The visible color scale must label its high and low values with the source unit.');
assert.deepEqual(Array.from(wide.option.visualMap.inRange.color), ['#123456', '#fedcba']);
const presetColors = ['#123456', '#89abcd', '#fedcba'];
const presetModel = { ...model, heatmap: { ...model.heatmap,
    style: { ...model.heatmap.style, colorScalePreset: 'sample', colorScaleColors: presetColors } } };
const preset = render(presetModel, 360, 240, 'ipsview');
assert.deepEqual(Array.from(preset.option.visualMap.inRange.color), presetColors,
    'All preset stops must reach ECharts in source order in compact IPSView widgets.');
assert.deepEqual(Array.from(preset.option.visualMap.text, label => label.replace(',', '.')),
    ['20.0 °C', '0.0 °C'], 'A palette must keep the observed endpoint labels.');
const invalidPreset = render({ ...model, heatmap: { ...model.heatmap,
    style: { ...model.heatmap.style, colorScaleColors: ['#123456', 'invalid'] } } });
assert.deepEqual(Array.from(invalidPreset.option.visualMap.inRange.color), ['#123456', '#fedcba'],
    'Malformed palette data must fall back to the legacy two-color design.');
assert.equal(wide.option.series[0].label.show, true);
assert.equal(wide.emptyElement.hidden, true, 'Populated data must not show the empty-state hint.');
assert.match(wide.option.tooltip.formatter({ data: wide.option.series[0].data[1] }), /4 Hourly intervals/);
const beforeClick = wide.option;
wide.window.handleMessage({ type: 'click' });
assert.equal(wide.option, beforeClick, 'An unrelated click must not clear the chart.');
const compact = render(model, 360, 240, 'ipsview');
assert.equal(compact.option.series[0].data.length, 2);
assert.deepEqual(Array.from(compact.option.visualMap.text, label => label.replace(',', '.')), ['20.0 °C', '0.0 °C'],
    'Compact IPSView widgets need the same color-scale endpoints.');
assert.equal(compact.option.series[0].label.show, false,
    'Cell labels must yield when a compact widget has insufficient space.');
compact.chartElement.clientWidth = 1400;
compact.chartElement.clientHeight = 520;
compact.resize();
assert.equal(compact.option.series[0].label.show, true,
    'Resizing must recalculate the available room for value labels.');
wide.window.handleMessage({ status: 'error', error: 'The Heatmap values could not be loaded.' });
assert.equal(wide.errorElement.hidden, true, 'The first transient failure must preserve the last chart.');
wide.window.handleMessage({ status: 'error', error: 'The Heatmap values could not be loaded.' });
assert.equal(wide.warningElement.hidden, false, 'Repeated failures need a non-destructive warning.');
const empty = render({ ...model, cells: [] });
assert.equal(empty.emptyElement.hidden, false, 'An empty archive range needs a visible explanation.');
assert.equal(empty.option.visualMap.show, false, 'An empty range must not show a meaningless color scale.');
const constant = render({ ...model, cells: [{ hour: 8, weekday: 1, value: 9.2, count: 1 }] });
assert.deepEqual(Array.from(constant.option.visualMap.text, label => label.replace(',', '.')), ['9.2 °C', '9.2 °C'],
    'A single measured value must not display the artificial scale padding as its maximum.');

const localECharts = path.join(__dirname, '..', '.tools', 'echarts-runtime', 'node_modules', 'echarts');
if (fs.existsSync(localECharts)) {
    const echarts = require(localECharts);
    const actual = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 1400, height: 520 });
    actual.setOption(wide.option);
    assert.equal(actual.getModel().getSeriesByIndex(0).getData().count(), 2,
        'ECharts 6.1.0 must accept the Heatmap option without inventing missing cells.');
    const svg = actual.renderToSVGString();
    assert.match(svg, /<svg/, 'The real ECharts engine must render the Heatmap.');
    assert.match(svg, /20[,.]0 °C/, 'The ECharts runtime must render the upper color-scale endpoint.');
    assert.match(svg, /0[,.]0 °C/, 'The ECharts runtime must render the lower color-scale endpoint.');
    actual.dispose();
    const presetChart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 360, height: 240 });
    presetChart.setOption(preset.option);
    assert.match(presetChart.renderToSVGString(), /<svg/,
        'The real ECharts engine must accept a multi-stop palette in a compact widget.');
    presetChart.dispose();
}

console.log('Heatmap renderer layout verified.');
