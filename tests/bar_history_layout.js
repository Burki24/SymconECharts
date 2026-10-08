'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(
    path.join(__dirname, '..', 'EChartsBarHistory', 'visualization', 'app.js'),
    'utf8'
);

function render(truncated = false, mode = 'symcon', adaptToBackground = false) {
    const chartElement = { hidden: false };
    const warningElement = { hidden: true, textContent: '' };
    const errorElement = { hidden: true, textContent: '' };
    let option;
    let initCount = 0;
    let updateCount = 0;
    const chart = {
        setOption: next => { option = next; updateCount++; }, clear: () => {}, resize: () => {}, dispose: () => {}
    };
    const palette = {
        background: '#101114', text: '#f4f5f7', muted: '#969aa2', border: '#a5a9b0',
        accent: '#55cbb5', seriesColors: ['#5070dd']
    };
    const window = {
        SYMC_VISUALIZATION: {
            mode,
            state: {
                status: 'ready',
                chart: {
                    theme: 'dark',
                    bar: {
                        title: 'History', timeAxisLabelFormat: 'date-time', unit: '°C', decimals: 1,
                        style: { showValues: true, showGrid: false, roundedBars: true, barWidthPercent: 60 }
                    },
                    series: [{
                        label: 'Temperature', color: '#e5754f',
                        points: [[1780000100, 21.5], [1780000200, 22.0]]
                    }],
                    truncated
                }
            },
            translations: {},
            options: {
                echartsThemes: { auto: palette, dark: palette }, tileHeaderVisible: true,
                adaptToBackground
            }
        },
        echarts: { init: () => { initCount++; return chart; } },
        addEventListener: () => {},
        getComputedStyle: () => ({ color: '' })
    };
    const document = {
        body: { appendChild: () => {} },
        createElement: () => ({ style: {}, remove: () => {} }),
        getElementById: id => ({
            'echarts-bar-history-chart': chartElement,
            'echarts-bar-history-warning': warningElement,
            'echarts-bar-history-error': errorElement
        })[id]
    };
    vm.runInNewContext(source, { window, document });
    return {
        get option() { return option; },
        get initCount() { return initCount; },
        get updateCount() { return updateCount; },
        warningElement, window
    };
}

const ready = render();
assert.ok(ready.option, 'The Historical Bar chart should render.');
assert.equal(ready.option.xAxis.type, 'time');
assert.equal(ready.option.yAxis.type, 'value');
assert.equal(ready.option.yAxis.name, '°C');
assert.equal(ready.option.yAxis.splitLine.show, false);
assert.equal(ready.option.series[0].type, 'bar');
assert.equal(ready.option.series[0].barMaxWidth, 29);
assert.equal(ready.option.series[0].itemStyle.color, '#e5754f');
assert.deepEqual(Array.from(ready.option.series[0].itemStyle.borderRadius), [6, 6, 0, 0]);
assert.deepEqual(Array.from(ready.option.series[0].data[0]), [1780000100000, 21.5]);
assert.equal(ready.warningElement.hidden, true);

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

console.log('Historical Bar renderer layout verified.');
