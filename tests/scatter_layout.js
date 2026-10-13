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

function render(model, mode = 'symcon') {
    const chartElement = { hidden: false, clientWidth: 700, clientHeight: 410,
        addEventListener: () => {}, getBoundingClientRect: () => ({ left: 0, top: 0, width: 700, height: 410 }) };
    const emptyElement = { hidden: true, textContent: '' };
    const warningElement = { hidden: true, textContent: '' };
    const errorElement = { hidden: true, textContent: '' };
    let option;
    let resizeCount = 0;
    const chart = {
        setOption: next => { option = next; }, getOption: () => option, clear: () => {},
        resize: () => { resizeCount++; }, dispose: () => {}
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
            'echarts-scatter-error': errorElement
        })[id]
    };
    vm.runInNewContext(designSource, { window });
    vm.runInNewContext(zoomSource, { window });
    vm.runInNewContext(source, { window, document });
    return { option, window, chartElement, emptyElement, warningElement, errorElement,
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
assert.equal(bubble.option.dataZoom.length, 4);
assert.equal(bubble.option.dataZoom[2].xAxisIndex, 0);
assert.equal(bubble.option.dataZoom[3].yAxisIndex, 0);
assert.equal(bubble.option.dataZoom[0].zoomOnMouseWheel, true);
assert.equal(bubble.option.xAxis.name, 'Außen (°C)');
assert.equal(bubble.option.yAxis.name, 'Feuchte (%)');
assert.ok(!bubble.option.title.show, 'Visible Symcon tile headers must not duplicate the chart title.');
assert.ok(bubble.option.tooltip.formatter({ value: [10, 50, 1, 100] }).includes('Wind'));
const ipsview = render(model, 'ipsview');
assert.equal(ipsview.option.title.show, true);
assert.equal(ipsview.option.dataZoom[1].zoomOnMouseWheel, false);
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
