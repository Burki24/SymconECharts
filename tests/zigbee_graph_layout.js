'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '..', 'EChartsGraphZ2M', 'visualization', 'app.js'), 'utf8');
const designSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-design.js'), 'utf8');
const palette = {
    background: '#101114', text: '#f4f5f7', muted: '#969aa2',
    accent: '#55cbb5', seriesColors: ['#5070dd']
};
const graph = {
    title: 'Mesh', capturedAt: 1800000000, totalLinks: 4,
    nodes: [
        { id: 'device-a', name: '<Bridge>', kind: 'Coordinator' },
        { id: 'device-b', name: 'Router', kind: 'Router' },
        { id: 'device-c', name: 'Sensor', kind: 'EndDevice' }
    ],
    links: [
        { source: 'device-a', target: 'device-b', lqi: 240 },
        { source: 'device-b', target: 'device-c', lqi: 75 }
    ]
};

function render(style, width = 1000, network = graph) {
    const chartElement = { hidden: false, clientWidth: width, clientHeight: 400 };
    const metaElement = { hidden: true, textContent: '' };
    const errorElement = { hidden: true, textContent: '' };
    const warningElement = { hidden: true, textContent: '' };
    let option;
    const chart = {
        setOption: next => { option = next; }, clear: () => {}, resize: () => {}, dispose: () => {}
    };
    const model = { family: 'zigbee-graph', theme: 'dark', graph: { ...network, style } };
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
            'echarts-zigbee-graph-chart': chartElement,
            'echarts-zigbee-graph-meta': metaElement,
            'echarts-zigbee-graph-error': errorElement,
            'echarts-zigbee-graph-warning': warningElement
        })[id]
    };
    vm.runInNewContext(designSource, { window });
    vm.runInNewContext(source, { window, document, Date });
    return { option, metaElement };
}

const style = {
    graphLayout: 'force', nodeSize: 16, roamEnabled: true, showLabels: true,
    showLinkArrows: true, titleFontSizePercent: 100, labelFontSizePercent: 100
};
const large = render(style);
assert.equal(large.option.series[0].type, 'graph');
assert.equal(large.option.series[0].edgeSymbol[1], 'arrow');
assert.equal(large.option.series[0].links[0].lqi, 240);
assert.ok(large.option.series[0].links[0].lineStyle.opacity > large.option.series[0].links[1].lineStyle.opacity);
assert.equal(large.option.series[0].label.show, true);
assert.match(large.option.tooltip.formatter({ dataType: 'edge', data: large.option.series[0].links[0] }), /LQI: 240/);
assert.match(large.option.tooltip.formatter({ dataType: 'edge', data: large.option.series[0].links[0] }), /&lt;Bridge&gt;/);
assert.match(large.metaElement.textContent, /3 devices · 2\/4 links · snapshot:/);

const compact = render(style, 400);
assert.equal(compact.option.series[0].label.show, true);
assert.ok(compact.option.series[0].symbolSize < large.option.series[0].symbolSize);
const many = render(style, 400, {
    ...graph,
    nodes: Array.from({ length: 98 }, (_, index) => ({
        id: 'device-' + index, name: 'Device ' + index, kind: 'Router'
    })),
    links: []
});
assert.equal(many.option.series[0].label.show, false);
assert.equal(render({ ...style, showLinkArrows: false }).option.series[0].edgeSymbol[1], 'none');

const echarts = require(path.join(__dirname, '..', '.tools', 'echarts-runtime', 'node_modules', 'echarts'));
const chart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 800, height: 400 });
chart.setOption(large.option);
assert.equal(chart.getModel().getSeriesByIndex(0).getData().count(), 3);
assert.equal(chart.getModel().getSeriesByIndex(0).getEdgeData().count(), 2);
chart.dispose();

console.log('Zigbee graph renderer layout verified.');
