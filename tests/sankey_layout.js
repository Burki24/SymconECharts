'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.join(__dirname, '..');
const source = fs.readFileSync(path.join(root, 'EChartsSankey/visualization/app.js'), 'utf8');
const designSource = fs.readFileSync(path.join(root, 'libs/echarts-design.js'), 'utf8');
const palette = { background: '#101114', text: '#f4f5f7', muted: '#969aa2', border: '#737780', accent: '#55cbb5' };

function render(model, mode = 'symcon', headerVisible = true, width = 900) {
    const chartElement = { style: {}, hidden: false, clientWidth: width, clientHeight: 500 };
    const rootElement = { style: {} };
    const errorElement = { hidden: true, textContent: '' };
    const emptyElement = { hidden: true, textContent: '' };
    const warningElement = { hidden: true, textContent: '' };
    let option;
    const chart = { setOption: next => { option = next; }, clear: () => {}, resize: () => {}, dispose: () => {} };
    const window = {
        SYMC_VISUALIZATION: { mode, state: { status: 'ready', chart: model }, translations: {},
            options: { echartsThemes: { auto: palette, dark: palette }, tileHeaderVisible: headerVisible,
                adaptToBackground: mode === 'ipsview' } },
        echarts: { init: () => chart }, addEventListener: () => {}, getComputedStyle: () => ({ color: '' })
    };
    const document = {
        body: { appendChild: () => {} }, createElement: () => ({ style: {}, remove: () => {} }),
        getElementById: id => ({ 'echarts-sankey-root': rootElement, 'echarts-sankey-chart': chartElement,
            'echarts-sankey-error': errorElement, 'echarts-sankey-empty': emptyElement,
            'echarts-sankey-warning': warningElement })[id]
    };
    vm.runInNewContext(designSource, { window });
    vm.runInNewContext(source, { window, document });
    return { option, chartElement, errorElement, emptyElement };
}

const model = { family: 'sankey', theme: 'dark', sankey: {
    title: 'Current power', unit: 'W', data: [
        { name: 'a', label: '<Supply>', value: 30 },
        { name: 'b', label: 'Load', value: 20 }, { name: 'c', label: 'Load', value: 10 }
    ], links: [{ source: 'a', target: 'b', value: 20 }, { source: 'a', target: 'c', value: 10 }],
    style: { orientation: 'horizontal', nodeAlign: 'justify', nodeWidth: 24, nodeGap: 10,
        showLabels: true, showValues: true, showTooltip: true, draggable: true,
        edgeColorMode: 'gradient', edgeOpacityPercent: 35, edgeCurvenessPercent: 50,
        titleFontSizePercent: 100, labelFontSizePercent: 125, animationEnabled: false }
} };
const tile = render(model);
const series = tile.option.series[0];
assert.equal(series.type, 'sankey');
assert.equal(series.links[0].value, 20);
assert.equal(series.data[1].name, 'b');
assert.equal(series.data[2].name, 'c');
assert.equal(series.lineStyle.color, 'gradient');
assert.equal(series.lineStyle.opacity, 0.35);
assert.equal(series.nodeWidth, 24);
assert.equal(series.emphasis.focus, 'adjacency');
assert.equal(tile.option.animation, false);
assert.equal(tile.chartElement.style.top, '58px', 'The canvas viewport must protect the Symcon tile header while dragging nodes.');
assert.ok(series.top > tile.option.title.top + tile.option.title.textStyle.fontSize);
assert.match(tile.option.tooltip.formatter({ dataType: 'edge', data: series.links[0] }), /&lt;Supply&gt; → Load<br>20 W/);
assert.equal(series.label.formatter({ data: series.data[1] }), 'Load\n20 W');

const independent = render({ ...model, sankey: { ...model.sankey, style: { ...model.sankey.style,
    orientation: 'vertical', nodeColor: '#abcdef', edgeColorMode: 'fixed', edgeColor: '#123456',
    edgeOpacityPercent: 0, showLabels: false, showTooltip: false, draggable: false } } }, 'ipsview', true, 350);
assert.equal(independent.chartElement.style.top, '0px');
assert.equal(independent.option.backgroundColor, 'transparent');
assert.equal(independent.option.series[0].orient, 'vertical');
assert.equal(independent.option.series[0].lineStyle.color, '#123456');
assert.equal(independent.option.series[0].lineStyle.opacity, 0);
assert.equal(independent.option.series[0].data[0].itemStyle.color, '#abcdef');
assert.equal(independent.option.series[0].label.show, false);
assert.equal(independent.option.tooltip.show, false);
assert.equal(independent.option.series[0].draggable, false);
assert.equal(render(model, 'symcon', false).chartElement.style.top, '0px');
assert.equal(render({ ...model, sankey: { ...model.sankey, links: [] } }).emptyElement.hidden, false);

const echarts = require(path.join(root, '.tools/echarts-runtime/node_modules/echarts'));
for (const option of [tile.option, independent.option]) {
    const chart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 900, height: 500 });
    chart.setOption(option);
    const actual = chart.getModel().getSeriesByIndex(0);
    assert.equal(actual.getData().count(), 3);
    assert.equal(actual.getGraph().edges.length, 2);
    const layout = actual.getData().getItemLayout(0);
    assert.ok(Number.isFinite(layout.x) && Number.isFinite(layout.y));
    assert.match(chart.renderToSVGString(), /<path/);
    chart.dispose();
}
console.log('Sankey layout verified.');
