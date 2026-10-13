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
    const classLegendElement = { hidden: true, style: {}, setAttribute: () => {} };
    const classMinElement = { textContent: '' };
    const classSegmentsElement = { innerHTML: '' };
    const classMaxElement = { textContent: '' };
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
            'echarts-heatmap-error': errorElement,
            'echarts-heatmap-class-legend': classLegendElement,
            'echarts-heatmap-class-min': classMinElement,
            'echarts-heatmap-class-segments': classSegmentsElement,
            'echarts-heatmap-class-max': classMaxElement
        })[id]
    };
    vm.runInNewContext(designSource, { window });
    vm.runInNewContext(source, { window, document });
    return { get option() { return option; }, window, chartElement, warningElement, emptyElement, errorElement,
        classLegendElement, classMinElement, classSegmentsElement, classMaxElement,
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
const userPalette = render({ ...model, cells: [
    { hour: 0, weekday: 0, value: 0, count: 1 },
    { hour: 1, weekday: 0, value: 5, count: 1 },
    { hour: 2, weekday: 0, value: 10, count: 1 },
    { hour: 3, weekday: 0, value: 15, count: 1 },
    { hour: 4, weekday: 0, value: 20, count: 1 }
], heatmap: { ...model.heatmap, style: { ...model.heatmap.style,
    lowColor: '#111111', highColor: '#eeeeee', customMiddleColors: ['#ee1111', '#11ee11', '#1111ee'] } } });
assert.deepEqual(Array.from(userPalette.option.visualMap.inRange.color),
    ['#111111', '#ee1111', '#11ee11', '#1111ee', '#eeeeee'],
    'The custom gradient must keep up to three intermediate colors in user order.');
assert.equal(userPalette.option.series[0].data[0].label.color, '#FFFFFF',
    'Automatic value labels must be light on a dark cell.');
assert.equal(userPalette.option.series[0].data[4].label.color, '#111111',
    'Automatic value labels must be dark on a bright cell.');
const explicitLabel = render({ ...userPalette.window.SYMC_VISUALIZATION.state.chart,
    heatmap: { ...userPalette.window.SYMC_VISUALIZATION.state.chart.heatmap,
        style: { ...userPalette.window.SYMC_VISUALIZATION.state.chart.heatmap.style,
            valueColor: '#AABBCC' } } });
assert.equal(explicitLabel.option.series[0].data[0].label.color, '#AABBCC',
    'An explicit value color must override adaptive contrast.');
const presetColors = ['#123456', '#89abcd', '#fedcba'];
const presetModel = { ...model, cells: [
    { hour: 0, weekday: 0, value: -10, count: 1 },
    { hour: 8, weekday: 6, value: 20, count: 4 },
    { hour: 12, weekday: 3, value: 0, count: 1 }
],
    heatmap: { ...model.heatmap,
    style: { ...model.heatmap.style, colorScalePreset: 'sample', colorScaleColors: presetColors,
        colorScaleRange: { min: -10, max: 20 } } } };
const preset = render(presetModel, 360, 240, 'ipsview');
assert.equal(preset.option.visualMap.type, 'piecewise',
    'A sourced palette must assign classes instead of interpolating between color stops.');
assert.deepEqual(Array.from(preset.option.visualMap.pieces, piece => piece.color), presetColors,
    'All preset classes must reach ECharts in source order in compact IPSView widgets.');
assert.equal(preset.option.visualMap.show, false,
    'A compact widget must not show one oversized ECharts legend item per class.');
assert.equal(preset.option.visualMap.pieces[0].lt, 0);
assert.equal(preset.option.visualMap.pieces.at(-1).gte, 10);
assert.equal(preset.classLegendElement.hidden, false);
assert.equal(preset.classMinElement.textContent.replace(',', '.'), '-10.0 °C');
assert.equal(preset.classMaxElement.textContent.replace(',', '.'), '20.0 °C');
assert.equal((preset.classSegmentsElement.innerHTML.match(/<span /g) || []).length, 3,
    'The compact legend must show every distinct class without a text label for each one.');
const twentyColors = Array.from({ length: 20 }, (_, index) =>
    '#' + (index * 0x0d0d0d).toString(16).padStart(6, '0'));
const twentyClassPreset = render({ ...presetModel, heatmap: { ...presetModel.heatmap,
    style: { ...presetModel.heatmap.style, colorScaleColors: twentyColors } } }, 260, 240, 'symcon');
assert.equal(twentyClassPreset.option.visualMap.pieces.length, 20);
assert.equal((twentyClassPreset.classSegmentsElement.innerHTML.match(/<span /g) || []).length, 20,
    'Even a 20-class preset must use one compact bar, not 20 labeled legend entries.');
const hiddenPreset = render({ ...presetModel, heatmap: { ...presetModel.heatmap,
    style: { ...presetModel.heatmap.style, showColorScale: false } } });
assert.equal(hiddenPreset.option.visualMap.type, 'piecewise');
assert.equal(hiddenPreset.classLegendElement.hidden, true,
    'Disabling the legend must not disable classed cell colors.');
const emptyPreset = render({ ...presetModel, cells: [] });
assert.equal(emptyPreset.classLegendElement.hidden, true,
    'An empty archive range must not show a meaningless class legend.');
const constantPreset = render({ ...presetModel, cells: [{ hour: 8, weekday: 1, value: 9.2, count: 1 }] });
assert.equal(constantPreset.classMaxElement.textContent.replace(',', '.'), '20.0 °C',
    'A constant archive range must not shift a preset class boundary.');
const noaaColors = ['#7A8484', '#C2C3C3', '#ECF7FE', '#FBE0ED', '#C198C5', '#6F3A8F', '#223F92',
    '#008CCE', '#00ADE4', '#9DD7F1', '#009641', '#78B927', '#B0CB01', '#F1E400', '#FDC100',
    '#F29019', '#E73C2F', '#E11213', '#BD4693', '#F082B2'];
const noaa = render({ ...presetModel, cells: [
    { hour: 0, weekday: 0, value: 1.5, count: 1 },
    { hour: 1, weekday: 0, value: 9, count: 1 },
    { hour: 2, weekday: 0, value: 22, count: 1 }
], heatmap: { ...presetModel.heatmap, style: { ...presetModel.heatmap.style,
    colorScalePreset: 'noaa2023', colorScaleColors: noaaColors,
    colorScaleRange: { min: -50, max: 50 } } } });
assert.equal(noaa.option.visualMap.pieces.length, 20);
assert.equal(noaa.option.visualMap.pieces[0].lt, -45);
assert.equal(noaa.option.visualMap.pieces.at(-1).gte, 45);
assert.equal(noaa.classMinElement.textContent.replace(',', '.'), '-50.0 °C');
assert.equal(noaa.classMaxElement.textContent.replace(',', '.'), '50.0 °C');
assert.equal(noaa.option.series[0].data[2].label.color, '#111111',
    'The yellow NOAA cell in the reported IPSView case needs dark value text.');
const invalidPreset = render({ ...model, heatmap: { ...model.heatmap,
    style: { ...model.heatmap.style, colorScalePreset: 'sample',
        colorScaleColors: ['#123456', 'invalid'], customMiddleColors: ['#abcdef'] } } });
assert.equal(invalidPreset.option.visualMap.type, 'continuous');
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
    const userChart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 1400, height: 520 });
    userChart.setOption(userPalette.option);
    const userData = userChart.getModel().getSeriesByIndex(0).getData();
    assert.deepEqual([0, 1, 2, 3, 4].map(index => userData.getItemVisual(index, 'style').fill),
        ['rgba(17,17,17,1)', 'rgba(238,17,17,1)', 'rgba(17,238,17,1)',
            'rgba(17,17,238,1)', 'rgba(238,238,238,1)'],
        'The real ECharts runtime must preserve all five selected color stops.');
    assert.equal(userData.getItemModel(0).get(['label', 'color']), '#FFFFFF');
    assert.equal(userData.getItemModel(4).get(['label', 'color']), '#111111',
        'The real ECharts runtime must accept per-cell contrast colors.');
    const userSvg = userChart.renderToSVGString();
    assert.match(userSvg, /<svg/,
        'The real ECharts runtime must render the custom palette with adaptive labels.');
    assert.match(userSvg, /<text[^>]*fill="#111111"/,
        'Bright cells must render dark text, not merely retain a configuration value.');
    assert.match(userSvg, /<text[^>]*fill="#FFFFFF"/,
        'Dark cells must render light text, not merely retain a configuration value.');
    userChart.dispose();
    const presetChart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 360, height: 240 });
    presetChart.setOption(preset.option);
    assert.match(presetChart.renderToSVGString(), /<svg/,
        'The real ECharts engine must accept a multi-stop palette in a compact widget.');
    const presetData = presetChart.getModel().getSeriesByIndex(0).getData();
    assert.equal(presetData.getItemVisual(0, 'style').fill, '#123456');
    assert.equal(presetData.getItemVisual(1, 'style').fill, '#fedcba',
        'The actual ECharts engine must color cells by class rather than interpolate.');
    assert.equal(presetData.getItemVisual(2, 'style').fill, '#89abcd',
        'A middle value must receive the exact middle class color.');
    presetChart.dispose();
    const noaaChart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 1400, height: 520 });
    noaaChart.setOption(noaa.option);
    const noaaData = noaaChart.getModel().getSeriesByIndex(0).getData();
    assert.deepEqual([0, 1, 2].map(index => noaaData.getItemVisual(index, 'style').fill),
        ['#009641', '#78B927', '#FDC100'],
        'NOAA colors must follow fixed 5 °C classes, not the observed 1.5–22 °C range.');
    noaaChart.dispose();
    const noaaOutliers = render({ ...noaa.window.SYMC_VISUALIZATION.state.chart, cells: [
        { hour: 0, weekday: 0, value: -55, count: 1 },
        { hour: 1, weekday: 0, value: 55, count: 1 }
    ] });
    const outlierChart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 1400, height: 520 });
    outlierChart.setOption(noaaOutliers.option);
    const outlierData = outlierChart.getModel().getSeriesByIndex(0).getData();
    assert.deepEqual([0, 1].map(index => outlierData.getItemVisual(index, 'style').fill),
        ['#7A8484', '#F082B2'], 'Values outside the preset range must retain its end colors.');
    outlierChart.dispose();
}

console.log('Heatmap renderer layout verified.');
