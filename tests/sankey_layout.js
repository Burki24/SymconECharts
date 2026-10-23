'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.join(__dirname, '..');
const echarts = require(path.join(root, '.tools/echarts-runtime/node_modules/echarts'));
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
            options: { echartsThemes: { auto: palette, dark: palette }, tileHeaderVisible: mode === 'symcon' && (headerVisible), tileHeaderInset: mode === 'symcon' && (headerVisible) ? 58 : 0,
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
assert.equal(series.layoutIterations, 32);
assert.equal(series.data[0].label.position, 'right');
assert.equal(series.data[1].label.position, 'left');
assert.equal(series.label.distance, 5);
assert.equal(series.edgeLabel.show, false);
assert.equal(series.itemStyle.borderWidth, 0);
assert.equal(tile.option.animation, false);
assert.equal(tile.chartElement.style.top, '58px', 'The canvas viewport must protect the Symcon tile header while dragging nodes.');
assert.ok(series.top > tile.option.title.top + tile.option.title.textStyle.fontSize);
assert.match(tile.option.tooltip.formatter({ dataType: 'edge', data: series.links[0] }), /&lt;Supply&gt; → Load<br>20 W/);
assert.equal(series.label.formatter({ data: series.data[1] }), 'Load\n20 W');
for (const adapter of ['symcon', 'ipsview']) {
    for (const unit of ['kWh', 'µg/m³', '', '<W&>']) {
        const resolved = render({ ...model, sankey: { ...model.sankey, unit,
            style: { ...model.sankey.style, showEdgeValues: true } } }, adapter);
        const flow = resolved.option.series[0];
        const suffix = unit ? ' ' + unit : '';
        assert.equal(flow.links[0].value, 20, 'Resolved units must not convert or normalize widths.');
        assert.equal(flow.edgeLabel.formatter({ data: flow.links[0] }), '20' + suffix);
        assert.equal(flow.label.formatter({ data: flow.data[1] }), 'Load\n20' + suffix);
        const tooltip = resolved.option.tooltip.formatter({ dataType: 'edge', data: flow.links[0] });
        assert.ok(tooltip.endsWith('20' + (unit === '<W&>' ? ' &lt;W&amp;&gt;' : suffix)),
            'Effective units must remain escaped in tooltips and readable in native labels.');
    }
}

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
assert.equal(independent.option.series[0].emphasis.label.show, false, 'Hover must not restore deliberately hidden labels.');
assert.equal(independent.option.tooltip.show, false);
assert.equal(independent.option.series[0].draggable, false);
assert.equal(render(model, 'symcon', false).chartElement.style.top, '0px');
assert.equal(render({ ...model, sankey: { ...model.sankey, links: [] } }).emptyElement.hidden, false);

for (const adapter of ['symcon', 'ipsview']) {
    for (const orientation of ['horizontal', 'vertical']) {
        const signed = render({ ...model, sankey: { ...model.sankey,
            style: { ...model.sankey.style, orientation, showEdgeValues: true },
            links: [{ source: 'b', target: 'a', value: 20.5, originalValue: -20.5,
                configuredSource: 'a', configuredTarget: 'b', reversed: true, timestamp: 123 },
            { source: 'a', target: 'c', value: 10 }] } }, adapter).option;
        const reversed = signed.series[0].links[0];
        assert.equal(reversed.value, 20.5, 'The engine must receive only positive widths.');
        assert.equal(reversed.originalValue, -20.5, 'Signed source metadata must survive adapter projection.');
        const tooltip = signed.tooltip.formatter({ dataType: 'edge', data: reversed });
        assert.match(tooltip, /^Load → &lt;Supply&gt;<br>20[.,]5 W/);
        assert.match(tooltip, /Original value: -20[.,]5 W/);
        assert.match(tooltip, /Configured connection: &lt;Supply&gt; → Load/);
        assert.equal(signed.series[0].edgeLabel.formatter({ data: reversed }), (20.5).toLocaleString() + ' W');
        const engine = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 900, height: 500 });
        engine.setOption(signed);
        const actual = engine.getModel().getSeriesByIndex(0).getData();
        const a = actual.getItemLayout(0), b = actual.getItemLayout(1);
        assert.ok(orientation === 'horizontal' ? b.x < a.x : b.y < a.y,
            'Real ECharts geometry must follow the reversed flow, not the configured direction.');
        assert.match(engine.renderToSVGString(), /<path/);
        engine.dispose();
    }
}

const decoratedModel = { ...model, sankey: { ...model.sankey, style: { ...model.sankey.style,
    layoutIterations: 0, labelPosition: 'inside', labelDistance: 0, labelMaxWidth: 72,
    labelOverflow: 'breakAll', labelBold: true, hideOverlappingLabels: true,
    showEdgeValues: true, edgeLabelColor: '#876543', edgeLabelFontSizePercent: 150,
    nodeColor: '#dddddd', nodeColors: { b: '#ff0000', c: '#00ff00' },
    nodeBorderWidth: 3, nodeBorderColor: '#123456', nodeBorderRadius: 8,
    nodeOpacityPercent: 60, nodeShadowBlur: 9, nodeShadowColor: '#334455',
    hoverFocus: 'trajectory', highlightEdgeOpacityPercent: 100, blurEdgeOpacityPercent: 0
} } };
const decorated = render(decoratedModel).option;
const customized = decorated.series[0];
assert.equal(customized.layoutIterations, 0, 'Zero iterations must preserve list order, not fall back to 32.');
assert.equal(customized.data[0].itemStyle.color, '#dddddd');
assert.equal(customized.data[1].itemStyle.color, '#ff0000');
assert.equal(customized.data[2].itemStyle.color, '#00ff00', 'Duplicate labels must not merge individually colored node identities.');
assert.equal(customized.data[1].label.position, 'inside');
assert.equal(customized.label.distance, 0);
assert.equal(customized.label.width, 72);
assert.equal(customized.label.overflow, 'breakAll');
assert.equal(customized.label.fontWeight, 'bold');
assert.equal(customized.labelLayout({ dataIndex: 1, rect: { width: 24, height: 100 } }).hideOverlap, true);
assert.equal(customized.edgeLabel.show, true);
assert.equal(customized.edgeLabel.color, '#876543');
assert.equal(customized.edgeLabel.fontSize, 18);
assert.equal(customized.edgeLabel.formatter({ data: customized.links[0] }), '20 W');
assert.equal(customized.itemStyle.borderWidth, 3);
assert.equal(customized.itemStyle.borderRadius, 8);
assert.equal(customized.itemStyle.borderColor, '#123456');
assert.equal(customized.itemStyle.opacity, 0.6);
assert.equal(customized.itemStyle.shadowBlur, 9);
assert.equal(customized.itemStyle.shadowColor, '#334455');
assert.equal(customized.emphasis.focus, 'trajectory');
assert.equal(customized.emphasis.lineStyle.opacity, 1);
assert.equal(customized.blur.lineStyle.opacity, 0);
const noHover = render({ ...decoratedModel, sankey: { ...decoratedModel.sankey,
    style: { ...decoratedModel.sankey.style, hoverFocus: 'none', nodeOpacityPercent: 0 } } }).option.series[0];
assert.equal(noHover.emphasis.disabled, true);
assert.equal(noHover.emphasis.focus, 'none');
assert.equal(noHover.itemStyle.opacity, 0);

// Reproduce one source feeding many unequal consumers, without enabling hideOverlap in saved design.
const crowdedWeights = [500, 250, 120, 60, 30, 15, ...Array(18).fill(1)];
const crowdedNodes = [{ name: 'root', label: 'All consumers', value: crowdedWeights.reduce((sum, value) => sum + value, 0) },
    ...crowdedWeights.map((value, index) => ({ name: 'sink' + index, label: 'Consumer with a long name ' + index, value }))];
for (const orientation of ['vertical', 'horizontal']) {
    const crowdedModel = { ...model, sankey: { ...model.sankey, data: crowdedNodes,
        links: crowdedWeights.map((value, index) => ({ source: 'root', target: 'sink' + index, value })),
        style: { ...model.sankey.style, orientation, hideOverlappingLabels: false, labelMaxWidth: 0 } } };
    const before = JSON.stringify(crowdedModel);
    const option = render(crowdedModel).option;
    const engine = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 900, height: 500 });
    engine.setOption(option);
    const data = engine.getModel().getSeriesByIndex(0).getData();
    const labelBoxes = [];
    for (let index = 0; index < data.count(); index++) {
        const rectangle = data.getItemGraphicEl(index);
        const label = rectangle.getTextContent();
        if (orientation === 'vertical') {
            assert.ok(label.style.width <= Math.max(0, rectangle.shape.width - 4) + 0.01,
                'Vertical labels must fit their own consumer width instead of overlapping adjacent consumers.');
        } else {
            assert.ok(label.style.height <= Math.max(0, rectangle.shape.height - 2) + 0.01,
                'Horizontal labels must respect the available consumer height.');
        }
        const box = label.getBoundingRect().clone();
        box.applyTransform(label.getComputedTransform());
        if (!label.ignore && box.width > 0 && box.height > 0) {
            assert.ok(labelBoxes.every(other => !box.intersect(other)),
                'The real engine must render crowded source/consumer labels without overlapping.');
            labelBoxes.push(box);
        }
    }
    assert.ok(labelBoxes.length >= 2, 'Fitting must retain readable labels where space permits, not hide every label.');
    assert.equal(JSON.stringify(crowdedModel), before, 'Responsive label fitting must not mutate the configured design or model.');
    assert.match(option.tooltip.formatter({ dataType: 'node', data: option.series[0].data[24] }), /Consumer with a long name 23/);
    engine.dispose();
}
for (const option of [tile.option, independent.option, decorated,
    render({ ...decoratedModel, sankey: { ...decoratedModel.sankey, style: { ...decoratedModel.sankey.style, orientation: 'vertical' } } }).option]) {
    const chart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 900, height: 500 });
    chart.setOption(option);
    const actual = chart.getModel().getSeriesByIndex(0);
    assert.equal(actual.getData().count(), 3);
    assert.equal(actual.getGraph().edges.length, 2);
    const layout = actual.getData().getItemLayout(0);
    assert.ok(Number.isFinite(layout.x) && Number.isFinite(layout.y));
    assert.match(chart.renderToSVGString(), /<path/);
    if (option.series[0].edgeLabel.show) {
        const node = actual.getData().getItemGraphicEl(1);
        assert.equal(node.shape.r, 8, 'The real engine must apply rounded node rectangles.');
        assert.equal(node.style.stroke, '#123456');
        assert.equal(node.style.fill, '#ff0000');
        assert.equal(node.style.opacity, 0.6);
        assert.match(actual.getGraph().edges[0].getGraphicEl().getTextContent().style.text, /20 W/);
    }
    chart.dispose();
}
console.log('Sankey layout verified.');
