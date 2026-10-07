'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(
    path.join(__dirname, '..', 'EChartsBarCategory', 'visualization', 'app.js'),
    'utf8'
);

function render(orientation, sortOrder, mode = 'symcon') {
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
            mode,
            state: {
                status: 'ready',
                chart: {
                    theme: 'dark',
                    bar: {
                        title: 'Temperatures', orientation, sortOrder, unit: '°C', decimals: 1,
                        style: { showValues: true, showGrid: false, roundedBars: true, barWidthPercent: 60 }
                    },
                    items: [
                        { label: 'A', value: 10, order: 0, color: '#ff0000' },
                        { label: 'B', value: 30, order: 1, color: '' },
                        { label: 'C', value: 20, order: 2, color: '' }
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

console.log('Category Bar renderer layout verified.');
