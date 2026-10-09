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
assert.equal(ready.option.series[0].itemStyle.opacity, 1);
assert.equal(ready.option.title.textStyle.fontSize, 18);
assert.deepEqual(Array.from(ready.option.series[0].itemStyle.borderRadius), [6, 6, 0, 0]);
assert.deepEqual(Array.from(ready.option.series[0].data[0]), [1780000100000, 21.5]);
assert.equal(ready.warningElement.hidden, true);

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
                titleFontSizePercent: 130, axisFontSizePercent: 115,
                valueFontSizePercent: 125, titleColor: '#ffeedd',
                axisColor: '#ddeeff', valueColor: '#112233', gridColor: '#445566'
            }
        },
        series: [{ label: 'Temperature', color: '#e5754f', points: [[1780000300, 23.0]] }]
    }
});
assert.equal(ready.option.title.textStyle.color, '#ffeedd');
assert.equal(ready.option.title.textStyle.fontSize, 23);
assert.equal(ready.option.xAxis.axisLabel.color, '#ddeeff');
assert.equal(ready.option.xAxis.axisLabel.fontSize, 14);
assert.equal(ready.option.xAxis.axisLine.lineStyle.color, '#ddeeff');
assert.equal(ready.option.yAxis.nameTextStyle.color, '#ddeeff');
assert.equal(ready.option.yAxis.splitLine.lineStyle.color, '#445566');
assert.equal(ready.option.yAxis.splitLine.lineStyle.opacity, 1);
assert.equal(ready.option.series[0].label.color, '#112233');
assert.equal(ready.option.series[0].label.fontSize, 15);
assert.equal(ready.option.series[0].itemStyle.opacity, 0.65);
assert.deepEqual(Array.from(ready.option.series[0].itemStyle.borderRadius), [10, 10, 0, 0]);
assert.equal(ready.option.series[0].itemStyle.color.type, 'linear');
assert.equal(ready.option.series[0].itemStyle.color.colorStops[0].color, '#e5754f');
assert.equal(ready.option.series[0].itemStyle.color.colorStops[1].color, '#55ccaa');

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

console.log('Historical Bar renderer layout verified.');
