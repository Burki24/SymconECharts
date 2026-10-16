'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '..', 'EChartsTreemap', 'visualization', 'app.js'), 'utf8');
const designSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-design.js'), 'utf8');
const palette = {
    background: '#101114', text: '#f4f5f7', muted: '#969aa2', accent: '#55cbb5',
    seriesColors: ['#5070dd', '#b6d634']
};

function render(model, width = 1000) {
    const chartElement = { hidden: false, clientWidth: width, clientHeight: 400 };
    const errorElement = { hidden: true, textContent: '' };
    const emptyElement = { hidden: true, textContent: '' };
    const warningElement = { hidden: true, textContent: '' };
    let option;
    const chart = {
        setOption: next => { option = next; }, clear: () => {}, resize: () => {}, dispose: () => {}
    };
    const window = {
        SYMC_VISUALIZATION: {
            mode: 'symcon', state: { status: 'ready', chart: model }, translations: {},
            options: { echartsThemes: { auto: palette, dark: palette }, tileHeaderVisible: true }
        },
        echarts: { init: () => chart }, addEventListener: () => {}, getComputedStyle: () => ({ color: '' })
    };
    const document = {
        body: { appendChild: () => {} },
        createElement: () => ({ style: {}, remove: () => {} }),
        getElementById: id => ({
            'echarts-treemap-chart': chartElement,
            'echarts-treemap-error': errorElement,
            'echarts-treemap-empty': emptyElement,
            'echarts-treemap-warning': warningElement
        })[id]
    };
    vm.runInNewContext(designSource, { window });
    vm.runInNewContext(source, { window, document });
    return { option, chartElement, errorElement, emptyElement };
}

const model = {
    family: 'treemap', theme: 'dark', treemap: {
        title: 'Rooms', positiveCount: 2,
        style: { showLabels: true, titleFontSizePercent: 100, labelFontSizePercent: 100 },
        data: [
            { id: 'group-a', name: 'Room A', value: 7, children: [
                { id: 'variable-1', name: '<Battery>', value: 5 },
                { id: 'variable-2', name: 'Sensor', value: 2 }
            ] },
            { id: 'group-b', name: 'Room B', value: 0, children: [
                { id: 'variable-3', name: 'Empty', value: 0 }
            ] }
        ]
    }
};

const result = render(model);
assert.equal(result.option.series[0].type, 'treemap');
assert.equal(result.option.series[0].data[0].value, 7);
assert.equal(result.option.series[0].data[0].children[0].value, 5);
assert.equal(result.option.series[0].data[0].itemStyle.color, '#5070dd');
assert.equal(result.option.series[0].data[1].itemStyle.color, '#b6d634');
assert.equal(result.option.series[0].data[1].upperLabel.color, '#101114');
assert.equal(result.option.series[0].nodeClick, 'zoomToNode');
assert.equal(result.option.series[0].label.show, true);
assert.match(result.option.tooltip.formatter({ name: '<Battery>', value: 5, treePathInfo: [
    { name: 'Room A' }, { name: '<Battery>' }
] }), /&lt;Battery&gt;/);
assert.doesNotMatch(result.option.tooltip.formatter({ name: '<Battery>', value: 5 }), /<Battery>/);

const compact = render(model, 400);
assert.equal(compact.option.series[0].label.show, false);
assert.equal(compact.option.series[0].breadcrumb.show, false);
const zeros = render({ ...model, treemap: { ...model.treemap, positiveCount: 0 } });
assert.equal(zeros.option, undefined);
assert.equal(zeros.emptyElement.hidden, false);

console.log('Treemap layout verified.');
