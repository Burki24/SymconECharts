'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(
    path.join(__dirname, '..', 'EChartsBarCategory', 'visualization', 'app.js'),
    'utf8'
);
const designSource = fs.readFileSync(
    path.join(__dirname, '..', 'libs', 'echarts-design.js'),
    'utf8'
);

function render(orientation, sortOrder, outputMode = 'symcon', barMode = 'simple', items) {
    const chartElement = { hidden: false, clientWidth: 800 };
    const errorElement = { hidden: true, textContent: '' };
    let option;
    const chart = {
        setOption: next => { option = next; }, clear: () => {}, resize: () => {}, dispose: () => {}
    };
    const palette = {
        background: '#101114', text: '#f4f5f7', muted: '#969aa2', border: '#a5a9b0',
        accent: '#55cbb5', surface: '#25272b', seriesColors: ['#111111', '#222222', '#333333']
    };
    const window = {
        SYMC_VISUALIZATION: {
            mode: outputMode,
            state: {
                status: 'ready',
                chart: {
                    theme: 'dark',
                    bar: {
                        title: 'Temperatures', mode: barMode, orientation, sortOrder, unit: '°C', decimals: 1,
                        style: { showValues: true, showGrid: false, roundedBars: true, barWidthPercent: 60 }
                    },
                    items: items || [
                        { category: 'A', series: 'A', value: 10, order: 0, color: '#ff0000' },
                        { category: 'B', series: 'B', value: 30, order: 1, color: '' },
                        { category: 'C', series: 'C', value: 20, order: 2, color: '' }
                    ]
                }
            },
            translations: {},
            options: { echartsThemes: { auto: palette, dark: palette }, tileHeaderVisible: true }
        },
        echarts: { init: () => chart },
        addEventListener: () => {},
        getComputedStyle: () => ({ color: '' })
    };
    const document = {
        body: { appendChild: () => {} },
        createElement: () => ({ style: {}, remove: () => {} }),
        getElementById: id => ({
            'echarts-bar-category-chart': chartElement,
            'echarts-bar-category-error': errorElement
        })[id]
    };
    vm.runInNewContext(designSource, { window });
    vm.runInNewContext(source, { window, document });
    assert.ok(option, 'The Category Bar chart should render.');
    return option;
}

const horizontal = render('horizontal', 'descending');
assert.equal(horizontal.xAxis.type, 'value');
assert.equal(horizontal.yAxis.type, 'category');
assert.deepEqual(Array.from(horizontal.yAxis.data), ['B', 'C', 'A']);
assert.equal(horizontal.series[0].label.position, 'right');
assert.equal(horizontal.series[0].barWidth, '60%');
assert.deepEqual(Array.from(horizontal.series[0].data[0].itemStyle.borderRadius), [0, 7, 7, 0]);
assert.equal(horizontal.series[0].data[2].itemStyle.color, '#ff0000');

const vertical = render('vertical', 'configured');
assert.equal(vertical.xAxis.type, 'category');
assert.equal(vertical.yAxis.type, 'value');
assert.deepEqual(Array.from(vertical.xAxis.data), ['A', 'B', 'C']);
assert.equal(vertical.series[0].label.position, 'top');
assert.equal(vertical.yAxis.splitLine.show, false);

const matrix = [
    { category: 'Kitchen', series: 'Today', value: 18, order: 0, color: '#aa0000' },
    { category: 'Kitchen', series: 'Yesterday', value: 20, order: 1, color: '#0000aa' },
    { category: 'Office', series: 'Today', value: 24, order: 2, color: '#aa0000' },
    { category: 'Office', series: 'Yesterday', value: 22, order: 3, color: '#0000aa' }
];
const grouped = render('vertical', 'descending', 'symcon', 'grouped', matrix);
assert.equal(grouped.legend.show, true);
assert.deepEqual(Array.from(grouped.xAxis.data), ['Office', 'Kitchen']);
assert.equal(grouped.series.length, 2);
assert.equal(grouped.series[0].name, 'Today');
assert.deepEqual(Array.from(grouped.series[0].data, item => item.value), [24, 18]);
assert.equal(grouped.series[0].stack, undefined);
assert.equal(grouped.series[0].barCategoryGap, '40%');

const stacked = render('horizontal', 'configured', 'symcon', 'stacked', matrix);
assert.deepEqual(Array.from(stacked.yAxis.data), ['Kitchen', 'Office']);
assert.equal(stacked.series[0].stack, 'total');
assert.equal(stacked.series[1].stack, 'total');
assert.equal(stacked.series[0].label.position, 'inside');
assert.equal(stacked.series[0].data[0].label.color, '#f4f5f7');
assert.equal(stacked.series[1].data[0].label.color, '#f4f5f7');

const automaticStack = render('vertical', 'configured', 'symcon', 'stacked', [
    { category: '', series: 'Room', value: 91, order: 0, color: '#aa0000' },
    { category: '', series: 'Floor', value: 82, order: 1, color: '#00aa00' },
    { category: '', series: 'Wall', value: 73, order: 2, color: '#0000aa' }
]);
assert.deepEqual(Array.from(automaticStack.xAxis.data), ['']);
assert.deepEqual(Array.from(automaticStack.legend.data), ['Room', 'Floor', 'Wall']);
assert.equal(automaticStack.series.length, 3);
assert.equal(automaticStack.series[0].stack, 'total');
assert.equal(automaticStack.series[0].data[0].value, 91);
assert.equal(automaticStack.series[2].data[0].itemStyle.color, '#0000aa');
assert.equal(automaticStack.series[0].data[0].label.color, '#f4f5f7');
assert.equal(automaticStack.series[1].data[0].label.color, '#101114');
assert.equal(automaticStack.series[2].data[0].label.color, '#f4f5f7');

const sparse = render('vertical', 'configured', 'symcon', 'grouped', [
    { category: 'Kitchen', series: 'Today', value: 18, order: 0, color: '#aa0000' },
    { category: 'Kitchen', series: 'Yesterday', value: 20, order: 1, color: '#0000aa' },
    { category: 'Office', series: 'Today', value: 24, order: 2, color: '#aa0000' }
]);
assert.deepEqual(Array.from(sparse.xAxis.data), ['Kitchen', 'Office']);
assert.deepEqual(Array.from(sparse.legend.data), ['Today', 'Yesterday']);
assert.deepEqual(Array.from(sparse.series[0].data, item => item.value), [18, 24]);
assert.deepEqual(Array.from(sparse.series[1].data, item => item.value), [20, null]);

console.log('Category Bar renderer layout verified.');
