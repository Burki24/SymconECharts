'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.join(__dirname, '..');
const app = fs.readFileSync(path.join(root, 'EChartsCalendar/visualization/app.js'), 'utf8');
const design = fs.readFileSync(path.join(root, 'libs/echarts-design.js'), 'utf8');
const palette = { background: '#151619', text: '#ffffff', border: '#333333', surface: '#25272B' };
const model = { schemaVersion: 1, family: 'calendar', theme: 'dark', calendar: {
    title: 'Daily means', sourceLabel: '<Outside>', unit: '°C', decimals: 1, year: 2024, baseYear: 2024,
    windowOffset: 0, maxOffset: 53,
    data: [{ date: '2024-02-28', value: -3 }, { date: '2024-02-29', value: 0 }, { date: '2024-03-02', value: 15 }],
    style: { orientation: 'auto', showTooltip: true, firstWeekday: 1, showWeekdayLabels: true,
        showMonthLabels: true, showValues: true, showDayNumbers: true, showColorScale: true,
        lowColor: '#112233', highColor: '#ddeeff', cellBorderWidth: 2, cellBorderColor: '#445566',
        emptyCellColor: '#222222', titleFontSizePercent: 100, axisFontSizePercent: 100,
        valueFontSizePercent: 100, animationEnabled: false }
} };
function harness(data = model, mode = 'symcon', width = 1100, height = 500, header = true) {
    const elements = {};
    function element(id) {
        if (!elements[id]) { elements[id] = { style: {}, clientWidth: width, clientHeight: height, hidden: false,
            attributes: {}, addEventListener(type, fn) { this[type] = fn; },
            setAttribute(name, value) { this.attributes[name] = value; } }; }
        return elements[id];
    }
    let option;
    let clears = 0;
    const requests = [];
    const chart = { setOption: value => { option = value; }, clear() { clears++; }, resize() {}, dispose() {} };
    const window = { SYMC_VISUALIZATION: { mode, state: { schemaVersion: 1, family: 'calendar', status: 'ready', chart: data },
        options: { tileHeaderVisible: header, adaptToBackground: mode === 'ipsview', echartsThemes: { dark: palette, auto: palette },
            calendarStatePath: '/hook/SymconECharts/state/5000/' + 'a'.repeat(32) }, translations: { 'Daily mean': 'Tagesmittelwert' } },
        echarts: { init: () => chart }, getComputedStyle: () => ({ color: '' }), addEventListener() {},
        location: { protocol: 'https:', host: 'symcon.example' } };
    const document = { getElementById: element, createElement: () => ({ style: {}, remove() {} }), body: { appendChild() {} } };
    vm.runInNewContext(design, { window });
    const context = { window, document, fetch: async url => {
        requests.push(url);
        const offset = Number(new URL(url).searchParams.get('offset'));
        return { ok: true, json: async () => ({ schemaVersion: 1, family: 'calendar', status: 'ready', chart: {
            ...data, calendar: { ...data.calendar, windowOffset: offset, year: data.calendar.baseYear - offset }
        } }) };
    } };
    vm.runInNewContext(app, context);
    return { window, elements, requests, get option() { return option; }, get clears() { return clears; } };
}
const wide = harness();
assert.equal(wide.option.calendar.orient, 'horizontal');
assert.equal(wide.option.calendar.range, '2024');
assert.equal(wide.option.calendar.dayLabel.firstDay, 1);
assert.equal(wide.option.calendar.itemStyle.color, '#222222');
assert.equal(wide.option.series[0].coordinateSystem, 'calendar');
assert.deepEqual(JSON.parse(JSON.stringify(wide.option.series[0].data)), [['2024-02-28', -3], ['2024-02-29', 0], ['2024-03-02', 15]]);
assert.equal(wide.option.visualMap.dimension, 1);
assert.equal(wide.option.visualMap.min, -3);
assert.equal(wide.option.visualMap.max, 15);
assert.equal(wide.option.visualMap.inRange.color[0], '#112233');
assert.equal(wide.option.series[0].itemStyle.borderWidth, 2);
assert.equal(wide.option.animation, false);
assert.equal(wide.option.title.show, true);
assert.ok(wide.option.calendar.top > wide.option.title.top + wide.option.title.textStyle.fontSize);
assert.equal(wide.elements['echarts-calendar-chart'].style.top, '88px');
assert.match(wide.option.tooltip.formatter({ value: ['2024-02-29', 0] }), /&lt;Outside&gt;.*Tagesmittelwert: 0[.,]0 °C/);
assert.equal(harness(model, 'symcon', 330, 400).option.series[0].label.show, false,
    'Do not paint values/day numbers into cells too narrow to fit them.');
const tall = harness(model, 'ipsview', 550, 800);
assert.equal(tall.option.calendar.orient, 'vertical');
assert.equal(tall.elements['echarts-calendar-chart'].style.top, '30px');
assert.equal(tall.option.backgroundColor, 'transparent');
assert.equal(harness(model, 'symcon', 550, 800, false).elements['echarts-calendar-chart'].style.top, '30px');
const empty = harness({ ...model, calendar: { ...model.calendar, data: [] } });
assert.equal(empty.elements['echarts-calendar-empty'].hidden, false);
assert.equal(empty.option.visualMap.show, false);
assert.equal(empty.option.calendar.range, '2024', 'An empty archive still shows the annual calendar, not zero-filled fake days.');
for (const value of [0, -10, 10, Number.MAX_VALUE, -Number.MAX_VALUE]) {
    const result = harness({ ...model, calendar: { ...model.calendar, data: [{ date: '2024-01-01', value }] } });
    assert.ok(Number.isFinite(result.option.visualMap.min) && Number.isFinite(result.option.visualMap.max));
    assert.ok(result.option.visualMap.max > result.option.visualMap.min);
}
const echarts = require(path.join(root, '.tools/echarts-runtime/node_modules/echarts'));
for (const h of [wide, tall, empty]) {
    const engine = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 1100, height: 500 });
    engine.setOption(h.option);
    assert.match(engine.renderToSVGString(), /<svg/);
    const series = engine.getModel().getSeriesByIndex(0).getData();
    for (let index = 0; index < series.count(); index++) {
        const shape = series.getItemGraphicEl(index).shape;
        assert.ok(Number.isFinite(shape.x) && Number.isFinite(shape.y) && shape.width > 0 && shape.height > 0);
    }
    engine.dispose();
}
async function navigation() {
    wide.elements['echarts-calendar-previous'].click();
    await new Promise(resolve => setImmediate(resolve));
    assert.match(wide.requests[0], /offset=1&output=tile$/);
    assert.equal(wide.option.calendar.range, '2023');
    assert.equal(wide.elements['echarts-calendar-next'].disabled, false);
    wide.window.handleMessage({ schemaVersion: 1, family: 'calendar', status: 'ready', chart: model });
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(wide.option.calendar.range, '2023', 'A live push must not reset a browsed historical year.');
    wide.elements['echarts-calendar-selected'].click();
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(wide.option.calendar.range, '2024');
    assert.equal(wide.elements['echarts-calendar-next'].disabled, true);
    const before = wide.clears;
    wide.window.handleMessage({ schemaVersion: 1, family: 'calendar', status: 'error', chart: null,
        error: 'The Calendar values could not be loaded.' });
    assert.equal(wide.clears, before, 'Transient failure must retain the previous chart.');
    wide.window.handleMessage({ schemaVersion: 1, family: 'calendar', status: 'error', chart: null,
        error: 'The Calendar values could not be loaded.' });
    assert.equal(wide.elements['echarts-calendar-warning'].hidden, false);
    console.log('Calendar layout, real-engine geometry and read-only navigation verified.');
}
navigation().catch(error => { console.error(error); process.exitCode = 1; });
