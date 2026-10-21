'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.join(__dirname, '..');
const read = name => fs.readFileSync(path.join(root, name), 'utf8');
const echarts = require(path.join(root, '.tools/echarts-runtime/node_modules/echarts'));
assert.equal(echarts.version, '6.1.0');
const palette = {background: '#303236', text: '#ffffff', muted: '#969AA2', border: '#777B83', surface: '#25272B', seriesColors: ['#5070DD', '#B6D634', '#FF994D']};
const model = {schemaVersion: 1, family: 'calendarPie', theme: 'auto', calendarPie: {
    title: 'Daily comparison <test>', mode: 'average', month: '2024-02', baseMonth: '2024-02', windowOffset: 0, maxOffset: 120,
    timezone: 'Europe/Berlin', unit: 'W', decimals: 1,
    categories: [{id: 'variable-1', label: '<Same>', color: '#ABCDEF'}, {id: 'variable-2', label: '<Same>', color: ''}],
    days: Array.from({length: 29}, (_, index) => ({date: '2024-02-' + String(index + 1).padStart(2, '0'),
        status: ['complete', 'zero', 'partial', 'missing', 'pending'][index % 5],
        slices: index % 5 === 0 ? [{id: 'variable-1', label: '<Same>', value: 12, color: '#ABCDEF'}, {id: 'variable-2', label: '<Same>', value: 18, color: ''}]
            : (index % 5 === 2 ? [{id: 'variable-1', label: '<Same>', value: 2, color: '#ABCDEF'}] : []),
        missing: index % 5 === 2 ? ['variable-2'] : [], daySeconds: 86400})),
    style: {showTooltip: true, showLegend: true, showDayNumbers: true, showValues: false, firstWeekday: 1,
        pieRadiusPercent: 70, innerRadiusPercent: 0, startAngle: 90, clockwise: true, sliceBorderWidth: 1,
        cellBorderWidth: 1, cellOpacityPercent: 100, titleFontSizePercent: 100, axisFontSizePercent: 100, valueFontSizePercent: 100,
        animationEnabled: false}
}};
function harness(data = model, mode = 'symcon', width = 900, height = 500, header = true, locationOptions = {}) {
    const inset = mode === 'symcon' && header ? 58 : 0;
    const elements = {}, events = {}, requests = [], pending = [];
    function element(id) {
        const name = id.replace('echarts-calendar-pie-', '');
        if (!elements[name]) {
            elements[name] = {style: {}, clientWidth: width, clientHeight: height - inset, hidden: false, textContent: '',
                attributes: {}, addEventListener(type, fn) {this[type] = fn;}, setAttribute(key, value) {this.attributes[key] = value;}};
        }
        return elements[name];
    }
    let option;
    const engine = {setOption: value => {option = value;}, clear: () => {option = null;}, resize() {}, dispose() {}};
    const window = {SYMC_VISUALIZATION: {mode, language: 'de', state: {status: 'ready', chart: data}, translations: {},
        options: {tileHeaderVisible: mode === 'symcon' && (header), tileHeaderInset: mode === 'symcon' && (header) ? 58 : 0, adaptToBackground: mode === 'ipsview', echartsThemes: {auto: palette, dark: palette},
            calendarPieStatePath: '/hook/SymconECharts/state/5000/' + 'a'.repeat(32)}},
        echarts: {init: () => engine, format: echarts.format}, getComputedStyle: () => ({color: ''}), addEventListener: (name, fn) => {events[name] = fn;},
        location: {href: 'https://symcon.example/widget'}, ...locationOptions.window};
    const document = {getElementById: element, createElement: () => ({style: {}, remove() {}}), body: {appendChild() {}}, ...locationOptions.document};
    const context = {window, document, URL, console, fetch: (url, options) => {
        requests.push({url, options});
        return new Promise(resolve => {pending.push(resolve);});
    }};
    vm.runInNewContext(read('libs/echarts-design.js'), context);
    vm.runInNewContext(read('EChartsCalendarPie/visualization/app.js'), context);
    return {window, document, elements, events, requests, pending, get option() {return option;}};
}
const before = JSON.stringify(model);
const tile = harness();
assert.equal(tile.elements.chart.style.top, '58px');
assert.equal(tile.elements.chart.style.height, 'calc(100% - 58px)');
assert.equal(tile.option.calendar.orient, 'vertical');
assert.equal(tile.option.calendar.range, '2024-02');
assert.equal(tile.option.calendar.dayLabel.firstDay, 1);
assert.equal(tile.option.legend.data.length, 2);
assert.notEqual(tile.option.legend.data[0].name, tile.option.legend.data[1].name);
assert.equal(tile.option.legend.formatter('variable-1'), tile.option.legend.formatter('variable-2'));
assert.equal(tile.option.legend.selectedMode, false, 'Legend cannot silently renormalize daily proportions by hiding categories.');
assert.doesNotMatch(tile.option.aria.label.description, /variable-[12]/, 'Native accessibility text must not expose technical category IDs.');
const pies = tile.option.series.filter(series => series.id.startsWith('day-2024'));
assert.equal(pies.length, 6, 'Only fully available positive numeric days may have a pie.');
assert.ok(pies.every(pie => pie.coordinateSystem === 'calendar' && pie.center.startsWith('2024-02-')));
assert.ok(pies.every(pie => pie.stillShowZeroSum === false && pie.emphasis.scale === false));
assert.equal(pies[0].data[0].itemStyle.color, '#ABCDEF');
assert.equal(pies[0].data[1].itemStyle.color, '#B6D634');
assert.equal(tile.option.series.find(series => series.id === 'day-status').data.length, 23);
const partial = tile.option.series.find(series => series.id === 'day-status').data.find(day => day.date === '2024-02-03');
assert.equal(partial.symbol, 'diamond');
const partialTip = tile.option.tooltip.formatter({data: partial});
assert.match(partialTip, /Incomplete data/);
assert.match(partialTip, /&lt;Same&gt;/);
assert.doesNotMatch(partialTip, /<Same>|variable-[12]| %/);
const completeTip = tile.option.tooltip.formatter({data: pies[0].data[0]});
assert.match(completeTip, /40 %/);
assert.match(completeTip, /60 %/);
assert.equal(JSON.stringify(model), before, 'Renderer must never mutate data or merge duplicate labels.');
const counter = harness({...model, calendarPie: {...model.calendarPie, mode: 'counter', unit: 'kWh'}});
assert.equal(counter.option.series.filter(series => series.id.startsWith('day-2024')).length, 6);
assert.match(counter.option.tooltip.formatter({data: pies[0].data[0]}), /Daily consumption/);
assert.match(counter.option.tooltip.formatter({data: pies[0].data[0]}), /kWh/);
const missing = harness({...model, calendarPie: {...model.calendarPie, days: model.calendarPie.days.map(day => ({...day, status: 'missing', slices: []}))}});
assert.equal(missing.option.series.filter(series => series.id.startsWith('day-2024')).length, 0);
assert.equal(missing.option.series[0].radius, 0, 'Legend-only zero-radius provider cannot invent a nonempty chart.');
const styled = harness({...model, calendarPie: {...model.calendarPie, style: {...model.calendarPie.style, showLegend: false,
    showTooltip: false, showDayNumbers: false, showValues: true, firstWeekday: 0, pieRadiusPercent: 90, innerRadiusPercent: 80,
    startAngle: 270, clockwise: false, sliceBorderWidth: 5, sliceBorderColor: '#123456', cellBorderWidth: 4,
    cellOpacityPercent: 25, cellBorderColor: '#234567', emptyCellColor: '#345678', titleColor: '#456789', axisColor: '#56789A', valueColor: '#6789AB'}}}, 'ipsview', 1600, 1000);
assert.equal(styled.option.backgroundColor, 'transparent');
assert.equal(styled.option.legend.show, false);
assert.equal(styled.option.tooltip.show, false);
assert.equal(styled.option.calendar.dayLabel.firstDay, 0);
assert.equal(styled.option.calendar.itemStyle.color, '#345678');
assert.equal(styled.option.calendar.itemStyle.borderColor, '#234567');
assert.equal(styled.option.calendar.itemStyle.opacity, 0.25);
assert.equal(styled.option.title.textStyle.color, '#456789');
assert.equal(styled.option.series[0].label.show, true);
assert.equal(styled.option.series[0].clockwise, false);
assert.equal(styled.option.series[0].startAngle, 270);
assert.equal(styled.option.series[0].itemStyle.borderColor, '#123456');
assert.ok(Math.abs(styled.option.series[0].radius[0] - styled.option.series[0].radius[1] * 0.8) < 1e-10);
const stateModel = {...model, calendarPie: {...model.calendarPie, mode: 'states', unit: 'h', decimals: 2,
    categories: [...model.calendarPie.categories, {id: 'unknown', label: 'Unknown', color: ''}],
    days: [{date: '2024-02-01', status: 'partial', daySeconds: 82800, missing: ['unknown'],
        slices: [{id: 'variable-1', label: '<Same>', value: 41400, color: ''}, {id: 'unknown', label: 'Unknown', value: 41400, color: ''}]}],
    style: {...model.calendarPie.style, unknownColor: '#778899'}}};
const state = harness(stateModel);
const statePie = state.option.series.find(series => series.id === 'day-2024-02-01');
assert.equal(statePie.data[1].itemStyle.color, '#778899');
const stateTip = state.option.tooltip.formatter({data: statePie.data[1]});
assert.match(stateTip, /11,50 h \(50 %\)/, 'State shares use actual 23-hour day seconds and display hours.');
assert.match(stateTip, /Unknown/);
for (const seconds of [86400, 90000]) {
    const output = harness({...stateModel, calendarPie: {...stateModel.calendarPie, days: [{...stateModel.calendarPie.days[0], daySeconds: seconds,
        slices: [{id: 'unknown', label: 'Unknown', value: seconds, color: ''}]}]}});
    const pie = output.option.series.find(series => series.id === 'day-2024-02-01');
    assert.match(output.option.tooltip.formatter({data: pie.data[0]}), /100 %/);
}
tile.window.handleMessage({status: 'error', error: 'The CalendarPie values could not be loaded.'});
assert.equal(tile.elements.chart.hidden, false, 'Transient failures retain the previous ready chart.');
tile.window.handleMessage({status: 'error', error: 'Configure sources.'});
assert.equal(tile.elements.chart.hidden, true);
assert.equal(tile.elements.error.textContent, 'Configure sources.');
tile.window.handleMessage({status: 'ready', chart: model});
assert.equal(tile.elements.chart.hidden, false);

let visibleValueLabelCount = 0;
function geometry(data, mode, width, height, header, firstWeekday, fontSize, innerRadiusPercent = 0) {
    const input = {...data, calendarPie: {...data.calendarPie, style: {...data.calendarPie.style,
        firstWeekday, showValues: true, pieRadiusPercent: 90, innerRadiusPercent,
        titleFontSizePercent: fontSize, axisFontSizePercent: fontSize, valueFontSizePercent: fontSize}}};
    const output = harness(input, mode, width, height, header);
    const inset = mode === 'symcon' && header ? 58 : 0;
    const engine = echarts.init(null, null, {renderer: 'svg', ssr: true, width, height: height - inset});
    engine.setOption(output.option);
    const coordinate = engine.getModel().getComponent('calendar').coordinateSystem;
    const rect = coordinate.getRect();
    assert.ok(rect.x >= 0 && rect.y >= 0 && rect.x + rect.width <= width + 0.01 && rect.y + rect.height <= height - inset + 0.01);
    const cellWidth = coordinate.getCellWidth(), cellHeight = coordinate.getCellHeight();
    const numbers = engine.getModel().getSeries().find(series => series.id === 'day-numbers');
    const numberBoxes = new Map();
    const numberData = numbers.getData();
    for (let index = 0; index < numberData.count(); index++) {
        const element = numberData.getItemGraphicEl(index);
        const text = element && (element.getTextContent() || element.children && element.children().map(child => child.getTextContent()).find(Boolean));
        if (text && !text.ignore) {
            const box = text.getBoundingRect().clone();
            box.applyTransform(text.getComputedTransform());
            numberBoxes.set(numberData.getRawDataItem(index).date, box);
        }
    }
    if (output.option.series.find(series => series.id === 'day-numbers').label.show) {assert.ok(numberBoxes.size > 0);}
    let count = 0;
    engine.getModel().getSeries().forEach(series => {
        if (!series.id.startsWith('day-2024')) {return;}
        const data = series.getData();
        const visibleLabels = [];
        for (let index = 0; index < data.count(); index++) {
            const shape = data.getItemLayout(index);
            assert.ok(Number.isFinite(shape.r) && shape.r > 0);
            assert.ok(shape.r <= Math.min(cellWidth, cellHeight) / 2, 'Every native pie stays inside its own day cell.');
            assert.ok(shape.cx - shape.r >= rect.x && shape.cx + shape.r <= rect.x + rect.width + 0.01);
            assert.ok(shape.cy - shape.r >= rect.y && shape.cy + shape.r <= rect.y + rect.height + 0.01);
            const numbers = output.option.series.find(entry => entry.id === 'day-numbers');
            if (numbers.label.show) {
                assert.ok(shape.cy - shape.r >= shape.cy - cellHeight / 2 + numbers.label.fontSize + 6 - 0.01,
                    'Native pie must leave the entire day-number band clear, not merely keep its center in the day.');
                const box = numberBoxes.get(series.option.center);
                assert.ok(!box || shape.cy - shape.r >= box.y + box.height,
                    'Actual native day-number text bounds must remain above every pie sector.');
            }
            const graphic = data.getItemGraphicEl(index);
            const text = graphic && graphic.getTextContent();
            if (text && !text.ignore && !text.invisible && String(text.style.text || '').trim()) {
                visibleValueLabelCount++;
                const box = text.getBoundingRect().clone();
                box.applyTransform(text.getComputedTransform());
                for (const [x, y] of [[box.x, box.y], [box.x + box.width, box.y], [box.x, box.y + box.height], [box.x + box.width, box.y + box.height]]) {
                    assert.ok(Math.hypot(x - shape.cx, y - shape.cy) <= shape.r + 0.01,
                        'Every visible native value label must fit completely inside its own daily pie.');
                }
                const nearestX = Math.max(box.x - shape.cx, shape.cx - box.x - box.width, 0);
                const nearestY = Math.max(box.y - shape.cy, shape.cy - box.y - box.height, 0);
                assert.ok(Math.hypot(nearestX, nearestY) >= shape.r0 - 0.01,
                    'Visible values must fit in the ring, not cross its empty center.');
                for (const other of visibleLabels) {
                    assert.ok(box.x + box.width <= other.x || other.x + other.width <= box.x
                        || box.y + box.height <= other.y || other.y + other.height <= box.y,
                    'Visible native value labels must never overlap one another.');
                }
                visibleLabels.push(box);
            }
            count++;
        }
    });
    assert.ok(count > 0);
    const svg = engine.renderToSVGString();
    assert.doesNotMatch(svg, /NaN|Infinity/);
    assert.ok(svg.includes('<path'));
    engine.dispose();
}
for (const data of [model, stateModel]) {
    for (const mode of ['symcon', 'ipsview']) {
        for (const [width, height] of [[300, 300], [400, 500], [900, 460], [1600, 900]]) {
            for (const firstWeekday of [0, 1, 6]) {
                for (const fontSize of [50, 200]) {
                    for (const ring of [0, 50, 80]) {geometry(data, mode, width, height, true, firstWeekday, fontSize, ring);}
                }
            }
        }
    }
}
const crowded = {...model, calendarPie: {...model.calendarPie, mode: 'counter', unit: 'kWh',
    categories: Array.from({length: 8}, (_, index) => ({id: 'category-' + index, label: 'Area', color: ''})),
    days: [{date: '2024-02-01', status: 'complete', missing: [], daySeconds: 86400,
        slices: Array.from({length: 8}, (_, index) => ({id: 'category-' + index, label: 'Area', color: '', value: index % 2 ? 20.05 : 99999999.99}))}]}};
for (const ring of [0, 50, 80]) {geometry(crowded, 'ipsview', 1600, 900, false, 1, 100, ring);}
const overlapCase = {...crowded, calendarPie: {...crowded.calendarPie, style: {...crowded.calendarPie.style, showDayNumbers: false},
    days: [{...crowded.calendarPie.days[0], slices: crowded.calendarPie.days[0].slices.map(slice => ({...slice, value: 111}))}]}};
const beforeOverlap = visibleValueLabelCount;
geometry(overlapCase, 'ipsview', 900, 500, false, 1, 100, 0);
assert.ok(visibleValueLabelCount - beforeOverlap > 0 && visibleValueLabelCount - beforeOverlap < 8,
    'Native LabelLayout must actually suppress conflicting labels while retaining readable values.');
assert.ok(visibleValueLabelCount > 0, 'Geometry checks must exercise actually visible native value text, not only hidden labels.');
const normalizedModel = {...model, calendarPie: {...model.calendarPie, normalized: true, unit: '',
    days: [{date: '2024-02-01', status: 'complete', missing: [], daySeconds: 86400, slices: [
        {id: 'variable-1', label: '<Same>', value: 50, originalValue: 20, unit: '°C', decimals: 1, minimum: 0, maximum: 40, color: ''},
        {id: 'variable-2', label: '<Same>', value: 60, originalValue: 60, unit: '%', decimals: 0, minimum: 0, maximum: 100, color: ''}]}]}};
for (const output of ['symcon', 'ipsview']) {
    for (const mode of ['average', 'counter']) {
        const normalized = harness({...normalizedModel, calendarPie: {...normalizedModel.calendarPie, mode}}, output);
        const daily = normalized.option.series.find(series => series.id === 'day-2024-02-01');
        assert.deepEqual(Array.from(daily.data, slice => slice.value), [50, 60], 'Native pie angles must use normalized weights rather than original numbers.');
        const tip = normalized.option.tooltip.formatter({data: daily.data[0]});
        assert.match(tip, /Normalized comparison \(0–100\)/);
        assert.match(tip, /&lt;Same&gt;: 20,0 °C/);
        assert.match(tip, /&lt;Same&gt;: 60 %/);
        assert.match(tip, /Range: 0–40 °C/);
        assert.match(tip, /Circle share: 45,5 %/);
        assert.match(tip, /Circle share: 54,5 %/);
        assert.equal(daily.label.formatter({data: daily.data[0], value: 50}), '20,0', 'Optional inside labels must preserve original numbers too.');
        geometry({...normalizedModel, calendarPie: {...normalizedModel.calendarPie, mode}}, output, 900, 500, true, 1, 100);
    }
}
const clippedModel = {...normalizedModel, calendarPie: {...normalizedModel.calendarPie, days: [{...normalizedModel.calendarPie.days[0],
    slices: normalizedModel.calendarPie.days[0].slices.map((slice, index) => ({...slice, value: index ? 100 : 0, originalValue: index ? 150 : -10}))}]}};
const clipped = harness(clippedModel);
assert.equal(clipped.option.series.find(series => series.id === 'day-2024-02-01').data.length, 1);
const clippedTip = clipped.option.tooltip.formatter({data: {date: '2024-02-01'}});
assert.match(clippedTip, /-10,0 °C/);
assert.match(clippedTip, /150 %/);
assert.match(clippedTip, /Normalized: 0 \/ 100/);
const normalizedZero = harness({...clippedModel, calendarPie: {...clippedModel.calendarPie,
    days: [{...clippedModel.calendarPie.days[0], status: 'zero', slices: clippedModel.calendarPie.days[0].slices.map(slice => ({...slice, value: 0}))}]}});
assert.equal(normalizedZero.option.series.filter(series => series.id === 'day-2024-02-01').length, 0);
assert.match(normalizedZero.option.tooltip.formatter({data: {date: '2024-02-01'}}), /-10,0 °C/);
const normalizedPartial = harness({...normalizedModel, calendarPie: {...normalizedModel.calendarPie,
    days: [{...normalizedModel.calendarPie.days[0], status: 'partial', missing: ['variable-2'], slices: [normalizedModel.calendarPie.days[0].slices[0]]}]}});
assert.doesNotMatch(normalizedPartial.option.tooltip.formatter({data: {date: '2024-02-01'}}), /Circle share/);
const escapedUnit = harness({...normalizedModel, calendarPie: {...normalizedModel.calendarPie,
    days: [{...normalizedModel.calendarPie.days[0], slices: [{...normalizedModel.calendarPie.days[0].slices[0], unit: '<img src=x>'}]}]}});
assert.doesNotMatch(escapedUnit.option.tooltip.formatter({data: {date: '2024-02-01'}}), /<img/);
for (const count of [1, 2, 3, 4, 5]) {
    const categories = Array.from({length: count}, (_, index) => ({id: 'variable-' + (index + 1), label: 'Source ' + (index + 1), color: ''}));
    for (const numericMode of ['average', 'counter']) {
        const data = {...model, calendarPie: {...model.calendarPie, mode: numericMode, categories,
            days: [{date: '2024-02-01', status: 'complete', missing: [], daySeconds: 86400,
                slices: categories.map((category, index) => ({...category, value: index + 1}))}]}};
        for (const output of ['symcon', 'ipsview']) {
            const rendered = harness(data, output);
            const daily = rendered.option.series.find(series => series.id === 'day-2024-02-01');
            assert.equal(rendered.option.legend.data.length, count);
            assert.equal(daily.data.length, count);
            assert.deepEqual(Array.from(daily.data, slice => slice.value), Array.from({length: count}, (_, index) => index + 1));
            geometry(data, output, 900, 500, true, 1, 100);
        }
    }
}
function response(offset, data = model) {
    return {ok: true, json: async () => ({status: 'ready', chart: {...data, calendarPie: {...data.calendarPie,
        month: offset ? '2024-01' : data.calendarPie.month, windowOffset: offset}}})};
}
async function flush() {for (let index = 0; index < 8; index++) {await Promise.resolve();}}
(async () => {
    const navigation = harness();
    navigation.elements.previous.click();
    await flush();
    assert.match(navigation.requests[0].url, /^https:\/\/symcon\.example\/hook\/SymconECharts\/state\/5000\//);
    assert.match(navigation.requests[0].url, /offset=1&output=tile$/);
    assert.equal(navigation.requests[0].options.method, 'GET');
    navigation.window.handleMessage({status: 'ready', chart: model});
    await flush();
    assert.match(navigation.requests[1].url, /offset=1&output=tile$/,
        'A timer push during the first month navigation must retain the pending target.');
    navigation.pending.shift()(response(1));
    await flush();
    assert.equal(navigation.option.calendar.range, '2024-02', 'An older request cannot win after a timer refresh.');
    navigation.pending.shift()(response(1));
    await flush();
    assert.equal(navigation.option.calendar.range, '2024-01');
    navigation.window.handleMessage({status: 'ready', chart: model});
    await flush();
    navigation.pending.shift()(response(1));
    await flush();
    assert.equal(navigation.option.calendar.range, '2024-01', 'Base-month timer pushes cannot reset a browsed month.');
    navigation.elements.selected.click();
    await flush();
    navigation.window.handleMessage({status: 'ready', chart: {...model, calendarPie: {...model.calendarPie, mode: 'counter'}}});
    navigation.pending.shift()(response(0));
    await flush();
    assert.equal(navigation.option.aria.label.description.includes('Daily consumption'), true, 'Stale in-flight responses cannot overwrite a configuration update.');
    for (const origin of ['base', 'referrer', 'ancestor']) {
        const parent = {};
        Object.defineProperty(parent, 'location', {get() {throw new Error('Cross origin denied');}});
        const output = harness(model, 'ipsview', 900, 500, true, {
            window: {location: {href: 'data:text/html;base64,PHRlc3Q+', ancestorOrigins: origin === 'ancestor' ? ['https://symcon.example'] : []}, parent, top: parent},
            document: {baseURI: origin === 'base' ? 'https://symcon.example/local' : 'data:text/html;base64,PHRlc3Q+',
                referrer: origin === 'referrer' ? 'https://symcon.example/host' : ''}});
        output.elements.previous.click();
        await flush();
        assert.match(output.requests[0].url, /^https:\/\/symcon\.example\//, 'Opaque embedding must resolve ' + origin + ' without unsafe ancestor access.');
        output.pending.shift()(response(1));
        await flush();
    }
    const destroyed = harness();
    destroyed.elements.previous.click();
    await flush();
    destroyed.events.beforeunload();
    destroyed.pending.shift()(response(1));
    await flush();
    assert.equal(destroyed.option.calendar.range, '2024-02');
    console.log('CalendarPie native calendar geometry, data states, three modes, design and safe navigation verified.');
})().catch(error => {console.error(error); process.exitCode = 1;});
