'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '..', 'EChartsGraphZ2M', 'visualization', 'app.js'), 'utf8');
const stylesheet = fs.readFileSync(path.join(__dirname, '..', 'EChartsGraphZ2M', 'visualization', 'style.css'), 'utf8');
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

function render(style, width = 1000, network = graph, mode = 'symcon', tileHeaderVisible = true) {
    const rootElement = { style: {} };
    const chartElement = { hidden: false, clientWidth: width, clientHeight: 400, style: {} };
    const metaElement = { hidden: true, textContent: '' };
    const legendElement = { hidden: true, innerHTML: '' };
    const errorElement = { hidden: true, textContent: '' };
    const warningElement = { hidden: true, textContent: '' };
    let option;
    const chart = {
        setOption: next => { option = next; }, clear: () => {}, resize: () => {}, dispose: () => {}
    };
    const model = { family: 'zigbee-graph', theme: 'dark', graph: { ...network, style } };
    const window = {
        SYMC_VISUALIZATION: {
            mode, state: { status: 'ready', chart: model }, translations: {},
            options: { echartsThemes: { auto: palette, dark: palette }, tileHeaderVisible }
        },
        echarts: { init: () => chart }, addEventListener: () => {}, getComputedStyle: () => ({ color: '' })
    };
    const document = {
        body: { appendChild: () => {} },
        createElement: () => ({ style: {}, remove: () => {} }),
        getElementById: id => ({
            'echarts-zigbee-graph-root': rootElement,
            'echarts-zigbee-graph-chart': chartElement,
            'echarts-zigbee-graph-meta': metaElement,
            'echarts-zigbee-graph-legend': legendElement,
            'echarts-zigbee-graph-error': errorElement,
            'echarts-zigbee-graph-warning': warningElement
        })[id]
    };
    vm.runInNewContext(designSource, { window });
    vm.runInNewContext(source, { window, document, Date });
    return { option, rootElement, chartElement, metaElement, legendElement };
}

const style = {
    graphLayout: 'force', nodeSize: 16, roamEnabled: true, showLabels: true,
    showLinkArrows: true, titleFontSizePercent: 100, labelFontSizePercent: 100
};
const large = render(style);
assert.match(stylesheet, /\.echarts-zigbee-graph-root, \.echarts-zigbee-graph-chart\s*\{[^}]*overflow: hidden;/s,
    'The Zigbee Graph canvas must clip force-layout nodes outside its own viewport.');
assert.equal(large.chartElement.style.top, '58px',
    'The Zigbee Graph canvas must start below the visible Symcon tile header.');
assert.equal(large.chartElement.style.height, 'calc(100% - 58px)',
    'The shifted canvas must end at the tile bottom rather than overflow it.');
assert.equal(large.rootElement.style.backgroundColor, large.option.backgroundColor,
    'The space behind the Symcon tile header must match the chart background.');
assert.equal(large.option.title.show, true);
assert.ok(58 + large.option.title.top >= 66 && large.option.series[0].top > large.option.title.top + 18,
    'The Zigbee Graph title and nodes must clear the Symcon tile header.');
const hiddenHeader = render(style, 1000, graph, 'symcon', false);
assert.equal(hiddenHeader.chartElement.style.top, '0px');
assert.equal(hiddenHeader.chartElement.style.height, '100%');
const untitled = render(style, 1000, { ...graph, title: '' });
assert.equal(untitled.option.title.show, false);
assert.equal(untitled.chartElement.style.top, '58px',
    'An empty diagram title must not let force-layout nodes overlap the Symcon tile name.');
const ipsView = render(style, 1000, graph, 'ipsview');
assert.equal(ipsView.chartElement.style.top, '0px');
assert.equal(ipsView.chartElement.style.height, '100%');
assert.equal(large.option.series[0].type, 'graph');
assert.equal(large.option.series[0].edgeSymbol[1], 'arrow');
assert.equal(large.option.series[0].links[0].lqi, 240);
assert.ok(large.option.series[0].links[0].lineStyle.opacity > large.option.series[0].links[1].lineStyle.opacity);
assert.ok(large.option.series[0].links[0].lineStyle.opacity < 0.55);
assert.equal(large.option.series[0].label.show, true);
assert.equal(large.option.series[0].label.position, 'right');
assert.equal(large.option.series[0].labelLayout.hideOverlap, true);
assert.equal(large.option.series[0].emphasis.focus, 'adjacency');
assert.equal(large.option.series[0].emphasis.label.show, true);
assert.equal(large.option.series[0].data[0].itemStyle.color, '#5070dd');
assert.equal(large.option.series[0].data[1].itemStyle.color, '#B6D634');
assert.equal(large.option.series[0].data[2].itemStyle.color, '#FF994D');
assert.match(large.legendElement.innerHTML, /Coordinator/);
assert.match(large.legendElement.innerHTML, /Router/);
assert.match(large.legendElement.innerHTML, /End device/);
assert.equal(large.legendElement.hidden, false);
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
assert.equal(render({ ...style, showLabels: false }).option.series[0].emphasis.label.show, false);
const custom = render({ ...style, nodeColor: '#123456', routerColor: '#234567', endDeviceColor: '#345678' });
assert.deepEqual(Array.from(custom.option.series[0].data, node => node.itemStyle.color),
    ['#123456', '#234567', '#345678']);
assert.match(custom.legendElement.innerHTML, /background-color:#234567/);
const lqiNetwork = {
    ...graph,
    links: [
        { source: 'device-a', target: 'device-b', lqi: 0 },
        { source: 'device-b', target: 'device-c', lqi: 128 },
        { source: 'device-c', target: 'device-a', lqi: 255 }
    ]
};
const lqiColored = render(style, 1000, lqiNetwork);
assert.deepEqual(Array.from(lqiColored.option.series[0].links, link => link.lineStyle.color),
    ['#D9534F', '#E6B94A', '#37A873']);
assert.equal(lqiColored.option.series[0].emphasis.focus, 'adjacency');
assert.match(lqiColored.legendElement.innerHTML, /LQI 0/);
assert.match(lqiColored.legendElement.innerHTML, /255/);
const customLqiColors = render({
    ...style, lqiLowColor: '#102030', lqiMidColor: '#405060', lqiHighColor: '#708090'
}, 1000, lqiNetwork);
assert.deepEqual(Array.from(customLqiColors.option.series[0].links, link => link.lineStyle.color),
    ['#102030', '#405060', '#708090']);
assert.match(customLqiColors.legendElement.innerHTML, /#102030,#405060,#708090/);
const unknown = render(style, 1000, {
    ...graph, nodes: [{ id: 'device-x', name: 'Unknown device', kind: 'Unknown' }], links: []
});
assert.equal(unknown.option.series[0].data[0].itemStyle.color, '#5070dd');
assert.match(unknown.legendElement.innerHTML, /Unknown/);

const echarts = require(path.join(__dirname, '..', '.tools', 'echarts-runtime', 'node_modules', 'echarts'));
const chart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 800, height: 400 });
chart.setOption(large.option);
assert.equal(chart.getModel().getSeriesByIndex(0).getData().count(), 3);
assert.equal(chart.getModel().getSeriesByIndex(0).getEdgeData().count(), 2);
chart.setOption(lqiColored.option, true);
assert.equal(chart.getModel().getSeriesByIndex(0).getEdgeData().getItemModel(0).get('lineStyle.color'), '#D9534F');
assert.equal(chart.getModel().getSeriesByIndex(0).getEdgeData().getItemModel(2).get('lineStyle.color'), '#37A873');
chart.dispose();

const crowded = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 500, height: 300 });
crowded.setOption({
    animation: false,
    series: [{
        ...large.option.series[0], layout: 'none', links: [],
        data: [
            { id: 'a', name: 'Alpha', x: 500, y: 100 },
            { id: 'b', name: 'Beta', x: 502, y: 100 },
            { id: 'c', name: 'Left', x: 0, y: 0 },
            { id: 'd', name: 'Right', x: 1000, y: 200 }
        ]
    }]
});
const crowdedSVG = crowded.renderToSVGString();
assert.equal((crowdedSVG.match(/>Alpha<\/text>/g) || []).length + (crowdedSVG.match(/>Beta<\/text>/g) || []).length, 1);
crowded.dispatchAction({ type: 'graphRoam', seriesIndex: 0, zoom: 80, originX: 250, originY: 150 });
const zoomedSVG = crowded.renderToSVGString();
assert.equal((zoomedSVG.match(/>Alpha<\/text>/g) || []).length + (zoomedSVG.match(/>Beta<\/text>/g) || []).length, 2);
crowded.dispose();

console.log('Zigbee graph renderer layout verified.');
