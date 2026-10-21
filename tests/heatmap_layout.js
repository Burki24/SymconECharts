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

function render(model, width = 800, height = 420, mode = 'symcon', fetchImpl = null) {
    const chartElement = { hidden: false, clientWidth: width, clientHeight: height };
    const warningElement = { hidden: true, textContent: '' };
    const emptyElement = { hidden: true, textContent: '' };
    const errorElement = { hidden: true, textContent: '' };
    const classLegendElement = { hidden: true, style: {}, setAttribute: () => {} };
    const classMinElement = { textContent: '' };
    const classSegmentsElement = { innerHTML: '' };
    const classMaxElement = { textContent: '' };
    const periodElement = { hidden: false, style: {}, setAttribute: () => {} };
    const periodLabelElement = { textContent: '', setAttribute: () => {} };
    const buttonListeners = {};
    const previousElement = { disabled: false, setAttribute: () => {},
        addEventListener: (_, listener) => { buttonListeners.previous = listener; } };
    const nextElement = { disabled: false, setAttribute: () => {},
        addEventListener: (_, listener) => { buttonListeners.next = listener; } };
    const todayElement = { disabled: false, textContent: '',
        addEventListener: (_, listener) => { buttonListeners.today = listener; } };
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
            options: { echartsThemes: { auto: palette, dark: palette }, tileHeaderVisible: mode === 'symcon', tileHeaderInset: mode === 'symcon' ? 58 : 0,
                heatmapStatePath: '/hook/SymconECharts/state/5000/0123456789abcdef0123456789abcdef' }
        },
        echarts: { init: () => chart },
        location: { protocol: 'https:', host: 'symcon.example.test' },
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
            'echarts-heatmap-class-max': classMaxElement,
            'echarts-heatmap-period': periodElement,
            'echarts-heatmap-period-label': periodLabelElement,
            'echarts-heatmap-previous': previousElement,
            'echarts-heatmap-next': nextElement,
            'echarts-heatmap-today': todayElement
        })[id]
    };
    vm.runInNewContext(designSource, { window });
    vm.runInNewContext(source, { window, document, fetch: fetchImpl || (() => Promise.reject(new Error('No fetch stub.'))) });
    return { get option() { return option; }, window, chartElement, warningElement, emptyElement, errorElement,
        classLegendElement, classMinElement, classSegmentsElement, classMaxElement,
        periodElement, periodLabelElement, previousElement, nextElement, todayElement,
        click: name => buttonListeners[name](),
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
const datedModel = { ...model, heatmap: { ...model.heatmap, range: '1w', windowOffset: 0,
    startDate: '2026-10-07', endDate: '2026-10-13', days: [
        { date: '2026-10-07', weekday: 2 }, { date: '2026-10-08', weekday: 3 },
        { date: '2026-10-09', weekday: 4 }, { date: '2026-10-10', weekday: 5 },
        { date: '2026-10-11', weekday: 6 }, { date: '2026-10-12', weekday: 0 },
        { date: '2026-10-13', weekday: 1 }
    ] }, cells: [
    { hour: 8, weekday: 2, value: 10, count: 1 },
    { hour: 8, weekday: 1, value: 20, count: 1 }
] };
const dated = render(datedModel, 1400, 520);
assert.equal(dated.option.yAxis.data[0], 'Wed 07.10.',
    'The first row of seven days must be the oldest actual local date, not Monday.');
assert.equal(dated.option.yAxis.data[6], 'Tue 13.10.');
assert.deepEqual(Array.from(dated.option.series[0].data, item => item.value[1]), [0, 6],
    'Weekday buckets must map to chronological seven-day rows.');
assert.match(dated.option.tooltip.formatter({ data: dated.option.series[0].data[1] }), /13\.10\.2026/,
    'A seven-day tooltip must identify the actual date.');
const month = render({ ...model, heatmap: { ...model.heatmap, range: '1m', windowOffset: 0,
    startDate: '2026-09-01', endDate: '2026-09-30', days: [] } }, 600, 400);
assert.equal(month.option.yAxis.data[0], 'Mon',
    'Calendar-month averages must retain seven Monday–Sunday rows.');
assert.match(month.periodLabelElement.textContent, /01\.09\.2026 – 30\.09\.2026 \(1-month average\)/,
    'The complete calendar month and its averaging must both be visible.');
assert.match(month.option.tooltip.formatter({ data: month.option.series[0].data[1] }),
    /Average of 4 hourly intervals/,
    'A calendar-month cell must identify the averaging behind its displayed value.');
const extendedModels = ['custom', 'absolute'].map(range => ({ ...model, heatmap: {
    ...model.heatmap, range, viewKey: range + '-original', windowOffset: 0, maxOffset: 120,
    startDate: '2026-09-28', endDate: '2026-10-13', days: [], timezone: 'Europe/Berlin',
    requestedStartLabel: '2026-09-28 08:15:00 +02:00',
    requestedEndLabel: '2026-10-13 12:45:00 +02:00'
} }));
for (const extended of extendedModels) {
    for (const mode of ['symcon', 'ipsview']) {
        for (const width of [360, 1400]) {
            const view = render(extended, width, 520, mode);
            assert.equal(view.option.yAxis.data.length, 7);
            assert.equal(view.option.yAxis.data[0], 'Mon');
            assert.equal(view.option.series[0].data.length, model.cells.length,
                'Custom and fixed periods must not invent absent weekday/hour cells.');
            assert.match(view.periodLabelElement.textContent, /2026-09-28 08:15:00 \+02:00/);
            assert.match(view.periodLabelElement.textContent, /2026-10-13 12:45:00 \+02:00/);
            assert.match(view.periodLabelElement.textContent, /Europe\/Berlin/);
            assert.match(view.periodLabelElement.textContent, /Until \(exclusive\)/);
            assert.match(view.option.aria.description, /2026-09-28 08:15:00/);
            assert.match(view.option.tooltip.formatter({ data: view.option.series[0].data[1] }),
                /2026-10-13 12:45:00 \+02:00/);
            assert.equal(view.todayElement.textContent, extended.heatmap.range === 'absolute' ? 'Selected period' : 'Today');
            if (fs.existsSync(path.join(__dirname, '..', '.tools', 'echarts-runtime', 'node_modules', 'echarts'))) {
                const echarts = require('../.tools/echarts-runtime/node_modules/echarts');
                const actual = echarts.init(null, null, { renderer: 'svg', ssr: true, width, height: 520 });
                actual.setOption(view.option);
                assert.match(actual.renderToSVGString(), /<svg/);
                assert.equal(actual.getModel().getSeriesByIndex(0).getData().count(), 2);
                actual.dispose();
            }
        }
    }
}
for (const [metric, label, countLabel] of [
    ['minimum', 'Minimum of hourly minima', 'Minimum of 4 hourly intervals'],
    ['maximum', 'Maximum of hourly maxima', 'Maximum of 4 hourly intervals'],
    ['consumption', 'Sum of hourly consumption', 'Consumption total of 4 hourly intervals']
]) {
    for (const mode of ['symcon', 'ipsview']) {
        const view = render({ ...model, heatmap: { ...month.window.SYMC_VISUALIZATION.state.chart.heatmap,
            metric, unit: '<kWh&>' } }, 600, 400, mode);
        assert.match(view.periodLabelElement.textContent, /\(1 month\)/);
        assert.ok(!view.periodLabelElement.textContent.includes('average'));
        assert.ok(view.periodLabelElement.textContent.includes(label));
        assert.ok(view.option.aria.description.includes(label));
        const tooltip = view.option.tooltip.formatter({ data: view.option.series[0].data[1] });
        assert.ok(tooltip.includes(countLabel));
        assert.match(tooltip, /01\.09\.2026 – 30\.09\.2026/);
        assert.ok(tooltip.includes('&lt;kWh&amp;&gt;') && !tooltip.includes('<kWh&>'));
    }
}
for (const [range, label] of [
    ['3m', '3-month average'], ['6m', '6-month average'],
    ['12m', '12-month average'], ['ytd', 'Since January 1 average']
]) {
    const view = render({ ...model, heatmap: { ...model.heatmap, range, windowOffset: 0,
        startDate: '2026-01-01', endDate: '2026-10-13', days: [], maxOffset: 0 } }, 600, 400);
    assert.match(view.periodLabelElement.textContent, new RegExp(label));
    assert.equal(view.previousElement.disabled, true,
        'The renderer must honor the range-specific navigation boundary.');
}
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
assert.match(wide.option.tooltip.formatter({ data: wide.option.series[0].data[1] }), /Average of 4 hourly intervals/);
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

async function verifyNavigation() {
    const previousModel = { ...datedModel, heatmap: { ...datedModel.heatmap,
        windowOffset: 1, startDate: '2026-09-30', endDate: '2026-10-06' } };
    const requests = [];
    const navigable = render(datedModel, 600, 400, 'symcon', url => {
        requests.push(url);
        const chart = url.includes('offset=1') ? previousModel : datedModel;
        return Promise.resolve({ ok: true, json: () => Promise.resolve({ status: 'ready', chart }) });
    });
    assert.equal(navigable.nextElement.disabled, true, 'Next must be disabled at the current period.');
    navigable.click('previous');
    await new Promise(resolve => setImmediate(resolve));
    assert.match(requests[0], /offset=1&output=tile$/,
        'Native tile navigation must request the previous archive period through its scoped hook.');
    assert.match(navigable.periodLabelElement.textContent, /30\.09\.2026 – 06\.10\.2026/);
    assert.equal(navigable.nextElement.disabled, false);
    navigable.window.handleMessage({ status: 'ready', chart: datedModel });
    assert.match(navigable.periodLabelElement.textContent, /30\.09\.2026/,
        'A periodic live push must not reset a user-selected historical window.');
    await new Promise(resolve => setImmediate(resolve));
    assert.match(requests[1], /offset=1&output=tile$/,
        'A live push must refresh the selected historical window, not replace it with today.');
    navigable.click('next');
    await new Promise(resolve => setImmediate(resolve));
    assert.match(requests[2], /offset=0&output=tile$/);
    assert.equal(navigable.nextElement.disabled, true);
    const ipsView = render(datedModel, 600, 400, 'ipsview', url => {
        requests.push(url);
        return Promise.resolve({ ok: true, json: () => Promise.resolve({ status: 'ready', chart: previousModel }) });
    });
    ipsView.click('previous');
    await new Promise(resolve => setImmediate(resolve));
    assert.match(requests[3], /offset=1$/,
        'IPSView navigation must use the same read-only state endpoint without tile styling.');
    const failing = render(datedModel, 600, 400, 'symcon', () => Promise.reject(new Error('offline')));
    const retainedOption = failing.option;
    failing.click('previous');
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(failing.option, retainedOption,
        'A failed historical request must retain the last good Heatmap.');
    assert.equal(failing.warningElement.hidden, false,
        'A failed historical request must be visible without replacing the chart.');
    let resolveOld;
    const changing = render(datedModel, 600, 400, 'symcon', () => new Promise(resolve => { resolveOld = resolve; }));
    changing.click('previous');
    changing.window.handleMessage({ status: 'ready', chart: { ...datedModel,
        heatmap: { ...datedModel.heatmap, metric: 'maximum' } } });
    resolveOld({ ok: true, json: () => Promise.resolve({ status: 'ready', chart: previousModel }) });
    await new Promise(resolve => setImmediate(resolve));
    assert.ok(changing.periodLabelElement.textContent.includes('Maximum of hourly maxima'),
        'A delayed average navigation response must not overwrite a newly selected maximum metric.');
    for (const extended of extendedModels) {
        const oldPeriod = { ...extended, heatmap: { ...extended.heatmap, windowOffset: 1,
            requestedStartLabel: '2026-09-13 03:45:00 +02:00',
            requestedEndLabel: extended.heatmap.requestedStartLabel } };
        let resolvePeriod;
        const browsing = render(extended, 360, 520, 'ipsview', () => new Promise(resolve => { resolvePeriod = resolve; }));
        browsing.click('previous');
        resolvePeriod({ ok: true, json: () => Promise.resolve({ status: 'ready', chart: oldPeriod }) });
        await new Promise(resolve => setImmediate(resolve));
        assert.match(browsing.periodLabelElement.textContent, /2026-09-13 03:45:00/);
        browsing.window.handleMessage({ status: 'ready', chart: extended });
        assert.match(browsing.periodLabelElement.textContent, /2026-09-13 03:45:00/,
            'A refresh with the same view identity must preserve historical browsing.');
        resolvePeriod({ ok: true, json: () => Promise.resolve({ status: 'ready', chart: oldPeriod }) });
        await new Promise(resolve => setImmediate(resolve));
        browsing.click('today');
        resolvePeriod({ ok: true, json: () => Promise.resolve({ status: 'ready', chart: extended }) });
        await new Promise(resolve => setImmediate(resolve));
        assert.equal(browsing.nextElement.disabled, true);
        for (const changedSetting of ['duration', 'fixed-bounds', 'source', 'style']) {
            let resolvePending;
            const pending = render(extended, 600, 400, 'symcon', () => new Promise(resolve => { resolvePending = resolve; }));
            pending.click('previous');
            const changed = { ...extended, heatmap: { ...extended.heatmap,
                viewKey: extended.heatmap.viewKey + '-' + changedSetting,
                requestedStartLabel: '2026-10-01 00:00:00 +02:00' } };
            pending.window.handleMessage({ status: 'ready', chart: changed });
            resolvePending({ ok: true, json: () => Promise.resolve({ status: 'ready', chart: oldPeriod }) });
            await new Promise(resolve => setImmediate(resolve));
            assert.match(pending.periodLabelElement.textContent, /2026-10-01 00:00:00/,
                'A changed ' + changedSetting + ' must reject an in-flight response for the previous view.');
            assert.equal(pending.nextElement.disabled, true,
                'A changed view must reset navigation even when the range name remains unchanged.');
        }
    }
}

verifyNavigation().then(() => console.log('Heatmap renderer layout verified.'))
    .catch(error => { console.error(error); process.exitCode = 1; });
