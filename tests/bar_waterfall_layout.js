'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(
    path.join(__dirname, '..', 'EChartsBarWaterfall', 'visualization', 'app.js'), 'utf8'
);
const designSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-design.js'), 'utf8');
const chartElement = { hidden: false, clientWidth: 800, clientHeight: 400 };
const errorElement = { hidden: true, textContent: '' };
let option;
let initialized = 0;
let resized = 0;
let resizeCallback;
let chartWidth = chartElement.clientWidth;
let chartHeight = chartElement.clientHeight;
const chart = {
    setOption: next => { option = next; },
    getWidth: () => chartWidth, getHeight: () => chartHeight,
    clear: () => {},
    resize: () => {
        resized++;
        chartWidth = chartElement.clientWidth;
        chartHeight = chartElement.clientHeight;
    },
    dispose: () => {}
};
const palette = {
    background: '#111111', text: '#eeeeee', muted: '#aaaaaa', border: '#777777',
    accent: '#55cbb5', seriesColors: ['#55cbb5', '#6070cc']
};
const window = {
    SYMC_VISUALIZATION: {
        mode: 'symcon',
        state: {
            status: 'ready',
            chart: {
                theme: 'dark',
                bar: {
                    title: 'Balance', unit: '°C', decimals: 1,
                    style: {
                        showValues: true, showGrid: true, barWidthPercent: 60,
                        colors: {
                            startColor: '#123456', increaseColor: '#008800',
                            decreaseColor: '#880000', totalColor: '#654321'
                        }
                    }
                },
                steps: [
                    { label: 'Start', kind: 'start', change: 10, before: 0, after: 10 },
                    { label: '<drop>', kind: 'change', change: -15, before: 10, after: -5 },
                    { label: 'Recovery', kind: 'change', change: 2, before: -5, after: -3 },
                    { label: 'Total', kind: 'total', change: -3, before: 0, after: -3 }
                ]
            }
        },
        options: { echartsThemes: { auto: palette, dark: palette }, tileHeaderVisible: true },
        translations: {}
    },
    echarts: { init: () => { initialized++; return chart; } },
    ResizeObserver: class {
        constructor(callback) { resizeCallback = callback; }
        observe() {}
    },
    addEventListener: () => {}
};
const document = {
    getElementById: id => id === 'echarts-bar-waterfall-chart' ? chartElement : errorElement,
    createElement: () => ({ style: {}, remove: () => {} }),
    body: { appendChild: () => {} }
};
vm.runInNewContext(designSource, { window });
vm.runInNewContext(source, { window, document, console, ResizeObserver: window.ResizeObserver });

assert.equal(initialized, 1);
resizeCallback();
assert.equal(resized, 0, 'An unchanged observer notification must preserve the Waterfall animation.');
chartElement.clientWidth = 600;
resizeCallback();
assert.equal(resized, 1, 'A changed Waterfall tile width must resize the chart.');
assert.deepEqual(Array.from(option.xAxis.data), ['Start', '<drop>', 'Recovery', 'Total']);
assert.equal(option.series.length, 4);
assert.deepEqual(Array.from(option.series[0].data), [0, 0, 0, 0]);
assert.deepEqual(Array.from(option.series[1].data, item => item.value), [10, 10, 0, 0]);
assert.deepEqual(Array.from(option.series[2].data), [0, 0, -3, 0]);
assert.deepEqual(Array.from(option.series[3].data, item => item.value), [0, -5, -2, -3]);
assert.equal(option.series[1].data[0].itemStyle.color, '#123456');
assert.equal(option.series[3].data[1].itemStyle.color, '#880000');
assert.equal(option.series[3].data[2].itemStyle.color, '#008800');
assert.equal(option.series[3].data[3].itemStyle.color, '#654321');
assert.match(option.tooltip.formatter([{ dataIndex: 1 }]), /&lt;drop&gt;/);
assert.doesNotMatch(option.tooltip.formatter([{ dataIndex: 1 }]), /<drop>/);

const localECharts = path.join(__dirname, '..', '.tools', 'echarts-runtime', 'node_modules', 'echarts');
if (fs.existsSync(localECharts)) {
    const echarts = require(localECharts);
    const realChart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 800, height: 400 });
    realChart.setOption(option);
    const positiveCrossing = realChart.getModel().getSeriesByIndex(1).getData().getItemLayout(1);
    const negativeCrossing = realChart.getModel().getSeriesByIndex(3).getData().getItemLayout(1);
    assert.ok(positiveCrossing.height < 0, 'The positive crossing segment must extend above zero.');
    assert.ok(negativeCrossing.height > 0, 'The negative crossing segment must extend below zero.');
    assert.ok(realChart.renderToSVGString().includes('<svg'));
    realChart.dispose();
}

window.handleMessage({ status: 'error', error: 'Configure valid Waterfall sources.' });
assert.equal(chartElement.hidden, true);
assert.equal(errorElement.hidden, false);

console.log('Waterfall layout verified.');
