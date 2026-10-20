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
function harness(data = model, mode = 'symcon', width = 1100, height = 500, header = true, responseForOffset) {
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
        return { ok: true, json: async () => ({ schemaVersion: 1, family: 'calendar', status: 'ready', chart: responseForOffset ? responseForOffset(offset) : {
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
for (const mode of ['symcon', 'ipsview']) {
    for (const [metric, label, emptyText] of [
        ['average', 'Tagesmittelwert', 'No completed daily means in this year.'],
        ['minimum', 'Daily minimum', 'No completed daily minima in this year.'],
        ['maximum', 'Daily maximum', 'No completed daily maxima in this year.'],
        ['consumption', 'Daily consumption', 'No completed daily consumption in this year.']
    ]) {
        const result = harness({ ...model, calendar: { ...model.calendar, metric, unit: '<kWh&>' } }, mode);
        assert.ok(result.option.aria.description.endsWith(label));
        const tooltip = result.option.tooltip.formatter({ value: ['2024-02-29', 0] });
        assert.ok(tooltip.includes(label + ': '));
        assert.match(tooltip, /0[.,]0 &lt;kWh&amp;&gt;/);
        assert.ok(tooltip.includes('&lt;Outside&gt;'));
        assert.equal(result.elements['echarts-calendar-empty'].textContent, emptyText);
    }
}
for (const value of [0, -10, 10, Number.MAX_VALUE, -Number.MAX_VALUE]) {
    const result = harness({ ...model, calendar: { ...model.calendar, data: [{ date: '2024-01-01', value }] } });
    assert.ok(Number.isFinite(result.option.visualMap.min) && Number.isFinite(result.option.visualMap.max));
    assert.ok(result.option.visualMap.max > result.option.visualMap.min);
}
const echarts = require(path.join(root, '.tools/echarts-runtime/node_modules/echarts'));
const decoratedModel = { ...model, calendar: { ...model.calendar, style: { ...model.calendar.style,
    showMonthSeparators: false, monthLineWidth: 3, monthLineType: 'dashed', monthLineColor: '#abcdef',
    monthLabelPosition: 'end', weekdayLabelPosition: 'end', monthLabelMargin: 16, weekdayLabelMargin: 12,
    monthLabelAlign: 'left', monthLabelFormat: 'year-month', showYearLabel: true, yearLabelPosition: 'right',
    yearLabelMargin: 18, yearFontSizePercent: 150, yearColor: '#ff8800', labelBold: true,
    cellOpacityPercent: 45, cellBorderType: 'dotted', hoverHighlight: false, hoverBorderColor: '#123456',
    reverseColorScale: true, useFixedColorRange: true, colorScaleMinimum: -20.5, colorScaleMaximum: 40.5
} } };
const decorated = harness(decoratedModel, 'ipsview');
assert.equal(decorated.option.calendar.splitLine.show, false);
assert.equal(decorated.option.calendar.splitLine.lineStyle.type, 'dashed');
assert.equal(decorated.option.calendar.monthLabel.position, 'end');
assert.equal(decorated.option.calendar.monthLabel.formatter, '{yyyy}-{MM}');
assert.equal(decorated.option.calendar.dayLabel.margin, 12);
assert.equal(decorated.option.calendar.yearLabel.show, true);
assert.equal(decorated.option.calendar.yearLabel.position, 'right');
assert.equal(decorated.option.calendar.yearLabel.fontSize, 27);
assert.equal(decorated.option.series[0].itemStyle.opacity, 0.45);
assert.equal(decorated.option.series[0].itemStyle.borderType, 'dotted');
assert.equal(decorated.option.series[0].label.fontWeight, 'bold');
assert.equal(decorated.option.series[0].emphasis.disabled, true);
assert.equal(decorated.option.visualMap.min, -20.5);
assert.equal(decorated.option.visualMap.max, 40.5);
assert.equal(decorated.option.visualMap.inRange.color[0], '#ddeeff');
const saturated = harness({ ...decoratedModel, calendar: { ...decoratedModel.calendar, data: [
    { date: '2024-01-01', value: -100 }, { date: '2024-01-02', value: 100 }
] } }, 'ipsview');
const saturatedEngine = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 1100, height: 500 });
saturatedEngine.setOption(saturated.option);
const saturatedData = saturatedEngine.getModel().getSeriesByIndex(0).getData();
assert.equal(saturatedData.count(), 2, 'Fixed color limits must not filter archive points.');
assert.equal(saturatedData.getItemVisual(0, 'style').fill.toLowerCase(), 'rgba(221,238,255,1)');
assert.equal(saturatedData.getItemVisual(1, 'style').fill.toLowerCase(), 'rgba(17,34,51,1)');
saturatedEngine.dispose();
assert.equal(JSON.stringify(decoratedModel.calendar.data), JSON.stringify(model.calendar.data), 'Design must not rewrite archive means.');
for (const h of [wide, tall, empty, decorated]) {
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
for (const [width, height] of [[420, 680], [1100, 430]]) {
    for (const position of ['top', 'bottom', 'left', 'right']) {
        const h = harness({ ...decoratedModel, calendar: { ...decoratedModel.calendar,
            style: { ...decoratedModel.calendar.style, yearLabelPosition: position, showMonthSeparators: true } } },
        'ipsview', width, height);
        const engine = echarts.init(null, null, { renderer: 'svg', ssr: true, width, height });
        engine.setOption(h.option);
        const view = engine.getViewOfComponentModel(engine.getModel().getComponent('calendar'));
        view.group.traverse(element => {
            if (element.type !== 'text' || element.ignore) { return; }
            const box = element.getBoundingRect().clone();
            box.applyTransform(element.getComputedTransform());
            assert.ok(box.x >= -1 && box.y >= -1 && box.x + box.width <= width + 1 && box.y + box.height <= height + 1,
                'Calendar labels must remain inside the viewport: ' + position + ', ' + element.style.text);
        });
        engine.dispose();
    }
}
const narrowMonths = harness({ ...decoratedModel, calendar: { ...decoratedModel.calendar,
    style: { ...decoratedModel.calendar.style, orientation: 'horizontal', axisFontSizePercent: 150 } } }, 'ipsview', 420, 680);
assert.equal(typeof narrowMonths.option.calendar.monthLabel.formatter, 'function');
assert.equal(narrowMonths.option.calendar.monthLabel.formatter({ M: 2, MM: '02', yyyy: '2024' }), '',
    'Crowded month names must be thinned rather than overlap; all daily data remains.');
const crowded = harness({ ...decoratedModel, calendar: { ...decoratedModel.calendar,
    style: { ...decoratedModel.calendar.style, orientation: 'vertical', axisFontSizePercent: 200,
        yearFontSizePercent: 200, monthLabelMargin: 40, weekdayLabelMargin: 40, yearLabelMargin: 60 } } }, 'ipsview', 300, 240);
assert.ok(crowded.option.calendar.left + crowded.option.calendar.right < 300);
assert.ok(crowded.option.calendar.top + crowded.option.calendar.bottom < 240);
const views = [
    ['month', '2024-02', ['2024-02-01', '2024-02-29'], 29, 1, 'Previous month', 'Selected month'],
    ['quarter', '2024 Q1', ['2024-01-01', '2024-03-31'], 91, 3, 'Previous quarter', 'Selected quarter'],
    ['absolute', '2023-12-20 – 2024-01-09', ['2023-12-20', '2024-01-09'], 21, 2, 'Previous period', 'Selected period'],
    ['absolute', '2024-02-29 – 2024-02-29', ['2024-02-29', '2024-02-29'], 1, 1, 'Previous period', 'Selected period']
];
function viewModel(view, offset = 0) {
    const [period, periodLabel, calendarRange, dayCount, monthCount] = view;
    return { ...decoratedModel, calendar: { ...decoratedModel.calendar, period, periodLabel, calendarRange,
        viewKey: 'selected-' + period + ':' + calendarRange.join(':'), dayCount, monthCount, windowOffset: offset,
        startWeekday: new Date(calendarRange[0] + 'T12:00:00Z').getUTCDay(),
        data: [{ date: calendarRange[0], value: 0 }, { date: calendarRange[1], value: 12.5 }] } };
}
for (const view of views) {
    for (const mode of ['symcon', 'ipsview']) {
        for (const [width, height, orientation] of [[420, 680, 'vertical'], [1100, 430, 'horizontal']]) {
            const data = viewModel(view);
            data.calendar.style = { ...data.calendar.style, orientation };
            const h = harness(data, mode, width, height);
            assert.deepEqual(JSON.parse(JSON.stringify(h.option.calendar.range)), view[2]);
            assert.equal(h.elements['echarts-calendar-period-label'].textContent, view[1]);
            assert.equal(h.elements['echarts-calendar-previous'].attributes['aria-label'], view[5]);
            assert.equal(h.elements['echarts-calendar-selected'].textContent, view[6]);
            const engine = echarts.init(null, null, { renderer: 'svg', ssr: true, width, height });
            engine.setOption(h.option);
            assert.match(engine.renderToSVGString(), /<svg/);
            const points = engine.getModel().getSeriesByIndex(0).getData();
            assert.equal(points.count(), 2, 'Cross-year and shorter views keep their actual dates and values.');
            for (let i = 0; i < points.count(); i++) {
                const shape = points.getItemGraphicEl(i).shape;
                assert.ok(Number.isFinite(shape.x) && Number.isFinite(shape.y) && shape.width > 0 && shape.height > 0);
            }
            const calendarView = engine.getViewOfComponentModel(engine.getModel().getComponent('calendar'));
            calendarView.group.traverse(element => {
                if (element.type !== 'text' || element.ignore) { return; }
                const box = element.getBoundingRect().clone();
                box.applyTransform(element.getComputedTransform());
                assert.ok(box.x >= -1 && box.y >= -1 && box.x + box.width <= width + 1 && box.y + box.height <= height + 1,
                    'Short/cross-year calendar labels must remain in the viewport: ' + view[0] + '/' + element.style.text);
            });
            engine.dispose();
        }
    }
}
const shortNarrow = harness(viewModel(views[0]), 'ipsview', 420, 680);
assert.equal(shortNarrow.option.series[0].label.show, true, 'Short views use actual week counts rather than annual cell density.');
async function navigation() {
    for (const view of views) {
        for (const mode of ['symcon', 'ipsview']) {
            const base = viewModel(view);
            const past = { ...base, calendar: { ...base.calendar, calendarRange: ['2023-11-01', '2023-11-30'], periodLabel: 'historical', windowOffset: 1 } };
            const h = harness(base, mode, 600, 600, true, offset => offset ? past : base);
            h.elements['echarts-calendar-previous'].click();
            await new Promise(resolve => setImmediate(resolve));
            assert.deepEqual(JSON.parse(JSON.stringify(h.option.calendar.range)), past.calendar.calendarRange);
            assert.match(h.requests[0], mode === 'symcon' ? /offset=1&output=tile$/ : /offset=1$/);
            h.window.handleMessage({ schemaVersion: 1, family: 'calendar', status: 'ready', chart: base });
            await new Promise(resolve => setImmediate(resolve));
            assert.equal(h.elements['echarts-calendar-period-label'].textContent, 'historical', 'Refresh must preserve a browser-local period selection.');
            h.elements['echarts-calendar-selected'].click();
            await new Promise(resolve => setImmediate(resolve));
            assert.equal(h.elements['echarts-calendar-period-label'].textContent, view[1]);
            h.elements['echarts-calendar-previous'].click();
            const changed = viewModel(views[(views.indexOf(view) + 1) % views.length]);
            h.window.handleMessage({ schemaVersion: 1, family: 'calendar', status: 'ready', chart: changed });
            await new Promise(resolve => setImmediate(resolve));
            assert.equal(h.elements['echarts-calendar-period-label'].textContent, changed.calendar.periodLabel,
                'In-flight responses from another configured source/period/design must not overwrite the new view.');
        }
    }
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
    const changing = harness({ ...model, calendar: { ...model.calendar, metric: 'maximum' } });
    changing.elements['echarts-calendar-previous'].click();
    changing.window.handleMessage({ schemaVersion: 1, family: 'calendar', status: 'ready',
        chart: { ...model, calendar: { ...model.calendar, metric: 'consumption' } } });
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(changing.option.calendar.range, '2024', 'An in-flight previous-metric response must not replace a changed metric.');
    assert.ok(changing.option.aria.description.endsWith('Daily consumption'));
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
