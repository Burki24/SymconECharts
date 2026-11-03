'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '..', 'EChartsTreemap', 'visualization', 'app.js'), 'utf8');
const designSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-design.js'), 'utf8')
    + '\n' + fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-hierarchy-values.js'), 'utf8');
const palette = {
    background: '#101114', text: '#f4f5f7', muted: '#969aa2', accent: '#55cbb5',
    seriesColors: ['#5070dd', '#b6d634']
};

function render(model, width = 1000, mode = 'symcon', adaptToBackground = false) {
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
            mode, state: { status: 'ready', chart: model }, translations: {},
            options: { echartsThemes: { auto: palette, dark: palette }, tileHeaderVisible: mode === 'symcon', tileHeaderInset: mode === 'symcon' ? 58 : 0,
                adaptToBackground }
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
assert.equal(result.option.title.show, true);
assert.ok(result.option.title.top >= 58
    && result.option.series[0].top > result.option.title.top + result.option.title.textStyle.fontSize,
    'The Treemap title and rectangles must clear the Symcon tile header.');
assert.equal(result.option.series[0].type, 'treemap');
assert.equal(result.option.series[0].data[0].value, 7);
assert.equal(result.option.series[0].data[0].children[0].value, 5);
assert.equal(result.option.series[0].data[0].itemStyle.color, '#5070dd');
assert.equal(result.option.series[0].data[1].itemStyle.color, '#b6d634');
assert.equal(result.option.series[0].data[0].upperLabel.color, '#101114');
assert.equal(result.option.series[0].data[1].upperLabel.color, '#101114');
assert.equal(result.option.series[0].data[0].itemStyle.borderColor, '#5070dd');
assert.equal(result.option.series[0].data[1].itemStyle.borderColor, '#b6d634');
assert.equal(result.option.series[0].nodeClick, 'zoomToNode');
assert.equal(result.option.series[0].roam, true);
assert.equal(result.option.series[0].breadcrumb.show, true);
assert.equal(result.option.series[0].levels[0].itemStyle.gapWidth, 0);
assert.equal(result.option.series[0].levels[1].itemStyle.borderWidth, 3);
assert.equal(result.option.series[0].levels[1].itemStyle.gapWidth, 3);
assert.equal(result.option.series[0].levels[2].itemStyle.borderWidth, 2);
assert.equal(result.option.series[0].levels[2].itemStyle.gapWidth, 1);
assert.equal(result.option.series[0].label.show, true);
assert.match(result.option.tooltip.formatter({ name: '<Battery>', value: 5, treePathInfo: [
    { name: 'Room A' }, { name: '<Battery>' }
] }), /&lt;Battery&gt;/);
assert.doesNotMatch(result.option.tooltip.formatter({ name: '<Battery>', value: 5 }), /<Battery>/);

const chosenLabels = render({ ...model, treemap: { ...model.treemap, style: {
    ...model.treemap.style, labelColor: '#123456', labelFontSizePercent: 150
} } });
assert.equal(chosenLabels.option.series[0].label.color, '#123456');
assert.equal(chosenLabels.option.series[0].label.fontSize, 18);
assert.equal(chosenLabels.option.series[0].upperLabel.fontSize, 18);
assert.equal(chosenLabels.option.series[0].data[0].upperLabel.color, '#123456');
assert.equal(chosenLabels.option.series[0].data[0].children[0].label.color, '#123456');

const custom = render({ ...model, treemap: { ...model.treemap, style: {
    ...model.treemap.style, showValues: true,
    groupColors: { 'Room A': '#112233' },
    groupLabelColor: '#ffffff', subgroupLabelColor: '#000000',
    groupLabelFontSizePercent: 150, subgroupLabelFontSizePercent: 125
} } });
assert.equal(custom.option.series[0].data[0].itemStyle.color, '#112233');
assert.equal(custom.option.series[0].data[1].itemStyle.color, '#b6d634');
assert.equal(custom.option.series[0].data[0].upperLabel.color, '#ffffff');
assert.equal(custom.option.series[0].data[0].upperLabel.fontSize, 18);
assert.match(custom.option.series[0].data[0].children[0].label.formatter({ name: 'Battery', value: 5 }), /Battery\n5/);

const framed = render({ ...model, treemap: { ...model.treemap, style: {
    ...model.treemap.style, frameColor: '#112233',
    groupGapWidth: 9, level1BorderWidth: 7, level1GapWidth: 8,
    level2BorderWidth: 5, level2GapWidth: 6,
    level3BorderWidth: 2,
    allowPanZoom: false, clickToZoom: false, showBreadcrumb: false
} } });
assert.equal(framed.option.series[0].roam, false);
assert.equal(framed.option.series[0].nodeClick, false);
assert.equal(framed.option.series[0].breadcrumb.show, false);
assert.equal(framed.option.series[0].bottom, 8);
assert.equal(framed.option.series[0].levels[0].itemStyle.gapWidth, 9);
assert.equal(framed.option.series[0].levels[1].itemStyle.borderWidth, 7);
assert.equal(framed.option.series[0].levels[1].itemStyle.gapWidth, 8);
assert.equal(framed.option.series[0].levels[2].itemStyle.borderWidth, 5);
assert.equal(framed.option.series[0].levels[2].itemStyle.gapWidth, 6);
assert.equal(framed.option.series[0].levels[3].itemStyle.borderWidth, 2);
assert.equal(framed.option.series[0].levels[3].itemStyle.gapWidth, undefined);
assert.equal(framed.option.series[0].data[0].itemStyle.borderColor, '#112233');
assert.equal(framed.option.series[0].data[0].children[0].itemStyle.borderColor, '#112233');

const nestedModel = { ...model, treemap: { ...model.treemap, data: [{
    id: 'group-a', name: 'Room A', value: 5, children: [{
        id: 'subgroup-a', name: 'Lighting', value: 5, children: [
            { id: 'variable-1', name: 'Lamp', value: 5 }
        ]
    }]
}] } };
const nested = render(nestedModel, 1000, 'ipsview');
assert.equal(nested.option.series[0].data[0].children[0].itemStyle.borderColor, '#5070dd');
assert.equal(nested.option.series[0].data[0].children[0].upperLabel.color,
    nested.option.series[0].data[0].upperLabel.color);
const nestedCustom = render({ ...nestedModel, treemap: { ...nestedModel.treemap, style: {
    ...model.treemap.style, subgroupLabelColor: '#123456', subgroupLabelFontSizePercent: 150
} } }, 1000, 'ipsview');
assert.equal(nestedCustom.option.series[0].data[0].children[0].upperLabel.color, '#123456');
assert.equal(nestedCustom.option.series[0].data[0].children[0].upperLabel.fontSize, 18);
const nestedFrame = render({ ...nestedModel, treemap: { ...nestedModel.treemap, style: {
    ...model.treemap.style, frameColor: '#112233'
} } }, 1000, 'ipsview');
assert.equal(nestedFrame.option.series[0].data[0].children[0].itemStyle.borderColor, '#112233');
assert.equal(nestedFrame.option.series[0].data[0].children[0].children[0].itemStyle.borderColor, '#112233');
let deepestNode = { id: 'variable-8', name: '<Value>', value: 5 };
for (let depth = 7; depth >= 1; depth--) {
    deepestNode = { id: 'deep-' + depth, name: 'Level ' + depth, value: 5, children: [deepestNode] };
}
const deepModel = { ...model, treemap: { ...model.treemap, depth: 8, data: [deepestNode] } };
for (const mode of ['symcon', 'ipsview']) {
    const deep = render(deepModel, 1000, mode);
    assert.equal(deep.option.series[0].levels.length, 9,
        'Treemap must style all eight hierarchy levels plus its invisible root.');
    assert.equal(deep.option.series[0].leafDepth, 3,
        'Deep hierarchies must use native drill-down instead of squeezing eight headers into one view.');
    for (let depth = 3; depth <= 8; depth++) {
        assert.equal(deep.option.series[0].levels[depth].itemStyle.borderWidth, 1);
    }
    let node = deep.option.series[0].data[0];
    for (let depth = 1; depth <= 7; depth++) {
        assert.equal(node.upperLabel.show, true, 'Deep groups require upper labels.');
        node = node.children[0];
    }
    assert.equal(node.id, 'variable-8');
    assert.equal(deep.option.tooltip.confine, true);
    assert.match(deep.option.tooltip.extraCssText, /overflow-wrap/);
    assert.match(deep.option.tooltip.formatter({ name: '<Value>', value: 5,
        treePathInfo: Array.from({ length: 7 }, (_, i) => ({ name: 'Level ' + (i + 1) })).concat({ name: '<Value>' })
    }), /Level 7 \/ &lt;Value&gt;/);
}
const transparentIPSView = render(model, 1000, 'ipsview', true);
assert.equal(transparentIPSView.option.backgroundColor, 'transparent');
const darkIPSView = render(model, 1000, 'ipsview');

const echarts = require(path.join(__dirname, '..', '.tools', 'echarts-runtime', 'node_modules', 'echarts'));
const chart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 1000, height: 400 });
function rootFill(svg) {
    const root = svg.match(/<path[^>]*ecmeta_data_index="0"[^>]*>/);
    assert.ok(root, 'The rendered Treemap root must be present');
    return root[0].match(/fill="([^"]+)"/)[1];
}
chart.setOption(result.option);
assert.equal(rootFill(chart.renderToSVGString()), palette.background);
chart.setOption(darkIPSView.option, true);
assert.equal(rootFill(chart.renderToSVGString()), palette.background);
chart.setOption(transparentIPSView.option, true);
assert.equal(rootFill(chart.renderToSVGString()), 'none');
chart.setOption(chosenLabels.option);
assert.equal(chart.getModel().getSeriesByIndex(0).type, 'series.treemap');
assert.match(chart.renderToSVGString(), /Room A/);
chart.setOption(custom.option, true);
assert.match(chart.renderToSVGString(), /Room A/);
chart.setOption(framed.option, true);
assert.match(chart.renderToSVGString(), /Room A/);
assert.equal(rootFill(chart.renderToSVGString()), '#112233');
const deepOption = render(deepModel, 1000, 'ipsview').option;
chart.setOption({ ...deepOption, animation: false }, true);
chart.dispatchAction({ type: 'treemapRootToNode', seriesId: 'treemap', targetNodeId: 'deep-3' });
chart.dispatchAction({ type: 'treemapRootToNode', seriesId: 'treemap', targetNodeId: 'deep-5' });
assert.equal(chart.getModel().getSeriesByIndex(0).getViewRoot().getId(), 'deep-5');
const designWindow = {};
vm.runInNewContext(designSource, { window: designWindow });
designWindow.SYMC_ECHARTS_DESIGN.updateHierarchyChart(chart,
    { ...deepOption, animation: false }, 'treemapRootToNode', true);
assert.equal(chart.getModel().getSeriesByIndex(0).getViewRoot().getId(), 'deep-5',
    'The pinned ECharts engine must retain a native drilled group when its values refresh.');
assert.match(chart.renderToSVGString(), /Level 6/,
    'A retained deep Treemap view must still render its nested groups, not just a breadcrumb.');
chart.dispatchAction({ type: 'treemapRootToNode', seriesId: 'treemap', targetNodeId: 'deep-7' });
designWindow.SYMC_ECHARTS_DESIGN.updateHierarchyChart(chart,
    { ...deepOption, animation: false }, 'treemapRootToNode', true);
assert.equal(chart.getModel().getSeriesByIndex(0).getViewRoot().getId(), 'deep-7');
assert.match(chart.renderToSVGString(), /&lt;Value&gt;/,
    'Refreshing the seventh-level group must still render the eighth-level variable leaf.');
chart.dispatchAction({ type: 'treemapRootToNode', seriesId: 'treemap', targetNodeId: 'deep-2' });
assert.equal(chart.getModel().getSeriesByIndex(0).getViewRoot().getId(), 'deep-2',
    'The native breadcrumb action must navigate back to an ancestor.');
designWindow.SYMC_ECHARTS_DESIGN.updateHierarchyChart(chart, result.option, 'treemapRootToNode', true);
assert.notEqual(chart.getModel().getSeriesByIndex(0).getViewRoot().getId(), 'deep-2',
    'A removed group must reset navigation rather than restoring a stale ID.');
chart.dispose();

const compact = render(model, 400);
assert.equal(compact.option.series[0].label.show, false);
assert.equal(compact.option.series[0].breadcrumb.show, false);
const compactDeep = render(deepModel, 400);
assert.equal(compactDeep.option.series[0].breadcrumb.show, true,
    'Deep compact Treemaps must keep native back navigation after drilling into a group.');
const compactDeepNoBreadcrumb = render({ ...deepModel, treemap: { ...deepModel.treemap,
    style: { ...deepModel.treemap.style, showBreadcrumb: false } } }, 400);
assert.equal(compactDeepNoBreadcrumb.option.series[0].breadcrumb.show, false,
    'An explicit breadcrumb opt-out must remain respected even in a deep compact Treemap.');
const compactChart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 400, height: 400 });
compactChart.setOption({ ...compactDeep.option, animation: false }, true);
compactChart.dispatchAction({ type: 'treemapRootToNode', seriesId: 'treemap', targetNodeId: 'deep-3' });
compactChart.dispatchAction({ type: 'treemapRootToNode', seriesId: 'treemap', targetNodeId: 'deep-5' });
assert.equal(compactChart.getModel().getSeriesByIndex(0).getViewRoot().getId(), 'deep-5');
assert.match(compactChart.renderToSVGString(), /Level 2/,
    'The compact native breadcrumb must render an ancestor after a deep drill.');
designWindow.SYMC_ECHARTS_DESIGN.updateHierarchyChart(compactChart,
    { ...compactDeep.option, animation: false }, 'treemapRootToNode', true);
assert.match(compactChart.renderToSVGString(), /Level 6/,
    'Refreshing a deep compact view must keep its actual nested rectangles and labels.');
compactChart.dispatchAction({ type: 'treemapRootToNode', seriesId: 'treemap', targetNodeId: 'deep-2' });
assert.equal(compactChart.getModel().getSeriesByIndex(0).getViewRoot().getId(), 'deep-2',
    'Compact deep Treemaps must support the native ancestor/back action.');
compactChart.dispose();
const zeros = render({ ...model, treemap: { ...model.treemap, positiveCount: 0 } });
assert.equal(zeros.option, undefined);
assert.equal(zeros.emptyElement.hidden, false);

const normalizedHierarchy = { ...model, treemap: { ...model.treemap, normalized: true, total: 130,
    style: { ...model.treemap.style, showValues: true },
    data: [{ id: 'group-climate', name: 'Climate <unsafe>', value: 130, children: [
        { id: 'variable-1', name: 'Temperature', value: 50, originalValue: 20, unit: '°C', decimals: 1, minimum: -20, maximum: 60 },
        { id: 'variable-2', name: 'Humidity', value: 80, originalValue: 80, unit: '%', decimals: 0, minimum: 0, maximum: 100 }
    ] }] } };
for (const mode of ['symcon', 'ipsview']) {
    const normalizedOption = render(normalizedHierarchy, 1000, mode).option;
    assert.match(normalizedOption.title.subtext || '', /Normalized weights/);
    const leaf = normalizedOption.series[0].data[0].children[0];
    const parameters = { name: leaf.name, value: leaf.value, data: leaf, treePathInfo: [{ name: 'Climate <unsafe>' }, { name: leaf.name }] };
    const label = leaf.label.formatter(parameters);
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
    const emptyTitle = render({ ...normalizedHierarchy, treemap: { ...normalizedHierarchy.treemap, title: '' } }, 1000, mode).option;
    assert.equal(emptyTitle.title.show, true, 'The weight notice remains visible even without a configured diagram title.');
    const pinned = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 1000, height: 400 });
    pinned.setOption({ ...normalizedOption, animation: false }, true);
    assert.match(pinned.renderToSVGString(), /Normalized weights/);
    assert.equal(pinned.getModel().getSeriesByIndex(0).getData().tree.getNodeById('variable-1').getValue(), 50);
    pinned.dispose();
}
console.log('Treemap layout verified.');
