'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '..', 'EChartsPie', 'visualization', 'app.js'), 'utf8');
const designSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-design.js'), 'utf8');
const selectionSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-series-selection.js'), 'utf8');

const palette = {
    background: '#101114', text: '#f4f5f7', muted: '#969aa2',
    accent: '#55cbb5', seriesColors: ['#111111', '#222222', '#333333']
};

function render(style, items, width = 800, mode = 'symcon', height = 400, normalized = false, selectable = false, observer = true) {
    const chartElement = { hidden: false, clientWidth: width, clientHeight: height };
    const errorElement = { hidden: true, textContent: '' };
    const emptyElement = { hidden: true, textContent: '' };
    const warningElement = { hidden: true, textContent: '' };
    let option;
    const handlers = {};
    const windowHandlers = {};
    let resizeObserver;
    let nativeChart;
    const chart = {
        setOption: next => { option = next; if (nativeChart) { nativeChart.setOption(next, true); } },
        getOption: () => nativeChart ? nativeChart.getOption() : option,
        on: (name, handler) => { handlers[name] = handler; },
        clear: () => {}, resize: () => { if (nativeChart) { nativeChart.resize({width: chartElement.clientWidth, height: chartElement.clientHeight}); } }, dispose: () => {},
        getWidth: () => width, getHeight: () => height
    };
    const model = {
        family: 'pie', theme: 'dark', pie: { title: 'Shares', style, normalized, enableSourceSelection: selectable }, items
    };
    const window = {
        SYMC_VISUALIZATION: {
            mode, state: { status: 'ready', chart: model },
            translations: { 'No positive values': 'Keine positiven Werte', All: 'Alle', Invert: 'Umkehren' },
            options: { echartsThemes: { auto: palette, dark: palette }, tileHeaderVisible: mode === 'symcon', tileHeaderInset: mode === 'symcon' ? 58 : 0}
        },
        echarts: { init: () => chart },
        addEventListener: (name, handler) => { windowHandlers[name] = handler; },
        getComputedStyle: () => ({ color: '' })
    };
    if (observer) { window.ResizeObserver = function (handler) { resizeObserver = handler; this.observe = () => {}; }; }
    const document = {
        body: { appendChild: () => {} },
        createElement: () => ({ style: {}, remove: () => {} }),
        getElementById: id => ({
            'echarts-pie-chart': chartElement,
            'echarts-pie-error': errorElement,
            'echarts-pie-empty': emptyElement,
            'echarts-pie-warning': warningElement
        })[id]
    };
    vm.runInNewContext(designSource, { window });
    vm.runInNewContext(selectionSource, { window });
    vm.runInNewContext(source, { window, document, ResizeObserver: window.ResizeObserver });
    return { get option() { return option; }, window, chartElement, errorElement, emptyElement, model,
        useNative: engine => {
            nativeChart = engine;
            if (engine) { Object.entries(handlers).forEach(([name, handler]) => engine.on(name, handler)); engine.setOption(option); }
        },
        select: (id, visible) => { option.legend.selected[id] = visible; handlers.legendselectchanged(); },
        scroll: index => { option.legend.scrollDataIndex = index; },
        update: () => window.handleMessage({status: 'ready', chart: model}),
        resize: (nextWidth, nextHeight) => {
            chartElement.clientWidth = nextWidth; chartElement.clientHeight = nextHeight;
            (resizeObserver || windowHandlers.resize)();
        }
    };
}

const sources = [
    { id: 'variable-1', name: 'Temperatur', value: 10, order: 0, unit: '°C', decimals: 1, color: '#aa0000' },
    { id: 'variable-2', name: 'Temperatur', value: 20, order: 1, unit: '°C', decimals: 1, color: '' }
];
const baseStyle = {
    mode: 'pie', sortOrder: 'configured', innerRadiusPercent: 35, outerRadiusPercent: 90,
    showLegend: true, showLabels: true, showPercent: true, labelPosition: 'outside'
};
const selectablePie = render(baseStyle, sources.map((item, index) => ({...item, initiallyVisible: index === 0})),
    320, 'symcon', 400, false, true);
assert.equal(selectablePie.option.legend.show, true, 'Source selection must remain available in compact tiles.');
assert.equal(selectablePie.option.legend.selected['variable-2'], false);
assert.equal(selectablePie.option.legend.selector.length, 2);
const normalizedPie = render(baseStyle, [
    { ...sources[0], value: 50, originalValue: -10, minimum: -20, maximum: 0 },
    { ...sources[1], value: 75, originalValue: 75, minimum: 0, maximum: 100, unit: '%' }
], 800, 'symcon', 400, true);
const normalizedTooltip = normalizedPie.option.tooltip.formatter({ name: 'variable-1', dataIndex: 0, percent: 40 });
assert.match(normalizedTooltip, /-10/);
assert.match(normalizedTooltip, /°C/);
assert.match(normalizedTooltip, /Normalized/);
assert.match(normalizedTooltip, /normalized weights/);
assert.match(normalizedTooltip, /Range/);
const narrowNormalizedPie = render(baseStyle, sources.map(item => ({ ...item,
    originalValue: item.value, minimum: -20, maximum: 40 })), 320, 'symcon', 400, true);
assert.equal(narrowNormalizedPie.option.tooltip.confine, true,
    'Long normalization tooltips must stay inside a narrow embedded tile.');
assert.match(narrowNormalizedPie.option.tooltip.extraCssText, /max-width:296px.*overflow-wrap:anywhere/);
for (const outputMode of ['symcon', 'ipsview']) {
    const result = render({ ...baseStyle, showPercent: false }, [
        { ...sources[0], value: 100, originalValue: 140, minimum: 0, maximum: 100, unit: 'LQI' }
    ], 800, outputMode, 400, true);
    assert.equal(result.option.series[0].data[0].value, 100);
    assert.match(result.option.tooltip.formatter({ name: 'variable-1', percent: 100 }), /140/);
    assert.match(result.option.tooltip.formatter({ name: 'variable-1', percent: 100 }), /LQI/);
    assert.match(result.option.title.subtext, /normalized weights/);
    assert.match(result.option.aria.description, /normalized weights/);
}
const crowdedTile = render({ ...baseStyle, outerRadiusPercent: 100 }, [
    { ...sources[0], name: 'Feuchtigkeit' },
    { ...sources[1], name: 'Feuchtigkeit' },
    { ...sources[1], id: 'variable-3', name: 'Feuchtigkeit' },
    { ...sources[1], id: 'variable-4', name: 'Feuchtigkeit' }
], 586, 'symcon', 552);
const crowdedSeries = crowdedTile.option.series[0];
const crowdedCenter = crowdedSeries.center;
const crowdedRadius = crowdedSeries.radius[1];
assert.equal(crowdedTile.option.legend.orient, 'horizontal',
    'A medium-sized tile should place its legend below the Pie instead of beside the labels.');
assert.ok(crowdedCenter[1] - crowdedRadius >= 58,
    'The Pie must leave the visible Symcon tile header unobstructed.');
assert.ok(crowdedCenter[1] + crowdedRadius <= 552 - 45,
    'The Pie must not collide with its bottom legend.');
assert.ok(crowdedCenter[0] - crowdedRadius >= 60 && crowdedCenter[0] + crowdedRadius <= 586 - 60,
    'Outside labels need room on both sides of the Pie.');
const wideTile = render({ ...baseStyle, outerRadiusPercent: 100 }, sources, 1000, 'symcon', 560);
assert.equal(wideTile.option.legend.orient, 'vertical');
assert.ok(wideTile.option.series[0].center[0] + wideTile.option.series[0].radius[1]
    < 1000 - wideTile.option.legend.width,
    'A wide tile must reserve a separate column for the legend.');
assert.ok(wideTile.option.series[0].right >= wideTile.option.legend.width + 16,
    'The Pie series layout box must exclude the right legend, including edge-aligned labels.');
const pie = render(baseStyle, sources);
assert.equal(pie.option.title.show, true);
assert.ok(pie.option.title.top >= 58
    && pie.option.series[0].center[1] - pie.option.series[0].radius[1]
        > pie.option.title.top + pie.option.title.textStyle.fontSize,
    'The Pie title and plot must clear the Symcon tile header.');
const routedLabels = render({ ...baseStyle, outsideLabelAlignment: 'labelLine',
    leaderLineLength: 24, leaderLineLength2: 16, labelLineGap: 7,
    labelEdgeDistance: 12 }, sources);
assert.equal(routedLabels.option.series[0].label.alignTo, 'labelLine');
assert.equal(routedLabels.option.series[0].label.distanceToLabelLine, 7);
assert.equal(routedLabels.option.series[0].labelLine.length, 24);
assert.equal(routedLabels.option.series[0].labelLine.length2, 16);
assert.ok(pie.option, 'Pie must render current positive values.');
assert.equal(pie.option.series[0].type, 'pie');
assert.equal(pie.option.series[0].radius[0], 0);
assert.ok(pie.option.series[0].radius[1] > 0);
assert.equal(pie.option.series[0].roseType, false);
assert.equal(pie.option.series[0].stillShowZeroSum, false);
assert.equal(pie.option.series[0].showEmptyCircle, false, 'No selection must not produce an artificial grey placeholder circle.');
assert.deepEqual(Array.from(pie.option.series[0].data, item => item.name), ['variable-1', 'variable-2']);
assert.equal(pie.option.legend.formatter('variable-1'), 'Temperatur');
assert.equal(pie.option.legend.formatter('variable-2'), 'Temperatur');
assert.ok(!pie.option.legend.formatter('variable-1').includes('variable-'));
assert.ok(!pie.option.tooltip.formatter({ name: 'variable-1', marker: '', percent: 33.3 }).includes('variable-'),
    'Variable IDs must remain internal even when visible names collide.');
assert.match(pie.option.series[0].label.formatter({ name: 'variable-2', percent: 66.7 }), /^Temperatur\n66[,.]7%$/);
assert.equal(pie.option.series[0].data[0].itemStyle.color, '#aa0000');
assert.equal(pie.option.series[0].data[1].itemStyle.color, '#222222');

const donut = render({ ...baseStyle, mode: 'donut', sortOrder: 'descending', labelPosition: 'inside',
    showPercent: false, legendFontSizePercent: 150, labelColor: '#123456' }, sources, 800, 'ipsview');
assert.ok(Math.abs(donut.option.series[0].radius[0] - donut.option.series[0].radius[1] * 0.35) < 0.001);
assert.deepEqual(Array.from(donut.option.series[0].data, item => item.name), ['variable-2', 'variable-1']);
assert.equal(donut.option.series[0].label.position, 'inside');
assert.equal(donut.option.series[0].label.color, '#123456');
assert.equal(donut.option.series[0].label.formatter({ name: 'variable-2', percent: 66.7 }), 'Temperatur');
assert.equal(donut.option.legend.textStyle.fontSize, 18);

const rose = render({ ...baseStyle, mode: 'nightingale', animationEnabled: true,
    animationDuration: 800, animationDurationUpdate: 1200, animationEasing: 'bounceOut' }, sources);
assert.equal(rose.option.series[0].roseType, 'area');
assert.equal(rose.option.animationDuration, 800);
assert.equal(rose.option.animationDurationUpdate, 1200);
assert.equal(rose.option.animationEasing, 'bounceOut');

const compact = render(baseStyle, sources, 320);
assert.equal(compact.option.legend.show, false, 'Compact tiles should reserve space for sectors.');
const zero = render(baseStyle, sources.map(item => ({ ...item, value: 0 })));
assert.equal(zero.option.series[0].data.length, 2, 'Zero-valued models must retain their legend and recover on an update.');
assert.equal(zero.emptyElement.textContent, 'Keine positiven Werte');
assert.equal(zero.emptyElement.hidden, false);

const hostile = render(baseStyle, [
    { ...sources[0], name: '<img src=x onerror=alert(1)>', unit: '<b>' }
]);
const tooltip = hostile.option.tooltip.formatter({ name: 'variable-1', marker: '', percent: 100 });
assert.ok(tooltip.includes('&lt;img'));
assert.ok(tooltip.includes('&lt;b&gt;'));
assert.ok(!tooltip.includes('<img'));

const manyItems = Array.from({length: 100}, (_, index) => ({
    id: 'variable-' + (index + 1), name: 'Gleicher Name', value: 10, order: index,
    unit: 'W', decimals: 1, color: '', initiallyVisible: index < 50
}));
for (const outputMode of ['symcon', 'ipsview']) {
    for (const observer of [true, false]) {
        const many = render({...baseStyle, sortOrder: 'descending'}, manyItems.map(item => ({...item})),
            800, outputMode, 400, false, true, observer);
        assert.equal(many.option.series[0].data.length, 100);
        assert.equal(many.option.legend.selected['variable-100'], false);
        let text = many.option.tooltip.formatter({name: 'variable-1', percent: 999});
        assert.match(text, /Share of visible selection: 2%/);
        assert.match(text, /Share of full model: 1%/);
        many.select('variable-1', false);
        many.scroll(10);
        const anchoredId = many.option.legend.data[10];
        const oldColor = many.option.series[0].data.find(item => item.id === 'variable-2').itemStyle.color;
        many.model.items[1].value = 1000;
        many.model.theme = 'auto';
        many.update();
        assert.equal(many.option.legend.selected['variable-1'], false);
        assert.equal(many.option.legend.data[many.option.legend.scrollDataIndex], anchoredId);
        assert.equal(many.option.series[0].data.find(item => item.id === 'variable-2').itemStyle.color, oldColor);
        many.resize(300, 300);
        assert.equal(many.option.legend.show, true);
        assert.equal(many.option.legend.selected['variable-1'], false);
        assert.equal(many.option.legend.data[many.option.legend.scrollDataIndex], anchoredId);
        many.model.items = many.model.items.filter(item => item.id !== 'variable-2');
        many.update();
        assert.equal(many.option.legend.selected['variable-2'], undefined);
        for (const item of many.model.items) { many.select(item.id, false); }
        assert.equal(many.emptyElement.textContent, 'No sources selected.');
        assert.equal(many.chartElement.hidden, false);
        assert.equal(many.option.legend.show, true);
        many.select('variable-100', true);
        assert.equal(many.emptyElement.hidden, true);
        assert.match(many.option.series[0].label.formatter({name: 'variable-100', percent: 0}), /^Gleicher Name\n100[,.]0%$/);
    }
}
const legacySelection = render(baseStyle, sources.map(item => ({...item, initiallyVisible: false})));
assert.equal(legacySelection.option.legend.selected['variable-1'], true, 'Disabled optional selection ignores initial flags.');
legacySelection.select('variable-1', false);
legacySelection.update();
assert.equal(legacySelection.option.legend.selected['variable-1'], false, 'Existing legend clicks survive refresh.');
assert.match(legacySelection.option.tooltip.formatter({name: 'variable-2', percent: 0}), /visible selection: 100%/);
const selectedZero = render(baseStyle, [{...sources[0], value: 0}, {...sources[1], initiallyVisible: false}],
    800, 'symcon', 400, false, true);
assert.equal(selectedZero.emptyElement.textContent, 'No positive values in the selected sources.');
selectedZero.select('variable-2', true);
assert.equal(selectedZero.emptyElement.hidden, true);
const huge = render(baseStyle, sources.map(item => ({...item, value: Number.MAX_VALUE})));
assert.equal(huge.option.series[0].data[0].value, 1, 'Native sector geometry must not overflow when finite weights sum above MAX_VALUE.');
assert.match(huge.option.tooltip.formatter({name: 'variable-1', percent: 0}), /visible selection: 50%/);
assert.match(huge.option.tooltip.formatter({name: 'variable-1', percent: 0}), /full model: 50%/);
const tinySelection = render(baseStyle, [{...sources[0], value: Number.MIN_VALUE},
    {...sources[1], value: Number.MAX_VALUE, initiallyVisible: false}], 800, 'symcon', 400, false, true);
assert.equal(tinySelection.emptyElement.hidden, true, 'A hidden huge source cannot erase a selected tiny positive source.');
assert.match(tinySelection.option.tooltip.formatter({name: 'variable-1', percent: 0}), /visible selection: 100%/);
const normalizedMany = render(baseStyle, manyItems.map(item => ({...item, value: 1, originalValue: -10,
    minimum: -20, maximum: 980, unit: '°C', initiallyVisible: true})), 300, 'ipsview', 300, true, true);
assert.match(normalizedMany.option.tooltip.formatter({name: 'variable-100', percent: 0}), /all normalized weights: 1%/);
assert.match(normalizedMany.option.tooltip.formatter({name: 'variable-100', percent: 0}), /-10[,.]0 °C/);
const crowdedLabels = render(baseStyle, manyItems.map(item => ({...item, initiallyVisible: true})),
    586, 'symcon', 552, false, true);
assert.ok(crowdedLabels.option.series[0].data.filter(item => item.label?.show !== false).length <= 24,
    'A crowded Pie must bound visible labels without depending on an unavailable native label-layout component.');
const shownCrowdedIds = crowdedLabels.option.series[0].data.filter(item => item.label?.show !== false).map(item => Number(item.id.split('-')[1]));
assert.ok(shownCrowdedIds.some(id => id > 90) && shownCrowdedIds.some(id => id < 10),
    'Crowded labels must sample around the entire visible circle, not only the first sources.');
assert.equal(crowdedLabels.option.series[0].data.length, 100);
assert.equal(crowdedLabels.option.legend.data.length, 100);
const initiallyTen = render(baseStyle, manyItems.map((item, index) => ({...item, initiallyVisible: index < 10})),
    586, 'symcon', 552, false, true);
assert.equal(initiallyTen.option.series[0].data.slice(0, 10).filter(item => item.label?.show !== false).length, 10,
    'A readable ten-source selection retains every label.');
const largeLabels = render({...baseStyle, labelFontSizePercent: 200}, manyItems.map(item => ({...item, initiallyVisible: true})),
    586, 'symcon', 552, false, true);
assert.ok(largeLabels.option.series[0].data.filter(item => item.label?.show !== false).length < shownCrowdedIds.length,
    'Larger fonts must reduce label density.');

const localECharts = path.join(__dirname, '..', '.tools', 'echarts-runtime', 'node_modules', 'echarts');
if (fs.existsSync(localECharts)) {
    const echarts = require(localECharts);
    const chart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 800, height: 400 });
    chart.setOption(pie.option);
    let layout = chart.getModel().getSeriesByIndex(0).getData();
    assert.equal(layout.count(), 2, 'The bundled ECharts Pie series must keep equal visible names separate.');
    assert.equal(layout.getItemLayout(0).r0, 0);
    chart.setOption(donut.option, true);
    layout = chart.getModel().getSeriesByIndex(0).getData();
    assert.ok(layout.getItemLayout(0).r0 > 0, 'The Donut must leave a real inner opening.');
    chart.setOption(rose.option, true);
    layout = chart.getModel().getSeriesByIndex(0).getData();
    assert.equal(layout.getItemLayout(0).angle, layout.getItemLayout(1).angle,
        'The Nightingale rose must use equal sector angles.');
    assert.ok(layout.getItemLayout(0).r < layout.getItemLayout(1).r,
        'The Nightingale rose must use value-dependent radii.');
    chart.dispose();
}

// Run the actual shipped, limited Pie bundle; the external SVG renderer is only a headless test surface.
const shipped = {};
vm.runInNewContext(fs.readFileSync(path.join(__dirname, '..', 'libs/echarts/6.1.0/echarts.pie.min.js'), 'utf8'),
    {window: shipped, navigator: {userAgent: 'Node.js'}, console, setTimeout, clearTimeout});
shipped.echarts.use(require('../.tools/echarts-runtime/node_modules/echarts/renderers').SVGRenderer);
for (const mode of ['pie', 'donut', 'nightingale']) {
    for (const normalized of [false, true]) {
        for (const fontSize of [100, 200]) {
            const wide = render({...baseStyle, mode, animationEnabled: false, labelFontSizePercent: fontSize,
                legendFontSizePercent: fontSize}, manyItems.map((item, index) => ({...item,
                initiallyVisible: index < 10, value: 1, originalValue: 10, minimum: 0, maximum: 100})),
            1232, 'ipsview', 712, normalized, true);
            assert.ok(wide.option.legend.width >= wide.option.legend.textStyle.fontSize * 12 + 24,
                'The right legend must reserve font-aware room for translated selectors.');
            assert.ok(Number.isFinite(wide.option.legend.left)
                && wide.option.legend.left + wide.option.legend.width <= 1232,
                'The right legend must have an explicit in-canvas left anchor, independent of native text measurements.');
            const engine = shipped.echarts.init(null, null, {renderer: 'svg', ssr: true, width: 1232, height: 712});
            engine.setOption(wide.option);
            const legendView = engine.getViewOfComponentModel(engine.getModel().getComponent('legend'));
            let selectorCount = 0;
            legendView.group.traverse(element => {
                if (element.type === 'text' && ['Alle', 'Umkehren'].includes(element.style.text)) {
                    const box = element.getBoundingRect().clone();
                    box.applyTransform(element.getComputedTransform());
                    assert.ok(box.x >= 0 && box.x + box.width <= 1232,
                        'Both translated source selectors must fit inside a wide canvas even with large fonts.');
                    selectorCount += 1;
                }
            });
            assert.equal(selectorCount, 2);
            const data = engine.getModel().getSeriesByIndex(0).getData();
            const plotRight = 1232 - wide.option.series[0].right;
            assert.equal(data.count(), 10);
            for (let index = 0; index < data.count(); index += 1) {
                const text = data.getItemGraphicEl(index)?.getTextContent();
                if (text && !text.ignore) {
                    const box = text.getBoundingRect().clone();
                    box.applyTransform(text.getComputedTransform());
                    assert.ok(box.x + box.width <= plotRight + 1,
                        'Native edge-aligned labels must stay out of the reserved right legend column.');
                }
            }
            engine.dispose();
        }
    }
}
for (const mode of ['pie', 'donut', 'nightingale']) {
    for (const normalized of [false, true]) {
        const crowded = render({...baseStyle, mode, animationEnabled: false},
            manyItems.map(item => ({...item, value: 1, initiallyVisible: true, originalValue: 10,
                minimum: 0, maximum: 100})), 586, 'symcon', 552, normalized, true);
        const engine = shipped.echarts.init(null, null, {renderer: 'svg', ssr: true, width: 586, height: 552});
        engine.setOption(crowded.option);
        const data = engine.getModel().getSeriesByIndex(0).getData();
        assert.equal(data.count(), 100);
        let visibleTexts = 0;
        for (let index = 0; index < data.count(); index += 1) {
            const text = data.getItemGraphicEl(index)?.getTextContent();
            if (text && !text.ignore) { visibleTexts += 1; }
            if (crowded.option.series[0].data[index].label?.show === false) {
                assert.equal(data.getItemModel(index).get(['labelLine', 'show']), false);
            }
        }
        assert.ok(visibleTexts > 0 && visibleTexts <= 24,
            'The shipped lean runtime must actually suppress excess labels for every Pie mode and normalization.');
        engine.dispose();
    }
}
const tinyEngine = shipped.echarts.init(null, null, {renderer: 'svg', ssr: true, width: 800, height: 400});
tinySelection.useNative(tinyEngine);
assert.equal(tinyEngine.getModel().getSeriesByIndex(0).getData().count(), 1);
assert.ok(Math.abs(tinyEngine.getModel().getSeriesByIndex(0).getData().getItemLayout(0).angle - Math.PI * 2) < 1e-10,
    'The native engine must retain a fully visible tiny positive sector when huge sources are hidden.');
tinySelection.useNative(null);
tinyEngine.dispose();
for (const outputMode of ['symcon', 'ipsview']) {
    for (const observer of [true, false]) {
        const native = render({...baseStyle, sortOrder: 'descending', animationEnabled: false}, manyItems.map(item => ({...item})),
            800, outputMode, 400, false, true, observer);
        const engine = shipped.echarts.init(null, null, {renderer: 'svg', ssr: true, width: 800, height: 400});
        native.useNative(engine);
        assert.equal(engine.getModel().getSeriesByIndex(0).getData().count(), 50);
        let data = engine.getModel().getSeriesByIndex(0).getData();
        assert.equal(data.getName(0), 'variable-1');
        assert.ok(Math.abs(data.getItemLayout(0).angle - Math.PI * 2 / 50) < 1e-10);
        engine.dispatchAction({type: 'legendInverseSelect'});
        assert.equal(engine.getOption().legend[0].selected['variable-100'], true);
        data = engine.getModel().getSeriesByIndex(0).getData();
        assert.equal(data.count(), 50);
        assert.equal(data.getName(49), 'variable-100', 'Equal display names cannot merge the final native sector.');
        let parameter = engine.getModel().getSeriesByIndex(0).getDataParams(49);
        assert.match(native.option.tooltip.formatter(parameter), /visible selection: 2%/);
        assert.match(native.option.tooltip.formatter(parameter), /full model: 1%/);
        engine.dispatchAction({type: 'legendScroll', scrollDataIndex: 75});
        const anchorId = native.option.legend.data[75];
        native.model.items[99].value = 1000;
        native.update();
        assert.equal(engine.getOption().legend[0].selected['variable-1'], false);
        assert.equal(native.option.legend.data[native.option.legend.scrollDataIndex], anchorId);
        assert.equal(native.option.series[0].data[0].name, 'variable-100');
        native.resize(300, 260);
        assert.equal(native.option.legend.show, true);
        assert.equal(native.option.legend.data[native.option.legend.scrollDataIndex], anchorId);
        engine.dispatchAction({type: 'legendAllSelect'});
        assert.equal(engine.getModel().getSeriesByIndex(0).getData().count(), 100);
        engine.dispatchAction({type: 'legendInverseSelect'});
        assert.equal(engine.getModel().getSeriesByIndex(0).getData().count(), 0);
        assert.equal(native.emptyElement.textContent, 'No sources selected.');
        assert.equal(native.chartElement.hidden, false);
        engine.dispatchAction({type: 'legendAllSelect'});
        assert.equal(engine.getModel().getSeriesByIndex(0).getData().count(), 100);
        assert.equal(native.emptyElement.hidden, true);
        native.model.items.forEach(item => {item.value = Number.MAX_VALUE;});
        native.update();
        data = engine.getModel().getSeriesByIndex(0).getData();
        assert.ok(Number.isFinite(data.getItemLayout(99).angle) && data.getItemLayout(99).angle > 0);
        assert.match(native.option.tooltip.formatter(engine.getModel().getSeriesByIndex(0).getDataParams(99)), /full model: 1%/);
        native.model.items.forEach(item => {item.value = 0;});
        native.update();
        assert.equal(native.emptyElement.textContent, 'Keine positiven Werte');
        assert.equal(native.option.legend.data.length, 100);
        native.model.items[99].value = 1;
        native.model.pie.style.labelFontSizePercent = 200;
        native.model.pie.style.legendFontSizePercent = 200;
        native.model.pie.style.mode = 'nightingale';
        native.model.theme = 'auto';
        native.update();
        assert.equal(native.emptyElement.hidden, true);
        assert.ok(engine.renderToSVGString().includes('<svg'));
        native.useNative(null);
        engine.dispose();
    }
}

console.log('Pie renderer layout verified.');
