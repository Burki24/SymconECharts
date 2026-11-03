'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.join(__dirname, '..');
const read = name => fs.readFileSync(path.join(root, name), 'utf8');
const echarts = require(path.join(root, '.tools/echarts-runtime/node_modules/echarts'));
assert.equal(echarts.version, '6.1.0');
const palette = {background: '#303236', text: '#f4f5f7', muted: '#969aa2', border: '#777b83', accent: '#55cbb5', seriesColors: ['#5070DD', '#B6D634', '#FF994D']};
const start = 1792144800;
const model = {schemaVersion: 1, family: 'stateTimeline', theme: 'auto', range: {key: '24h', startTimestamp: start, endTimestamp: start + 86400, timezone: 'Europe/Berlin'},
    stateTimeline: {title: 'Zustände im Tagesverlauf', rows: [
        {id: 'variable-1', label: '<Same> with a very long device name', segments: [
            {startTimestamp: start, endTimestamp: start + 18000, value: null, label: '<Unknown>', color: ''},
            {startTimestamp: start + 18000, endTimestamp: start + 18001, value: 1, label: 'Tiny', color: '#ABCDEF'},
            {startTimestamp: start + 18001, endTimestamp: start + 60000, value: 2, label: '<Active>', color: '#AABBCC'},
            {startTimestamp: start + 60000, endTimestamp: start + 86400, value: 0, label: 'Off', color: ''}]},
        {id: 'variable-2', label: '<Same> with a very long device name', segments: [
            {startTimestamp: start, endTimestamp: start + 86400, value: -2, label: 'Repeated', color: ''}]}],
        style: {enableZoom: true, showTooltip: true, showLabels: true, showGrid: true, rowHeightPercent: 65, barOpacityPercent: 90,
            borderWidth: 0, titleFontSizePercent: 110, axisFontSizePercent: 75, labelFontSizePercent: 75, unknownColor: '#666666', animationEnabled: false}}};
function render(model, mode = 'symcon', width = 900, height = 500, headerVisible = true) {
    const inset = mode === 'symcon' && headerVisible ? 58 : 0;
    const elements = Object.fromEntries(['root', 'chart', 'error', 'empty', 'warning'].map(name => [name,
        {hidden: name !== 'chart' && name !== 'root', style: {}, textContent: '', clientWidth: width, clientHeight: height - inset,
            addEventListener: () => {}}]));
    let option, nativeEngine = null;
    const actions = [], events = {};
    const chart = {setOption: next => {option = next; if (nativeEngine) {nativeEngine.setOption(next, true);}},
        getOption: () => nativeEngine ? nativeEngine.getOption() : option, clear: () => {option = null;},
        resize: () => {}, dispose: () => {}, dispatchAction: action => actions.push(action)};
    const window = {SYMC_VISUALIZATION: {mode, language: 'de', state: {status: 'ready', chart: model}, translations: {},
        options: {tileHeaderVisible: mode === 'symcon' && (headerVisible), tileHeaderInset: mode === 'symcon' && (headerVisible) ? 58 : 0, adaptToBackground: mode === 'ipsview', echartsThemes: {auto: palette, dark: palette}}},
        echarts: {init: () => chart, graphic: echarts.graphic}, addEventListener: (name, fn) => {events[name] = fn;}, getComputedStyle: () => ({color: ''})};
    const document = {body: {appendChild: () => {}}, createElement: () => ({style: {}, remove: () => {}}),
        getElementById: id => elements[id.replace('echarts-state-timeline-', '')]};
    for (const name of ['libs/echarts-design.js', 'libs/echarts-zoom.js', 'EChartsStateTimeline/visualization/app.js']) {vm.runInNewContext(read(name), {window, document});}
    return {get option() {return option;}, useNativeEngine: engine => {nativeEngine = engine;}, ...elements, window, engine: chart, events, actions};
}
const before = JSON.stringify(model);
const tile = render(model);
assert.equal(tile.chart.style.top, '58px');
assert.equal(tile.chart.style.height, 'calc(100% - 58px)');
assert.equal(tile.option.series[0].type, 'custom');
assert.equal(tile.option.animation, false);
const animated = render({...model, stateTimeline: {...model.stateTimeline, style: {...model.stateTimeline.style,
    animationEnabled: true, animationDuration: 0, animationDurationUpdate: 1250, animationEasing: 'linear', animationEasingUpdate: 'cubicOut'}}});
assert.equal(animated.option.animation, true);
assert.equal(animated.option.animationDuration, 0);
assert.equal(animated.option.animationDurationUpdate, 1250);
assert.equal(animated.option.animationEasing, 'linear');
assert.equal(animated.option.animationEasingUpdate, 'cubicOut');
assert.equal(tile.option.series[0].data.length, 5, 'Keep tiny and unknown intervals, with exact endpoints.');
assert.equal(tile.option.series[0].data[1].value[1] - tile.option.series[0].data[1].value[0], 1000);
assert.equal(tile.option.series[0].data[0].itemStyle.color, '#666666');
assert.equal(tile.option.series[0].data[2].itemStyle.color, '#AABBCC');
assert.equal(tile.option.yAxis.data.length, 2);
assert.notEqual(tile.option.yAxis.data[0], tile.option.yAxis.data[1]);
assert.equal(tile.option.yAxis.axisLabel.formatter('variable-1'), tile.option.yAxis.axisLabel.formatter('variable-2'));
assert.ok(tile.option.dataZoom.every(entry => entry.filterMode === 'none'), 'A segment spanning the zoom window must not be filtered away.');
const tip = tile.option.tooltip.formatter({data: tile.option.series[0].data[2]});
assert.match(tip, /&lt;Active&gt;/);
assert.match(tip, /&lt;Same&gt;/);
assert.match(tip, /Duration: 11 h 39 min 59 s/);
assert.match(tip, /Value: 2/);
assert.doesNotMatch(tip, /<Active>|<Same>|variable-1/);
assert.doesNotMatch(tile.option.tooltip.formatter({data: tile.option.series[0].data[0]}), /Value: null/);
assert.equal(JSON.stringify(model), before, 'Rendering must never merge, mutate or rewrite source data.');
tile.window.handleMessage({status: 'error', error: 'The StateTimeline values could not be loaded.'});
assert.equal(tile.chart.hidden, false, 'Transient archive errors retain the last diagram.');
tile.window.handleMessage({status: 'error', error: 'Configure sources.'});
assert.equal(tile.error.hidden, false);
assert.equal(tile.error.textContent, 'Configure sources.');
tile.window.handleMessage({status: 'ready', chart: model});
assert.equal(tile.chart.hidden, false);
const empty = render({...model, stateTimeline: {...model.stateTimeline, rows: []}});
assert.equal(empty.empty.hidden, false);
const disabled = render({...model, stateTimeline: {...model.stateTimeline, style: {...model.stateTimeline.style,
    showTooltip: false, showLabels: false, showGrid: false, enableZoom: false, barOpacityPercent: 0, rowHeightPercent: 100,
    borderWidth: 5, borderColor: '#123456', titleColor: '#223344', axisColor: '#334455', labelColor: '#445566'}}}, 'ipsview');
assert.equal(disabled.option.backgroundColor, 'transparent');
assert.equal(disabled.option.dataZoom.length, 0);
assert.equal(disabled.option.tooltip.show, false);
assert.equal(disabled.option.xAxis.splitLine.show, false);
assert.equal(disabled.option.title.textStyle.color, '#223344');
const styled = render({...model, stateTimeline: {...model.stateTimeline, style: {...model.stateTimeline.style,
    borderType: 'dotted', borderRadius: 20, borderWidth: 2, labelPosition: 'left', fontFamily: 'Georgia',
    titleBold: false, titleItalic: true, axisLabelBold: true, axisLabelItalic: true, labelBold: true, labelItalic: true,
    showTimeLabels: true, showSourceLabels: false, showAxisLine: false, showAxisTicks: true,
    axisLineWidth: 3, axisLineType: 'dashed', axisLabelMargin: 30, timeLabelFormat: 'dateTime',
    gridColor: '#112233', gridOpacityPercent: 35, gridLineWidth: 2, gridLineType: 'dotted',
    emphasisEnabled: true, emphasisFocus: 'self', blurOpacityPercent: 20}}});
assert.equal(styled.option.yAxis.axisLabel.show, false);
assert.equal(styled.option.xAxis.axisLine.show, false);
assert.equal(styled.option.xAxis.axisTick.show, true);
assert.equal(styled.option.xAxis.axisLine.lineStyle.width, 3);
assert.equal(styled.option.xAxis.axisLine.lineStyle.type, 'dashed');
assert.equal(styled.option.xAxis.axisLabel.margin, 30);
assert.match(styled.option.xAxis.axisLabel.formatter(start * 1000), /16\.10\.\n\d{2}:\d{2}/);
assert.equal(styled.option.xAxis.splitLine.lineStyle.color, '#112233');
assert.equal(styled.option.xAxis.splitLine.lineStyle.opacity, 0.35);
assert.equal(styled.option.xAxis.splitLine.lineStyle.width, 2);
assert.equal(styled.option.xAxis.splitLine.lineStyle.type, 'dotted');
assert.equal(styled.option.title.textStyle.fontFamily, 'Georgia, serif');
assert.equal(styled.option.title.textStyle.fontWeight, 'normal');
assert.equal(styled.option.title.textStyle.fontStyle, 'italic');
assert.equal(styled.option.xAxis.axisLabel.fontWeight, 'bold');
assert.equal(styled.option.xAxis.axisLabel.fontStyle, 'italic');
const hiddenAxes = render({...model, stateTimeline: {...model.stateTimeline, style: {...model.stateTimeline.style,
    showTimeLabels: false, showSourceLabels: false}}});
assert.equal(hiddenAxes.option.xAxis.axisLabel.show, false);
assert.equal(hiddenAxes.option.yAxis.axisLabel.show, false);
assert.equal(hiddenAxes.option.grid.left, 24, 'Hidden source labels release their left plot margin.');
for (const [name, family] of [['Arial', 'Arial, sans-serif'], ['Verdana', 'Verdana, sans-serif'], ['Georgia', 'Georgia, serif'], ['monospace', 'monospace'], ['constructor', 'Arial, sans-serif']]) {
    const output = render({...model, stateTimeline: {...model.stateTimeline, style: {...model.stateTimeline.style, fontFamily: name}}});
    assert.equal(output.option.textStyle.fontFamily, family, 'Only named font choices map to local CSS stacks.');
}
const styledEngine = echarts.init(null, null, {renderer: 'svg', ssr: true, width: 900, height: 442});
styledEngine.setOption(styled.option);
const styledData = styledEngine.getModel().getSeries()[0].getData();
const styledGroup = styledData.getItemGraphicEl(0);
const styledRect = styledGroup.children()[0], styledText = styledGroup.children()[1];
assert.equal(styledRect.style.lineDash, 'dotted');
assert.ok(styledRect.shape.r > 0 && styledRect.shape.r <= styledRect.shape.width / 2 && styledRect.shape.r <= styledRect.shape.height / 2);
assert.equal(styledText.x, styledRect.shape.x + 4);
assert.equal(styledText.style.align, 'left');
assert.equal(styledText.style.fontFamily, 'Georgia, serif');
assert.equal(styledText.style.fontWeight, 'bold');
assert.equal(styledText.style.fontStyle, 'italic');
assert.ok(Math.abs(styledRect.states.blur.style.opacity - 0.18) < 1e-12);
assert.equal(styledText.states.blur.style.opacity, 0.2);
const tinyRect = styledData.getItemGraphicEl(1).children()[0];
assert.equal(tinyRect.shape.r, tinyRect.shape.width / 2, 'Corner rounding cannot enlarge tiny exact intervals.');
assert.match(styledEngine.renderToSVGString(), /stroke-dasharray=/, 'Native SVG geometry must contain real dotted strokes.');
styledEngine.getZr().trigger('mouseover', {target: styledRect, topTarget: styledRect, event: {}});
styledEngine.getZr().animation.update();
assert.ok(styledRect.currentStates.includes('emphasis'), 'Actual native mouseover must emphasize the hovered interval.');
assert.notEqual(styledRect.style.fill, '#666666', 'Native emphasis must visibly lift the normal interval fill.');
const blurredGroup = styledData.getItemGraphicEl(2), blurredRect = blurredGroup.children()[0], blurredText = blurredGroup.children()[1];
assert.ok(blurredRect.currentStates.includes('blur'));
assert.ok(blurredText.currentStates.includes('blur'));
assert.ok(Math.abs(blurredRect.style.opacity - 0.18) < 1e-12, 'Native focus-self applies relative opacity to other interval rectangles.');
assert.equal(blurredText.style.opacity, 0.2, 'Other interval labels must fade with their rectangle.');
styledEngine.getZr().trigger('mouseout', {target: styledRect, topTarget: styledRect, event: {}});
styledEngine.getZr().animation.update();
assert.equal(blurredRect.currentStates.length, 0);
assert.equal(blurredRect.style.opacity, 0.9, 'Mouseout restores the configured bar opacity.');
styledEngine.dispose();
for (const [enabled, focus] of [[false, 'self'], [true, 'none']]) {
    const output = render({...model, stateTimeline: {...model.stateTimeline, style: {...model.stateTimeline.style,
        emphasisEnabled: enabled, emphasisFocus: focus, blurOpacityPercent: 0}}});
    const engine = echarts.init(null, null, {renderer: 'svg', ssr: true, width: 900, height: 442});
    engine.setOption(output.option);
    const data = engine.getModel().getSeries()[0].getData();
    const target = data.getItemGraphicEl(0).children()[0], other = data.getItemGraphicEl(2).children()[0];
    engine.getZr().trigger('mouseover', {target, topTarget: target, event: {}});
    engine.getZr().animation.update();
    assert.equal(target.currentStates.includes('emphasis'), enabled, 'Disabling emphasis prevents the actual native hover state.');
    assert.equal(other.currentStates.includes('blur'), false, 'Disabled emphasis and focus-none never fade unrelated intervals.');
    assert.equal(other.style.opacity, 0.9);
    engine.dispose();
}
for (const position of ['center', 'left', 'right']) {
    const output = render({...model, stateTimeline: {...model.stateTimeline, style: {...model.stateTimeline.style, labelPosition: position,
        fontFamily: 'monospace', borderType: 'dashed', borderRadius: 20, borderWidth: 2}}});
    const engine = echarts.init(null, null, {renderer: 'svg', ssr: true, width: 900, height: 442});
    engine.setOption(output.option);
    const [rectangle, text] = engine.getModel().getSeries()[0].getData().getItemGraphicEl(2).children();
    assert.equal(text.style.align, position);
    assert.equal(text.style.fontFamily, 'monospace');
    const expectedX = position === 'left' ? rectangle.shape.x + 4 : position === 'right' ? rectangle.shape.x + rectangle.shape.width - 4 : rectangle.shape.x + rectangle.shape.width / 2;
    assert.equal(text.x, expectedX);
    assert.ok(text.getBoundingRect().width <= rectangle.shape.width - 8 + 1e-5, 'Label position must retain inner padding, never bleed into neighboring intervals.');
    assert.match(engine.renderToSVGString(), /stroke-dasharray=/);
    engine.dispose();
}

function geometry(option, width, height, zoomed) {
    const engine = echarts.init(null, null, {renderer: 'svg', ssr: true, width, height});
    engine.setOption(option);
    if (zoomed) {engine.dispatchAction({type: 'dataZoom', start: 30, end: 50});}
    const series = engine.getModel().getSeries()[0];
    const data = series.getData();
    const grid = engine.getModel().getComponent('grid').coordinateSystem.getRect();
    const rectangles = [];
    for (let i = 0; i < data.count(); i++) {
        const group = data.getItemGraphicEl(i);
        if (!group) {continue;}
        const rectangle = group.children().find(child => child.type === 'rect');
        assert.ok(rectangle);
        const shape = rectangle.shape;
        assert.ok(shape.x >= grid.x - 1e-5 && shape.y >= grid.y - 1e-5, 'Intervals start inside plot bounds.');
        assert.ok(shape.x + shape.width <= grid.x + grid.width + 1e-5, 'Panning cannot paint beyond the time-axis viewport.');
        assert.ok(shape.y + shape.height <= grid.y + grid.height + 1e-5, 'Rows cannot paint outside the plot.');
        assert.ok(shape.width > 0 && shape.height > 0 && [shape.x, shape.y, shape.width, shape.height].every(Number.isFinite));
        if (group.children().length > 1) {
            const text = group.children()[1];
            assert.ok(text.style.width <= shape.width - 8 + 1e-5);
            assert.ok(text.style.fontSize + 4 <= shape.height + 1e-5);
        }
        rectangles.push({index: i, shape, style: rectangle.style});
    }
    const svg = engine.renderToSVGString();
    assert.ok(!svg.includes('NaN') && !svg.includes('Infinity'));
    assert.ok(!/>variable-[12]</.test(svg), 'Technical source IDs never leak into axis labels.');
    const titleView = engine.getViewOfComponentModel(engine.getModel().getComponent('title'));
    const bounds = titleView.group.getBoundingRect().clone();
    bounds.applyTransform(titleView.group.getComputedTransform());
    assert.ok(grid.y >= bounds.y + bounds.height + 4, 'Plot clears the diagram title.');
    assert.ok(bounds.x >= -1 && bounds.x + bounds.width <= width + 1, 'Even long diagram titles remain inside the viewport.');
    const axisView = engine.getViewOfComponentModel(engine.getModel().getComponent('xAxis'));
    const axisBounds = axisView.group.getBoundingRect().clone();
    axisBounds.applyTransform(axisView.group.getComputedTransform());
    const sliderReserve = option.dataZoom.length ? 12 + option.dataZoom[1].height : 0;
    assert.ok(axisBounds.y + axisBounds.height <= height - sliderReserve - 4, 'One- and two-line time labels retain footer/zoom clearance.');
    engine.dispose();
    return rectangles;
}
for (const mode of ['symcon', 'ipsview']) {
    for (const width of [320, 630, 2048]) {
        for (const headerVisible of [true, false]) {
            for (const percent of [50, 75, 200]) {
                const rendered = render({...model, stateTimeline: {...model.stateTimeline, style: {...model.stateTimeline.style,
                    axisFontSizePercent: percent, labelFontSizePercent: percent, titleFontSizePercent: percent}}}, mode, width, 500, headerVisible);
                const height = 500 - (mode === 'symcon' && headerVisible ? 58 : 0);
                assert.equal(rendered.chart.style.top, height === 442 ? '58px' : '0px');
                const shapes = geometry(rendered.option, width, height, false);
                assert.equal(shapes.length, 5, 'Native custom renderer draws all exact intervals, including a one-second state.');
                assert.equal(shapes[0].style.fill, '#666666');
                assert.equal(shapes[1].style.fill, '#ABCDEF');
                assert.equal(shapes[2].style.fill, '#AABBCC');
                assert.equal(shapes[0].style.opacity, 0.9);
                assert.ok(shapes[1].shape.width < 1, 'A one-second state is not padded to a false duration.');
                assert.ok(Math.abs(shapes[0].shape.x + shapes[0].shape.width - shapes[1].shape.x) < 1e-5, 'Adjacent half-open intervals meet exactly.');
                const zoomed = geometry(rendered.option, width, height, true);
                assert.equal(zoomed.length, 2, 'Only overlapping intervals remain drawn, including the whole-range second source.');
            }
        }
    }
}
const allUnknown = render({...model, stateTimeline: {...model.stateTimeline, rows: [{id: 'variable-1', label: 'Unknown source', segments: [
    {startTimestamp: start, endTimestamp: start + 86400, value: null, label: 'Unknown', color: ''}]}]}});
assert.equal(geometry(allUnknown.option, 900, 442, false).length, 1, 'A wholly unknown archive remains explicitly visible, not empty or zero-filled.');
const designedShapes = geometry(disabled.option, 900, 500, false);
assert.ok(designedShapes.every(item => item.style.opacity === 0 && item.style.lineWidth === 5 && item.style.stroke === '#123456'));
for (const mode of ['symcon', 'ipsview']) {
    for (const width of [320, 900]) {
        for (const format of ['auto', 'time', 'date', 'dateTime']) {
            for (const percent of [50, 200]) {
                const output = render({...model, stateTimeline: {...model.stateTimeline, style: {...model.stateTimeline.style,
                    timeLabelFormat: format, axisFontSizePercent: percent, axisLabelMargin: 30, fontFamily: 'Verdana',
                    showAxisTicks: true, axisLabelBold: true, axisLabelItalic: true, borderRadius: 20}}}, mode, width);
                geometry(output.option, width, mode === 'symcon' ? 442 : 500, false);
                geometry(output.option, width, mode === 'symcon' ? 442 : 500, true);
            }
        }
    }
}
const noTimeAxis = render({...model, stateTimeline: {...model.stateTimeline, style: {...model.stateTimeline.style,
    showTimeLabels: false, showSourceLabels: false, showAxisTicks: false, showAxisLine: false, enableZoom: false}}});
geometry(noTimeAxis.option, 900, 442, false);
for (const days of [7, 30]) {
    for (const width of [320, 2048]) {
        const longRange = render({...model, range: {...model.range, endTimestamp: start + days * 86400}, stateTimeline: {...model.stateTimeline,
            title: 'A deliberately very long title that must not extend outside the smallest tile viewport despite large typography',
            style: {...model.stateTimeline.style, titleFontSizePercent: 200},
            rows: Array.from({length: 12}, (_, i) => ({...model.stateTimeline.rows[0], id: 'variable-' + i}))}}, 'symcon', width).option;
        geometry(longRange, width, 442, false);
        const engine = echarts.init(null, null, {renderer: 'svg', ssr: true, width, height: 442});
        engine.setOption(longRange);
        const ticks = engine.getModel().getComponent('xAxis').axis.scale.getTicks().slice(1, -1);
        const labels = ticks.map(tick => longRange.xAxis.axisLabel.formatter(tick.value));
        assert.equal(new Set(labels).size, labels.length, 'Date-only ticks must never repeat the same day in wide views.');
        engine.dispose();
    }
}
assert.equal(JSON.stringify(model), before, 'Native option construction must not mutate archive models.');
for (const mode of ['symcon', 'ipsview']) {
    for (const enableZoom of [true, false]) {
        const largeModel = {...model, stateTimeline: {...model.stateTimeline,
            rows: Array.from({length: 32}, (_, index) => ({id: 'variable-' + (100 + index), label: 'Same contact',
                segments: [{startTimestamp: start, endTimestamp: start + 86400, value: index === 31 ? null : index,
                    label: index === 31 ? 'Unknown last row' : 'State ' + index, color: index === 31 ? '' : '#2288AA'}]})),
            style: {...model.stateTimeline.style, enableZoom}}};
        const output = render(largeModel, mode, 630, 500);
        const rowZoom = output.option.dataZoom.find(entry => entry.id === 'state-row-scroll');
        assert.ok(rowZoom, 'More than eight rows need a native independent row navigator.');
        assert.equal(output.option.yAxis.data.length, 32, 'Navigation must keep all ID-distinct rows in the model.');
        assert.equal(output.option.series[0].data.length, 32);
        assert.ok(rowZoom.endValue < 8, 'Initial rows remain legible instead of squeezing all 32 into the tile.');
        const engine = echarts.init(null, null, {renderer: 'svg', ssr: true, width: 630, height: mode === 'symcon' ? 442 : 500});
        engine.setOption(output.option);
        output.useNativeEngine(engine);
        const rowCount = rowZoom.endValue + 1;
        engine.dispatchAction({type: 'dataZoom', dataZoomId: 'state-row-scroll', startValue: 32 - rowCount, endValue: 31});
        if (enableZoom) {engine.dispatchAction({type: 'dataZoom', dataZoomId: 'state-time-inside', start: 25, end: 75});}
        const data = engine.getModel().getSeries()[0].getData();
        const last = data.getItemGraphicEl(data.count() - 1);
        assert.ok(last && last.children().some(child => child.type === 'rect'), 'The 32nd row is reachable after real native row scrolling.');
        assert.equal(data.count(), rowCount, 'Row navigation filters the native series only, never the source model.');
        assert.equal(last.children()[0].style.fill, '#666666', 'Offscreen unknown intervals retain their real state and color.');
        const grid = engine.getModel().getComponent('grid').coordinateSystem.getRect();
        assert.ok(Math.abs(last.children()[0].shape.x - grid.x) < 1e-5
            && Math.abs(last.children()[0].shape.width - grid.width) < 1e-5,
            'A whole-window state still fills exactly the visible time interval after independent time and row zoom.');
        assert.match(output.option.tooltip.formatter({data: output.option.series[0].data[31]}), /Unknown last row/);
        assert.doesNotMatch(engine.renderToSVGString(), /NaN|Infinity/);
        const scrolled = engine.getOption().dataZoom.find(entry => entry.id === 'state-row-scroll');
        assert.equal(scrolled.startValue, 32 - rowCount);
        output.window.handleMessage({status: 'ready', chart: {...largeModel,
            range: {...largeModel.range, startTimestamp: start + 60, endTimestamp: start + 86460}}});
        const refreshed = engine.getOption().dataZoom;
        assert.equal(refreshed.find(entry => entry.id === 'state-row-scroll').startValue, 32 - rowCount,
            'Minute refresh must retain the scrolled source ID rather than restart at the first row.');
        if (enableZoom) {
            const time = refreshed.find(entry => entry.id === 'state-time-inside');
            assert.equal(time.start, 25); assert.equal(time.end, 75);
        }
        output.chart.clientWidth = 700;
        output.events.resize();
        const resized = engine.getOption().dataZoom;
        assert.equal(resized.find(entry => entry.id === 'state-row-scroll').startValue, 32 - rowCount);
        if (enableZoom) {assert.equal(resized.find(entry => entry.id === 'state-time-inside').start, 25);}
        // Source insertion/reordering must preserve the same ID, not the previous ordinal or equal label.
        const reordered = {...largeModel, stateTimeline: {...largeModel.stateTimeline,
            rows: [...largeModel.stateTimeline.rows].reverse()}};
        output.window.handleMessage({status: 'ready', chart: reordered});
        assert.equal(engine.getOption().dataZoom.find(entry => entry.id === 'state-row-scroll').startValue, rowCount - 1,
            'Reordering all identical labels must follow the selected source ID to its new row index.');
        engine.dispatchAction({type: 'dataZoom', dataZoomId: 'state-row-scroll', startValue: 31, endValue: 31});
        output.chart.clientHeight = 600;
        output.events.resize();
        assert.equal(engine.getOption().dataZoom.find(entry => entry.id === 'state-row-scroll').startValue, 31,
            'A height increase must not move a selected last source back to fill the preceding rows.');
        engine.dispose();
    }
}
console.log('StateTimeline exact intervals, unknown states, zoom clipping and pinned-engine layouts verified.');
