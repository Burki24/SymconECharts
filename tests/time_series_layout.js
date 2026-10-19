'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const zlib = require('node:zlib');

const source = fs.readFileSync(
    path.join(__dirname, '..', 'EChartsTimeSeries', 'visualization', 'app.js'),
    'utf8'
);
const designSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-design.js'), 'utf8');
const zoomSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-zoom.js'), 'utf8');
const patternSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-pattern.js'), 'utf8');
const startupSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-startup.js'), 'utf8');
const palette = {
    background: '#202020', text: '#ffffff', muted: '#aaaaaa', border: '#cccccc', track: '#444444',
    accent: '#55cbb5', seriesColors: ['#111111', '#222222', '#333333']
};

function render(
    design,
    axes,
    width = 750,
    chartSeries,
    theme = 'dark',
    resolvedColors = {},
    mode = 'symcon',
    adaptToBackground = false,
    enableImages = false,
    timeAxisLabelFormat = 'auto',
    range,
    observeResize = false,
    height = 400,
    actualChart = null,
    deferredStartup = false
) {
    const listeners = {};
    const chartListeners = {};
    const scheduled = [];
    const chartElement = {
        hidden: false,
        clientWidth: width,
        clientHeight: height,
        addEventListener: (type, listener) => { listeners[type] = listener; },
        getBoundingClientRect: () => ({ left: 0, width })
    };
    const warningElement = { hidden: true, textContent: '' };
    const errorElement = { hidden: true, textContent: '' };
    let option;
    let dispatchedAction;
    let setOptionCalls = 0;
    let clearCount = 0;
    let resizeCallback;
    let resized = 0;
    let chartWidth = width;
    let chartHeight = chartElement.clientHeight;
    let resolveStartup;
    let startupJSON;
    const windowListeners = {};
    const chart = actualChart || {
        setOption: next => { option = next; setOptionCalls += 1; },
        getWidth: () => chartWidth,
        getHeight: () => chartHeight,
        getOption: () => option,
        dispatchAction: action => { dispatchedAction = action; },
        clear: () => { clearCount++; },
        resize: () => {
            resized++;
            chartWidth = chartElement.clientWidth;
            chartHeight = chartElement.clientHeight;
        },
        dispose: () => {}
    };
    const nativeSetOption = actualChart && actualChart.setOption.bind(actualChart);
    if (nativeSetOption) {
        chart.setOption = (next, ...args) => { option = next; setOptionCalls++; nativeSetOption(next, ...args); };
        const nativeResize = chart.resize.bind(chart);
        chart.resize = () => { resized++; nativeResize({ width: chartElement.clientWidth, height: chartElement.clientHeight }); };
    } else {
        chart.on = (event, callback) => { chartListeners[event] = callback; };
    }
    function TestImage() {
        this.onload = null;
        this.onerror = null;
    }
    Object.defineProperty(TestImage.prototype, 'src', {
        set(value) {
            this.source = value;
            if (typeof this.onload === 'function') { this.onload(); }
        }
    });
    const window = {
        SYMC_VISUALIZATION: {
            mode,
            state: {
                status: 'ready',
                chart: {
                    theme,
                    chart: { title: 'Climate', enableZoom: true, timeAxisLabelFormat, design },
                    range: range || { startTimestamp: 1000, endTimestamp: 2000 },
                    axes: axes || [{ unit: '°C' }, { unit: '%' }],
                    annotations: [
                        {
                            type: 'line', seriesIndex: 0, label: 'Target', value: 22.5,
                            color: '#E5754F', lineType: 'dashed', lineWidthPercent: 150,
                            opacityPercent: 20
                        },
                        {
                            type: 'area', seriesIndex: 1, label: 'Warning', value: 60, maximum: 80,
                            color: '#123456', lineType: 'solid', lineWidthPercent: 100,
                            opacityPercent: 25
                        }
                    ],
                    series: chartSeries || [
                        {
                            id: 'temperature', label: 'Temperature', axisIndex: 0, decimals: 1,
                            unit: '°C', color: '#E5754F', style: 'area', design: [],
                            points: [[1000, 20], [2000, 21]]
                        },
                        {
                            id: 'humidity', label: 'Humidity', axisIndex: 1, decimals: 0,
                            unit: '%', color: '', style: 'line', points: [[1000, 50], [2000, 55]]
                        }
                    ],
                    truncated: false
                }
            },
            translations: {},
            options: {
                echartsThemes: { auto: palette, dark: palette },
                tileHeaderVisible: true,
                adaptToBackground
            }
        },
        echarts: { init: () => chart },
        ResizeObserver: observeResize ? class {
            constructor(callback) { resizeCallback = callback; }
            observe() {}
        } : undefined,
        Image: enableImages ? TestImage : undefined,
        getComputedStyle: probe => ({
            color: resolvedColors[probe.variable] || probe.fallback || ''
        }),
        setTimeout: callback => { scheduled.push(callback); },
        addEventListener: (event, callback) => { windowListeners[event] = callback; }
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
        documentElement: { classList: { contains: () => false } },
        body: { appendChild: () => {} },
        createElement: tagName => tagName === 'canvas' ? {
            width: 0,
            height: 0,
            getContext: () => ({ drawImage: () => {} })
        } : ({
            style: {
                set color(value) {
                    const match = /^var\((--[^,]+),\s*(.+)\)$/.exec(value);
                    this.owner.variable = match ? match[1] : '';
                    this.owner.fallback = match ? match[2] : value;
                },
                get color() { return ''; },
                display: ''
            },
            variable: '',
            fallback: '',
            remove: () => {}
        }),
        getElementById: id => ({
            'echarts-timeseries-chart': chartElement,
            'echarts-timeseries-warning': warningElement,
            'echarts-timeseries-error': errorElement
        })[id]
    };
    const originalCreateElement = document.createElement;
    document.createElement = tagName => {
        const probe = originalCreateElement(tagName);
        if (tagName === 'canvas') { return probe; }
        probe.style.owner = probe;
        return probe;
    };

    const context = { window, document, ResizeObserver: window.ResizeObserver };
    vm.runInNewContext(designSource, context);
    vm.runInNewContext(zoomSource, context);
    vm.runInNewContext(patternSource, context);
    vm.runInNewContext(startupSource, context);
    vm.runInNewContext(source, context);
    while (scheduled.length > 0) { scheduled.shift()(); }
    if (!deferredStartup) { assert.ok(option, 'The time-series chart should be rendered.'); }
    const result = option || {};
    Object.defineProperties(result, {
        testListeners: { value: listeners },
        chartListeners: { value: chartListeners },
        testChart: { value: chart },
        finishStartup: { value: async () => { resolveStartup(startupJSON); await Promise.resolve(); await Promise.resolve(); } },
        unload: { value: () => windowListeners.beforeunload() },
        startupState: { value: () => JSON.parse(startupJSON) },
        getDispatchedAction: { value: () => dispatchedAction },
        getSetOptionCalls: { value: () => setOptionCalls },
        resizeTest: { value: observeResize ? {
            notify: () => resizeCallback(),
            resizeWidth: next => { chartElement.clientWidth = next; resizeCallback(); },
            get resized() { return resized; },
            get optionUpdates() { return setOptionCalls; }
        } : null },
        getState: { value: () => window.SYMC_VISUALIZATION.state },
        handleMessage: { value: window.handleMessage },
        getLatestOption: { value: () => option },
        getRecoveryView: { value: () => ({ chartElement, warningElement, errorElement, clearCount }) }
    });
    return result;
}

const timeSeriesResize = render(undefined, undefined, 750, undefined, 'dark', {},
    'symcon', false, false, 'auto', undefined, true).resizeTest;
timeSeriesResize.notify();
assert.equal(timeSeriesResize.resized, 0, 'An unchanged TimeSeries notification must preserve animation.');
assert.equal(timeSeriesResize.optionUpdates, 1, 'An unchanged TimeSeries notification must preserve zoom.');
timeSeriesResize.resizeWidth(620);
assert.equal(timeSeriesResize.resized, 1, 'A changed TimeSeries width must resize.');
assert.equal(timeSeriesResize.optionUpdates, 2, 'A changed TimeSeries width must rebuild its layout.');

const custom = render({
    legendPosition: 'bottom', lineWidthPercent: 150, smoothLines: true,
    showSymbols: true, symbolSizePercent: 125, areaOpacityPercent: 40,
    showGrid: false, showXAxis: false, showYAxis: true,
    titleFontSizePercent: 150, legendFontSizePercent: 125, axisFontSizePercent: 150,
    titleColor: '#123456', legendColor: '#234567', axisColor: '#345678', gridColor: '#456789',
    animationEnabled: true, animationEasing: 'bounceOut',
    animationEasingUpdate: 'linear', animationDelay: 120,
    animationDelayUpdate: 240
});
const valueLabels = render({ showValues: true, valueLabelPosition: 'bottom',
    valueLabelDistance: 14, valueFontSizePercent: 150, valueColor: '#123456' });
assert.equal(valueLabels.series[0].label.show, true);
assert.equal(valueLabels.series[0].label.position, 'bottom');
assert.equal(valueLabels.series[0].label.distance, 14);
assert.equal(valueLabels.series[0].label.fontSize, 18);
assert.equal(valueLabels.series[0].label.color, '#123456');
assert.equal(custom.animationEasing, 'bounceOut');
assert.equal(custom.animationEasingUpdate, 'linear');
assert.equal(custom.animationDelay, 120);
assert.equal(custom.animationDelayUpdate, 240);
const duplicateNameSeries = render(
    undefined,
    [{ unit: '°C' }],
    750,
    [
        {
            id: 'variable-4711', variableID: 4711, label: 'Temperatur', axisIndex: 0,
            decimals: 1, unit: '°C', color: '', style: 'line', points: [[1000, 20]]
        },
        {
            id: 'variable-4717', variableID: 4717, label: 'Temperatur', axisIndex: 0,
            decimals: 1, unit: '°C', color: '', style: 'line', points: [[1000, 21]]
        }
    ],
    'dark', {}, 'symcon', false, false, 'auto',
    {
        startTimestamp: 0, endTimestamp: 1000, durationSeconds: 3600,
        calendarAligned: false, acceptLiveUpdates: true, pointLimitPerSeries: 100
    }
);
assert.deepEqual(Array.from(duplicateNameSeries.legend.data), ['variable-4711', 'variable-4717']);
assert.deepEqual(Array.from(duplicateNameSeries.legend.data, duplicateNameSeries.legend.formatter), ['Temperatur', 'Temperatur']);
assert.deepEqual(Array.from(duplicateNameSeries.series, item => item.name), ['variable-4711', 'variable-4717']);
const duplicateTooltip = duplicateNameSeries.tooltip.formatter([
    { seriesIndex: 0, axisValueLabel: '12:00', marker: '', value: [1000, 20] },
    { seriesIndex: 1, axisValueLabel: '12:00', marker: '', value: [1000, 21] }
]);
assert.equal(duplicateTooltip.includes('variable-'), false);
assert.equal((duplicateTooltip.match(/Temperatur/g) || []).length, 2);
assert.deepEqual(Array.from(duplicateNameSeries.series, item => item.id), ['variable-4711', 'variable-4717']);
duplicateNameSeries.handleMessage({ messageType: 'append', variableID: 4717, timestamp: 1100, value: 22 });
assert.equal(duplicateNameSeries.getLatestOption().series[0].data.length, 1);
assert.equal(duplicateNameSeries.getLatestOption().series[1].data.length, 2);
assert.equal(custom.legend.show, true);
assert.equal(custom.title.textStyle.color, '#123456');
assert.equal(custom.title.textStyle.fontSize, 27);
assert.equal(custom.legend.textStyle.color, '#234567');
assert.equal(custom.legend.textStyle.fontSize, 15);
assert.equal(custom.xAxis.axisLabel.color, '#345678');
assert.equal(custom.xAxis.axisLabel.fontSize, 18);
assert.equal(custom.yAxis[0].axisLabel.color, '#345678');
assert.equal(custom.yAxis[0].axisLabel.fontSize, 18);
assert.equal(custom.yAxis[0].nameTextStyle.fontSize, 18);
assert.ok(custom.grid.left > 58);
assert.ok(custom.grid.top > 106);
assert.equal(custom.legend.bottom, 6);
assert.equal(custom.dataZoom[1].bottom, 38);
assert.equal(custom.dataZoom[1].height, 12,
    'The regular TimeSeries zoom slider must match the slim BarHistory control.');
const compactZoom = render({ legendPosition: 'bottom' }, undefined, 433,
    undefined, 'dark', {}, 'ipsview', false, false, 'auto', undefined, false, 213);
assert.equal(compactZoom.dataZoom[1].height, 8,
    'The compact TimeSeries zoom slider must not dominate the tile.');
assert.equal(compactZoom.dataZoom[1].showDetail, false);
assert.equal(custom.series[0].lineStyle.width, 3);
assert.equal(custom.series[0].lineStyle.color, '#E5754F');
assert.equal(custom.series[0].itemStyle.color, '#E5754F');
assert.equal(custom.series[1].lineStyle.color, '#222222');
assert.equal(custom.series[1].itemStyle.color, '#222222');
assert.equal(custom.series[0].smooth, true);
assert.equal(custom.series[0].showSymbol, true);
assert.equal(custom.series[0].symbolSize, 7.5);
assert.equal(custom.series[0].areaStyle.opacity, 0.4);
assert.equal(custom.series[0].markLine.symbol, 'none');
assert.equal(custom.series[0].markLine.data[0].yAxis, 22.5);
assert.equal(custom.series[0].markLine.data[0].lineStyle.color, '#E5754F');
assert.equal(custom.series[0].markLine.data[0].lineStyle.type, 'dashed');
assert.equal(custom.series[0].markLine.data[0].lineStyle.width, 3);
assert.equal(custom.series[1].markArea.data[0][0].yAxis, 60);
assert.equal(custom.series[1].markArea.data[0][1].yAxis, 80);
assert.equal(custom.series[1].markArea.data[0][0].itemStyle.color, '#123456');
assert.equal(custom.series[1].markArea.data[0][0].itemStyle.opacity, 0.25);
assert.equal(custom.xAxis.axisLine.show, false);
assert.equal(custom.xAxis.splitLine.show, false);
assert.equal(custom.yAxis[0].axisLine.show, true);
assert.equal(custom.yAxis[0].axisLine.lineStyle.color, '#345678');
assert.equal(custom.yAxis[0].axisTick.lineStyle.color, '#345678');
assert.equal(custom.yAxis[0].nameTextStyle.color, '#345678');
assert.equal(custom.yAxis[1].axisLine.lineStyle.color, '#345678');
assert.equal(custom.yAxis[1].axisTick.lineStyle.color, '#345678');
assert.equal(custom.yAxis[1].nameTextStyle.color, '#345678');
assert.equal(custom.yAxis[0].splitLine.show, false);
assert.equal(custom.yAxis[0].splitLine.lineStyle.color, '#456789');
assert.equal(custom.yAxis[0].splitLine.lineStyle.opacity, 1);
assert.equal(custom.yAxis[0].position, 'left');
assert.equal(custom.yAxis[1].position, 'right');
assert.equal(custom.yAxis[0].offset, 0);
assert.equal(custom.yAxis[1].offset, 0);

const hidden = render({ legendPosition: 'hidden', showGrid: true, showXAxis: true, showYAxis: false });
assert.equal(hidden.legend.show, false);
assert.equal(hidden.xAxis.axisLine.show, true);
assert.equal(hidden.xAxis.splitLine.show, false);
assert.equal(hidden.yAxis[0].axisLine.show, false);
assert.equal(hidden.yAxis[0].splitLine.show, true);
assert.equal(hidden.yAxis[0].splitLine.lineStyle.color, '#444444');
assert.equal(hidden.yAxis[1].splitLine.show, false);
assert.equal(hidden.grid.left, 22);
assert.equal(hidden.grid.right, 22);

const compatible = render(undefined);
assert.equal(compatible.legend.show, true);
assert.equal(compatible.legend.top, 92);
assert.equal(compatible.series[0].lineStyle.width, 2);
assert.equal(compatible.series[0].smooth, false);
assert.equal(compatible.series[0].showSymbol, false);
assert.equal(compatible.series[0].areaStyle.opacity, 0.22);
assert.equal(compatible.xAxis.axisLine.show, true);
assert.equal(compatible.yAxis[0].axisLine.show, true);
assert.equal(compatible.xAxis.axisLabel.formatter, undefined);
assert.equal(compatible.title.textStyle.color, '#ffffff');
assert.equal(compatible.title.textStyle.fontSize, 18);
assert.equal(compatible.legend.textStyle.fontSize, undefined);
assert.equal(compatible.xAxis.axisLabel.fontSize, undefined);
assert.equal(compatible.yAxis[0].axisLabel.color, '#E5754F');

const gapSeries = [{
    id: 'temperature', variableID: 4711, label: 'Temperature', axisIndex: 0, decimals: 1,
    unit: '°C', color: '#E5754F', style: 'line',
    points: [[1000, 20], [1060, 21], [1120, 22], [2000, 23]]
}];
const automaticGaps = render(
    undefined,
    [{ unit: '°C' }],
    750,
    gapSeries,
    'dark',
    {},
    'symcon',
    false,
    false,
    'auto',
    { startTimestamp: 1000, endTimestamp: 2000, gapDetectionMode: 'automatic' }
);
assert.equal(
    JSON.stringify(automaticGaps.series[0].data),
    '[[1000000,20],[1060000,21],[1120000,22],[1560000,null],[2000000,23]]'
);
const customGaps = render(
    undefined,
    [{ unit: '°C' }],
    750,
    gapSeries,
    'dark',
    {},
    'symcon',
    false,
    false,
    'auto',
    {
        startTimestamp: 1000, endTimestamp: 2000,
        gapDetectionMode: 'custom', gapThresholdSeconds: 600
    }
);
assert.equal(customGaps.series[0].data[3][1], null);
const uninterruptedGaps = render(
    undefined,
    [{ unit: '°C' }],
    750,
    gapSeries,
    'dark',
    {},
    'symcon',
    false,
    false,
    'auto',
    { startTimestamp: 1000, endTimestamp: 2000, gapDetectionMode: 'off' }
);
assert.equal(uninterruptedGaps.series[0].data.length, 4);

const formattedTimeAxis = render(
    undefined,
    undefined,
    750,
    undefined,
    'dark',
    {},
    'symcon',
    false,
    false,
    'date-time'
);
assert.equal(typeof formattedTimeAxis.xAxis.axisLabel.formatter, 'function');
assert.match(formattedTimeAxis.xAxis.axisLabel.formatter(1780000000000), /\d/);

const automaticTheme = render(undefined, undefined, 750, undefined, 'auto', {
    '--symc-background': '#ffffff',
    '--symc-text': '#202124',
    '--symc-muted': '#5f6368',
    '--symc-border': '#dadce0',
    '--symc-surface': '#f1f3f4',
    '--symc-accent': '#55cbb5'
});
assert.equal(automaticTheme.backgroundColor, '#ffffff');
assert.equal(automaticTheme.title.textStyle.color, '#202124');

const positioned = render(undefined, [
    { unit: '°C', position: 'left', positionIndex: 0, minimum: -40, maximum: 60 },
    { unit: '%', position: 'right', positionIndex: 0 },
    { unit: 'hPa', position: 'left', positionIndex: 1 },
    { unit: 'm/s', position: 'right', positionIndex: 1 },
    { unit: 'W/m²', position: 'left', positionIndex: 2 },
    { unit: 'mm', position: 'right', positionIndex: 2 }
], 600, Array.from({ length: 6 }, (_, index) => ({
    id: 'axis-source-' + index, axisIndex: index, label: 'Axis ' + index,
    unit: '', points: [[1000, index]], style: 'line'
})));
assert.deepEqual(
    Array.from(positioned.yAxis, axis => axis.position),
    ['left', 'right', 'left', 'right', 'left', 'right']
);
assert.equal(positioned.yAxis[0].offset, 0);
assert.equal(positioned.yAxis[0].min, -40);
assert.equal(positioned.yAxis[0].max, 60);
assert.equal(positioned.yAxis[1].scale, true);
assert.deepEqual(Array.from(positioned.yAxis[1].boundaryGap), ['5%', '5%']);
assert.equal(Object.hasOwn(positioned.yAxis[1], 'min'), false);
assert.equal(Object.hasOwn(positioned.yAxis[1], 'max'), false);
assert.equal(positioned.yAxis[1].offset, 0);
assert.ok(positioned.yAxis[2].offset > 0);
assert.equal(positioned.yAxis[2].offset, positioned.yAxis[3].offset);
assert.ok(positioned.yAxis[4].offset > positioned.yAxis[2].offset);
assert.equal(positioned.grid.left, positioned.grid.right);
assert.ok(positioned.grid.left <= 192);
assert.equal(positioned.yAxis.filter(axis => axis.splitLine.show).length, 1);

const sharedAxis = render(undefined, [{ unit: '°C', position: 'left', positionIndex: 0 }], 750, [
    {
        id: 'inside', label: 'Inside', axisIndex: 0, decimals: 1,
        unit: '°C', color: '#AABBCC', style: 'line', points: [[1000, 20], [2000, 21]]
    },
    {
        id: 'outside', label: 'Outside', axisIndex: 0, decimals: 1,
        unit: '°C', color: '#DDEEFF', style: 'line', points: [[1000, 10], [2000, 11]]
    }
]);
assert.equal(sharedAxis.yAxis[0].axisLine.lineStyle.color, '#AABBCC');
assert.equal(sharedAxis.series[1].lineStyle.color, '#DDEEFF');

const individual = render(undefined, [{ unit: '°C', position: 'left', positionIndex: 0 }], 750, [
    {
        id: 'individual', label: 'Individual', axisIndex: 0, decimals: 1,
        unit: '°C', color: '#AABBCC', style: 'area', points: [[1000, 20], [2000, 21]],
        design: {
            lineType: 'dotted', lineWidthPercent: 175, smoothLine: true,
            pointSymbol: 'diamond', pointSizePercent: 150,
            areaOpacityPercent: 55, areaFillMode: 'gradient', areaGradientColor: '#123456'
        }
    }
]);
assert.equal(individual.series[0].lineStyle.type, 'dotted');
assert.equal(individual.series[0].lineStyle.width, 3.5);
assert.equal(individual.series[0].smooth, true);
assert.equal(individual.series[0].showSymbol, true);
assert.equal(individual.series[0].symbol, 'diamond');
assert.equal(individual.series[0].symbolSize, 9);
assert.equal(individual.series[0].areaStyle.opacity, 0.55);
assert.equal(individual.series[0].areaStyle.color.type, 'linear');
assert.equal(individual.series[0].areaStyle.color.colorStops[1].color, '#123456');

const svgArea = render(undefined, [{ unit: '%', position: 'left', positionIndex: 0 }], 750, [
    {
        id: 'svg-area', label: 'SVG area', axisIndex: 0, decimals: 0,
        unit: '%', color: '#55CBB5', style: 'area', points: [[1000, 40], [2000, 60]],
        design: {
            lineType: 'solid', lineWidthPercent: 100, smoothLine: false,
            pointSymbol: 'none', pointSizePercent: 100,
            areaOpacityPercent: 35, areaFillMode: 'svg',
            areaPatternImage: 'data:image/svg+xml;base64,PHN2Zy8+',
            areaPatternAspectRatio: 2, areaSVGSizePercent: 125
        }
    }
], 'dark', {}, 'symcon', false, true);
assert.equal(svgArea.series[0].showSymbol, false);
assert.equal(svgArea.series[0].areaStyle.opacity, 0.35);
assert.equal(svgArea.series[0].areaStyle.color.repeat, 'repeat');
assert.equal(svgArea.series[0].areaStyle.color.image.width, 80);
assert.equal(svgArea.series[0].areaStyle.color.image.height, 40);
assert.equal(svgArea.getSetOptionCalls(), 1);

const ipsViewZoom = render(undefined, undefined, 750, undefined, 'dark', {}, 'ipsview');
assert.equal(ipsViewZoom.dataZoom[0].zoomOnMouseWheel, false);
assert.equal(typeof ipsViewZoom.testListeners.wheel, 'function');
let prevented = false;
let stopped = false;
ipsViewZoom.testListeners.wheel({
    deltaY: -100,
    clientX: 375,
    preventDefault: () => { prevented = true; },
    stopPropagation: () => { stopped = true; }
});
const zoomAction = ipsViewZoom.getDispatchedAction();
assert.equal(prevented, true);
assert.equal(stopped, true);
assert.equal(zoomAction.type, 'dataZoom');
assert.equal(zoomAction.dataZoomIndex, 0);
assert.equal(zoomAction.start, 10);
assert.equal(zoomAction.end, 90);

const retainedTimeZoom = render();
retainedTimeZoom.dataZoom[0].start = 25;
retainedTimeZoom.dataZoom[0].end = 75;
const timeState = retainedTimeZoom.getState();
retainedTimeZoom.handleMessage({
    status: 'ready',
    chart: {
        ...timeState.chart,
        range: { ...timeState.chart.range, startTimestamp: 1060, endTimestamp: 2060 }
    }
});
assert.equal(retainedTimeZoom.getLatestOption().dataZoom[0].start, 25,
    'Time Series must retain the zoom window after a data refresh.');
assert.equal(retainedTimeZoom.getLatestOption().dataZoom[1].end, 75);

const retainedOnArchiveFailure = render();
const timeReadyState = retainedOnArchiveFailure.getState();
retainedOnArchiveFailure.handleMessage({
    status: 'error', chart: null, error: 'The time series could not be loaded.'
});
assert.equal(retainedOnArchiveFailure.getRecoveryView().chartElement.hidden, false,
    'A transient TimeSeries archive error must keep the last valid chart visible.');
assert.equal(retainedOnArchiveFailure.getRecoveryView().clearCount, 0);
retainedOnArchiveFailure.handleMessage({
    status: 'error', chart: null, error: 'The time series could not be loaded.'
});
assert.equal(retainedOnArchiveFailure.getRecoveryView().warningElement.hidden, false,
    'Repeated TimeSeries archive errors must mark the retained values as stale.');
retainedOnArchiveFailure.handleMessage(timeReadyState);
assert.equal(retainedOnArchiveFailure.getRecoveryView().warningElement.hidden, true,
    'A successful TimeSeries refresh must clear the stale-data warning.');
retainedOnArchiveFailure.handleMessage({
    status: 'error', chart: null, error: 'Configure 1 to 8 unique numeric sources.'
});
assert.equal(retainedOnArchiveFailure.getRecoveryView().chartElement.hidden, true,
    'A TimeSeries configuration error must not retain an obsolete chart.');

const adaptedIPSView = render(undefined, undefined, 750, undefined, 'dark', {}, 'ipsview', true);
assert.equal(adaptedIPSView.backgroundColor, 'transparent');
assert.equal(compatible.backgroundColor, '#202020');

const calendarSeries = [{
    id: 'calendar', variableID: 4711, label: 'Calendar', axisIndex: 0, decimals: 1,
    unit: '°C', color: '#E5754F', style: 'line', points: [[1100, 20]]
}];
const currentCalendar = render(
    undefined,
    [{ unit: '°C' }],
    750,
    calendarSeries,
    'dark',
    {},
    'symcon',
    false,
    false,
    'auto',
    {
        startTimestamp: 1000, endTimestamp: 2000, durationSeconds: 1001,
        calendarAligned: true, acceptLiveUpdates: true, pointLimitPerSeries: 100
    }
);
currentCalendar.handleMessage({ messageType: 'append', variableID: 4711, timestamp: 2100, value: 21 });
const updatedCalendar = currentCalendar.getLatestOption();
assert.equal(updatedCalendar.xAxis.min, 1000000);
assert.equal(updatedCalendar.xAxis.max, 2100000);
assert.equal(JSON.stringify(updatedCalendar.series[0].data), '[[1100000,20],[2100000,21]]');

const liveGapSeries = [{
    id: 'live-gap', variableID: 4711, label: 'Live gap', axisIndex: 0, decimals: 1,
    unit: '°C', color: '#E5754F', style: 'line', points: [[1000, 20]]
}];
const liveGap = render(
    undefined,
    [{ unit: '°C' }],
    750,
    liveGapSeries,
    'dark',
    {},
    'symcon',
    false,
    false,
    'auto',
    {
        startTimestamp: 0, endTimestamp: 1000, durationSeconds: 3600,
        calendarAligned: false, acceptLiveUpdates: true, pointLimitPerSeries: 100,
        gapDetectionMode: 'custom', gapThresholdSeconds: 300
    }
);
liveGap.handleMessage({ messageType: 'append', variableID: 4711, timestamp: 1600, value: 21 });
assert.equal(
    JSON.stringify(liveGap.getLatestOption().series[0].data),
    '[[1000000,20],[1300000,null],[1600000,21]]'
);

const completedCalendar = render(
    undefined,
    [{ unit: '°C' }],
    750,
    calendarSeries,
    'dark',
    {},
    'symcon',
    false,
    false,
    'auto',
    {
        startTimestamp: 1000, endTimestamp: 2000, durationSeconds: 1001,
        calendarAligned: true, acceptLiveUpdates: false, pointLimitPerSeries: 100
    }
);
const completedSetOptionCalls = completedCalendar.getSetOptionCalls();
completedCalendar.handleMessage({ messageType: 'append', variableID: 4711, timestamp: 2100, value: 21 });
assert.equal(completedCalendar.getSetOptionCalls(), completedSetOptionCalls);

const manySources = Array.from({ length: 32 }, (_, index) => ({
    id: 'variable-' + (10000 + index), variableID: 10000 + index,
    label: index < 2 ? 'Same label' : 'Synthetic sensor ' + (index + 1),
    axisIndex: index % 2, decimals: 1, unit: index % 2 ? '%' : '°C',
    initiallyVisible: index < 8, style: 'line', points: [[1000, index], [2000, index + 1]]
}));
const largeSelection = render(undefined, [{ unit: '°C' }, { unit: '%' }], 400, manySources);
assert.equal(largeSelection.legend.type, 'scroll', 'Many rows need bounded native legend navigation.');
assert.equal(largeSelection.legend.selected['variable-10000'], true);
assert.equal(largeSelection.legend.selected['variable-10008'], false,
    'Initially hidden rows must retain all their points but start unselected.');
assert.equal(largeSelection.series.length, 32);
assert.equal(largeSelection.series[8].data.length, 2);
assert.ok(largeSelection.legend.selector.length > 0);

const escapedTooltip = render(undefined, [{ unit: '°C' }], 750, [{
    id: 'variable-10000', label: '<img src=x onerror=alert(1)>', axisIndex: 0,
    unit: '<unit>', decimals: 1, style: 'line', points: [[1000, 20]]
}]).tooltip.formatter([{ seriesIndex: 0, axisValueLabel: '<time>', marker: '', value: [1000000, 20] }]);
assert.equal(escapedTooltip.includes('<img'), false);
assert.ok(escapedTooltip.includes('&lt;unit&gt;'));

largeSelection.legend.selected['variable-10000'] = false;
largeSelection.legend.scrollDataIndex = 10;
largeSelection.dataZoom[0].start = 20;
largeSelection.dataZoom[0].end = 70;
const largeState = largeSelection.getState();
largeSelection.handleMessage({ status: 'ready', chart: { ...largeState.chart,
    series: [...manySources].reverse() } });
let largeUpdated = largeSelection.getLatestOption();
assert.equal(largeUpdated.legend.selected['variable-10000'], false,
    'Independent selection of equally named source IDs must survive refresh/reorder.');
assert.equal(largeUpdated.legend.selected['variable-10001'], true);
assert.equal(largeUpdated.legend.scrollDataIndex, 21, 'Legend navigation must track its source ID on reorder.');
assert.equal(largeUpdated.dataZoom[0].start, 20);
largeSelection.handleMessage({ status: 'ready', chart: { ...largeState.chart,
    series: manySources.map(item => item.id === 'variable-10000' ? { ...item, initiallyVisible: false } : item) } });
largeSelection.handleMessage({ status: 'ready', chart: { ...largeState.chart,
    series: manySources.map(item => item.id === 'variable-10000' ? { ...item, initiallyVisible: true } : item) } });
assert.equal(largeSelection.getLatestOption().legend.selected['variable-10000'], true,
    'An explicit changed initial visibility must apply only to its own source.');

const echarts = require(path.join(__dirname, '..', '.tools', 'echarts-runtime', 'node_modules', 'echarts'));
for (const mode of ['symcon', 'ipsview']) {
    const native = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 420, height: 520 });
    const stressSources = manySources.map(item => ({ ...item,
        points: Array.from({ length: 1000 }, (_, index) => [1000 + index, Number((20 + Math.sin(index)).toFixed(2))])
    }));
    const test = render({ legendPosition: 'bottom' }, [{ unit: '°C' }, { unit: '%' }],
        420, stressSources, 'dark', {}, mode, false, false, 'auto',
        { key: 'day', startTimestamp: 1000, endTimestamp: 2000 }, true, 520, native);
    function option() { return native.getOption(); }
    assert.equal(option().series.length, 32);
    assert.equal(option().series.reduce((sum, item) => sum + item.data.length, 0), 32000,
        'The native engine must retain the complete increased point budget, including hidden rows.');
    assert.equal(option().legend[0].type, 'scroll');
    native.dispatchAction({ type: 'legendUnSelect', name: 'variable-10000' });
    native.dispatchAction({ type: 'legendScroll', scrollDataIndex: 12 });
    native.dispatchAction({ type: 'dataZoom', dataZoomIndex: 0, start: 20, end: 80 });
    const state = test.getState();
    test.handleMessage({ status: 'ready', chart: { ...state.chart,
        series: [...stressSources].reverse().filter(item => item.id !== 'variable-10002') } });
    assert.equal(option().legend[0].selected['variable-10000'], false);
    assert.equal(option().legend[0].selected['variable-10001'], true);
    assert.equal(option().legend[0].scrollDataIndex, 19);
    assert.equal(option().dataZoom[0].start, 20);
    test.resizeTest.resizeWidth(600);
    assert.equal(option().legend[0].selected['variable-10000'], false);
    assert.equal(option().dataZoom[0].start, 20);
    native.dispatchAction({ type: 'legendAllSelect' });
    assert.ok(Object.values(option().legend[0].selected).every(Boolean));
    native.dispatchAction({ type: 'legendInverseSelect' });
    assert.ok(Object.values(option().legend[0].selected).every(value => value === false));
    assert.ok(option().yAxis.every(axis => axis.show === false), 'All-unselected native view must hide unused axes.');
    assert.equal(option().series.length, 31, 'Selection must not remove stored source data.');
    assert.ok(option().series.every(item => item.data.length === 1000));
    native.dispatchAction({ type: 'legendToggleSelect', name: 'variable-10001' });
    assert.equal(option().yAxis[0].show, false);
    assert.equal(option().yAxis[1].show, true);
    assert.equal(option().yAxis[1].splitLine.show, true);
    assert.equal(option().dataZoom[0].start, 20);
    assert.match(native.renderToSVGString(), /<svg/);
    assert.doesNotMatch(native.renderToSVGString(), /NaN/);
    native.dispose();
}

async function verifyCompressedStartup() {
    function compressed() { return render(undefined, [{ unit: '°C' }], 400, [{
        id: 'variable-10000', variableID: 10000, label: 'Synthetic', axisIndex: 0,
        style: 'line', points: [[1000, 20]]
    }], 'dark', {}, 'symcon', false, false, 'auto', {
        key: 'hour', startTimestamp: 0, endTimestamp: 1000, durationSeconds: 3600,
        acceptLiveUpdates: true, pointLimitPerSeries: 2
    }, false, 520, null, true); }
    const delayed = compressed();
    delayed.handleMessage({ messageType: 'append', variableID: 10000, timestamp: 1100, value: 21 });
    await delayed.finishStartup();
    assert.equal(JSON.stringify(delayed.getLatestOption().series[0].data), '[[1000000,20],[1100000,21]]',
        'Live points received during decompression must apply after the initial archive state.');
    const superseded = compressed();
    const newer = superseded.startupState();
    newer.chart.series[0].points = [[1200, 25]];
    superseded.handleMessage(newer);
    await superseded.finishStartup();
    assert.equal(JSON.stringify(superseded.getLatestOption().series[0].data), '[[1200000,25]]',
        'An older decompressed state must not overwrite a newer complete live state.');
    const unloaded = compressed();
    unloaded.unload();
    await unloaded.finishStartup();
    assert.equal(unloaded.getSetOptionCalls(), 0, 'A cancelled document must not initialize after decompression.');
    const bounded = compressed();
    for (let index = 0; index < 32005; index++) {
        bounded.handleMessage({ messageType: 'append', variableID: 10000, timestamp: 1100 + index, value: index });
    }
    await bounded.finishStartup();
    assert.equal(bounded.getLatestOption().series[0].data.length, 2);
    assert.equal(bounded.getLatestOption().series[0].data[1][1], 32004);
    assert.equal(bounded.getSetOptionCalls(), 2,
        'Queued live points must be folded once, not trigger thousands of expensive chart renders.');
}
verifyCompressedStartup().then(() => process.stdout.write('Time Series tile design layout verified.\n'))
    .catch(error => { console.error(error); process.exitCode = 1; });
