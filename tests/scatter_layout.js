'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '..', 'EChartsScatter', 'visualization', 'app.js'), 'utf8');
const designSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-design.js'), 'utf8');
const zoomSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-zoom.js'), 'utf8');
const selectionSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-series-selection.js'), 'utf8');
const palette = {
    background: '#101114', text: '#f4f5f7', muted: '#969aa2', border: '#999999',
    track: '#34363b', accent: '#55cbb5', seriesColors: ['#5588cc', '#dd8844']
};

function render(model, mode = 'symcon', width = 700, height = 410) {
    const chartElement = { hidden: false, clientWidth: width, clientHeight: height,
        addEventListener: () => {}, getBoundingClientRect: () => ({ left: 0, top: 0, width, height }) };
    const emptyElement = { hidden: true, textContent: '' };
    const warningElement = { hidden: true, textContent: '' };
    const selectionElement = { hidden: true, textContent: '', style: { setProperty: () => {} } };
    const errorElement = { hidden: true, textContent: '' };
    let option;
    let resizeCount = 0;
    let chartWidth = width;
    let chartHeight = height;
    const listeners = {};
    const windowListeners = {};
    const chart = {
        setOption: next => { option = next; }, getOption: () => option, clear: () => {},
        resize: () => { resizeCount++; chartWidth = chartElement.clientWidth;
            chartHeight = chartElement.clientHeight; },
        getWidth: () => chartWidth, getHeight: () => chartHeight, dispose: () => {},
        on: (name, listener) => { listeners[name] = listener; }
    };
    const window = {
        SYMC_VISUALIZATION: {
            mode, state: { status: 'ready', chart: model }, translations: {},
            options: { echartsThemes: { auto: palette, dark: palette }, tileHeaderVisible: mode === 'symcon', tileHeaderInset: mode === 'symcon' ? 58 : 0}
        },
        echarts: { init: () => chart },
        addEventListener: (name, listener) => { windowListeners[name] = listener; },
        getComputedStyle: () => ({ color: '' })
    };
    const document = {
        body: { appendChild: () => {} },
        createElement: () => ({ style: {}, remove: () => {} }),
        getElementById: id => ({
            'echarts-scatter-chart': chartElement,
            'echarts-scatter-empty': emptyElement,
            'echarts-scatter-warning': warningElement,
            'echarts-scatter-selection': selectionElement,
            'echarts-scatter-error': errorElement
        })[id]
    };
    vm.runInNewContext(designSource, { window });
    vm.runInNewContext(zoomSource, { window });
    vm.runInNewContext(selectionSource, { window });
    vm.runInNewContext(source, { window, document });
    return { option, window, chartElement, emptyElement, warningElement, selectionElement, errorElement,
        emit: (name, event) => listeners[name](event),
        triggerResize: () => windowListeners.resize(),
        currentOption: () => option,
        resizeCount: () => resizeCount };
}

const model = {
    theme: 'dark', scatter: { title: 'Climate', style: {
        pointSize: 10, bubbleMaxSize: 40, showGrid: true, enableZoom: true, showValues: true,
        valueLabelPosition: 'right', valueLabelDistance: 8,
        xAxisRangeMode: 'data', yAxisRangeMode: 'data',
        xAxisMinimum: 0, xAxisMaximum: 100, yAxisMinimum: 0, yAxisMaximum: 100,
        pointColor: '#123456', axisColor: '#cccccc'
    } },
    roles: {
        x: { label: 'Außen', unit: '°C', decimals: 1 },
        y: { label: 'Feuchte', unit: '%', decimals: 0 },
        size: { label: 'Wind', unit: 'm/s', decimals: 1 }
    },
    range: { key: '24h', startTimestamp: 1000, endTimestamp: 2000 },
    points: [[100, 10, 50, 1], [200, 20, 55, 3]], truncated: false
};
const bubble = render(model);
const groupedModel = { ...model, scatter: { ...model.scatter, style: { ...model.scatter.style, pointColor: '' } }, variant: 'groups', points: undefined, groups: [
    { id: 'group-a', label: 'Same <name>', colorIndex: 0, roles: model.roles, points: model.points },
    { id: 'group-b', label: 'Same <name>', colorIndex: 1, roles: { x: { ...model.roles.x, label: 'Other X' }, y: model.roles.y },
        points: [[100, 30, 60], [300, 40, 70]] }
] };
for (const mode of ['symcon', 'ipsview']) {
    const grouped = render(groupedModel, mode);
    assert.equal(grouped.option.series.length, 2, 'Every comparison group needs its own native series.');
    assert.equal(grouped.option.legend.formatter('group-b'), 'Same <name>');
    assert.equal(grouped.option.series[0].name, 'group-a');
    assert.equal(grouped.option.series[1].name, 'group-b', 'Equal labels must not merge legend identities.');
    assert.notEqual(grouped.option.series[0].itemStyle.color, grouped.option.series[1].itemStyle.color);
    assert.equal(grouped.option.series[1].symbolSize([30, 60, null, 100]), 10);
    const tooltip = grouped.option.tooltip.formatter({ seriesIndex: 1, value: [30, 60, null, 100] });
    assert.match(tooltip, /Same &lt;name&gt;/);
    assert.match(tooltip, /Other X/);
    assert.doesNotMatch(tooltip, /Wind/);
    grouped.emit('brushselected', { batch: [{ areas: [{}], selected: [
        { seriesIndex: 0, dataIndex: [1] }, { seriesIndex: 1, dataIndex: [0] }
    ] }] });
    assert.match(grouped.selectionElement.textContent, /Selected points: 2/);
    assert.match(grouped.selectionElement.textContent, /20.*30/);
    grouped.option.legend.selected['group-b'] = false;
    grouped.window.handleMessage({ status: 'ready', chart: { ...groupedModel,
        groups: groupedModel.groups.slice().reverse() } });
    assert.equal(grouped.currentOption().legend.selected['group-b'], false,
        'The shared selection state must keep a hidden group after reorder and refresh.');
    assert.ok(grouped.currentOption().grid.bottom > bubble.option.grid.bottom,
        'Group navigation must reserve space without changing legacy layouts.');
    const engine = require('../.tools/echarts-runtime/node_modules/echarts').init(null, null,
        { renderer: 'svg', ssr: true, width: 700, height: 520 });
    try {
        engine.setOption({ ...grouped.currentOption(), animation: false });
        assert.equal(engine.getModel().getSeriesCount(), 2);
        assert.match(engine.renderToSVGString(), /Same &lt;name&gt;/);
        assert.doesNotMatch(engine.renderToSVGString(), />group-[ab]</);
        engine.dispatchAction({ type: 'legendSelect', name: 'group-b' });
        assert.equal(engine.getModel().getSeriesByIndex(1).getData().count(), 2);
    } finally { engine.dispose(); }
}
const globalSizes = { ...groupedModel, groups: groupedModel.groups.map((g, i) => ({ ...g, roles: model.roles,
    points: i === 0 ? [[100, 10, 50, 1], [200, 20, 55, 3]] : [[100, 30, 60, 5], [200, 40, 70, 7]] })) };
const scale = render(globalSizes);
assert.equal(scale.option.series[0].symbolSize([20, 55, 3, 200]), 20,
    'Automatic Bubble scales use the complete size population across groups.');
scale.option.legend.selected['group-b'] = false;
scale.window.handleMessage({ status: 'ready', chart: globalSizes });
assert.equal(scale.currentOption().series[0].symbolSize([20, 55, 3, 200]), 20,
    'Hiding a group must not rescale the remaining Bubble sizes.');
const globalColors = render({ ...globalSizes, scatter: { ...globalSizes.scatter, style: {
    ...globalSizes.scatter.style, colorMapEnabled: true, colorMapDimension: 'size' } } });
assert.deepEqual(Array.from(globalColors.option.visualMap.seriesIndex), [0, 1]);
assert.equal(globalColors.option.visualMap.min, 1);
assert.equal(globalColors.option.visualMap.max, 7);
const extremes = render({ ...globalSizes, groups: globalSizes.groups.map((g, i) => ({ ...g,
    points: [[100, 10, 50, i === 0 ? -1e308 : 1e308]] })) });
assert.equal(extremes.option.series[0].symbolSize([10, 50, 0, 100]), 25,
    'Opposite finite group extrema must not overflow the shared automatic Bubble scale.');
const visibleBubbleOption = bubble.currentOption();
bubble.window.handleMessage({ type: 'click' });
assert.equal(bubble.currentOption(), visibleBubbleOption,
    'An unrelated browser click message must not replace the Scatter chart.');
assert.equal(bubble.errorElement.hidden, true,
    'An unrelated browser click message must not show a data-load error.');
assert.equal(bubble.option.series[0].type, 'scatter');
assert.equal(bubble.option.series[0].data.length, 2);
assert.equal(bubble.option.series[0].symbolSize([10, 50, 1, 100]), 10);
assert.equal(bubble.option.series[0].symbolSize([20, 55, 3, 200]), 40);
for (const mode of ['symcon', 'ipsview']) {
    const fixedModel = { ...model, scatter: { ...model.scatter, style: {
        ...model.scatter.style, bubbleRangeMode: 'manual', bubbleMinimum: 0, bubbleMaximum: 100
    } } };
    const fixed = render(fixedModel, mode);
    assert.equal(fixed.option.series[0].symbolSize([10, 50, 50, 100]), 25,
        'Fixed Bubble bounds must map the same original value to the same diameter.');
    assert.equal(fixed.option.series[0].symbolSize([10, 50, -10, 100]), 10);
    assert.equal(fixed.option.series[0].symbolSize([10, 50, 150, 100]), 40);
    const otherWindow = render({ ...fixedModel, points: [[300, 10, 50, 50]] }, mode);
    assert.equal(otherWindow.option.series[0].symbolSize([10, 50, 50, 300]), 25,
        'A single equal-size sample must still use its fixed range, not the automatic fallback.');
    assert.equal(otherWindow.option.series[0].data[0].value[2], 50);
    assert.match(otherWindow.option.tooltip.formatter({ value: [10, 50, 150, 300] }), /150[.,]0 m\/s/,
        'Clipping geometry must not clip the original value or unit in the tooltip.');
    fixed.window.handleMessage({ status: 'ready', chart: { ...fixedModel,
        points: [[400, 10, 50, 50], [500, 20, 55, 90]] } });
    assert.equal(fixed.currentOption().series[0].symbolSize([10, 50, 50, 400]), 25);
    const fixedScatter = render({ ...fixedModel, roles: { x: model.roles.x, y: model.roles.y } }, mode);
    assert.equal(fixedScatter.option.series[0].symbolSize([10, 50, null, 100]), 10,
        'A fixed Bubble range must not change ordinary Scatter points.');
}
assert.equal(render({ ...model, points: [[100, 10, 50, 3]] }).option.series[0].symbolSize([10, 50, 3, 100]), 10,
    'Automatic equal-size samples must retain the existing base-size fallback.');
const echarts = require('../.tools/echarts-runtime/node_modules/echarts');
for (const mode of ['symcon', 'ipsview']) {
    const fixed = render({ ...model, points: [-10, 0, 50, 100, 150].map((size, i) => [100 + i, 10 + i, 50, size]),
        scatter: { ...model.scatter, style: { ...model.scatter.style, animationEnabled: false,
            bubbleRangeMode: 'manual', bubbleMinimum: 0, bubbleMaximum: 100 } } }, mode);
    const engine = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 700, height: 410 });
    try {
        engine.setOption(fixed.option);
        const data = engine.getModel().getSeriesByIndex(0).getData();
        for (const [index, diameter] of [10, 10, 25, 40, 40].entries()) {
            assert.equal(data.getItemVisual(index, 'symbolSize'), diameter,
                'The pinned native ECharts engine must receive the fixed, bounded Bubble diameters.');
        }
        assert.match(engine.renderToSVGString(), /<svg/);
    } finally { engine.dispose(); }
}
assert.equal(bubble.option.series[0].label.position, 'right');
assert.equal(bubble.option.series[0].label.distance, 8);
assert.equal(bubble.option.series[0].label.formatter({ value: [10, 50, 1, 100] }), '50 %');
const xLabels = render({ ...model, scatter: { ...model.scatter, style: {
    ...model.scatter.style, valueLabelContent: 'x'
} } });
assert.match(xLabels.option.series[0].label.formatter({ value: [10, 50, 1, 100] }), /^10[.,]0 °C$/);
const bothLabels = render({ ...model, scatter: { ...model.scatter, style: {
    ...model.scatter.style, valueLabelContent: 'both'
} } });
assert.match(bothLabels.option.series[0].label.formatter({ value: [10, 50, 1, 100] }), /^10[.,]0 °C \/ 50 %$/);
assert.equal(bubble.option.series[0].labelLayout.hideOverlap, true,
    'Crowded value labels must be suppressed without hiding their points or tooltips.');
assert.equal(bubble.option.series[0].data[0].label, undefined,
    'Existing Scatter instances must keep their original point labels when adaptation is disabled.');
const densePoints = Array.from({ length: 30 }, (_, index) =>
    [100 + index, 1 + index * 0.45, 94 - index * 1.4, 1]);
const denseModel = { ...model, points: densePoints, scatter: { ...model.scatter, style: {
    ...model.scatter.style, adaptiveValueLabels: true, valueLabelContent: 'both'
} } };
const compactLabels = render(denseModel, 'ipsview', 400, 240);
const spaciousLabels = render(denseModel, 'ipsview', 1500, 800);
const shownLabels = option => option.series[0].data.filter(point => point.label.show).length;
assert.equal(compactLabels.option.series[0].data.length, densePoints.length,
    'Adaptive labels must never remove points.');
assert.ok(shownLabels(compactLabels.option) > 0 && shownLabels(compactLabels.option) < densePoints.length,
    'A small chart must show representative labels while suppressing crowded ones.');
assert.ok(shownLabels(spaciousLabels.option) > shownLabels(compactLabels.option),
    'A larger chart must make room for more point labels.');
assert.ok(compactLabels.option.tooltip.formatter({ value: [1, 94, 1, 100] }).includes('Außen'),
    'Hidden point labels must retain their tooltip values.');
compactLabels.chartElement.clientWidth = 1500;
compactLabels.chartElement.clientHeight = 800;
compactLabels.triggerResize();
assert.equal(shownLabels(compactLabels.currentOption()), shownLabels(spaciousLabels.option),
    'Resizing an existing widget must recalculate adaptive label density.');
assert.equal(bubble.option.xAxis.scale, true);
assert.equal(bubble.option.yAxis.scale, true);
assert.equal(bubble.option.xAxis.min, undefined);
assert.equal(bubble.option.yAxis.min, undefined);
assert.equal(bubble.option.series[0].itemStyle.color, '#123456');
assert.equal(bubble.option.visualMap, undefined, 'Existing charts keep their solid point color.');
assert.equal(bubble.option.brush, undefined, 'Existing charts must keep selection disabled.');
assert.equal(bubble.option.toolbox, undefined);
const selectable = render({ ...model, scatter: { ...model.scatter, style: {
    ...model.scatter.style, enableBrush: true
} } });
assert.deepEqual(Array.from(selectable.option.brush.toolbox), ['rect', 'clear']);
assert.equal(selectable.option.brush.brushMode, 'single');
assert.equal(selectable.option.toolbox.feature.brush.title.rect, 'Select points');
const smallTools = render({ ...model, scatter: { ...model.scatter, style: {
    ...model.scatter.style, enableBrush: true
} } }, 'ipsview', 400, 240);
const largeTools = render({ ...model, scatter: { ...model.scatter, style: {
    ...model.scatter.style, enableBrush: true
} } }, 'ipsview', 1200, 800);
assert.ok(smallTools.option.toolbox.itemSize >= 18,
    'Brush tools must remain legible in a compact widget.');
assert.ok(largeTools.option.toolbox.itemSize > smallTools.option.toolbox.itemSize,
    'Brush tools must scale with the available chart area.');
assert.ok(largeTools.option.toolbox.itemSize <= 28,
    'Brush tools must not become oversized in a large widget.');
assert.equal(smallTools.option.toolbox.iconStyle.borderColor, palette.text);
assert.ok(smallTools.option.toolbox.iconStyle.borderWidth >= 2);
assert.ok(smallTools.option.grid.top >= smallTools.option.toolbox.top
    + smallTools.option.toolbox.itemSize + 8,
    'The plot must leave room below the enlarged brush tools.');
smallTools.chartElement.clientWidth = 1200;
smallTools.chartElement.clientHeight = 800;
smallTools.triggerResize();
assert.equal(smallTools.currentOption().toolbox.itemSize, largeTools.option.toolbox.itemSize,
    'Resizing an existing widget must recalculate brush tool size.');
assert.equal(smallTools.resizeCount(), 1);
selectable.emit('brushselected', { batch: [{ areas: [{ brushType: 'rect' }],
    selected: [{ seriesIndex: 0, dataIndex: [0, 1] }] }] });
assert.equal(selectable.selectionElement.hidden, false);
assert.match(selectable.selectionElement.textContent, /Selected points: 2/);
assert.match(selectable.selectionElement.textContent, /Außen: .*10.*20/);
selectable.emit('brushselected', { batch: [{ areas: [], selected: [{ dataIndex: [] }] }] });
assert.equal(selectable.selectionElement.hidden, true, 'Clearing a brush must remove the summary.');
selectable.emit('brushselected', { batch: [{ areas: [{ brushType: 'rect' }],
    selected: [{ dataIndex: [1] }] }] });
selectable.window.handleMessage({ status: 'ready', chart: model });
assert.equal(selectable.selectionElement.hidden, true, 'A data refresh resets the temporary selection.');
const colorMapped = render({ ...model, scatter: { ...model.scatter, style: {
    ...model.scatter.style, colorMapEnabled: true, colorMapDimension: 'y',
    colorMapRangeMode: 'data', colorMapShowLegend: true,
    colorMapLowColor: '#123456', colorMapHighColor: '#fedcba'
} } });
assert.equal(colorMapped.option.visualMap.dimension, 4);
assert.equal(colorMapped.option.visualMap.min, 50);
assert.equal(colorMapped.option.visualMap.max, 55);
assert.equal(colorMapped.option.series[0].data[0].value[4], 50);
assert.deepEqual(Array.from(colorMapped.option.visualMap.inRange.color), ['#123456', '#fedcba']);
assert.equal(colorMapped.option.visualMap.orient, 'horizontal');
assert.equal(colorMapped.option.visualMap.itemWidth, 12,
    'ECharts uses itemWidth for the thickness of a horizontal visual map.');
assert.ok(colorMapped.option.visualMap.itemHeight >= 200,
    'The horizontal color scale must be a readable length on a regular Tile.');
const wideColorMapped = render({ ...model, scatter: { ...model.scatter, style: {
    ...model.scatter.style, colorMapEnabled: true, colorMapShowLegend: true
} } }, 'ipsview', 1900, 900);
assert.ok(wideColorMapped.option.visualMap.itemHeight > colorMapped.option.visualMap.itemHeight,
    'The color scale must grow in a wide IPSView view without remaining a narrow strip.');
assert.ok(wideColorMapped.option.visualMap.itemHeight <= 420,
    'The legend must remain bounded rather than span the whole screen.');
const compactColorMapped = render({ ...model, scatter: { ...model.scatter, style: {
    ...model.scatter.style, colorMapEnabled: true, colorMapShowLegend: true
} } }, 'ipsview', 400, 240);
assert.equal(compactColorMapped.option.visualMap.itemWidth, 8);
assert.equal(compactColorMapped.option.visualMap.itemHeight, 160);
assert.equal(colorMapped.option.series[0].itemStyle.color, undefined);
assert.ok(colorMapped.option.grid.top > bubble.option.grid.top,
    'A visible color scale must reserve its own space above the plot.');
const selectableColors = render({ ...model, scatter: { ...model.scatter, style: {
    ...model.scatter.style, enableBrush: true, colorMapEnabled: true, colorMapShowLegend: true
} } }, 'ipsview');
const selectableIPSView = render({ ...model, scatter: { ...model.scatter, style: {
    ...model.scatter.style, enableBrush: true
} } }, 'ipsview');
assert.ok(selectableColors.option.toolbox.top > selectableIPSView.option.toolbox.top,
    'The selection tools must move below a visible color scale.');
assert.equal(selectableColors.option.visualMap.dimension, 4,
    'Area selection must coexist with value-based point colors.');
const manualColors = render({ ...model, scatter: { ...model.scatter, style: {
    ...model.scatter.style, colorMapEnabled: true, colorMapDimension: 'x',
    colorMapRangeMode: 'manual', colorMapMinimum: -5, colorMapMaximum: 25
} } });
assert.equal(manualColors.option.visualMap.dimension, 4);
assert.equal(manualColors.option.visualMap.min, -5);
assert.equal(manualColors.option.visualMap.max, 25);
assert.equal(manualColors.option.series[0].data[0].value[4], 10);
const clippedColors = render({ ...model, scatter: { ...model.scatter, style: {
    ...model.scatter.style, colorMapEnabled: true, colorMapDimension: 'x',
    colorMapRangeMode: 'manual', colorMapMinimum: 12, colorMapMaximum: 18
} } });
assert.deepEqual(Array.from(clippedColors.option.series[0].data, entry => entry.value[4]), [12, 18],
    'Values outside manual color limits use the nearest endpoint color.');
const bubbleColors = render({ ...model, scatter: { ...model.scatter, style: {
    ...model.scatter.style, colorMapEnabled: true, colorMapDimension: 'size'
} } });
assert.equal(bubbleColors.option.visualMap.dimension, 4);
assert.equal(bubbleColors.option.visualMap.min, 1);
assert.equal(bubbleColors.option.visualMap.max, 3);
assert.equal(bubbleColors.option.series[0].data[1].value[4], 3);
assert.equal(bubble.option.dataZoom.length, 4);
assert.equal(bubble.option.dataZoom[2].xAxisIndex, 0);
assert.equal(bubble.option.dataZoom[3].yAxisIndex, 0);
assert.equal(bubble.option.dataZoom[0].zoomOnMouseWheel, true);
assert.equal(bubble.option.dataZoom[2].height, 12);
assert.equal(bubble.option.dataZoom[3].width, 12);
assert.equal(bubble.option.xAxis.name, 'Außen (°C)');
assert.equal(bubble.option.yAxis.name, 'Feuchte (%)');
assert.equal(bubble.option.title.show, true);
assert.ok(bubble.option.title.top >= 58 && bubble.option.grid.top > bubble.option.title.top + 18,
    'The Scatter title and plot must clear the Symcon tile header.');
assert.ok(bubble.option.tooltip.formatter({ value: [10, 50, 1, 100] }).includes('Wind'));
const ipsview = render(model, 'ipsview');
assert.equal(ipsview.option.title.show, true);
assert.equal(ipsview.option.dataZoom[1].zoomOnMouseWheel, false);
const compactBubble = render(model, 'ipsview', 400, 240);
assert.equal(compactBubble.option.dataZoom[2].height, 8);
assert.equal(compactBubble.option.dataZoom[3].width, 8);
for (const entry of bubble.option.dataZoom) {
    entry.start = entry.xAxisIndex === 0 ? 20 : 35;
    entry.end = entry.xAxisIndex === 0 ? 65 : 80;
}
bubble.window.handleMessage({ status: 'ready', chart: { ...model,
    range: { ...model.range, startTimestamp: 1100, endTimestamp: 2100 } } });
assert.deepEqual(Array.from(bubble.currentOption().dataZoom, entry => [entry.start, entry.end]),
    [[20, 65], [35, 80], [20, 65], [35, 80]]);
bubble.window.handleMessage({ status: 'ready', chart: { ...model,
    range: { ...model.range, key: '7d' } } });
assert.deepEqual(Array.from(bubble.currentOption().dataZoom, entry => [entry.start, entry.end]),
    [[0, 100], [0, 100], [0, 100], [0, 100]]);
for (const entry of bubble.currentOption().dataZoom) {
    entry.start = 20;
    entry.end = 60;
}
bubble.window.handleMessage({ status: 'ready', chart: { ...model, scatter: { ...model.scatter, style: {
    ...model.scatter.style, xAxisRangeMode: 'manual', xAxisMinimum: 1, xAxisMaximum: 25
} } } });
assert.deepEqual(Array.from(bubble.currentOption().dataZoom, entry => [entry.start, entry.end]),
    [[0, 100], [0, 100], [0, 100], [0, 100]],
    'Manual axis edits must not inherit a stale interactive zoom window.');
const noZoom = render({ ...model, scatter: { ...model.scatter, style: {
    ...model.scatter.style, enableZoom: false
} } });
assert.equal(noZoom.option.dataZoom.length, 0);
const zeroScale = render({ ...model, scatter: { ...model.scatter, style: {
    ...model.scatter.style, xAxisRangeMode: 'zero', yAxisRangeMode: 'manual',
    yAxisMinimum: 40, yAxisMaximum: 100
} } });
assert.equal(zeroScale.option.xAxis.scale, false);
assert.equal(zeroScale.option.yAxis.scale, true);
assert.equal(zeroScale.option.yAxis.min, 40);
assert.equal(zeroScale.option.yAxis.max, 100);
const scatter = render({ ...model, roles: { x: model.roles.x, y: model.roles.y },
    points: [[100, 10, 50]] });
assert.equal(scatter.option.series[0].symbolSize([10, 50, null, 100]), 10);
assert.equal(scatter.option.series[0].data[0].value[3], 100);
const empty = render({ ...model, points: [] });
assert.equal(empty.option, undefined);
assert.equal(empty.emptyElement.hidden, false);
assert.equal(empty.chartElement.hidden, true);
const hostile = render({ ...model, roles: {
    x: { label: '<img src=x>', unit: '<b>', decimals: 1 }, y: model.roles.y
} });
const tooltip = hostile.option.tooltip.formatter({ value: [10, 50, null, 100] });
assert.ok(tooltip.includes('&lt;img'));
assert.ok(tooltip.includes('&lt;b&gt;'));

console.log('Scatter and Bubble layout verified.');
