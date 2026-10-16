'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '..', 'EChartsParallel', 'visualization', 'app.js'), 'utf8');
const designSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-design.js'), 'utf8');
const palette = { background: '#101114', text: '#f4f5f7', muted: '#969aa2',
    border: '#737780', accent: '#55cbb5' };

function render(model, mode = 'symcon', width = 900, adaptToBackground = false) {
    const chartElement = { hidden: false, clientWidth: width, clientHeight: 400 };
    const errorElement = { hidden: true, textContent: '' };
    const emptyElement = { hidden: true, textContent: '' };
    const warningElement = { hidden: true, textContent: '' };
    let option;
    const chart = { setOption: next => { option = next; }, clear: () => {}, resize: () => {}, dispose: () => {} };
    const window = {
        SYMC_VISUALIZATION: { mode, state: { status: 'ready', chart: model }, translations: {},
            options: { echartsThemes: { auto: palette, dark: palette }, tileHeaderVisible: true,
                adaptToBackground } },
        echarts: { init: () => chart }, addEventListener: () => {}, getComputedStyle: () => ({ color: '' })
    };
    const document = {
        body: { appendChild: () => {} },
        createElement: () => ({ style: {}, remove: () => {} }),
        getElementById: id => ({
            'echarts-parallel-chart': chartElement,
            'echarts-parallel-error': errorElement,
            'echarts-parallel-empty': emptyElement,
            'echarts-parallel-warning': warningElement
        })[id]
    };
    vm.runInNewContext(designSource, { window });
    vm.runInNewContext(source, { window, document });
    return { option, chartElement, errorElement, emptyElement, warningElement };
}

const model = { family: 'parallel', theme: 'dark', parallel: {
    title: 'Archive', aggregation: 'hour', truncated: false,
    axes: [
        { label: '<Temperature>', unit: '°C' },
        { label: 'Humidity', unit: '%' },
        { label: 'Power', unit: 'W' }
    ],
    rows: [
        { timestamp: 1780000100, values: [18.5, 42, 100] },
        { timestamp: 1780003700, values: [19.5, 40, 120] }
    ],
    style: { showTooltip: true, smooth: false, lineWidth: 2, lineOpacityPercent: 45,
        lineColor: '#123456', axisColor: '#abcdef', axisFontSizePercent: 150,
        titleColor: '#ffffff', titleFontSizePercent: 125 }
} };

const result = render(model);
assert.equal(result.option.series[0].type, 'parallel');
assert.equal(result.option.parallelAxis.length, 3);
assert.equal(result.option.parallelAxis[0].dim, 0);
assert.equal(result.option.parallelAxis[0].name, '<Temperature> (°C)');
assert.deepEqual(Array.from(result.option.series[0].data[0].value), [18.5, 42, 100]);
assert.equal(result.option.series[0].lineStyle.color, '#123456');
assert.equal(result.option.series[0].lineStyle.opacity, 0.45);
assert.equal(result.option.parallel.parallelAxisDefault.nameTextStyle.color, '#abcdef');
assert.equal(result.option.title.show, false);
assert.match(result.option.tooltip.formatter({ name: '<Now>', value: [18.5, 42, 100] }), /&lt;Temperature&gt;/);
assert.doesNotMatch(result.option.tooltip.formatter({ name: '<Now>', value: [18.5, 42, 100] }), /<Temperature>/);

const ipsView = render(model, 'ipsview', 320, true);
assert.equal(ipsView.option.title.show, true);
assert.equal(ipsView.option.backgroundColor, 'transparent');
assert.equal(ipsView.option.parallel.left, 28);

const empty = render({ ...model, parallel: { ...model.parallel, rows: [] } });
assert.equal(empty.option, undefined);
assert.equal(empty.emptyElement.hidden, false);

const truncated = render({ ...model, parallel: { ...model.parallel, truncated: true } });
assert.equal(truncated.warningElement.hidden, false);

console.log('Parallel layout verified.');
