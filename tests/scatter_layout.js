'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '..', 'EChartsScatter', 'visualization', 'app.js'), 'utf8');
const designSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-design.js'), 'utf8');
const zoomSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-zoom.js'), 'utf8');
const palette = {
    background: '#101114', text: '#f4f5f7', muted: '#969aa2', border: '#999999',
    track: '#34363b', accent: '#55cbb5', seriesColors: ['#5588cc']
};

function render(model, mode = 'symcon', width = 700, height = 410) {
    const chartElement = { hidden: false, clientWidth: width, clientHeight: height,
        addEventListener: () => {}, getBoundingClientRect: () => ({ left: 0, top: 0, width, height }) };
    const emptyElement = { hidden: true, textContent: '' };
    const warningElement = { hidden: true, textContent: '' };
    const selectionElement = { hidden: true, textContent: '', style: { setProperty: () => {} } };
    const errorElement = { hidden: true, textContent: '' };
    let option;
    let resizeCount = 0;
    const listeners = {};
    const chart = {
        setOption: next => { option = next; }, getOption: () => option, clear: () => {},
        resize: () => { resizeCount++; }, dispose: () => {},
        on: (name, listener) => { listeners[name] = listener; }
    };
    const window = {
        SYMC_VISUALIZATION: {
            mode, state: { status: 'ready', chart: model }, translations: {},
            options: { echartsThemes: { auto: palette, dark: palette }, tileHeaderVisible: true }
        },
        echarts: { init: () => chart },
        addEventListener: () => {}, getComputedStyle: () => ({ color: '' })
    };
    const document = {
        body: { appendChild: () => {} },
        createElement: () => ({ style: {}, remove: () => {} }),
        getElementById: id => ({
            'echarts-scatter-chart': chartElement,
            'echarts-scatter-empty': emptyElement,
            'echarts-scatter-warning': warningElement,
            'echarts-scatter-selection': selectionElement,
            'echarts-scatter-error': errorElement
        })[id]
    };
    vm.runInNewContext(designSource, { window });
    vm.runInNewContext(zoomSource, { window });
    vm.runInNewContext(source, { window, document });
    return { option, window, chartElement, emptyElement, warningElement, selectionElement, errorElement,
        emit: (name, event) => listeners[name](event),
        currentOption: () => option,
        resizeCount: () => resizeCount };
}

const model = {
    theme: 'dark', scatter: { title: 'Climate', style: {
        pointSize: 10, bubbleMaxSize: 40, showGrid: true, enableZoom: true, showValues: true,
        valueLabelPosition: 'right', valueLabelDistance: 8,
        xAxisRangeMode: 'data', yAxisRangeMode: 'data',
        xAxisMinimum: 0, xAxisMaximum: 100, yAxisMinimum: 0, yAxisMaximum: 100,
        pointColor: '#123456', axisColor: '#cccccc'
    } },
    roles: {
        x: { label: 'Außen', unit: '°C', decimals: 1 },
        y: { label: 'Feuchte', unit: '%', decimals: 0 },
        size: { label: 'Wind', unit: 'm/s', decimals: 1 }
    },
    range: { key: '24h', startTimestamp: 1000, endTimestamp: 2000 },
    points: [[100, 10, 50, 1], [200, 20, 55, 3]], truncated: false
};
const bubble = render(model);
const visibleBubbleOption = bubble.currentOption();
bubble.window.handleMessage({ type: 'click' });
assert.equal(bubble.currentOption(), visibleBubbleOption,
    'An unrelated browser click message must not replace the Scatter chart.');
assert.equal(bubble.errorElement.hidden, true,
    'An unrelated browser click message must not show a data-load error.');
assert.equal(bubble.option.series[0].type, 'scatter');
assert.equal(bubble.option.series[0].data.length, 2);
assert.equal(bubble.option.series[0].symbolSize([10, 50, 1, 100]), 10);
assert.equal(bubble.option.series[0].symbolSize([20, 55, 3, 200]), 40);
assert.equal(bubble.option.series[0].label.position, 'right');
assert.equal(bubble.option.series[0].label.distance, 8);
assert.equal(bubble.option.series[0].label.formatter({ value: [10, 50, 1, 100] }), '50 %');
const xLabels = render({ ...model, scatter: { ...model.scatter, style: {
    ...model.scatter.style, valueLabelContent: 'x'
} } });
assert.match(xLabels.option.series[0].label.formatter({ value: [10, 50, 1, 100] }), /^10[.,]0 °C$/);
const bothLabels = render({ ...model, scatter: { ...model.scatter, style: {
    ...model.scatter.style, valueLabelContent: 'both'
} } });
assert.match(bothLabels.option.series[0].label.formatter({ value: [10, 50, 1, 100] }), /^10[.,]0 °C \/ 50 %$/);
assert.equal(bubble.option.series[0].labelLayout.hideOverlap, true,
    'Crowded value labels must be suppressed without hiding their points or tooltips.');
assert.equal(bubble.option.xAxis.scale, true);
assert.equal(bubble.option.yAxis.scale, true);
assert.equal(bubble.option.xAxis.min, undefined);
assert.equal(bubble.option.yAxis.min, undefined);
assert.equal(bubble.option.series[0].itemStyle.color, '#123456');
assert.equal(bubble.option.visualMap, undefined, 'Existing charts keep their solid point color.');
assert.equal(bubble.option.brush, undefined, 'Existing charts must keep selection disabled.');
assert.equal(bubble.option.toolbox, undefined);
const selectable = render({ ...model, scatter: { ...model.scatter, style: {
    ...model.scatter.style, enableBrush: true
} } });
assert.deepEqual(Array.from(selectable.option.brush.toolbox), ['rect', 'clear']);
assert.equal(selectable.option.brush.brushMode, 'single');
assert.equal(selectable.option.toolbox.feature.brush.title.rect, 'Select points');
selectable.emit('brushselected', { batch: [{ areas: [{ brushType: 'rect' }],
    selected: [{ seriesIndex: 0, dataIndex: [0, 1] }] }] });
assert.equal(selectable.selectionElement.hidden, false);
assert.match(selectable.selectionElement.textContent, /Selected points: 2/);
assert.match(selectable.selectionElement.textContent, /Außen: .*10.*20/);
selectable.emit('brushselected', { batch: [{ areas: [], selected: [{ dataIndex: [] }] }] });
assert.equal(selectable.selectionElement.hidden, true, 'Clearing a brush must remove the summary.');
selectable.emit('brushselected', { batch: [{ areas: [{ brushType: 'rect' }],
    selected: [{ dataIndex: [1] }] }] });
selectable.window.handleMessage({ status: 'ready', chart: model });
assert.equal(selectable.selectionElement.hidden, true, 'A data refresh resets the temporary selection.');
const colorMapped = render({ ...model, scatter: { ...model.scatter, style: {
    ...model.scatter.style, colorMapEnabled: true, colorMapDimension: 'y',
    colorMapRangeMode: 'data', colorMapShowLegend: true,
    colorMapLowColor: '#123456', colorMapHighColor: '#fedcba'
} } });
assert.equal(colorMapped.option.visualMap.dimension, 4);
assert.equal(colorMapped.option.visualMap.min, 50);
assert.equal(colorMapped.option.visualMap.max, 55);
assert.equal(colorMapped.option.series[0].data[0].value[4], 50);
assert.deepEqual(Array.from(colorMapped.option.visualMap.inRange.color), ['#123456', '#fedcba']);
assert.equal(colorMapped.option.visualMap.orient, 'horizontal');
assert.equal(colorMapped.option.visualMap.itemWidth, 12,
    'ECharts uses itemWidth for the thickness of a horizontal visual map.');
assert.ok(colorMapped.option.visualMap.itemHeight >= 200,
    'The horizontal color scale must be a readable length on a regular Tile.');
const wideColorMapped = render({ ...model, scatter: { ...model.scatter, style: {
    ...model.scatter.style, colorMapEnabled: true, colorMapShowLegend: true
} } }, 'ipsview', 1900, 900);
assert.ok(wideColorMapped.option.visualMap.itemHeight > colorMapped.option.visualMap.itemHeight,
    'The color scale must grow in a wide IPSView view without remaining a narrow strip.');
assert.ok(wideColorMapped.option.visualMap.itemHeight <= 420,
    'The legend must remain bounded rather than span the whole screen.');
const compactColorMapped = render({ ...model, scatter: { ...model.scatter, style: {
    ...model.scatter.style, colorMapEnabled: true, colorMapShowLegend: true
} } }, 'ipsview', 400, 240);
assert.equal(compactColorMapped.option.visualMap.itemWidth, 8);
assert.equal(compactColorMapped.option.visualMap.itemHeight, 160);
assert.equal(colorMapped.option.series[0].itemStyle.color, undefined);
assert.ok(colorMapped.option.grid.top > bubble.option.grid.top,
    'A visible color scale must reserve its own space above the plot.');
const selectableColors = render({ ...model, scatter: { ...model.scatter, style: {
    ...model.scatter.style, enableBrush: true, colorMapEnabled: true, colorMapShowLegend: true
} } }, 'ipsview');
const selectableIPSView = render({ ...model, scatter: { ...model.scatter, style: {
    ...model.scatter.style, enableBrush: true
} } }, 'ipsview');
assert.ok(selectableColors.option.toolbox.top > selectableIPSView.option.toolbox.top,
    'The selection tools must move below a visible color scale.');
assert.equal(selectableColors.option.visualMap.dimension, 4,
    'Area selection must coexist with value-based point colors.');
const manualColors = render({ ...model, scatter: { ...model.scatter, style: {
    ...model.scatter.style, colorMapEnabled: true, colorMapDimension: 'x',
    colorMapRangeMode: 'manual', colorMapMinimum: -5, colorMapMaximum: 25
} } });
assert.equal(manualColors.option.visualMap.dimension, 4);
assert.equal(manualColors.option.visualMap.min, -5);
assert.equal(manualColors.option.visualMap.max, 25);
assert.equal(manualColors.option.series[0].data[0].value[4], 10);
const clippedColors = render({ ...model, scatter: { ...model.scatter, style: {
    ...model.scatter.style, colorMapEnabled: true, colorMapDimension: 'x',
    colorMapRangeMode: 'manual', colorMapMinimum: 12, colorMapMaximum: 18
} } });
assert.deepEqual(Array.from(clippedColors.option.series[0].data, entry => entry.value[4]), [12, 18],
    'Values outside manual color limits use the nearest endpoint color.');
const bubbleColors = render({ ...model, scatter: { ...model.scatter, style: {
    ...model.scatter.style, colorMapEnabled: true, colorMapDimension: 'size'
} } });
assert.equal(bubbleColors.option.visualMap.dimension, 4);
assert.equal(bubbleColors.option.visualMap.min, 1);
assert.equal(bubbleColors.option.visualMap.max, 3);
assert.equal(bubbleColors.option.series[0].data[1].value[4], 3);
assert.equal(bubble.option.dataZoom.length, 4);
assert.equal(bubble.option.dataZoom[2].xAxisIndex, 0);
assert.equal(bubble.option.dataZoom[3].yAxisIndex, 0);
assert.equal(bubble.option.dataZoom[0].zoomOnMouseWheel, true);
assert.equal(bubble.option.dataZoom[2].height, 12);
assert.equal(bubble.option.dataZoom[3].width, 12);
assert.equal(bubble.option.xAxis.name, 'Außen (°C)');
assert.equal(bubble.option.yAxis.name, 'Feuchte (%)');
assert.ok(!bubble.option.title.show, 'Visible Symcon tile headers must not duplicate the chart title.');
assert.ok(bubble.option.tooltip.formatter({ value: [10, 50, 1, 100] }).includes('Wind'));
const ipsview = render(model, 'ipsview');
assert.equal(ipsview.option.title.show, true);
assert.equal(ipsview.option.dataZoom[1].zoomOnMouseWheel, false);
const compactBubble = render(model, 'ipsview', 400, 240);
assert.equal(compactBubble.option.dataZoom[2].height, 8);
assert.equal(compactBubble.option.dataZoom[3].width, 8);
for (const entry of bubble.option.dataZoom) {
    entry.start = entry.xAxisIndex === 0 ? 20 : 35;
    entry.end = entry.xAxisIndex === 0 ? 65 : 80;
}
bubble.window.handleMessage({ status: 'ready', chart: { ...model,
    range: { ...model.range, startTimestamp: 1100, endTimestamp: 2100 } } });
assert.deepEqual(Array.from(bubble.currentOption().dataZoom, entry => [entry.start, entry.end]),
    [[20, 65], [35, 80], [20, 65], [35, 80]]);
bubble.window.handleMessage({ status: 'ready', chart: { ...model,
    range: { ...model.range, key: '7d' } } });
assert.deepEqual(Array.from(bubble.currentOption().dataZoom, entry => [entry.start, entry.end]),
    [[0, 100], [0, 100], [0, 100], [0, 100]]);
for (const entry of bubble.currentOption().dataZoom) {
    entry.start = 20;
    entry.end = 60;
}
bubble.window.handleMessage({ status: 'ready', chart: { ...model, scatter: { ...model.scatter, style: {
    ...model.scatter.style, xAxisRangeMode: 'manual', xAxisMinimum: 1, xAxisMaximum: 25
} } } });
assert.deepEqual(Array.from(bubble.currentOption().dataZoom, entry => [entry.start, entry.end]),
    [[0, 100], [0, 100], [0, 100], [0, 100]],
    'Manual axis edits must not inherit a stale interactive zoom window.');
const noZoom = render({ ...model, scatter: { ...model.scatter, style: {
    ...model.scatter.style, enableZoom: false
} } });
assert.equal(noZoom.option.dataZoom.length, 0);
const zeroScale = render({ ...model, scatter: { ...model.scatter, style: {
    ...model.scatter.style, xAxisRangeMode: 'zero', yAxisRangeMode: 'manual',
    yAxisMinimum: 40, yAxisMaximum: 100
} } });
assert.equal(zeroScale.option.xAxis.scale, false);
assert.equal(zeroScale.option.yAxis.scale, true);
assert.equal(zeroScale.option.yAxis.min, 40);
assert.equal(zeroScale.option.yAxis.max, 100);
const scatter = render({ ...model, roles: { x: model.roles.x, y: model.roles.y },
    points: [[100, 10, 50]] });
assert.equal(scatter.option.series[0].symbolSize([10, 50, null, 100]), 10);
assert.equal(scatter.option.series[0].data[0].value[3], 100);
const empty = render({ ...model, points: [] });
assert.equal(empty.option, undefined);
assert.equal(empty.emptyElement.hidden, false);
assert.equal(empty.chartElement.hidden, true);
const hostile = render({ ...model, roles: {
    x: { label: '<img src=x>', unit: '<b>', decimals: 1 }, y: model.roles.y
} });
const tooltip = hostile.option.tooltip.formatter({ value: [10, 50, null, 100] });
assert.ok(tooltip.includes('&lt;img'));
assert.ok(tooltip.includes('&lt;b&gt;'));

console.log('Scatter and Bubble layout verified.');
