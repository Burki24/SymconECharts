'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const zlib = require('node:zlib');

const source = fs.readFileSync(
    path.join(__dirname, '..', 'EChartsBarHistory', 'visualization', 'app.js'),
    'utf8'
);
const designSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-design.js'), 'utf8');
const zoomSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-zoom.js'), 'utf8');
const patternSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-pattern.js'), 'utf8');
const startupSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-startup.js'), 'utf8');
const archiveStackSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-archive-stack.js'), 'utf8');
const selectionSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-series-selection.js'), 'utf8');

function render(truncated = false, mode = 'symcon', adaptToBackground = false, enableZoom = true, enableImages = false, width = 750, height = 420, initialState = null, actualChart = null, deferredStartup = false) {
    const listeners = {};
    const chartListeners = {};
    const windowListeners = {};
    const images = [];
    const scheduled = [];
    function TestImage() { images.push(this); }
    Object.defineProperty(TestImage.prototype, 'src', { set(value) { this.source = value; } });
    const chartElement = {
        hidden: false,
        clientWidth: width,
        clientHeight: height,
        addEventListener: (name, listener) => { listeners[name] = listener; },
        getBoundingClientRect: () => ({ left: 0, top: 0, width, height })
    };
    const warningElement = { hidden: true, textContent: '' };
    const errorElement = { hidden: true, textContent: '' };
    let option;
    let initCount = 0;
    let updateCount = 0;
    let resizeCount = 0;
    let clearCount = 0;
    let resizeCallback;
    let chartWidth = width;
    let chartHeight = height;
    let dispatchedAction;
    const chart = actualChart || {
        setOption: next => { option = next; updateCount++; },
        getOption: () => option,
        getWidth: () => chartWidth,
        getHeight: () => chartHeight,
        dispatchAction: action => { dispatchedAction = action; },
        clear: () => { clearCount++; },
        resize: () => {
            resizeCount++;
            chartWidth = chartElement.clientWidth;
            chartHeight = chartElement.clientHeight;
        },
        dispose: () => {}
    };
    if (actualChart) {
        const nativeSetOption = actualChart.setOption.bind(actualChart);
        chart.setOption = (next, ...args) => { option = next; updateCount++; nativeSetOption(next, ...args); };
        const nativeResize = chart.resize.bind(chart);
        chart.resize = () => { resizeCount++; nativeResize({ width: chartElement.clientWidth, height: chartElement.clientHeight }); };
    } else {
        chart.on = (event, callback) => { chartListeners[event] = callback; };
    }
    let resolveStartup;
    let startupJSON;
    const palette = {
        background: '#101114', text: '#f4f5f7', muted: '#969aa2', border: '#a5a9b0',
        accent: '#55cbb5', seriesColors: ['#5070dd']
    };
    const window = {
        SYMC_VISUALIZATION: {
            mode,
            state: initialState || {
                status: 'ready',
                chart: {
                    theme: 'dark',
                    bar: {
                        title: 'History', timeAxisLabelFormat: 'date-time', unit: '°C', decimals: 1,
                        style: {
                            showValues: true, showGrid: false, roundedBars: true,
                            barWidthPercent: 60, enableZoom
                        }
                    },
                    range: { key: '24h', dataMode: 'auto', startTimestamp: 1780000000, endTimestamp: 1780000400 },
                    series: [{
                        label: 'Temperature', color: '#e5754f',
                        points: [[1780000100, 21.5], [1780000200, 22.0]]
                    }],
                    truncated
                }
            },
            translations: {},
            options: {
                echartsThemes: { auto: palette, dark: palette }, tileHeaderVisible: mode === 'symcon', tileHeaderInset: mode === 'symcon' ? 58 : 0,
                adaptToBackground
            }
        },
        echarts: { init: () => { initCount++; return chart; } },
        ResizeObserver: class {
            constructor(callback) { resizeCallback = callback; }
            observe() {}
        },
        Image: enableImages ? TestImage : undefined,
        setTimeout: callback => { scheduled.push(callback); },
        addEventListener: (event, callback) => { windowListeners[event] = callback; },
        getComputedStyle: () => ({ color: '' })
    };
    if (deferredStartup) {
        startupJSON = JSON.stringify(window.SYMC_VISUALIZATION.state);
        window.SYMC_VISUALIZATION.options.compressedStateGzipBase64 = zlib.gzipSync(startupJSON).toString('base64');
        window.SYMC_VISUALIZATION.state = null;
        window.atob = value => Buffer.from(value, 'base64').toString('binary');
        window.Blob = class { constructor(parts) { this.bytes = parts[0]; }
            stream() { return { pipeThrough: () => this.bytes }; } };
        window.DecompressionStream = class { constructor(format) { assert.equal(format, 'gzip'); } };
        window.Response = class { constructor(bytes) {
            startupJSON = zlib.gunzipSync(Buffer.from(bytes)).toString('utf8');
        } text() { return new Promise(resolve => { resolveStartup = resolve; }); } };
    }
    const document = {
        body: { appendChild: () => {} },
        createElement: tag => tag === 'canvas'
            ? { width: 0, height: 0, getContext: () => ({ drawImage: () => {} }) }
            : { style: {}, remove: () => {} },
        getElementById: id => ({
            'echarts-bar-history-chart': chartElement,
            'echarts-bar-history-warning': warningElement,
            'echarts-bar-history-error': errorElement
        })[id]
    };
    const context = { window, document, ResizeObserver: window.ResizeObserver };
    vm.runInNewContext(designSource, context);
    vm.runInNewContext(zoomSource, context);
    vm.runInNewContext(patternSource, context);
    vm.runInNewContext(startupSource, context);
    vm.runInNewContext(archiveStackSource, context);
    vm.runInNewContext(selectionSource, context);
    vm.runInNewContext(source, context);
    return {
        get option() { return option; },
        get initCount() { return initCount; },
        get updateCount() { return updateCount; },
        get resizeCount() { return resizeCount; },
        get clearCount() { return clearCount; },
        get dispatchedAction() { return dispatchedAction; },
        getState: () => window.SYMC_VISUALIZATION.state,
        listeners, chartListeners, windowListeners, warningElement, errorElement, chartElement, window, images,
        resolveStartup: () => resolveStartup(startupJSON),
        resizeTo: (nextWidth, nextHeight) => {
            chartElement.clientWidth = nextWidth;
            chartElement.clientHeight = nextHeight;
            resizeCallback();
        },
        flush: () => { while (scheduled.length > 0) { scheduled.shift()(); } }
    };
}

const ready = render();
assert.ok(ready.option, 'The Historical Bar chart should render.');
assert.equal(ready.option.xAxis.type, 'time');
assert.equal(ready.option.dataZoom.length, 2);
assert.equal(ready.option.dataZoom[0].zoomOnMouseWheel, true);
assert.equal(ready.option.dataZoom[1].height, 12,
    'The History zoom slider should be slim in a regular tile.');
assert.ok(ready.option.dataZoom[1].bottom >= 20,
    'The regular zoom slider must clear the lower tile edge.');
assert.equal(ready.option.xAxis.min, 1780000000000);
assert.equal(ready.option.yAxis.type, 'value');
assert.equal(ready.option.yAxis.name, '°C');
assert.equal(ready.option.yAxis.splitLine.show, false);
assert.equal(ready.option.series[0].type, 'bar');
assert.equal(ready.option.series[0].barMaxWidth, 29);
assert.equal(ready.option.series[0].itemStyle.color, '#e5754f');
assert.equal(ready.option.series[0].itemStyle.opacity, 1);
assert.equal(ready.option.title.textStyle.fontSize, 18);
assert.deepEqual(Array.from(ready.option.series[0].itemStyle.borderRadius), [6, 6, 0, 0]);
assert.deepEqual(Array.from(ready.option.series[0].data[0]), [1780000100000, 21.5]);
assert.equal(ready.warningElement.hidden, true);

const compact = render(false, 'symcon', false, true, false, 433, 213);
assert.ok(213 - compact.option.grid.top - compact.option.grid.bottom >= 70,
    'A short BarHistory tile must reserve a readable plotting area (top '
        + compact.option.grid.top + ', bottom ' + compact.option.grid.bottom + ').');
assert.equal(compact.option.xAxis.axisLabel.hideOverlap, true,
    'Dense time labels must not overlap in a narrow tile.');
assert.ok(compact.option.xAxis.splitNumber <= 3,
    'The time axis must request fewer ticks when horizontal space is limited.');
assert.ok(compact.option.dataZoom[1].height <= 8,
    'The compact zoom slider must not dominate the short tile.');
assert.ok(compact.option.dataZoom[1].bottom >= 18,
    'The compact zoom slider must clear the lower tile edge.');
assert.ok(compact.option.grid.bottom >= compact.option.dataZoom[1].bottom
    + compact.option.dataZoom[1].height + 14,
    'The zoom slider must stay below the compact time-axis labels.');
compact.window.handleMessage({
    status: 'ready',
    chart: {
        ...compact.getState().chart,
        bar: { ...compact.getState().chart.bar, title: '' },
        axes: [
            { unit: '°C', position: 'left', positionIndex: 0 },
            { unit: '%', position: 'right', positionIndex: 0 }
        ],
        series: [
            { id: 'temperature', label: 'Temperatur', axisIndex: 0, points: [[1780000100, 21.5]] },
            { id: 'humidity', label: 'Feuchtigkeit', axisIndex: 1, points: [[1780000100, 49]] }
        ]
    }
});
assert.ok(213 - compact.option.grid.top - compact.option.grid.bottom >= 70,
    'A short two-axis History tile must keep enough height beneath the legend.');
assert.equal(compact.option.legend.show, true);
assert.equal(compact.option.yAxis[0].axisLabel.hideOverlap, true);
assert.equal(compact.option.yAxis[1].axisLabel.hideOverlap, true);
const localECharts = path.join(__dirname, '..', '.tools', 'echarts-runtime', 'node_modules', 'echarts');
if (fs.existsSync(localECharts)) {
    const echarts = require(localECharts);
    const actualChart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 433, height: 213 });
    actualChart.setOption(compact.option);
    const actualGrid = actualChart.getModel().getComponent('grid').coordinateSystem.getRect();
    assert.ok(actualGrid.height >= 45,
        'ECharts must retain a usable plot after reserving axis labels in the short tile.');
    assert.ok(actualChart.renderToSVGString().includes('<svg'));
    actualChart.dispose();
}

const resizedHistory = render();
resizedHistory.resizeTo(433, 213);
assert.equal(resizedHistory.resizeCount, 1);
assert.equal(resizedHistory.updateCount, 2,
    'A real tile resize must rebuild the responsive History option.');
assert.ok(213 - resizedHistory.option.grid.top - resizedHistory.option.grid.bottom >= 70);
resizedHistory.resizeTo(433, 213);
assert.equal(resizedHistory.updateCount, 2,
    'An unchanged observer notification must not restart the chart.');

ready.window.handleMessage({
    status: 'ready',
    chart: {
        theme: 'dark',
        bar: {
            title: 'Styled history', unit: '°C', decimals: 1,
            style: {
                showValues: true, showGrid: true, roundedBars: true,
                barWidthPercent: 60, barFillMode: 'gradient', barGradientColor: '#55ccaa',
                barOpacityPercent: 65, barCornerRadius: 10,
                titleFontSizePercent: 130, legendFontSizePercent: 150, axisFontSizePercent: 115,
                valueFontSizePercent: 125, titleColor: '#ffeedd',
                valueLabelPosition: 'bottom', valueLabelDistance: 12,
                legendColor: '#abcdef', axisColor: '#ddeeff', valueColor: '#112233', gridColor: '#445566',
                animationEnabled: true, animationEasing: 'bounceOut',
                animationEasingUpdate: 'linear', animationDelay: 120,
                animationDelayUpdate: 240
            }
        },
        series: [{ label: 'Temperature', color: '#e5754f', points: [[1780000300, 23.0]] }]
    }
});
assert.equal(ready.option.title.textStyle.color, '#ffeedd');
assert.equal(ready.option.animationEasing, 'bounceOut');
assert.equal(ready.option.animationEasingUpdate, 'linear');
assert.equal(ready.option.animationDelay, 120);
assert.equal(ready.option.animationDelayUpdate, 240);
assert.equal(ready.option.title.textStyle.fontSize, 23);
assert.equal(ready.option.legend.textStyle.fontSize, 18);
assert.equal(ready.option.legend.textStyle.color, '#abcdef');
assert.equal(ready.option.xAxis.axisLabel.color, '#ddeeff');
assert.equal(ready.option.xAxis.axisLabel.fontSize, 14);
assert.equal(ready.option.xAxis.axisLine.lineStyle.color, '#ddeeff');
assert.equal(ready.option.yAxis.nameTextStyle.color, '#ddeeff');
assert.equal(ready.option.yAxis.nameTextStyle.fontSize, 14);
assert.equal(ready.option.yAxis.splitLine.lineStyle.color, '#445566');
assert.equal(ready.option.yAxis.splitLine.lineStyle.opacity, 1);
assert.equal(ready.option.series[0].label.color, '#112233');
assert.equal(ready.option.series[0].label.fontSize, 15);
assert.equal(ready.option.series[0].label.position, 'bottom');
assert.equal(ready.option.series[0].label.distance, 12);
assert.equal(ready.option.series[0].itemStyle.opacity, 0.65);
assert.deepEqual(Array.from(ready.option.series[0].itemStyle.borderRadius), [10, 10, 0, 0]);
assert.equal(ready.option.series[0].itemStyle.color.type, 'linear');
assert.equal(ready.option.series[0].itemStyle.color.colorStops[0].color, '#e5754f');
assert.equal(ready.option.series[0].itemStyle.color.colorStops[1].color, '#55ccaa');

const patterned = render(false, 'symcon', false, true, true);
patterned.window.handleMessage({
    status: 'ready',
    chart: {
        theme: 'dark',
        bar: { title: 'Pattern', unit: '°C', decimals: 1, style: {
            barFillMode: 'svg', barPatternImage: 'data:image/svg+xml;base64,PHN2Zy8+',
            barPatternAspectRatio: 2, barSVGSizePercent: 125
        } },
        series: [{ label: 'Temperature', color: '#e5754f', points: [[1780000300, 23.0]] }]
    }
});
assert.equal(patterned.option.series[0].itemStyle.color, '#e5754f');
assert.equal(patterned.images.length, 1);
patterned.images[0].onload();
patterned.flush();
assert.equal(patterned.option.series[0].itemStyle.color.repeat, 'repeat');
assert.equal(patterned.option.series[0].itemStyle.color.image.width, 80);
assert.equal(patterned.option.series[0].itemStyle.color.image.height, 40);
patterned.window.handleMessage({
    status: 'ready', chart: { theme: 'dark', bar: { style: { barFillMode: 'solid' } },
        series: [{ label: 'Temperature', color: '#e5754f', points: [[1780000300, 23.0]] }] }
});
assert.equal(patterned.option.series[0].itemStyle.color, '#e5754f');

const truncated = render(true);
assert.equal(truncated.warningElement.hidden, false);
assert.match(truncated.warningElement.textContent, /truncated/);

const ipsView = render(false, 'ipsview', true);
assert.equal(ipsView.option.backgroundColor, 'transparent');
assert.equal(ipsView.initCount, 1);
ipsView.window.handleMessage({
    status: 'ready',
    chart: {
        theme: 'dark',
        bar: { unit: '°C', decimals: 1, style: {} },
        series: [{ label: 'Temperature', points: [[1780000300, 23.0]] }]
    }
});
assert.equal(ipsView.initCount, 1, 'An IPSView update must reuse the existing chart.');
assert.equal(ipsView.updateCount, 2);
assert.deepEqual(Array.from(ipsView.option.series[0].data[0]), [1780000300000, 23.0]);

const disabledZoom = render(false, 'symcon', false, false);
assert.equal(disabledZoom.option.dataZoom.length, 0);
assert.equal(disabledZoom.option.grid.bottom, 58);

const retainedZoom = render();
retainedZoom.option.dataZoom[0].start = 25;
retainedZoom.option.dataZoom[0].end = 75;
const refreshedState = retainedZoom.getState();
retainedZoom.window.handleMessage({
    status: 'error', chart: null,
    error: 'The Historical Bar values could not be loaded.'
});
assert.equal(retainedZoom.chartElement.hidden, false,
    'One failed archive refresh must not remove the last usable chart.');
assert.equal(retainedZoom.errorElement.hidden, true);
assert.equal(retainedZoom.warningElement.hidden, true);
assert.equal(retainedZoom.clearCount, 0);
retainedZoom.window.handleMessage({
    status: 'error', chart: null,
    error: 'The Historical Bar values could not be loaded.'
});
assert.equal(retainedZoom.chartElement.hidden, false);
assert.equal(retainedZoom.warningElement.hidden, false,
    'Repeated archive failures must be visible without discarding the chart.');
retainedZoom.window.handleMessage({
    status: 'ready',
    chart: {
        ...refreshedState.chart,
        range: { ...refreshedState.chart.range, startTimestamp: 1780000060, endTimestamp: 1780000460 }
    }
});
assert.equal(retainedZoom.option.dataZoom[0].start, 25,
    'An archive refresh must keep the selected zoom window.');
assert.equal(retainedZoom.option.dataZoom[1].end, 75);
assert.equal(retainedZoom.warningElement.hidden, true,
    'A successful refresh must clear the temporary archive warning.');
retainedZoom.window.handleMessage({
    status: 'ready',
    chart: {
        ...refreshedState.chart,
        range: { ...refreshedState.chart.range, key: '7d' }
    }
});
assert.equal(retainedZoom.option.dataZoom[0].start, 0,
    'A changed configured range must reset the zoom window.');

const invalidConfiguration = render();
invalidConfiguration.window.handleMessage({
    status: 'error', chart: null,
    error: 'Configure 1 to 16 valid Historical Bar sources.'
});
assert.equal(invalidConfiguration.chartElement.hidden, true,
    'A real configuration error must replace the stale chart.');
assert.equal(invalidConfiguration.errorElement.hidden, false);

const initialArchiveFailure = render(false, 'symcon', false, true, false, 750, 420, {
    status: 'error', chart: null,
    error: 'The Historical Bar values could not be loaded.'
});
assert.equal(initialArchiveFailure.chartElement.hidden, true,
    'An initial archive failure must still show its error.');
assert.equal(initialArchiveFailure.errorElement.hidden, false);

const ipsViewZoom = render(false, 'ipsview');
assert.equal(ipsViewZoom.option.dataZoom[0].zoomOnMouseWheel, false);
const compactIPSViewZoom = render(false, 'ipsview', false, true, false, 433, 213);
assert.equal(compactIPSViewZoom.option.dataZoom[1].height, 8);
assert.equal(compactIPSViewZoom.option.dataZoom[1].bottom, 18);
let prevented = false;
ipsViewZoom.listeners.wheel({
    deltaY: -100, clientX: 375,
    preventDefault: () => { prevented = true; }, stopPropagation: () => {}
});
assert.equal(prevented, true);
assert.equal(ipsViewZoom.dispatchedAction.type, 'dataZoom');
assert.equal(ipsViewZoom.dispatchedAction.start, 10);
assert.equal(ipsViewZoom.dispatchedAction.end, 90);
const horizontalState = ipsViewZoom.getState();
ipsViewZoom.window.handleMessage({ ...horizontalState, chart: { ...horizontalState.chart,
    bar: { ...horizontalState.chart.bar, style: { enableZoom: true, orientation: 'horizontal' } }
} });
ipsViewZoom.listeners.wheel({
    deltaY: -100, clientX: 600, clientY: 105,
    preventDefault() {}, stopPropagation() {}
});
assert.equal(ipsViewZoom.dispatchedAction.start, 5, 'Horizontal bars must anchor wheel zoom at the pointer time on Y.');
assert.equal(ipsViewZoom.dispatchedAction.end, 85);

const multi = render();
multi.window.handleMessage({
    status: 'ready',
    chart: {
        theme: 'dark',
        bar: { title: 'Several sources', style: { showValues: true } },
        axes: [
            { unit: '°C', position: 'left', positionIndex: 0 },
            { unit: '%', position: 'right', positionIndex: 0 },
            { unit: 'hPa', position: 'left', positionIndex: 1 }
        ],
        series: [
            { id: 'variable-4711', label: 'Climate', unit: '°C', decimals: 1,
                axisIndex: 0, points: [[1780000100, 21.5]] },
            { id: 'variable-4713', label: 'Climate', unit: '%', decimals: 0,
                axisIndex: 1, points: [[1780000100, 58]] },
            { id: 'variable-4714', label: 'Pressure', unit: 'hPa', decimals: 2,
                axisIndex: 2, points: [[1780000100, 1013.25]] }
        ]
    }
});
assert.equal(multi.option.legend.show, true);
assert.deepEqual(Array.from(multi.option.legend.data),
    ['variable-4711', 'variable-4713', 'variable-4714']);
assert.equal(multi.option.legend.formatter('variable-4713'), 'Climate');
assert.equal(multi.option.yAxis.length, 3);
assert.equal(multi.option.yAxis[2].position, 'left');
assert.ok(multi.option.yAxis[2].offset > 0);
assert.deepEqual(Array.from(multi.option.series, item => item.yAxisIndex), [0, 1, 2]);
assert.equal(multi.option.series[0].stack, undefined);
assert.equal(multi.option.series[1].barGap, '20%');
assert.ok(multi.option.series[0].barMaxWidth < ready.option.series[0].barMaxWidth);
const tooltip = multi.option.tooltip.formatter([
    { seriesIndex: 0, value: [1780000100000, 21.5], marker: '*' },
    { seriesIndex: 1, value: [1780000100000, 58], marker: '*' }
]);
assert.match(tooltip, /21.5 °C/);
assert.match(tooltip, /58 %/);

for (const mode of ['symcon', 'ipsview']) {
    for (const orientation of ['vertical', 'horizontal']) {
        const horizontal = orientation === 'horizontal';
        const axisKey = horizontal ? 'xAxis' : 'yAxis';
        const gridSides = horizontal ? ['bottom', 'top'] : ['left', 'right'];
        const renderSpacing = (valueAxisSpacing, zoomSliderSize = 0) => render(false, mode, false, true, false, 750, 600, {
            status: 'ready', chart: {
                bar: { style: { orientation, valueAxisSpacing, enableZoom: true, zoomSliderSize } },
                axes: ['left', 'left', 'right', 'right'].map(position => ({ unit: '°C', position })),
                series: [0, 1, 2, 3].map(axisIndex => ({ id: 's' + axisIndex, axisIndex,
                    points: [[1780000100, 21.5]] }))
            }
        });
        const automatic = renderSpacing(0).option;
        const touchSlider = renderSpacing(0, 32).option;
        const dimension = horizontal ? 'width' : 'height';
        const reservedSide = horizontal ? 'right' : 'bottom';
        assert.equal(automatic.dataZoom[1][dimension], 12);
        assert.equal(touchSlider.dataZoom[1][dimension], 32);
        assert.equal(touchSlider.grid[reservedSide] - automatic.grid[reservedSide], 20);
        assert.deepEqual({ ...automatic.grid }, { ...renderSpacing(undefined).option.grid });
        const layout = renderSpacing(24);
        assert.deepEqual(Array.from(layout.option[axisKey], axis => axis.offset), [0, 24, 0, 24]);
        const automaticStep = horizontal ? 12 * 2.8 : 54;
        assert.equal(automatic[axisKey][1].offset, automaticStep);
        for (const side of gridSides) {
            assert.ok(Math.abs(automatic.grid[side] - layout.option.grid[side] - (automaticStep - 24)) < 1e-6,
                'Reducing the spacing must return the reserved margin to the plot.');
        }
        const large = renderSpacing(120);
        assert.equal(large.option[axisKey][3].offset, 120);
        large.resizeTo(320, 195);
        assert.ok(large.option[axisKey][3].offset < 120, 'Limit repeated axes on a small display after a resize.');
    }
}

const manySources = Array.from({ length: 32 }, (_, index) => ({
    id: 'variable-' + (10000 + index), variableID: 10000 + index,
    label: index < 2 ? 'Same label' : 'Synthetic sensor ' + (index + 1),
    axisIndex: index % 2, decimals: 1, unit: index % 2 ? '%' : '°C',
    initiallyVisible: index < 8,
    // Preserve independently sampled time stamps rather than constructing shared categories.
    points: [[1780000000 + index, index], [1780000200 + index, index + 1]]
}));
const manyState = { status: 'ready', chart: {
    theme: 'dark', bar: { title: '32 sources', style: { enableZoom: true } },
    range: { key: 'day', dataMode: 'raw', startTimestamp: 1780000000, endTimestamp: 1780000400 },
    axes: [{ unit: '°C', position: 'left' }, { unit: '%', position: 'right' }],
    series: manySources, truncated: false
} };
const many = render(false, 'symcon', false, true, false, 420, 520, manyState);
for (const orientation of ['vertical', 'horizontal']) {
    const horizontal = orientation === 'horizontal';
    const valueDim = horizontal ? 'x' : 'y';
    const option = render(false, 'symcon', false, true, false, 620, 520, { status: 'ready', chart: {
        ...manyState.chart, bar: { style: { orientation } },
        axes: [{ unit: '°C', position: 'left', minimum: -20, maximum: 40 },
            { unit: '°C', position: 'right', minimum: 20, maximum: 60 }],
        series: manySources.slice(0, 2).map(item => ({ ...item, unit: '°C' }))
    } }).option;
    for (const [index, axis] of option[valueDim + 'Axis'].entries()) {
        assert.equal(axis.position, horizontal ? ['bottom', 'top'][index] : ['left', 'right'][index]);
        assert.equal(axis.min, [-20, 20][index]);
        assert.equal(axis.max, [40, 60][index]);
        assert.equal(option.series[index][valueDim + 'AxisIndex'], index);
    }
}
assert.equal(many.option.legend.type, 'scroll');
assert.equal(many.option.legend.selected['variable-10008'], false);
assert.equal(many.option.series.length, 32);
assert.equal(many.option.series[8].data.length, 2);
assert.equal(many.option.legend.formatter('variable-10001'), 'Same label');
assert.equal(many.option.legend.selector.length, 2);
const eightVisibleWidth = many.option.series[0].barMaxWidth;
many.option.legend.selected['variable-10000'] = false;
many.option.legend.scrollDataIndex = 10;
many.option.dataZoom[0].start = 20;
many.option.dataZoom[0].end = 70;
many.window.handleMessage({ status: 'ready', chart: { ...manyState.chart, series: [...manySources].reverse() } });
assert.equal(many.option.legend.selected['variable-10000'], false);
assert.equal(many.option.legend.selected['variable-10001'], true);
assert.equal(many.option.legend.scrollDataIndex, 21);
assert.equal(many.option.dataZoom[0].start, 20);
assert.ok(many.option.series[0].barMaxWidth > eightVisibleWidth,
    'Hidden rows must not consume visible bar width.');
assert.equal(many.option.series[31].data[0][0], 1780000000000);
assert.equal(many.option.series[30].data[0][0], 1780000001000);
const idTooltip = many.option.tooltip.formatter([
    { seriesId: 'variable-10000', seriesIndex: 0, value: [1780000000000, 21.5], marker: '*' }
]);
assert.match(idTooltip, /21.5 °C/, 'Tooltip source lookup must use stable IDs, not stale numeric indexes.');
many.resizeTo(600, 520);
assert.equal(many.option.legend.selected['variable-10000'], false);
assert.equal(many.option.dataZoom[0].start, 20);
many.window.handleMessage({ status: 'ready', chart: { ...manyState.chart,
    series: manySources.map(item => item.id === 'variable-10000' ? { ...item, initiallyVisible: false } : item) } });
many.window.handleMessage({ status: 'ready', chart: { ...manyState.chart,
    series: manySources.map(item => item.id === 'variable-10000' ? { ...item, initiallyVisible: true } : item) } });
assert.equal(many.option.legend.selected['variable-10000'], true);
assert.equal(many.option.legend.selected['variable-10008'], false);
const oneHidden = render(false, 'symcon', false, true, false, 420, 520, { status: 'ready', chart: {
    ...manyState.chart, series: [{ ...manySources[0], initiallyVisible: false }]
} });
assert.equal(oneHidden.option.legend.show, true,
    'An explicitly hidden single source must remain selectable without changing its configuration.');
assert.equal(oneHidden.option.yAxis.show, false);
const sparseAxes = render(false, 'symcon', false, true, false, 420, 520, { status: 'ready', chart: {
    ...manyState.chart,
    axes: Array.from({ length: 16 }, (_, index) => ({ unit: 'u' + index,
        position: index % 2 ? 'right' : 'left', positionIndex: Math.floor(index / 2) })),
    series: Array.from({ length: 32 }, (_, index) => ({ ...manySources[index], axisIndex: index % 16,
        initiallyVisible: index === 14 || index === 15 }))
} });
assert.equal(sparseAxes.option.yAxis.filter(axis => axis.show).length, 2);
assert.equal(sparseAxes.option.yAxis[14].offset, 0);
assert.equal(sparseAxes.option.yAxis[15].offset, 0,
    'Remaining unit axes must compact their offsets rather than reserve hidden-axis space.');
assert.equal(sparseAxes.option.yAxis[14].splitLine.show, true);
assert.ok(sparseAxes.option.grid.left < 100 && sparseAxes.option.grid.right < 100);
const escaped = render(false, 'symcon', false, true, false, 420, 520, { status: 'ready', chart: {
    ...manyState.chart, series: [{ ...manySources[0], label: '<img onerror=alert(1)>', unit: '<unit>' }]
} });
const escapedText = escaped.option.tooltip.formatter([
    { seriesId: manySources[0].id, value: [1780000000000, 20] }
]);
assert.doesNotMatch(escapedText, /<img/);
assert.match(escapedText, /&lt;unit&gt;/);

const echarts = require(path.join(__dirname, '..', '.tools', 'echarts-runtime', 'node_modules', 'echarts'));
function verifyStackedBars(mode, orientation) {
    const native = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 640, height: 400 });
    const points = values => values.map(([index, value]) => [1780000000 + index * 60, value]);
    const sources = [
        { id: 'east', stackGroup: 1, points: points([[1, 2], [2, 9], [3, -4], [4, 0]]) },
        { id: 'west', stackGroup: 1, points: points([[0, 8], [1, 3], [3, -2], [4, 4]]) },
        { id: 'other', stackGroup: 2, points: points([[2, 7]]) },
        { id: 'single', points: points([[2, 10]]) }
    ];
    const test = render(false, mode, false, true, false, 640, 400, { status: 'ready', chart: {
        ...ready.getState().chart, series: sources,
        bar: { ...ready.getState().chart.bar, style: { ...ready.getState().chart.bar.style, orientation } }
    } }, native);
    const [east, west, other, single] = test.option.series;
    assert.ok(east.stack && east.stack === west.stack);
    assert.notEqual(other.stack, east.stack);
    assert.equal(single.stack, undefined);
    assert.deepEqual(Array.from(east.data, p => p[1]), [2, -4, 0]);
    assert.deepEqual(Array.from(west.data, p => p[0]), Array.from(east.data, p => p[0]));
    assert.equal(other.data[0][1], 7);
    assert.equal(single.data[0][1], 10);
    const totals = () => {
        const data = native.getModel().getSeriesByIndex(1).getData();
        return Array.from({ length: data.count() }, (_, i) => data.get(data.getCalculationInfo('stackResultDimension'), i));
    };
    assert.deepEqual(totals(), [5, -6, 4]);
    const horizontal = orientation === 'horizontal';
    const timeDim = horizontal ? 'y' : 'x';
    const valueDim = horizontal ? 'x' : 'y';
    assert.equal(native.getModel().getSeriesByIndex(0).coordinateSystem.getBaseAxis().dim, timeDim);
    assert.equal(native.getOption()[timeDim + 'Axis'][0].type, 'time');
    assert.equal(native.getOption()[valueDim + 'Axis'][0].type, 'value');
    assert.equal(native.getOption()[timeDim + 'Axis'][0].inverse, horizontal);
    for (const zoom of native.getOption().dataZoom) {
        assert.deepEqual([].concat(zoom[timeDim + 'AxisIndex']), [0]);
        assert.equal(zoom[valueDim + 'AxisIndex'], undefined);
    }
    const data = native.getModel().getSeriesByIndex(0).getData();
    const early = data.getItemLayout(0);
    const late = data.getItemLayout(1);
    assert.ok(early[timeDim] < late[timeDim], 'Newer periods must appear farther right or lower down.');
    const valueExtent = horizontal ? 'width' : 'height';
    assert.ok(early[valueExtent] * late[valueExtent] < 0, 'Opposite signs must extend in opposite directions.');
    assert.match(east.label.formatter({ value: east.data[0] }), /^2[.,]0 °C$/);
    assert.match(test.option.tooltip.formatter([{ seriesIndex: 0, value: east.data[0] }]), /2[.,]0 °C/);
    assert.equal(east.label.position, horizontal ? 'right' : 'top');
    native.dispatchAction({ type: 'legendToggleSelect', name: 'east' });
    assert.deepEqual(totals(), [3, -2, 4]);
    native.dispose();
}
for (const mode of ['symcon', 'ipsview']) {
    for (const orientation of ['vertical', 'horizontal']) {
        verifyStackedBars(mode, orientation);
    }
}
function verifyLargeArchive(mode, orientation) {
    const native = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 420, height: 520 });
    const stressSources = manySources.map((item, row) => ({ ...item,
        points: Array.from({ length: 1000 }, (_, index) => [1780000000 + index * 60 + row,
            Number((20 + Math.sin(index)).toFixed(2))])
    }));
    const state = { status: 'ready', chart: { ...manyState.chart, series: stressSources,
        bar: { ...manyState.chart.bar, style: { ...manyState.chart.bar.style, orientation } },
        range: { ...manyState.chart.range, endTimestamp: 1780060000 } } };
    const test = render(false, mode, false, true, false, 420, 520, state, native);
    function option() { return native.getOption(); }
    const valueAxis = orientation === 'horizontal' ? 'xAxis' : 'yAxis';
    assert.equal(option().series.length, 32);
    assert.equal(option().series.reduce((sum, item) => sum + item.data.length, 0), 32000);
    assert.equal(option().legend[0].type, 'scroll');
    native.dispatchAction({ type: 'legendToggleSelect', name: 'variable-10000' });
    native.dispatchAction({ type: 'legendScroll', scrollDataIndex: 12 });
    native.dispatchAction({ type: 'dataZoom', dataZoomIndex: 0, start: 20, end: 80 });
    const grid = native.getModel().getComponent('grid').coordinateSystem.getRect();
    assert.ok(grid.height > 0 && grid.width > 0, 'Zoom with two visible units must leave a drawable plot.');
    test.window.handleMessage({ status: 'ready', chart: { ...state.chart,
        series: [...stressSources].reverse().filter(item => item.id !== 'variable-10002') } });
    assert.equal(option().legend[0].selected['variable-10000'], false);
    assert.equal(option().legend[0].selected['variable-10001'], true);
    assert.equal(option().legend[0].scrollDataIndex, 19);
    assert.equal(option().dataZoom[0].start, 20);
    test.resizeTo(600, 520);
    assert.equal(option().legend[0].selected['variable-10000'], false);
    assert.equal(option().dataZoom[0].start, 20);
    native.dispatchAction({ type: 'legendAllSelect' });
    assert.ok(Object.values(option().legend[0].selected).every(Boolean));
    native.dispatchAction({ type: 'legendInverseSelect' });
    assert.ok(Object.values(option().legend[0].selected).every(value => value === false));
    assert.ok(option()[valueAxis].every(axis => axis.show === false));
    assert.equal(option().series.length, 31, 'Selection must not filter or reallocate archived data.');
    assert.ok(option().series.every(item => item.data.length === 1000));
    native.dispatchAction({ type: 'legendToggleSelect', name: 'variable-10001' });
    assert.equal(option()[valueAxis][0].show, false);
    assert.equal(option()[valueAxis][1].show, true);
    assert.equal(option()[valueAxis][1].splitLine.show, true);
    assert.equal(option().dataZoom[0].start, 20);
    assert.match(native.renderToSVGString(), /<svg/);
    assert.doesNotMatch(native.renderToSVGString(), /NaN/);
    native.dispose();
}
for (const mode of ['symcon', 'ipsview']) {
    for (const orientation of ['vertical', 'horizontal']) {
        verifyLargeArchive(mode, orientation);
    }
}

async function verifyCompressedStartup() {
    const delayed = render(false, 'symcon', false, true, false, 420, 520, manyState, null, true);
    assert.equal(delayed.updateCount, 0);
    delayed.resolveStartup();
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(delayed.option.series.length, 32);
    const superseded = render(false, 'symcon', false, true, false, 420, 520, manyState, null, true);
    superseded.window.handleMessage({ status: 'ready', chart: { ...manyState.chart,
        series: [{ ...manySources[0], points: [[1780000300, 25]] }] } });
    superseded.resolveStartup();
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(superseded.option.series.length, 1);
    assert.equal(superseded.option.series[0].data[0][1], 25,
        'A delayed gzip startup must not overwrite a newer full state.');
    const unloaded = render(false, 'symcon', false, true, false, 420, 520, manyState, null, true);
    unloaded.windowListeners.beforeunload();
    unloaded.resolveStartup();
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(unloaded.updateCount, 0, 'A cancelled document must not initialize after decompression.');
}
verifyCompressedStartup().then(() => console.log('Historical Bar renderer layout verified.'))
    .catch(error => { console.error(error); process.exitCode = 1; });
