'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.join(__dirname, '..');
const source = fs.readFileSync(path.join(root, 'EChartsBoxplot/visualization/app.js'), 'utf8');
const designSource = fs.readFileSync(path.join(root, 'libs/echarts-design.js'), 'utf8');
const palette = { background: '#101114', text: '#f4f5f7', muted: '#969aa2', border: '#737780', accent: '#55cbb5' };

function render(model, mode = 'symcon', headerVisible = true, width = 900, height = 500, engine = null) {
    const chartElement = { style: {}, hidden: false, clientWidth: width, clientHeight: height };
    const rootElement = { style: {} };
    const errorElement = { hidden: true, textContent: '' };
    const emptyElement = { hidden: true, textContent: '' };
    const warningElement = { hidden: true, textContent: '' };
    let option;
    let resizeObserver;
    const events = {};
    const chart = { setOption: next => { option = next; if (engine) { engine.setOption(next, true); } },
        getOption: () => engine ? engine.getOption() : { ...option, xAxis: [option.xAxis], yAxis: [option.yAxis] },
        getWidth: () => engine ? engine.getWidth() : chartElement.clientWidth,
        getHeight: () => engine ? engine.getHeight() : chartElement.clientHeight,
        clear: () => { if (engine) { engine.clear(); } },
        resize: next => { if (engine) { engine.resize(next || { width: chartElement.clientWidth, height: chartElement.clientHeight }); } }, dispose: () => {} };
    const window = {
        SYMC_VISUALIZATION: { mode, language: 'en', state: { status: 'ready', chart: model }, translations: {},
            options: { echartsThemes: { auto: palette, dark: palette }, tileHeaderVisible: headerVisible,
                adaptToBackground: mode === 'ipsview' } },
        echarts: { init: () => chart }, addEventListener: (name, callback) => { events[name] = callback; },
        ResizeObserver: class { constructor(callback) { resizeObserver = callback; } observe() {} },
        getComputedStyle: () => ({ color: '' })
    };
    const document = {
        body: { appendChild: () => {} }, createElement: () => ({ style: {}, remove: () => {} }),
        getElementById: id => ({ 'echarts-boxplot-root': rootElement, 'echarts-boxplot-chart': chartElement,
            'echarts-boxplot-error': errorElement, 'echarts-boxplot-empty': emptyElement,
            'echarts-boxplot-warning': warningElement })[id]
    };
    vm.runInNewContext(designSource, { window });
    vm.runInNewContext(source, { window, document, ResizeObserver: window.ResizeObserver });
    return { get option() { return option; }, chartElement, errorElement, emptyElement, warningElement, window,
        resize: (width, height) => { chartElement.clientWidth = width; chartElement.clientHeight = height; resizeObserver(); }, events };
}


const model = { family: 'boxplot', theme: 'dark', boxplot: { title: 'Hourly temperatures', unit: '°C',
    data: [
        { id: 'variable-1', label: '<Room>', statistics: [-4, -1, 2, 5, 8], unit: '°C', decimals: 1, count: 4, firstTimestamp: 1792101600, lastTimestamp: 1792112400, color: '#abcdef' },
        { id: 'variable-2', label: '<Room>', statistics: [7, 7, 7, 7, 7], unit: '°C', decimals: 1, count: 1, firstTimestamp: 1792101600, lastTimestamp: 1792101600, color: '' },
        { id: 'variable-3', label: 'Empty', statistics: null, unit: '°C', decimals: 1, count: 0, firstTimestamp: null, lastTimestamp: null, color: '' }
    ],
    style: { orientation: 'vertical', boxWidth: 40, borderWidth: 2, boxOpacityPercent: 70,
        showGrid: true, showAxisLabels: true, showTooltip: true, animationEnabled: false }
} };
const tile = render(model);
const manyModel = { ...model, boxplot: { ...model.boxplot, data: Array.from({ length: 32 }, (_, index) => ({
    ...model.boxplot.data[0], id: 'source-' + index, label: index % 2 ? '<Duplicate>' : 'Source ' + index,
    statistics: index === 9 ? null : index === 31 ? [-100, -10, 0, 10, 1000] : [index, index + 1, index + 2, index + 3, index + 4],
    count: index === 9 ? 0 : index + 1
})) } };
const many = render(manyModel);
assert.equal(many.option.dataZoom.length, 2, 'A category slider and inside navigation must make all 32 sources reachable.');
assert.equal(tile.chartElement.style.top, '58px');
assert.equal(tile.option.series[0].type, 'boxplot');
assert.equal(tile.option.series[0].data[0].value.join(','), '-4,-1,2,5,8');
assert.equal(tile.option.series[0].data[0].itemStyle.color, '#abcdef');
assert.equal(tile.option.xAxis.data.join(','), 'variable-1,variable-2,variable-3');
assert.equal(tile.option.xAxis.axisLabel.formatter('variable-1'), '<Room>');
assert.equal(tile.option.xAxis.axisLabel.formatter('variable-2'), '<Room>');
assert.equal(tile.option.yAxis.scale, true, 'Do not force a zero-baseline onto temperature distributions.');
assert.equal(tile.option.animation, false);
assert.match(tile.option.tooltip.formatter({ dataIndex: 0, data: tile.option.series[0].data[0] }), /&lt;Room&gt;.*Minimum: -4 °C.*Median: 2 °C.*Hourly samples: 4/);
assert.equal(tile.option.tooltip.formatter({ dataIndex: 2, data: tile.option.series[0].data[2] }), '');
assert.ok(tile.option.grid.top > tile.option.title.top + tile.option.title.textStyle.fontSize);
assert.equal(render(model, 'symcon', false).chartElement.style.top, '0px');
const custom = render({ ...model, boxplot: { ...model.boxplot, style: { ...model.boxplot.style,
    orientation: 'horizontal', showGrid: false, showAxisLabels: false, showTooltip: false,
    borderWidth: 0, boxOpacityPercent: 0, axisColor: '#fedcba', titleColor: '#123456', axisFontSizePercent: 150
} } }, 'ipsview', true, 350);
assert.equal(custom.chartElement.style.top, '0px');
assert.equal(custom.option.yAxis.type, 'category');
assert.equal(custom.option.xAxis.type, 'value');
assert.equal(custom.option.xAxis.nameLocation, 'middle', 'Horizontal units must stay inside narrow charts.');
assert.match(custom.option.tooltip.extraCssText, /white-space:normal/);
assert.equal(custom.option.xAxis.splitLine.show, false);
assert.equal(custom.option.yAxis.axisLabel.show, false);
assert.equal(custom.option.tooltip.show, false);
assert.equal(custom.option.title.textStyle.color, '#123456');
assert.equal(custom.option.series[0].itemStyle.borderWidth, 0);
assert.equal(custom.option.series[0].itemStyle.opacity, 0);
assert.equal(custom.option.yAxis.axisLabel.fontSize, 15);
const empty = render({ ...model, boxplot: { ...model.boxplot, data: [model.boxplot.data[2]] } });
assert.equal(empty.emptyElement.hidden, false);
assert.equal(empty.chartElement.hidden, true);
tile.window.handleMessage({ status: 'error', error: 'The Boxplot values could not be loaded.' });
assert.equal(tile.chartElement.hidden, false);
tile.window.handleMessage({ status: 'error', error: 'The Boxplot values could not be loaded.' });
assert.equal(tile.warningElement.hidden, false);
tile.window.handleMessage({ status: 'error', error: 'Configure valid Boxplot archive variables.' });
assert.equal(tile.chartElement.hidden, true);
assert.equal(tile.errorElement.hidden, false);
tile.window.handleMessage({ status: 'ready', chart: model });
assert.equal(tile.chartElement.hidden, false);

// Real pinned ECharts engine: both orientations, sparse categories, one-value boxes and compact sizing.
const echarts = require(path.join(root, '.tools/echarts-runtime/node_modules/echarts'));
const decoration = { minBoxWidth: 12, borderType: 'dashed', shadowBlur: 8, shadowColor: '#123456', shadowOffsetX: 3, shadowOffsetY: -2,
    hoverFocus: 'none', blurOpacityPercent: 15, axisLineWidth: 3, showAxisTicks: false,
    axisLabelRotate: 45, axisLabelMaxWidth: 100, titleBold: false, axisLabelBold: true,
    splitNumber: 8, gridLineType: 'dotted', gridLineWidth: 2, gridOpacityPercent: 60, gridColor: '#fedcba' };
const decorated = render({ ...model, boxplot: { ...model.boxplot, style: { ...model.boxplot.style, ...decoration } } });
assert.equal(decorated.option.series[0].itemStyle.borderType, 'dashed');
assert.equal(decorated.option.series[0].itemStyle.shadowBlur, 8);
assert.equal(decorated.option.series[0].itemStyle.shadowColor, '#123456');
assert.equal(decorated.option.series[0].itemStyle.shadowOffsetX, 3);
assert.equal(decorated.option.series[0].itemStyle.shadowOffsetY, -2);
assert.equal(decorated.option.series[0].boxWidth[0], 12);
assert.equal(decorated.option.series[0].emphasis.disabled, true);
assert.equal(decorated.option.series[0].blur.itemStyle.opacity, 0.15);
assert.equal(decorated.option.xAxis.axisTick.show, false);
assert.equal(decorated.option.xAxis.axisLine.lineStyle.width, 3);
assert.equal(decorated.option.xAxis.axisLabel.rotate, 45);
assert.equal(decorated.option.xAxis.axisLabel.width, 100);
assert.equal(decorated.option.xAxis.axisLabel.fontWeight, 'bold');
assert.equal(decorated.option.title.textStyle.fontWeight, 'normal');
assert.equal(decorated.option.yAxis.splitNumber, 8);
assert.equal(decorated.option.yAxis.splitLine.lineStyle.type, 'dotted');
assert.equal(decorated.option.yAxis.splitLine.lineStyle.width, 2);
assert.equal(decorated.option.yAxis.splitLine.lineStyle.color, '#fedcba');
assert.equal(decorated.option.yAxis.splitLine.lineStyle.opacity, 0.6);
const engineDecoration = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 900, height: 500 });
engineDecoration.setOption(decorated.option);
const decoratedElement = engineDecoration.getModel().getSeriesByIndex(0).getData().getItemGraphicEl(0);
assert.equal(decoratedElement.style.lineDash, 'dashed');
assert.equal(decoratedElement.style.shadowBlur, 8);
assert.equal(decoratedElement.style.shadowOffsetY, -2);
assert.equal(decoratedElement.states.blur.style.opacity, 0.15);
assert.match(engineDecoration.renderToSVGString(), /stroke-dasharray/);
engineDecoration.dispose();
const transparent = render({ ...model, boxplot: { ...model.boxplot, style: { ...model.boxplot.style,
    gridLineWidth: 0, gridOpacityPercent: 0, axisLineWidth: 0, blurOpacityPercent: 0 } } });
assert.equal(transparent.option.yAxis.splitLine.lineStyle.width, 0);
assert.equal(transparent.option.yAxis.splitLine.lineStyle.opacity, 0);
assert.equal(transparent.option.xAxis.axisLine.lineStyle.width, 0);
assert.equal(transparent.option.series[0].blur.itemStyle.opacity, 0);
const crowdedModel = { ...model, boxplot: { ...model.boxplot,
    data: Array.from({ length: 10 }, (_, i) => ({ ...model.boxplot.data[0], id: 'v' + i, label: 'Very long category label ' + i })),
    style: { ...model.boxplot.style, ...decoration, axisLabelRotate: 90, axisLabelMaxWidth: 300, axisFontSizePercent: 200,
        minBoxWidth: 80, boxWidth: 80 } } };
const crowded = render(crowdedModel, 'symcon', true, 350);
assert.ok(crowded.option.series[0].boxWidth[0] <= crowded.option.series[0].boxWidth[1]);
const crowdedCount = crowded.option.dataZoom[0].endValue - crowded.option.dataZoom[0].startValue + 1;
assert.ok(crowded.option.series[0].boxWidth[1] <= (350 - crowded.option.grid.left - crowded.option.grid.right) / crowdedCount * 0.65);
assert.ok(crowded.option.xAxis.axisLabel.width <= 150, 'Rotated labels must fit the reserved bottom band.');
for (const horizontal of [false, true]) {
    for (const width of [350, 900]) {
        const rendered = render({ ...model, boxplot: { ...model.boxplot, style: { ...model.boxplot.style,
            ...decoration, orientation: horizontal ? 'horizontal' : 'vertical' } } }, 'symcon', true, width);
        const chart = echarts.init(null, null, { renderer: 'svg', ssr: true, width, height: 500 });
        chart.setOption(rendered.option);
        const data = chart.getModel().getSeriesByIndex(0).getData();
        const first = data.getItemLayout(0);
        const single = data.getItemLayout(1);
        assert.equal(data.count(), 3);
        assert.ok(first.ends.every(point => point.every(Number.isFinite)), 'Whiskers/quartiles must have finite real engine geometry.');
        assert.ok(single.ends.every(point => point.every(Number.isFinite)), 'Single-sample boxes must remain finite.');
        for (const point of first.ends) {
            assert.ok(point[0] >= 0 && point[0] <= width && point[1] >= rendered.option.grid.top && point[1] <= 500 - rendered.option.grid.bottom);
        }
        assert.equal(data.hasValue(2), false, 'An empty category must not draw a zero-valued box.');
        assert.equal(chart.getModel().getSeriesByIndex(0).getWhiskerBoxesLayout(), horizontal ? 'vertical' : 'horizontal');
        assert.ok(chart.renderToSVGString().includes('<path'));
        chart.dispose();
    }
}
// Exercise the shipped selective bundle, not merely the full development package.
const nativeWindow = {};
vm.runInNewContext(fs.readFileSync(path.join(root, 'libs/echarts/6.1.0/echarts.boxplot.min.js'), 'utf8'),
    { window: nativeWindow, navigator: { userAgent: 'Node.js' }, console, setTimeout, clearTimeout });
nativeWindow.echarts.use(require('../.tools/echarts-runtime/node_modules/echarts/renderers').SVGRenderer);
for (const mode of ['symcon', 'ipsview']) {
    for (const orientation of ['horizontal', 'vertical']) {
        const dimensions = { width: 640, height: 500 };
        const native = nativeWindow.echarts.init(null, null, { renderer: 'svg', ssr: true, ...dimensions });
        const input = { ...manyModel, boxplot: { ...manyModel.boxplot,
            style: { ...manyModel.boxplot.style, orientation } } };
        const fixture = render(input, mode, true, dimensions.width, dimensions.height, native);
        const initial = fixture.option;
        const count = initial.dataZoom[0].endValue + 1;
        assert.ok(count >= 1 && count < 32);
        assert.equal(initial.dataZoom[1].moveOnMouseWheel, true);
        assert.equal(initial.dataZoom[1].zoomOnMouseWheel, false);
        assert.equal(initial.dataZoom[0].zoomLock, true);
        const category = orientation === 'horizontal' ? initial.yAxis : initial.xAxis;
        const value = orientation === 'horizontal' ? initial.xAxis : initial.yAxis;
        assert.equal(category.data.length, 32, 'Even sources without samples retain their category.');
        assert.equal(category.axisLabel.formatter('source-1'), category.axisLabel.formatter('source-3'));
        assert.ok(value.min < -100 && value.max > 1000, 'An offscreen signed extreme must define the full-model scale.');
        const bounds = native.getModel().getComponent(orientation === 'horizontal' ? 'xAxis' : 'yAxis').axis.scale.getExtent().slice();
        assert.equal(native.getModel().getSeriesByIndex(0).getData().count(), count,
            'The actual selective runtime must filter the categories to a readable window.');
        native.dispatchAction({ type: 'dataZoom', dataZoomId: 'category-scroll', startValue: 32 - count, endValue: 31 });
        const tail = native.getModel().getSeriesByIndex(0).getData();
        assert.equal(tail.getRawIndex(tail.count() - 1), 31, 'The 32nd source must be reachable.');
        const lastParameters = native.getModel().getSeriesByIndex(0).getDataParams(tail.count() - 1);
        assert.equal(lastParameters.data.id, 'source-31');
        assert.match(initial.tooltip.formatter(lastParameters), /&lt;Duplicate&gt;.*Minimum: -100 °C.*Maximum: 1,000 °C.*Hourly samples: 32/);
        assert.match(initial.tooltip.formatter({ ...lastParameters, dataIndex: 0 }), /Hourly samples: 32/,
            'Tooltip identity belongs to the raw item, never a filtered local index.');
        assert.deepEqual(native.getModel().getComponent(orientation === 'horizontal' ? 'xAxis' : 'yAxis').axis.scale.getExtent(), bounds,
            'Scrolling must not rescale the numeric axis to only visible boxes.');
        assert.ok(tail.getItemLayout(tail.count() - 1).ends.every(point => point.every(Number.isFinite)));
        fixture.window.handleMessage({ status: 'ready', chart: input });
        assert.equal(fixture.option.dataZoom[0].startValue, 32 - count, 'Archive refresh preserves the scrolled viewport.');
        const allEmpty = { ...input, boxplot: { ...input.boxplot,
            data: input.boxplot.data.map(item => ({ ...item, statistics: null, count: 0 })) } };
        fixture.window.handleMessage({ status: 'ready', chart: allEmpty });
        assert.equal(fixture.emptyElement.hidden, false);
        fixture.window.handleMessage({ status: 'ready', chart: input });
        assert.equal(fixture.option.dataZoom[0].startValue, 32 - count, 'A temporarily empty archive model must not discard the source anchor.');
        // Keep the same source anchored when configuration order or display names change.
        const anchorID = input.boxplot.data[32 - count].id;
        const reorderedData = input.boxplot.data.slice(0, 32 - count)
            .concat(input.boxplot.data.slice(32 - count + 1), [{ ...input.boxplot.data[32 - count], label: 'Renamed' }]);
        const reordered = { ...input, boxplot: { ...input.boxplot, data: reorderedData } };
        // Move the anchor earlier so the complete window can start there without end clamping.
        reorderedData.unshift(reorderedData.pop());
        fixture.window.handleMessage({ status: 'ready', chart: reordered });
        const newCategory = orientation === 'horizontal' ? fixture.option.yAxis : fixture.option.xAxis;
        assert.equal(newCategory.data[fixture.option.dataZoom[0].startValue], anchorID,
            'Source IDs, not labels or previous ordinals, preserve the viewport on reorder.');
        const savedID = newCategory.data[fixture.option.dataZoom[0].startValue];
        fixture.resize(700, 550);
        const resizedCategory = orientation === 'horizontal' ? fixture.option.yAxis : fixture.option.xAxis;
        assert.equal(resizedCategory.data[fixture.option.dataZoom[0].startValue], savedID, 'Responsive reflow preserves the source anchor.');
        const ticks = native.getModel().getComponent(orientation === 'horizontal' ? 'yAxis' : 'xAxis').axis.getViewLabels();
        assert.ok(ticks.some(tick => tick.formattedLabel === 'Renamed'), 'Native scrolled labels use source IDs instead of filtered ordinals.');
        // Empty categories inside the native filtered window remain empty, not synthetic zero boxes.
        const emptyIndex = reorderedData.findIndex(item => item.statistics === null);
        native.dispatchAction({ type: 'dataZoom', dataZoomId: 'category-scroll', startValue: emptyIndex, endValue: emptyIndex + count - 1 });
        const sparse = native.getModel().getSeriesByIndex(0).getData();
        const emptyInside = sparse.indexOfRawIndex(emptyIndex);
        assert.ok(emptyInside >= 0);
        assert.equal(sparse.hasValue(emptyInside), false);
        assert.equal(fixture.option.tooltip.formatter(native.getModel().getSeriesByIndex(0).getDataParams(emptyInside)), '');
        const sparseAnchor = reorderedData[emptyIndex].id;
        fixture.resize(3500, 3500);
        assert.equal(fixture.option.dataZoom.length, 0, 'No navigation is shown when every source fits legibly.');
        assert.equal(native.getModel().getSeriesByIndex(0).getData().count(), 32,
            'Removing a previous navigation window restores all sources.');
        fixture.resize(640, 500);
        const restoredCategory = orientation === 'horizontal' ? fixture.option.yAxis : fixture.option.xAxis;
        assert.equal(restoredCategory.data[fixture.option.dataZoom[0].startValue], sparseAnchor,
            'The anchor survives an intermediate size where all categories fit.');
        native.dispose();
        const narrow = render({ ...input, boxplot: { ...input.boxplot,
            style: { ...input.boxplot.style, boxWidth: 10, axisFontSizePercent: 100 } } }, mode, true, 640);
        const largeFont = render({ ...input, boxplot: { ...input.boxplot,
            style: { ...input.boxplot.style, boxWidth: 10, axisFontSizePercent: 200 } } }, mode, true, 640);
        assert.ok(narrow.option.dataZoom[0].endValue >= initial.dataZoom[0].endValue,
            'Narrow boxes can make more categories visible.');
        assert.ok(largeFont.option.dataZoom[0].endValue < narrow.option.dataZoom[0].endValue,
            'Large fonts impose a readable minimum category spacing.');
    }
}
console.log('Boxplot renderer, 32-source native navigation, sparse identities, stable scales and responsive geometry verified.');
