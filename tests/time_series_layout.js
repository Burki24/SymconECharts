'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(
    path.join(__dirname, '..', 'EChartsTimeSeries', 'visualization', 'app.js'),
    'utf8'
);
const designSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-design.js'), 'utf8');
const zoomSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-zoom.js'), 'utf8');
const patternSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-pattern.js'), 'utf8');
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
    observeResize = false
) {
    const listeners = {};
    const scheduled = [];
    const chartElement = {
        hidden: false,
        clientWidth: width,
        clientHeight: 400,
        addEventListener: (type, listener) => { listeners[type] = listener; },
        getBoundingClientRect: () => ({ left: 0, width })
    };
    const warningElement = { hidden: true, textContent: '' };
    const errorElement = { hidden: true, textContent: '' };
    let option;
    let dispatchedAction;
    let setOptionCalls = 0;
    let resizeCallback;
    let resized = 0;
    let chartWidth = width;
    let chartHeight = chartElement.clientHeight;
    const chart = {
        setOption: next => { option = next; setOptionCalls += 1; },
        getWidth: () => chartWidth,
        getHeight: () => chartHeight,
        getOption: () => option,
        dispatchAction: action => { dispatchedAction = action; },
        clear: () => {},
        resize: () => {
            resized++;
            chartWidth = chartElement.clientWidth;
            chartHeight = chartElement.clientHeight;
        },
        dispose: () => {}
    };
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
        addEventListener: () => {}
    };
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
    vm.runInNewContext(source, context);
    while (scheduled.length > 0) { scheduled.shift()(); }
    assert.ok(option, 'The time-series chart should be rendered.');
    Object.defineProperties(option, {
        testListeners: { value: listeners },
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
        getLatestOption: { value: () => option }
    });
    return option;
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
    animationEnabled: true, animationEasing: 'bounceOut',
    animationEasingUpdate: 'linear', animationDelay: 120,
    animationDelayUpdate: 240
});
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
assert.equal(custom.legend.bottom, 6);
assert.equal(custom.dataZoom[1].bottom, 38);
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
assert.equal(custom.yAxis[0].axisLine.lineStyle.color, '#E5754F');
assert.equal(custom.yAxis[0].axisTick.lineStyle.color, '#E5754F');
assert.equal(custom.yAxis[0].axisLabel.color, '#E5754F');
assert.equal(custom.yAxis[0].nameTextStyle.color, '#E5754F');
assert.equal(custom.yAxis[1].axisLine.lineStyle.color, '#222222');
assert.equal(custom.yAxis[1].axisTick.lineStyle.color, '#222222');
assert.equal(custom.yAxis[1].axisLabel.color, '#222222');
assert.equal(custom.yAxis[1].nameTextStyle.color, '#222222');
assert.equal(custom.yAxis[0].splitLine.show, false);
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
], 600);
assert.deepEqual(
    Array.from(positioned.yAxis, axis => axis.position),
    ['left', 'right', 'left', 'right', 'left', 'right']
);
assert.equal(positioned.yAxis[0].offset, 0);
assert.equal(positioned.yAxis[0].min, -40);
assert.equal(positioned.yAxis[0].max, 60);
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

process.stdout.write('Time Series tile design layout verified.\n');
