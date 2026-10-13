'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '..', 'EChartsTree', 'visualization', 'app.js'), 'utf8');
const designSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-design.js'), 'utf8');
const palette = {
    background: '#101114', text: '#f4f5f7', muted: '#969aa2',
    accent: '#55cbb5', seriesColors: ['#111111', '#222222', '#333333']
};
const root = {
    id: 'object-100', name: 'Home', kind: 'category', children: [
        { id: 'object-101', name: 'Sensor', kind: 'instance', children: [
            { id: 'object-102', name: 'Temperature', kind: 'variable', displayValue: '21.3 °C', children: [] },
            { id: 'object-103', name: 'Temperature', kind: 'variable', displayValue: '22.4 °C', children: [] }
        ] }
    ]
};

function render(style, data = root, width = 800, mode = 'symcon', height = 400, truncated = false) {
    const chartElement = { hidden: false, clientWidth: width, clientHeight: height };
    const errorElement = { hidden: true, textContent: '' };
    const warningElement = { hidden: true, textContent: '' };
    let option;
    const listeners = {};
    const chart = {
        setOption: next => { option = next; }, clear: () => {}, resize: () => {}, dispose: () => {},
        on: (event, handler) => { listeners[event] = handler; },
        getModel: () => ({ getSeriesByIndex: () => ({ getDataParams: () => ({
            data: { id: 'object-101' }, collapsed: true
        }) }) })
    };
    const model = {
        family: 'tree', theme: 'dark', tree: { title: 'Home tree', style, root: data, count: 4, truncated }
    };
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
            'echarts-tree-chart': chartElement, 'echarts-tree-error': errorElement,
            'echarts-tree-warning': warningElement
        })[id]
    };
    vm.runInNewContext(designSource, { window });
    vm.runInNewContext(source, { window, document });
    return { get option() { return option; }, warningElement,
        collapseAndRefresh: () => {
            listeners.treeExpandAndCollapse({ dataIndex: 1 });
            window.handleMessage({ status: 'ready', chart: model });
        } };
}

const baseStyle = { treeLayout: 'orthogonal', orientation: 'LR', edgeShape: 'curve',
    initialDepth: 2, roamEnabled: true, showLabels: true, showValues: false, nodeSize: 10 };
const tree = render(baseStyle);
assert.equal(tree.option.series[0].type, 'tree');
assert.equal(tree.option.series[0].layout, 'orthogonal');
assert.equal(tree.option.series[0].orient, 'LR');
assert.equal(tree.option.series[0].roam, true);
assert.equal(tree.option.series[0].initialTreeDepth, 2);
assert.equal(tree.option.series[0].data[0].children[0].children[0].id, 'object-102');
assert.equal(tree.option.series[0].data[0].children[0].children[1].id, 'object-103');
assert.equal(tree.option.series[0].data[0].children[0].children[0].name, 'Temperature');
assert.equal(tree.option.series[0].data[0].children[0].children[1].name, 'Temperature');
assert.equal(tree.option.series[0].data[0].children[0].itemStyle.color, '#222222');
tree.collapseAndRefresh();
assert.equal(tree.option.series[0].data[0].children[0].collapsed, true);

const radial = render({ ...baseStyle, treeLayout: 'radial', edgeShape: 'polyline',
    showValues: true, labelFontSizePercent: 150, variableColor: '#aabbcc' }, root, 380, 'ipsview');
assert.equal(radial.option.series[0].layout, 'radial');
assert.equal(radial.option.series[0].edgeShape, 'polyline');
assert.equal(radial.option.series[0].data[0].children[0].children[0].itemStyle.color, '#aabbcc');
assert.match(radial.option.series[0].label.formatter({ data: {
    name: 'Temperature', kind: 'variable', displayValue: '21.3 °C'
} }), /21\.3 °C/);
assert.ok(radial.option.series[0].label.fontSize > tree.option.series[0].label.fontSize);

const limited = render(baseStyle, root, 800, 'symcon', 400, true);
assert.equal(limited.warningElement.hidden, false);

const healthRoot = { ...root, health: 'critical', children: [{
    ...root.children[0], health: 'critical', children: [
        { ...root.children[0].children[0], health: 'warning' },
        { ...root.children[0].children[1], health: 'critical' }
    ]
}] };
const health = render({ ...baseStyle, healthyColor: '#008800', warningColor: '#cc9900',
    criticalColor: '#cc0000' }, healthRoot);
assert.equal(health.option.series[0].data[0].itemStyle.color, '#cc0000');
assert.equal(health.option.series[0].data[0].children[0].children[0].itemStyle.color, '#cc9900');
assert.match(health.option.tooltip.formatter({ data: health.option.series[0].data[0] }), /Status: Critical/);

const hostile = render(baseStyle, { ...root, name: '<img src=x onerror=alert(1)>' });
const tooltip = hostile.option.tooltip.formatter({ data: { name: '<script>', displayValue: '<b>' } });
assert.ok(tooltip.includes('&lt;script&gt;'));
assert.ok(tooltip.includes('&lt;b&gt;'));
assert.ok(!tooltip.includes('<script>'));

const localECharts = path.join(__dirname, '..', '.tools', 'echarts-runtime', 'node_modules', 'echarts');
if (fs.existsSync(localECharts)) {
    const echarts = require(localECharts);
    const chart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 800, height: 400 });
    chart.setOption(tree.option);
    const series = chart.getModel().getSeriesByIndex(0);
    assert.equal(series.type, 'series.tree');
    assert.equal(series.getDataParams(1).data.id, 'object-100');
    chart.dispatchAction({ type: 'treeExpandAndCollapse', seriesId: 'tree', dataIndex: 1 });
    assert.equal(series.getDataParams(1).collapsed, true);
    chart.dispose();
}

console.log('Tree renderer layout verified.');
