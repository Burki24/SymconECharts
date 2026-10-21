'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '..', 'EChartsParallel', 'visualization', 'app.js'), 'utf8');
const designSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-design.js'), 'utf8');
const navigationSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-category-navigation.js'), 'utf8');
const palette = { background: '#101114', text: '#f4f5f7', muted: '#969aa2',
    border: '#737780', accent: '#55cbb5' };

function render(model, mode = 'symcon', width = 900, adaptToBackground = false) {
    const chartElement = { hidden: false, style: {}, clientWidth: width, clientHeight: 400 };
    const viewportListeners = {}, listeners = {}, buttonListeners = {};
    const viewport = { hidden: false, style: {}, clientWidth: width, clientHeight: 400, scrollLeft: 0,
        setAttribute: () => {}, addEventListener: (name, listener) => { viewportListeners[name] = listener; } };
    const navigation = { hidden: true, setAttribute: () => {} };
    const position = { textContent: '', setAttribute: () => {} };
    const previous = { disabled: false, setAttribute: () => {}, addEventListener: (_, listener) => { buttonListeners.previous = listener; } };
    const next = { disabled: false, setAttribute: () => {}, addEventListener: (_, listener) => { buttonListeners.next = listener; } };
    const errorElement = { hidden: true, textContent: '' };
    const emptyElement = { hidden: true, textContent: '' };
    const warningElement = { hidden: true, style: {}, textContent: '' };
    let option, postUpdate, failRender = false;
    let chartWidth = width, chartHeight = 400;
    const chart = { setOption: (value, replace) => {
        if (failRender) { failRender = false; throw new Error('Synthetic native numeric precision failure'); }
        option = replace || !option ? value : { ...option, ...value, title: { ...option.title, ...value.title } };
    }, clear: () => {}, resize: options => { chartWidth = options?.width || chartWidth; chartHeight = options?.height || chartHeight; },
    getWidth: () => chartWidth, getHeight: () => chartHeight, dispose: () => {}, dispatchAction: () => {} };
    const window = {
        SYMC_VISUALIZATION: { mode, state: { status: 'ready', chart: model }, translations: {},
            options: { echartsThemes: { auto: palette, dark: palette }, tileHeaderVisible: true,
                adaptToBackground } },
        echarts: { init: () => chart, registerPostUpdate: callback => { postUpdate = callback; } },
        addEventListener: (name, listener) => { listeners[name] = listener; }, getComputedStyle: () => ({ color: '' })
    };
    const document = {
        body: { appendChild: () => {} },
        createElement: () => ({ style: {}, remove: () => {} }),
        getElementById: id => ({
            'echarts-parallel-chart': chartElement,
            'echarts-parallel-viewport': viewport,
            'echarts-parallel-navigation': navigation,
            'echarts-parallel-position': position,
            'echarts-parallel-previous': previous,
            'echarts-parallel-next': next,
            'echarts-parallel-error': errorElement,
            'echarts-parallel-empty': emptyElement,
            'echarts-parallel-warning': warningElement
        })[id]
    };
    vm.runInNewContext(designSource, { window });
    vm.runInNewContext(navigationSource, { window });
    vm.runInNewContext(source, { window, document });
    return { get option() { return option; }, chartElement, errorElement, emptyElement, warningElement,
        window, viewport, navigation, position, previous, next, postUpdate, failNextRender: () => { failRender = true; },
        click: name => buttonListeners[name](), scroll: left => { viewport.scrollLeft = left; viewportListeners.scroll(); },
        key: key => viewportListeners.keydown({ key, target: viewport, preventDefault() {} }),
        resize: (width, height = 400) => { viewport.clientWidth = width; viewport.clientHeight = height; listeners.resize(); } };
}

const model = { family: 'parallel', theme: 'dark', parallel: {
    title: 'Archive', aggregation: 'hour', truncated: false,
    axes: [
        { label: '<Temperature>', unit: '°C' },
        { label: 'Humidity', unit: '%' },
        { label: 'Power', unit: 'W' }
    ],
    rows: [
        { timestamp: 1780000100, values: [18.5, 42, 100] },
        { timestamp: 1780003700, values: [19.5, 40, 120] }
    ],
    style: { showTooltip: true, smooth: false, lineWidth: 2, lineOpacityPercent: 45,
        lineType: 'dashed', activeLineOpacityPercent: 90, inactiveLineOpacityPercent: 12,
        axisLineWidth: 3, showAxisLabels: false, showAxisTicks: false, axisNameGap: 28,
        animationEnabled: false,
        lineColor: '#123456', axisColor: '#abcdef', axisFontSizePercent: 150,
        titleColor: '#ffffff', titleFontSizePercent: 125 }
} };

const result = render(model);
assert.equal(result.option.series[0].type, 'parallel');
assert.equal(result.option.parallelAxis.length, 3);
assert.equal(result.option.parallelAxis[0].dim, 0);
assert.equal(result.option.parallelAxis[0].name, '<Temperature> (°C)');
assert.deepEqual(Array.from(result.option.series[0].data[0].value), [18.5, 42, 100]);
assert.equal(result.option.series[0].lineStyle.color, '#123456');
assert.equal(result.option.series[0].lineStyle.opacity, 0.45);
assert.equal(result.option.series[0].lineStyle.type, 'dashed');
assert.equal(result.option.series[0].activeOpacity, 0.9);
assert.equal(result.option.series[0].inactiveOpacity, 0.12);
assert.equal(result.option.parallel.parallelAxisDefault.axisLine.lineStyle.width, 3);
assert.equal(result.option.parallel.parallelAxisDefault.axisLabel.show, false);
assert.equal(result.option.parallel.parallelAxisDefault.axisTick.show, false);
assert.equal(result.option.parallel.parallelAxisDefault.nameGap, 28);
assert.equal(result.option.animation, false);
assert.equal(result.option.parallel.parallelAxisDefault.nameTextStyle.color, '#abcdef');
assert.equal(result.option.title.show, true);
assert.ok(result.option.title.top >= 58, 'The chart title must clear the visible Symcon tile header.');
assert.ok(result.option.parallel.top > result.option.title.top + result.option.title.textStyle.fontSize,
    'Parallel axes must start below the chart title.');
assert.match(result.option.tooltip.formatter({ name: '<Now>', value: [18.5, 42, 100] }), /&lt;Temperature&gt;/);
assert.doesNotMatch(result.option.tooltip.formatter({ name: '<Now>', value: [18.5, 42, 100] }), /<Temperature>/);

const ipsView = render(model, 'ipsview', 320, true);
assert.equal(ipsView.option.title.show, true);
assert.equal(ipsView.option.backgroundColor, 'transparent');
assert.ok(ipsView.option.parallel.left >= 28, 'Compact viewports must reserve enough room for centered end-axis names.');

const empty = render({ ...model, parallel: { ...model.parallel, rows: [] } });
assert.equal(empty.option, undefined);
assert.equal(empty.emptyElement.hidden, false);
const twoAxes = render({ ...model, parallel: { ...model.parallel, axes: model.parallel.axes.slice(0, 2),
    rows: [{ timestamp: 1780000100, values: [18.5, 42] }], style: { ...model.parallel.style, axisFontSizePercent: 200 } } }, 'symcon', 320);
assert.equal(parseFloat(twoAxes.chartElement.style.width), 320, 'Two-axis comparisons must still show both axes together, even with large names.');
assert.equal(twoAxes.navigation.hidden, true);

const truncated = render({ ...model, parallel: { ...model.parallel, truncated: true } });
assert.equal(truncated.warningElement.hidden, false);

const many = { ...model, range: { lineBudget: 1000, effectiveLineBudget: 625, aggregateBudget: 10000 }, parallel: {
    ...model.parallel, axes: Array.from({ length: 16 }, (_, index) => ({ variableID: 10000 + index,
        label: index === 15 ? '<Last axis>' : 'Axis ' + (index + 1), unit: index % 2 ? '%' : '°C' })),
    rows: [{ timestamp: 1780000100, values: Array.from({ length: 16 }, (_, i) => i - 8) }]
} };
for (const mode of ['symcon', 'ipsview']) {
    const view = render(many, mode, 360, true);
    assert.ok(parseFloat(view.chartElement.style.width) > view.viewport.clientWidth,
        'Sixteen axes need a genuinely wider chart, not compressed labels or removed dimensions.');
    assert.equal(view.navigation.hidden, false);
    assert.equal(view.option.parallelAxis.length, 16);
    assert.equal(view.option.series[0].data[0].value.length, 16);
    assert.match(view.option.tooltip.formatter({ name: '<Now>', value: many.parallel.rows[0].values }), /&lt;Last axis&gt;/);
    const data = JSON.stringify(view.option.series[0].data);
    view.click('next');
    assert.ok(view.viewport.scrollLeft > 0);
    assert.equal(JSON.stringify(view.option.series[0].data), data, 'Panning must retain every line and dimension.');
    assert.equal(view.option.title.left, view.viewport.scrollLeft + 180, 'The chart title stays centered in the visible viewport.');
    view.key('End');
    assert.equal(view.next.disabled, true);
    assert.match(view.position.textContent, /16/);
    view.key('Home');
    assert.equal(view.previous.disabled, true);
    view.scroll(450);
    const priorLeft = view.viewport.scrollLeft;
    view.window.handleMessage({ status: 'ready', chart: { ...many,
        parallel: { ...many.parallel, rows: [{ timestamp: 1780003700, values: many.parallel.rows[0].values }] } } });
    assert.ok(Math.abs(view.viewport.scrollLeft - priorLeft) < 0.01, 'Data refresh must not jump back to the first axis.');
    view.resize(500);
    assert.ok(view.viewport.scrollLeft > 0, 'Resize must retain the axis anchor.');
    const allFitWidth = parseFloat(view.chartElement.style.width) + 500;
    view.resize(allFitWidth);
    assert.equal(view.navigation.hidden, true, 'A fitting chart needs no artificial scroll controls.');
    view.resize(500);
    assert.ok(view.viewport.scrollLeft > 0, 'Returning from an all-visible layout must restore the former axis anchor.');
    view.window.handleMessage({ status: 'ready', chart: { ...many, parallel: { ...many.parallel,
        style: { ...many.parallel.style, axisFontSizePercent: 200 } } } });
    assert.ok(parseFloat(view.chartElement.style.width) > 16 * 90, 'Large axis fonts must reserve real horizontal space.');
    const before = view.option;
    view.window.handleMessage({ status: 'error', error: 'offline' });
    assert.equal(view.option, before, 'A transient error must preserve the navigated chart.');
}

const identity = render(many, 'symcon', 360);
const originalSpacing = (parseFloat(identity.chartElement.style.width) - 2 * identity.option.parallel.left) / 15;
identity.scroll(originalSpacing * 3);
const reorderedAxes = many.parallel.axes.slice();
[reorderedAxes[3], reorderedAxes[7]] = [reorderedAxes[7], reorderedAxes[3]];
identity.window.handleMessage({ status: 'ready', chart: { ...many, parallel: { ...many.parallel, axes: reorderedAxes } } });
assert.ok(Math.abs(identity.viewport.scrollLeft / originalSpacing - 7) < 0.01,
    'Changing source order must keep the viewport anchored to the variable ID, not its old index or duplicate name.');

console.log('Parallel layout and sixteen-axis viewport verified.');

const fixed = { ...many, parallel: { ...many.parallel,
    axes: many.parallel.axes.map((axis, index) => index === 0 ? { ...axis, minimum: -10, maximum: 40 }
        : index === 15 ? { ...axis, minimum: 0, maximum: 100 } : axis),
    outOfRangeCount: 2,
    rows: [{ timestamp: 1780000100, values: [-15, ...many.parallel.rows[0].values.slice(1, 15), 125] }]
} };
for (const mode of ['symcon', 'ipsview']) {
    const view = render(fixed, mode, 360, true);
    assert.equal(view.option.parallelAxis[0].min, -10, 'Fixed axis bounds must reach the native renderer.');
    assert.equal(view.option.parallelAxis[0].max, 40);
    assert.equal(view.option.parallelAxis[15].max, 100);
    assert.equal(Object.hasOwn(view.option.parallelAxis[1], 'min'), false, 'Automatic axes must not inherit another range.');
    assert.deepEqual(Array.from(view.option.series[0].data[0].value), fixed.parallel.rows[0].values,
        'Do not clamp originals: that would also falsify native axis brushing.');
    const tip = view.option.tooltip.formatter({ name: '<Now>', value: view.option.series[0].data[0].value });
    assert.match(tip, /-15/);
    assert.match(tip, /125/);
    assert.equal((tip.match(/Outside fixed axis range/g) || []).length, 2);
    assert.equal(view.warningElement.hidden, false);
    assert.match(view.warningElement.textContent, /2 values outside fixed axis ranges/);
    assert.ok(view.option.parallel.bottom >= 68, 'The range warning needs space above the navigation.');
    view.key('End');
    assert.equal(view.option.parallelAxis[15].max, 100, 'Off-screen fixed limits must survive panning.');
    view.window.handleMessage({ status: 'ready', chart: { ...fixed, parallel: { ...fixed.parallel, truncated: true } } });
    assert.match(view.warningElement.textContent, /truncated/);
    assert.match(view.warningElement.textContent, /outside fixed/);
    view.window.handleMessage({ status: 'ready', chart: many });
    assert.equal(Object.hasOwn(view.option.parallelAxis[0], 'min'), false, 'Returning to auto must remove old fixed scales.');
    assert.equal(view.warningElement.hidden, true, 'The range warning must clear with the fixed range.');
    view.failNextRender();
    view.window.handleMessage({status:'ready', chart:fixed});
    assert.equal(view.viewport.hidden, true);
    assert.match(view.errorElement.textContent, /fixed axis ranges could not be rendered/,
        'Native precision failures must produce a controlled error, not a broken partial chart or silently widened bounds.');
    view.window.handleMessage({status:'ready', chart:many});
    assert.equal(view.viewport.hidden, false, 'A later valid range must recover without reloading the document.');
}

const localECharts = path.join(__dirname, '..', '.tools', 'echarts-runtime', 'node_modules', 'echarts');
if (fs.existsSync(localECharts)) {
    const echarts = require(localECharts);
    for (const mode of ['symcon', 'ipsview']) {
        for (const width of [320, 1100]) {
            const view = render(many, mode, width);
            const actual = echarts.init(null, null, { renderer: 'svg', ssr: true,
                width: parseFloat(view.chartElement.style.width), height: 400 });
            actual.setOption(view.option);
            const series = actual.getModel().getSeriesByIndex(0);
            const coord = series.coordinateSystem;
            const beforeExtent = coord.getAxis('dim15').scale.getExtent().slice();
            assert.equal(series.getData().count(), 1);
            assert.equal(coord.dimensions.length, 16, 'The actual renderer must retain all sixteen dimensions.');
            for (let index = 1; index < 16; index++) {
                assert.ok(coord.getAxisLayout('dim' + index).position[0] - coord.getAxisLayout('dim' + (index - 1)).position[0] >= 95,
                    'The actual native coordinate system must have readable axis separation.');
            }
            actual.dispatchAction({ type: 'axisAreaSelect', parallelAxisIndex: 15, intervals: [[6, 8]] });
            assert.equal(actual.getModel().getComponent('parallelAxis', 15).getActiveState(7), 'active');
            view.key('End');
            actual.setOption({ title: view.option.title });
            assert.deepEqual(coord.getAxis('dim15').scale.getExtent(), beforeExtent, 'Panning must not recalculate the scale from an excerpt.');
            assert.equal(actual.getModel().getComponent('parallelAxis', 15).getActiveState(7), 'active',
                'Title-only panning updates must preserve native selections, including off-screen axes.');
            const title = actual.getViewOfComponentModel(actual.getModel().getComponent('title'));
            const bounds = title.group.getBoundingRect();
            const screenCenter = title.group.x + bounds.x + bounds.width / 2 - view.viewport.scrollLeft;
            assert.ok(Math.abs(screenCenter - width / 2) < 8, 'The actual diagram title must stay centered inside the visible viewport.');
            assert.match(actual.renderToSVGString(), /<svg/);
            const p = view.option.tooltip.position([99999, 99999], null, null, null, { contentSize: [150, 200] });
            assert.ok(p[0] >= view.viewport.scrollLeft && p[0] + 150 <= view.viewport.scrollLeft + width,
                'All sixteen original tooltip values must remain inside the scrolled viewport.');
            actual.dispose();
        }
    }
    console.log('Pinned ECharts keeps full Parallel geometry, native selections, scales and visible titles while panning.');
    for (const mode of ['symcon', 'ipsview']) {
        const view = render(fixed, mode, 360, true);
        view.window.echarts.graphic = echarts.graphic;
        echarts.registerPostUpdate(view.postUpdate);
        const actual = echarts.init(null, null, {renderer: 'svg', ssr: true,
            width: parseFloat(view.chartElement.style.width), height: 400});
        actual.setOption(view.option);
        const coord = actual.getModel().getSeriesByIndex(0).coordinateSystem;
        assert.deepEqual(coord.getAxis('dim0').scale.getExtent(), [-10, 40]);
        assert.deepEqual(coord.getAxis('dim15').scale.getExtent(), [0, 100]);
        const series = actual.getModel().getSeriesByIndex(0);
        const clip = actual.getViewOfSeriesModel(series).group.getClipPath();
        assert.ok(clip, 'A fixed-range series needs a persistent root clip, independent of the temporary native startup clip.');
        const plot = coord.getRect();
        assert.deepEqual([clip.shape.x,clip.shape.y,clip.shape.width,clip.shape.height],
            [plot.x,plot.y,plot.width,plot.height], 'The native series clip must match the actual plot, not the full title-bearing canvas.');
        assert.equal(actual.getViewOfComponentModel(actual.getModel().getComponent('title')).group.getClipPath(), undefined,
            'Only lines are clipped; the title must not be clipped with the series.');
        assert.equal(series.getData().get('dim15', 0), 125);
        actual.dispatchAction({type:'axisAreaSelect', parallelAxisIndex:15, intervals:[[99,100]]});
        assert.equal(actual.getModel().getComponent('parallelAxis',15).getActiveState(125), 'inactive',
            'Out-of-range originals must not be turned into selected boundary values.');
        view.key('End');
        actual.setOption({title:view.option.title});
        assert.deepEqual(coord.getAxis('dim15').scale.getExtent(), [0,100]);
        assert.ok(actual.getViewOfSeriesModel(series).group.getClipPath(), 'Title-only panning updates must retain the clip.');
        assert.match(actual.renderToSVGString(), /<svg/);
        actual.setOption(render(many, mode, 360, true).option, true);
        const autoCoord = actual.getModel().getSeriesByIndex(0).coordinateSystem;
        assert.notDeepEqual(autoCoord.getAxis('dim15').scale.getExtent(), [0,100],
            'A real refresh back to automatic axes must discard the former fixed extent.');
        assert.equal(actual.getViewOfSeriesModel(actual.getModel().getSeriesByIndex(0)).group.getClipPath(), null,
            'Returning to automatic axes must remove the additional series-root clip.');
        actual.dispose();
    }
    console.log('Pinned Parallel fixed scales retain original values and honest native brushing.');
    for (const [minimum, maximum, value] of [[-1e150,1e150,1e149],[0.0001,0.0002,0.00015]]) {
        const numeric = {...fixed, parallel:{...fixed.parallel,
            axes:fixed.parallel.axes.map((axis,i)=>i===0?{...axis,minimum,maximum}:axis),
            rows:[{...fixed.parallel.rows[0],values:[value,...fixed.parallel.rows[0].values.slice(1)]}]}};
        const view = render(numeric);
        const actual = echarts.init(null,null,{renderer:'svg',ssr:true,width:parseFloat(view.chartElement.style.width),height:400});
        actual.setOption(view.option);
        assert.doesNotMatch(actual.renderToSVGString(), /NaN|Infinity/, 'Small fractional and large finite fixed ranges keep finite native geometry.');
        actual.dispose();
    }
}
