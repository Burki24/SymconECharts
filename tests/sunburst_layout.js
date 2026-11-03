'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '..', 'EChartsSunburst', 'visualization', 'app.js'), 'utf8');
const designSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-design.js'), 'utf8')
    + '\n' + fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-hierarchy-values.js'), 'utf8');
const echarts = require(path.join(__dirname, '..', '.tools', 'echarts-runtime', 'node_modules', 'echarts'));
const palette = {
    background: '#101114', text: '#f4f5f7', muted: '#969aa2', accent: '#55cbb5',
    seriesColors: ['#5070dd', '#b6d634']
};

function render(model, width = 1000, mode = 'symcon', adaptToBackground = false, nativeChart = null) {
    const chartElement = { hidden: false, clientWidth: width, clientHeight: 400 };
    const errorElement = { hidden: true, textContent: '' };
    const emptyElement = { hidden: true, textContent: '' };
    const warningElement = { hidden: true, textContent: '' };
    let option;
    const chart = nativeChart || {
        setOption: next => { option = next; }, clear: () => {}, resize: () => {}, dispose: () => {}
    };
    if (nativeChart) {
        const setNativeOption = chart.setOption.bind(chart);
        chart.setOption = (next, ...rest) => { option = next; setNativeOption(next, ...rest); };
    }
    const window = {
        SYMC_VISUALIZATION: {
            mode, state: { status: 'ready', chart: model }, translations: {},
            options: { echartsThemes: { auto: palette, dark: palette }, tileHeaderVisible: mode === 'symcon', tileHeaderInset: mode === 'symcon' ? 58 : 0,
                adaptToBackground }
        },
        echarts: { init: () => chart, format: echarts.format }, addEventListener: () => {}, getComputedStyle: () => ({ color: '' })
    };
    const document = {
        body: { appendChild: () => {} },
        createElement: () => ({ style: {}, remove: () => {} }),
        getElementById: id => ({
            'echarts-sunburst-chart': chartElement,
            'echarts-sunburst-error': errorElement,
            'echarts-sunburst-empty': emptyElement,
            'echarts-sunburst-warning': warningElement
        })[id]
    };
    vm.runInNewContext(designSource, { window });
    vm.runInNewContext(source, { window, document });
    return { option, chartElement, errorElement, emptyElement };
}

const model = {
    family: 'sunburst', theme: 'dark', sunburst: {
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
const series = result.option.series[0];
assert.equal(result.option.title.show, true);
assert.ok(result.option.title.top >= 58);
assert.ok(series.center[1] - parseFloat(series.radius[1]) * 2
    >= result.option.title.top + result.option.title.textStyle.fontSize,
    'The Sunburst rings must clear the Symcon tile header and chart title.');
assert.equal(series.type, 'sunburst');
assert.equal(series.data[0].value, 7);
assert.equal(series.data[0].children[0].value, 5);
assert.equal(series.data[0].itemStyle.color, '#5070dd');
assert.equal(series.data[1].itemStyle.color, '#b6d634');
assert.equal(series.data[0].children[0].label.color, '#101114');
assert.equal(series.nodeClick, 'rootToNode');
assert.equal(series.sort, null);
assert.equal(series.startAngle, 90);
assert.equal(series.clockwise, true);
assert.equal(series.label.rotate, 'radial');
assert.equal(series.label.minAngle, 0);
assert.equal(series.levels[1].label.show, true);
assert.equal(series.levels[2].label.show, true);
assert.equal(series.levels[3].label.show, true);
assert.equal(series.itemStyle.borderRadius, 0);
assert.equal(result.option.tooltip.show, true);
assert.equal(series.radius[0], '0%');
assert.ok(parseFloat(series.radius[1]) <= 85,
    'The configured outer radius is a maximum when the title needs vertical room.');
assert.match(result.option.tooltip.formatter({ name: '<Battery>', value: 5 }), /&lt;Battery&gt;/);
assert.doesNotMatch(result.option.tooltip.formatter({ name: '<Battery>', value: 5 }), /<Battery>/);

const custom = render({ ...model, sunburst: { ...model.sunburst, style: {
    ...model.sunburst.style, showValues: true, groupColors: { 'Room A': '#112233' },
    innerRadiusPercent: 15, outerRadiusPercent: 90, borderWidth: 4,
    borderColor: '#ffffff', clickToRoot: false, labelColor: '#123456',
    labelFontSizePercent: 150,
    startAngle: 210, clockwise: false, sortMode: 'desc',
    labelRotation: 'tangential', labelMinAngle: 8,
    showInnerRingLabels: false, showMiddleRingLabels: true, showOuterRingLabels: false,
    innerRingLabelFontSizePercent: 175, middleRingLabelFontSizePercent: 125,
    outerRingLabelFontSizePercent: 0, borderRadius: 6, showTooltip: false
} } });
assert.equal(custom.option.series[0].data[0].itemStyle.color, '#112233');
assert.equal(custom.option.series[0].nodeClick, false);
assert.equal(custom.option.series[0].radius[0], '15%');
assert.ok(parseFloat(custom.option.series[0].radius[1]) <= 90);
assert.equal(custom.option.series[0].itemStyle.borderWidth, 4);
assert.equal(custom.option.series[0].itemStyle.borderColor, '#ffffff');
assert.equal(custom.option.series[0].label.color, '#123456');
assert.equal(custom.option.series[0].label.fontSize, 18);
assert.equal(custom.option.series[0].startAngle, 210);
assert.equal(custom.option.series[0].clockwise, false);
assert.equal(custom.option.series[0].sort, 'desc');
assert.equal(custom.option.series[0].label.rotate, 'tangential');
assert.equal(custom.option.series[0].label.minAngle, 8);
assert.equal(custom.option.series[0].levels[1].label.show, false);
assert.equal(custom.option.series[0].levels[2].label.show, true);
assert.equal(custom.option.series[0].levels[3].label.show, false);
assert.equal(custom.option.series[0].levels[1].label.fontSize, 21);
assert.equal(custom.option.series[0].levels[2].label.fontSize, 15);
assert.equal(custom.option.series[0].levels[3].label.fontSize, 18);
assert.equal(custom.option.series[0].itemStyle.borderRadius, 6);
assert.equal(custom.option.tooltip.show, false);
assert.equal(render({ ...model, sunburst: { ...model.sunburst, style: {
    ...model.sunburst.style, sortMode: 'original'
} } }).option.series[0].sort, null);
assert.match(custom.option.series[0].label.formatter({ name: 'Battery', value: 5, data: {} }), /Battery\n5/);
assert.equal(custom.option.series[0].label.formatter({
    name: 'Room A', value: 7, data: { children: [] }
}), 'Room A');

const transparent = render(model, 1000, 'ipsview', true);
assert.equal(transparent.option.backgroundColor, 'transparent');
const compact = render(model, 300);
assert.equal(compact.option.series[0].label.show, false);
const zeros = render({ ...model, sunburst: { ...model.sunburst, positiveCount: 0 } });
assert.equal(zeros.option, undefined);
assert.equal(zeros.emptyElement.hidden, false);

let deepNode = { id: 'variable-8', name: '<Value>', value: 5 };
for (let depth = 7; depth >= 1; depth--) {
    deepNode = { id: 'deep-' + depth, name: 'Level ' + depth, value: 5, children: [deepNode] };
}
const deepModel = { ...model, sunburst: { ...model.sunburst, depth: 8, data: [deepNode], style: {
    ...model.sunburst.style, outerRingLabelFontSizePercent: 150
} } };
for (const mode of ['symcon', 'ipsview']) {
    const deep = render(deepModel, 1000, mode);
    assert.equal(deep.option.series[0].levels.length, 9,
        'Sunburst must style eight user levels and its invisible root.');
    for (let depth = 3; depth <= 8; depth++) {
        assert.equal(deep.option.series[0].levels[depth].label.fontSize, 18,
            'Deeper rings must retain the existing outer-ring design controls.');
    }
    assert.equal(deep.option.tooltip.confine, true);
    assert.match(deep.option.tooltip.extraCssText, /overflow-wrap/);
}

const chart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 1000, height: 400 });
chart.setOption(result.option);
assert.equal(chart.getModel().getSeriesByIndex(0).type, 'series.sunburst');
assert.match(chart.renderToSVGString(), /Room A/);
chart.setOption(custom.option, true);
assert.doesNotMatch(chart.renderToSVGString(), /Room A/);
assert.match(chart.renderToSVGString(), /&lt;Battery&gt;/);
const deepOption = render(deepModel, 1000, 'ipsview').option;
chart.setOption({ ...deepOption, animation: false }, true);
chart.dispatchAction({ type: 'sunburstRootToNode', seriesId: 'sunburst', targetNodeId: 'deep-5' });
assert.equal(chart.getModel().getSeriesByIndex(0).getViewRoot().getId(), 'deep-5');
const designWindow = {};
vm.runInNewContext(designSource, { window: designWindow });
chart.resize({ width: 800, height: 500 });
designWindow.SYMC_ECHARTS_DESIGN.updateHierarchyChart(chart,
    { ...deepOption, animation: false }, 'sunburstRootToNode', true);
assert.equal(chart.getModel().getSeriesByIndex(0).getViewRoot().getId(), 'deep-5',
    'The pinned engine must preserve the drilled group after refresh and resize.');
chart.dispatchAction({ type: 'sunburstRootToNode', seriesId: 'sunburst', targetNodeId: 'deep-4' });
assert.equal(chart.getModel().getSeriesByIndex(0).getViewRoot().getId(), 'deep-4',
    'Native roll-up must navigate back to a parent.');
designWindow.SYMC_ECHARTS_DESIGN.updateHierarchyChart(chart, result.option, 'sunburstRootToNode', true);
assert.notEqual(chart.getModel().getSeriesByIndex(0).getViewRoot().getId(), 'deep-4',
    'Removing a group must reset navigation rather than restoring stale nodes.');
chart.dispose();

const deepLabelsChart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 520, height: 400 });
render(deepModel, 520, 'symcon', false, deepLabelsChart);
function assertDeepRadialLabelsFit() {
    const seriesModel = deepLabelsChart.getModel().getSeriesByIndex(0);
    seriesModel.getData().tree.root.eachNode(node => {
        const piece = seriesModel.getData().getItemGraphicEl(node.dataIndex);
        const label = piece && piece.getTextContent();
        const layout = node.getLayout();
        if (!label || label.ignore || !layout || node.depth === 0) { return; }
        assert.ok(label.getBoundingRect().width <= layout.r - layout.r0 - 3,
            'Deep radial labels must fit their native ring thickness rather than overlap adjacent rings.');
    });
}
assertDeepRadialLabelsFit();
deepLabelsChart.dispatchAction({ type: 'sunburstRootToNode', seriesId: 'sunburst', targetNodeId: 'deep-5' });
assertDeepRadialLabelsFit();
deepLabelsChart.dispose();
for (const rotation of ['tangential', 'horizontal']) {
    const rotatedChart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 520, height: 400 });
    render({ ...deepModel, sunburst: { ...deepModel.sunburst, style: {
        ...deepModel.sunburst.style, showValues: true, labelRotation: rotation
    } } }, 520, 'symcon', false, rotatedChart);
    const seriesModel = rotatedChart.getModel().getSeriesByIndex(0);
    seriesModel.getData().tree.root.eachNode(node => {
        const piece = seriesModel.getData().getItemGraphicEl(node.dataIndex);
        const label = piece && piece.getTextContent();
        const layout = node.getLayout();
        if (!label || label.ignore || !layout || node.depth === 0 || !label.style.text.trim()) { return; }
        const rect = label.getBoundingRect();
        const delta = rotation === 'tangential' ? Math.PI / 2 : (layout.startAngle + layout.endAngle) / 2;
        const radialExtent = rect.width * Math.abs(Math.cos(delta)) + rect.height * Math.abs(Math.sin(delta));
        assert.ok(radialExtent <= layout.r - layout.r0 - 3,
            'Deep tangential and horizontal labels must also stay inside their native ring thickness.');
    });
    rotatedChart.dispose();
}

const normalizedHierarchy = { ...model, sunburst: { ...model.sunburst, normalized: true, total: 130,
    style: { ...model.sunburst.style, showValues: true },
    data: [{ id: 'group-climate', name: 'Climate <unsafe>', value: 130, children: [
        { id: 'variable-1', name: 'Temperature', value: 50, originalValue: 20, unit: '°C', decimals: 1, minimum: -20, maximum: 60 },
        { id: 'variable-2', name: 'Humidity', value: 80, originalValue: 80, unit: '%', decimals: 0, minimum: 0, maximum: 100 }
    ] }] } };
for (const mode of ['symcon', 'ipsview']) {
    const normalizedOption = render(normalizedHierarchy, 1000, mode).option;
    assert.match(normalizedOption.title.subtext || '', /Normalized weights/);
    const leaf = normalizedOption.series[0].data[0].children[0];
    const parameters = { name: leaf.name, value: leaf.value, data: leaf, treePathInfo: [{ name: 'Climate <unsafe>' }, { name: leaf.name }] };
    const label = normalizedOption.series[0].label.formatter(parameters);
    assert.match(label, /20[.,]0 °C/, 'Visible leaf labels must show original values with their precision, not the projected weight.');
    const tooltip = normalizedOption.tooltip.formatter(parameters);
    assert.match(tooltip, /20[.,]0 °C/);
    assert.match(tooltip, /50/);
    assert.match(tooltip, /Normalized weight/);
    assert.match(tooltip, /&lt;unsafe&gt;/);
    assert.doesNotMatch(tooltip, /<unsafe>/);
    const parentTip = normalizedOption.tooltip.formatter({ name: 'Climate', value: 130, data: normalizedOption.series[0].data[0] });
    assert.match(parentTip, /Sum of normalized weights/);
    assert.doesNotMatch(parentTip, /°C|130\s*%/, 'Mixed original units must never become a physical parent sum or percentage.');
    const emptyTitle = render({ ...normalizedHierarchy, sunburst: { ...normalizedHierarchy.sunburst, title: '' } }, 1000, mode).option;
    assert.equal(emptyTitle.title.show, true, 'The weight notice remains visible even without a configured diagram title.');
    const pinned = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 1000, height: 400 });
    pinned.setOption({ ...normalizedOption, animation: false }, true);
    assert.match(pinned.renderToSVGString(), /Normalized weights/);
    assert.equal(pinned.getModel().getSeriesByIndex(0).getData().tree.getNodeById('variable-1').getValue(), 50);
    pinned.dispose();
}
console.log('Sunburst layout verified.');
