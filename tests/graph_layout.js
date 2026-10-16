'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '..', 'EChartsGraph', 'visualization', 'app.js'), 'utf8');
const designSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-design.js'), 'utf8');
const palette = {
    background: '#101114', text: '#f4f5f7', muted: '#969aa2',
    accent: '#55cbb5', seriesColors: ['#5070dd']
};
const graph = {
    title: 'Test network', nodes: [
        { id: 'object-101', name: 'Room', kind: 'category', displayValue: '' },
        { id: 'object-102', name: 'Sensor', kind: 'variable', displayValue: '21 °C' },
        { id: 'object-103', name: 'Sensor', kind: 'variable', displayValue: '22 °C' }
    ], links: [
        { source: 'object-101', target: 'object-102' },
        { source: 'object-101', target: 'object-103' }
    ]
};
const baseStyle = {
    graphLayout: 'force', nodeSize: 16, roamEnabled: true, showLabels: true,
    showValues: false, titleFontSizePercent: 100, labelFontSizePercent: 100
};

function render(style, mode = 'symcon', width = 800) {
    const chartElement = { hidden: false, clientWidth: width, clientHeight: 400 };
    const errorElement = { hidden: true, textContent: '' };
    const warningElement = { hidden: true, textContent: '' };
    let option;
    const chart = {
        setOption: next => { option = next; }, clear: () => {}, resize: () => {}, dispose: () => {}
    };
    const model = { family: 'graph', theme: 'dark', graph: { ...graph, style } };
    const window = {
        SYMC_VISUALIZATION: {
            mode, state: { status: 'ready', chart: model }, translations: {},
            options: { echartsThemes: { auto: palette, dark: palette }, tileHeaderVisible: true }
        },
        echarts: { init: () => chart }, addEventListener: () => {}, getComputedStyle: () => ({ color: '' })
    };
    const document = {
        body: { appendChild: () => {} },
        createElement: () => ({ style: {}, remove: () => {} }),
        getElementById: id => ({
            'echarts-graph-chart': chartElement,
            'echarts-graph-error': errorElement,
            'echarts-graph-warning': warningElement
        })[id]
    };
    vm.runInNewContext(designSource, { window });
    vm.runInNewContext(source, { window, document });
    return { option, errorElement, warningElement };
}

const force = render(baseStyle);
assert.equal(force.option.title.show, true);
assert.ok(force.option.title.top >= 58 && force.option.series[0].top > force.option.title.top + 18,
    'The Graph title and nodes must clear the Symcon tile header.');
assert.equal(force.option.series[0].type, 'graph');
assert.equal(force.option.series[0].layout, 'force');
assert.equal(force.option.series[0].roam, true);
assert.equal(force.option.series[0].data[1].id, 'object-102');
assert.equal(force.option.series[0].data[2].id, 'object-103');
assert.equal(force.option.series[0].links[1].target, 'object-103');
assert.equal(force.option.series[0].label.formatter({ data: force.option.series[0].data[1] }), 'Sensor');

const circular = render({ ...baseStyle, graphLayout: 'circular', showValues: true,
    nodeColor: '#123456', edgeColor: '#abcdef', labelFontSizePercent: 150 }, 'ipsview', 380);
assert.equal(circular.option.series[0].layout, 'circular');
assert.equal(circular.option.series[0].data[0].itemStyle.color, '#123456');
assert.equal(circular.option.series[0].lineStyle.color, '#abcdef');
assert.match(circular.option.series[0].label.formatter({ data: circular.option.series[0].data[1] }), /21 °C/);
assert.ok(circular.option.series[0].label.fontSize > force.option.series[0].label.fontSize);
assert.ok(circular.option.series[0].symbolSize < force.option.series[0].symbolSize);

const tooltip = force.option.tooltip.formatter({ data: { name: '<script>', displayValue: '<b>' } });
assert.equal(tooltip, '&lt;script&gt;<br>&lt;b&gt;');

const echarts = require(path.join(__dirname, '..', '.tools', 'echarts-runtime', 'node_modules', 'echarts'));
const chart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 800, height: 400 });
chart.setOption(force.option);
const series = chart.getModel().getSeriesByIndex(0);
assert.equal(series.type, 'series.graph');
assert.equal(series.getData().count(), 3);
assert.equal(series.getEdgeData().count(), 2);
chart.dispose();

console.log('Graph renderer layout verified.');
