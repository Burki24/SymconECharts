'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(
    path.join(__dirname, '..', 'EChartsBarWaterfall', 'visualization', 'app.js'), 'utf8'
);
const designSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-design.js'), 'utf8');
const navigationSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-category-navigation.js'), 'utf8');
const chartElement = { hidden: false, clientWidth: 800, clientHeight: 400 };
const errorElement = { hidden: true, textContent: '' };
let option;
let initialized = 0;
let resized = 0;
let resizeCallback;
let chartWidth = chartElement.clientWidth;
let chartHeight = chartElement.clientHeight;
const chart = {
    setOption: next => { option = next; },
    getOption: () => ({ ...option, xAxis: [option.xAxis], yAxis: [option.yAxis] }),
    getWidth: () => chartWidth, getHeight: () => chartHeight,
    clear: () => {},
    resize: () => {
        resized++;
        chartWidth = chartElement.clientWidth;
        chartHeight = chartElement.clientHeight;
    },
    dispose: () => {}
};
const palette = {
    background: '#111111', text: '#eeeeee', muted: '#aaaaaa', border: '#777777',
    accent: '#55cbb5', seriesColors: ['#55cbb5', '#6070cc']
};
const window = {
    SYMC_VISUALIZATION: {
        mode: 'symcon',
        state: {
            status: 'ready',
            chart: {
                theme: 'dark',
                bar: {
                    title: 'Balance', unit: '°C', decimals: 1,
                    style: {
                        showValues: true, showGrid: true, barWidthPercent: 60,
                        animationEnabled: true, animationEasing: 'bounceOut',
                        animationEasingUpdate: 'linear', animationDelay: 120,
                        animationDelayUpdate: 240,
                        colors: {
                            startColor: '#123456', increaseColor: '#008800',
                            decreaseColor: '#880000', totalColor: '#654321'
                        }
                    }
                },
                steps: [
                    { label: 'Start', kind: 'start', change: 10, before: 0, after: 10 },
                    { label: '<drop>', kind: 'change', change: -15, before: 10, after: -5 },
                    { label: 'Recovery', kind: 'change', change: 2, before: -5, after: -3 },
                    { label: 'Total', kind: 'total', change: -3, before: 0, after: -3 }
                ]
            }
        },
        options: { echartsThemes: { auto: palette, dark: palette }, tileHeaderVisible: true, tileHeaderInset: 58},
        translations: {}
    },
    echarts: { init: () => { initialized++; return chart; } },
    ResizeObserver: class {
        constructor(callback) { resizeCallback = callback; }
        observe() {}
    },
    addEventListener: () => {}
};
const document = {
    getElementById: id => id === 'echarts-bar-waterfall-chart' ? chartElement : errorElement,
    createElement: () => ({ style: {}, remove: () => {} }),
    body: { appendChild: () => {} }
};
vm.runInNewContext(designSource, { window });
vm.runInNewContext(navigationSource, { window });
vm.runInNewContext(source, { window, document, console, ResizeObserver: window.ResizeObserver });

assert.equal(initialized, 1);
resizeCallback();
assert.equal(resized, 0, 'An unchanged observer notification must preserve the Waterfall animation.');
chartElement.clientWidth = 600;
resizeCallback();
assert.equal(resized, 1, 'A changed Waterfall tile width must resize the chart.');
assert.deepEqual(Array.from(option.xAxis.data), ['Start', '<drop>', 'Recovery', 'Total']);
assert.equal(option.series.length, 4);
assert.ok(option.xAxis.axisLabel.margin >= option.series[3].data[1].label.fontSize + option.series[3].data[1].label.distance,
    'Category names must leave space for negative value labels below the bottom of the plot.');
assert.equal(option.animationEasing, 'bounceOut');
assert.equal(option.animationEasingUpdate, 'linear');
assert.equal(option.animationDelay, 120);
assert.equal(option.animationDelayUpdate, 240);
assert.deepEqual(Array.from(option.series[0].data), [0, 0, 0, 0]);
assert.deepEqual(Array.from(option.series[1].data, item => item.value), [10, 10, 0, 0]);
assert.deepEqual(Array.from(option.series[2].data), [0, 0, -3, 0]);
assert.deepEqual(Array.from(option.series[3].data, item => item.value), [0, -5, -2, -3]);
assert.equal(option.series[1].data[0].itemStyle.color, '#123456');
assert.equal(option.series[3].data[1].itemStyle.color, '#880000');
assert.equal(option.series[3].data[2].itemStyle.color, '#008800');
assert.equal(option.series[3].data[3].itemStyle.color, '#654321');
assert.match(option.tooltip.formatter([{ dataIndex: 1 }]), /&lt;drop&gt;/);
assert.doesNotMatch(option.tooltip.formatter([{ dataIndex: 1 }]), /<drop>/);

window.handleMessage({
    status: 'ready', chart: {
        ...window.SYMC_VISUALIZATION.state.chart,
        bar: {
            ...window.SYMC_VISUALIZATION.state.chart.bar,
            style: {
                ...window.SYMC_VISUALIZATION.state.chart.bar.style,
                valueLabelPosition: 'insideTop', valueLabelDistance: 11,
                titleFontSizePercent: 150, axisFontSizePercent: 125, valueFontSizePercent: 150,
                titleColor: '#123456', axisColor: '#234567', valueColor: '#345678', gridColor: '#456789'
            }
        }
    }
});
assert.equal(option.title.textStyle.fontSize, 27);
assert.equal(option.title.textStyle.color, '#123456');
assert.equal(option.xAxis.axisLabel.fontSize, 15);
assert.equal(option.yAxis.nameTextStyle.fontSize, 15);
assert.equal(option.xAxis.axisLabel.color, '#234567');
assert.equal(option.yAxis.splitLine.lineStyle.color, '#456789');
assert.equal(option.series[1].data[0].label.fontSize, 18);
assert.equal(option.series[1].data[0].label.color, '#345678');
assert.equal(option.series[1].data[0].label.position, 'insideTop');
assert.equal(option.series[1].data[0].label.distance, 11);

const localECharts = path.join(__dirname, '..', '.tools', 'echarts-runtime', 'node_modules', 'echarts');
if (fs.existsSync(localECharts)) {
    const echarts = require(localECharts);
    const realChart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 800, height: 400 });
    realChart.setOption(option);
    const positiveCrossing = realChart.getModel().getSeriesByIndex(1).getData().getItemLayout(1);
    const negativeCrossing = realChart.getModel().getSeriesByIndex(3).getData().getItemLayout(1);
    assert.ok(positiveCrossing.height < 0, 'The positive crossing segment must extend above zero.');
    assert.ok(negativeCrossing.height > 0, 'The negative crossing segment must extend below zero.');
    assert.ok(realChart.renderToSVGString().includes('<svg'));
    realChart.dispose();
}

const largeSteps = Array.from({ length: 100 }, (_, index) => ({
    id: 'source-' + index, label: 'Repeated name', kind: index ? 'change' : 'start',
    change: index === 0 ? 50 : 1, before: index === 0 ? 0 : 49 + index, after: 50 + index
}));
largeSteps.push({ id: 'total', label: 'Total', kind: 'total', change: 149, before: 0, after: 149 });
window.handleMessage({ status: 'ready', chart: { ...window.SYMC_VISUALIZATION.state.chart, steps: largeSteps } });
assert.equal(option.dataZoom && option.dataZoom.length, 2,
    'Waterfall must navigate large step lists rather than squeeze all 101 categories into one chart.');

function waterfallHarness(mode, steps, style = {}, observer = true, width = 800) {
    const element = { clientWidth: width, clientHeight: 500, hidden: false };
    const sectionNote = {hidden: true, textContent: ''};
    const handlers = {};
    let nextOption, nativeChart, observed, actualWidth = width, actualHeight = 500;
    let state = { status: 'ready', chart: { theme: 'dark', steps,
        bar: {title: 'Balance', unit: '', decimals: 0, style: {animationEnabled: false, showValues: true, ...style}} } };
    const adapter = { setOption: value => { nextOption = value; if (nativeChart) { nativeChart.setOption(value, true); } },
        getOption: () => nativeChart ? nativeChart.getOption() : {...nextOption, xAxis: [nextOption.xAxis], yAxis: [nextOption.yAxis]},
        getWidth: () => actualWidth, getHeight: () => actualHeight,
        resize: () => { actualWidth = element.clientWidth; actualHeight = element.clientHeight; },
        clear() {}, dispose() {} };
    const host = {SYMC_VISUALIZATION: {mode, state, options: {echartsThemes: {auto: palette, dark: palette, light: palette}}},
        echarts: {init: () => adapter}, addEventListener: (name, callback) => { handlers[name] = callback; }};
    if (observer) { host.ResizeObserver = class {constructor(callback) {observed = callback;} observe() {}}; }
    const dom = {getElementById: id => id === 'echarts-bar-waterfall-chart' ? element
        : id === 'echarts-waterfall-sections' ? sectionNote : {hidden: true},
        createElement: () => ({style: {}, remove() {}}), body: {appendChild() {}}};
    vm.runInNewContext(designSource, {window: host});
    vm.runInNewContext(navigationSource, {window: host});
    vm.runInNewContext(source, {window: host, document: dom, ResizeObserver: host.ResizeObserver});
    return {get option() {return nextOption;}, sectionNote, useNative: engine => {nativeChart = engine;},
        error: () => host.handleMessage({status: 'error', error: 'Configure valid Waterfall sources.'}),
        update: (newSteps = state.chart.steps, patch = {}, theme = state.chart.theme) => {
            state = {status: 'ready', chart: {...state.chart, theme, steps: newSteps,
                bar: {...state.chart.bar, style: {...state.chart.bar.style, ...patch}}}};
            host.handleMessage(state);
        },
        resize: (newWidth, newHeight = 500) => {
            element.clientWidth = newWidth; element.clientHeight = newHeight;
            (observed || handlers.resize)();
        }};
}

function balanceSteps(count = 100) {
    let total = 0;
    const steps = Array.from({length: count}, (_, index) => {
        const change = index === 0 ? 50 : index === 1 ? 950 : index === 2 ? -2000 : index === 3 ? 1000 : index % 2 ? -2 : 3;
        const before = total;
        total += change;
        return {id: 'source-' + index, label: index === 99 ? 'Last change' : 'Same label',
            kind: index ? 'change' : 'start', change, before, after: total};
    });
    steps.push({id: 'total', label: 'Full total', kind: 'total', change: total, before: 0, after: total});
    return steps;
}
const shipped = {};
vm.runInNewContext(fs.readFileSync(path.join(__dirname, '..', 'libs/echarts/6.1.0/echarts.cartesian.min.js'), 'utf8'),
    {window: shipped, navigator: {userAgent: 'Node.js'}, console, setTimeout, clearTimeout});
shipped.echarts.use(require('../.tools/echarts-runtime/node_modules/echarts/renderers').SVGRenderer);
for (const mode of ['symcon', 'ipsview']) {
    for (const observer of [true, false]) {
        const steps = balanceSteps();
        const harness = waterfallHarness(mode, steps, {}, observer);
        assert.equal(harness.option.xAxis.data.length, 101);
        assert.deepEqual([harness.option.yAxis.min, harness.option.yAxis.max], [-1000, 1000],
            'The visible window must retain offscreen cumulative extrema.');
        const engine = shipped.echarts.init(null, null, {renderer: 'svg', ssr: true, width: 800, height: 500});
        harness.useNative(engine);
        engine.setOption(harness.option);
        const count = engine.getModel().getSeriesByIndex(1).getData().count();
        assert.ok(count > 0 && count < 101, 'The pinned renderer must actually filter categories.');
        const crossing = engine.getModel().getSeriesByIndex(3).getData().getItemLayout(2);
        assert.ok(crossing.height > 0, 'The initial crossing must retain its true negative segment.');
        engine.dispatchAction({type: 'dataZoom', dataZoomId: 'category-scroll', startValue: 101 - count, endValue: 100});
        const data = engine.getModel().getSeriesByIndex(1).getData();
        const last = data.count() - 1;
        assert.equal(data.getRawIndex(last), 100);
        const parameter = engine.getModel().getSeriesByIndex(1).getDataParams(last);
        assert.equal(parameter.data.id, 'total');
        assert.equal(harness.option.tooltip.formatter([parameter]), 'Full total<br>48');
        assert.equal(parameter.data.value, 48, 'The final bar must contain all 99 changes, including hidden ones.');
        const lastChange = engine.getModel().getSeriesByIndex(1).getDataParams(last - 1);
        assert.match(harness.option.tooltip.formatter([lastChange]), /Last change<br>-2<br>50 → 48/);
        const axis = engine.getModel().getComponent('xAxis').axis;
        assert.deepEqual(Array.from(axis.getViewLabels(), item => item.formattedLabel).slice(-2), ['Last change', 'Full total']);
        const anchorID = steps[101 - count].id;
        harness.update();
        assert.equal(harness.option.xAxis.data[harness.option.dataZoom[0].startValue], anchorID);
        const reordered = steps.slice();
        reordered.splice(5, 0, reordered.splice(101 - count, 1)[0]);
        harness.update(reordered);
        assert.equal(harness.option.xAxis.data[harness.option.dataZoom[0].startValue], anchorID,
            'Changing source order must retain the anchored variable ID despite equal labels.');
        harness.resize(420);
        assert.equal(harness.option.xAxis.data[harness.option.dataZoom[0].startValue], anchorID);
        assert.ok(harness.option.dataZoom[0].endValue - harness.option.dataZoom[0].startValue + 1 < count,
            'Observer and fallback resize must both recompute a readable window.');
        harness.update(reordered, {axisFontSizePercent: 200, valueFontSizePercent: 200}, 'light');
        assert.equal(harness.option.xAxis.data[harness.option.dataZoom[0].startValue], anchorID,
            'Theme and font changes must not discard the stable source anchor.');
        const removed = reordered.filter(step => step.id !== anchorID);
        harness.update(removed);
        assert.ok(harness.option.dataZoom[0].startValue >= 0 && harness.option.dataZoom[0].endValue < removed.length);
        harness.update([]);
        harness.update(steps);
        assert.equal(harness.option.dataZoom[0].startValue, 0, 'An emptied model must reset stale viewport state.');
        harness.useNative(null);
        engine.dispose();
    }
}
const thin = waterfallHarness('symcon', balanceSteps(), {barWidthPercent: 20, showValues: false}, true, 1800);
const thick = waterfallHarness('symcon', balanceSteps(), {barWidthPercent: 100, showValues: false}, true, 1800);
assert.ok(thin.option.dataZoom[0].endValue > thick.option.dataZoom[0].endValue,
    'Thin bars may expose more steps, while wider bars reserve enough physical space.');
assert.ok(thin.option.dataZoom[0].endValue > 16, 'Large charts must not retain an arbitrary sixteen-step viewport cap.');
const largerText = waterfallHarness('ipsview', balanceSteps(),
    {barWidthPercent: 20, showValues: false, axisFontSizePercent: 200}, true, 1800);
assert.ok(largerText.option.dataZoom[0].endValue < thin.option.dataZoom[0].endValue,
    'Larger fonts reserve more horizontal room per step.');
const densityEngine = shipped.echarts.init(null, null, {renderer: 'svg', ssr: true, width: 1800, height: 500});
densityEngine.setOption(thin.option);
const thinWidth = densityEngine.getModel().getSeriesByIndex(1).getData().getItemLayout(0).width;
densityEngine.setOption(thick.option, true);
const thickWidth = densityEngine.getModel().getSeriesByIndex(1).getData().getItemLayout(0).width;
assert.ok(thickWidth > thinWidth * 2, 'The bar-width choice changes real pinned-engine geometry, not only window counts.');
densityEngine.dispose();

const navigationHost = {};
vm.runInNewContext(navigationSource, {window: navigationHost});
const navigation = navigationHost.SymconEChartsCategoryNavigation.create();
navigation.remember({getOption: () => ({dataZoom: [{id: 'category-scroll', orient: 'vertical', startValue: 'last'}],
    yAxis: [{data: ['first', 'middle', 'last']} ]})});
assert.equal(navigation.window(['last', 'middle', 'first'], 1).first, 0, 'Shared navigation follows category keys, not names or old indexes.');
navigation.remember({getOption: () => ({dataZoom: [{id: 'category-scroll', start: 50}], xAxis: [{data: ['a', 'b', 'c']}]})});
assert.equal(navigation.window(['a', 'b', 'c'], 1).first, 1, 'Percentage-only native zoom state has a deterministic fallback.');
navigation.window([], 0);
assert.equal(navigation.window(['a', 'b', 'c'], 1).first, 0);
navigation.remember({getOption: () => undefined});

window.handleMessage({ status: 'error', error: 'Configure valid Waterfall sources.' });
assert.equal(chartElement.hidden, true);
assert.equal(errorElement.hidden, false);

const sectionSteps = [
    {id:'v1',label:'<Start>',kind:'start',change:10,before:0,after:10,section:1,sectionLabel:'<Start>'},
    {id:'v2',label:'Drop',kind:'change',change:-15,before:10,after:-5,section:1,sectionLabel:'<Start>'},
    {id:'subtotal-v2',label:'Subtotal: Drop',kind:'subtotal',change:-5,before:0,after:-5,calculated:true,section:1,sectionLabel:'<Start>'},
    {id:'v3',label:'Restart: Other',kind:'restart',change:-20,before:0,after:-20,previousTotal:-5,section:2,sectionLabel:'Other'},
    {id:'subtotal-v3',label:'Subtotal: Other',kind:'subtotal',change:-20,before:0,after:-20,calculated:true,section:2,sectionLabel:'Other'},
    {id:'v4',label:'Change',kind:'change',change:30,before:-20,after:10,section:2,sectionLabel:'Other'},
    {id:'total',label:'Final section total',kind:'total',change:10,before:0,after:10,section:2,sectionLabel:'Other'}
];
for (const mode of ['symcon', 'ipsview']) {
    const before = JSON.stringify(sectionSteps);
    const h = waterfallHarness(mode, sectionSteps, {colors:{startColor:'#123456',totalColor:'#654321'}}, true, 1200);
    assert.equal(h.sectionNote.hidden, false);
    assert.match(h.sectionNote.textContent, /last section/);
    assert.equal(h.option.grid.bottom, 80, 'A section note must have reserved space without changing old chart layouts.');
    assert.equal(h.option.series[3].data[2].value, -5, 'The subtotal is an absolute negative bar, not another negative delta.');
    assert.equal(h.option.series[3].data[3].value, -20, 'A reset starts from zero, never from the previous closing balance.');
    assert.equal(h.option.series[2].data[3], 0);
    assert.equal(h.option.series[3].data[3].itemStyle.color, '#123456');
    assert.equal(h.option.series[3].data[2].itemStyle.color, '#654321');
    assert.match(h.option.tooltip.formatter([{data:{id:'subtotal-v2'}}]), /not added again.*&lt;Start&gt;/);
    assert.match(h.option.tooltip.formatter([{data:{id:'v3'}}]), /absolute start.*Previous section ended at: -5.*Balance section 2: Other/);
    assert.match(h.option.tooltip.formatter([{data:{id:'total'}}]), /sections are not added together/);
    const engine = shipped.echarts.init(null,null,{renderer:'svg',ssr:true,width:1200,height:500});
    engine.setOption(h.option);
    const restartRect = engine.getModel().getSeriesByIndex(3).getData().getItemLayout(3);
    const subtotalRect = engine.getModel().getSeriesByIndex(3).getData().getItemLayout(2);
    assert.ok(Math.abs(restartRect.height / subtotalRect.height - 4) < 1e-8,
        'Pinned-engine absolute -20 restart must be four times the -5 subtotal, not the -15 transition.');
    engine.dispose();
    h.update(sectionSteps, {showValues:false});
    assert.equal(h.sectionNote.hidden,false, 'Hidden value labels must not hide section semantics.');
    h.error();
    assert.equal(h.sectionNote.hidden,true, 'Error states must not show stale section notes.');
    h.update(sectionSteps);
    h.update(sectionSteps.filter(s=>s.kind!=='restart'));
    assert.equal(h.sectionNote.hidden,true);
    assert.equal(JSON.stringify(sectionSteps), before, 'Rendering must not mutate arithmetic, row identity or labels.');

    const manySections = [];
    for(let i=0;i<100;i++) {
        manySections.push({id:'variable-'+i,label:'Same',kind:i?'restart':'start',change:i+1,before:0,after:i+1,
            ...(i?{previousTotal:i}:{}), section:i+1,sectionLabel:'Same'});
        manySections.push({id:'subtotal-variable-'+i,label:'Subtotal: Same',kind:'subtotal',change:i+1,before:0,after:i+1,
            calculated:true,section:i+1,sectionLabel:'Same'});
    }
    manySections.push({id:'total',label:'Final section total',kind:'total',change:100,before:0,after:100,section:100,sectionLabel:'Same'});
    const nav = waterfallHarness(mode, manySections, {}, true, 460);
    assert.equal(nav.option.xAxis.data.length,201);
    assert.deepEqual([nav.option.yAxis.min,nav.option.yAxis.max],[0,100], 'Earlier sections and hidden values determine the full scale.');
    assert.equal(nav.option.dataZoom[0].bottom,36, 'Navigation must leave room for the permanent section note.');
    const full = shipped.echarts.init(null,null,{renderer:'svg',ssr:true,width:460,height:500});
    nav.useNative(full); full.setOption(nav.option);
    full.dispatchAction({type:'dataZoom',dataZoomId:'category-scroll',startValue:198,endValue:200});
    const lastData = full.getModel().getSeriesByIndex(1).getData();
    const parameter = full.getModel().getSeriesByIndex(1).getDataParams(lastData.count()-1);
    assert.equal(parameter.data.id,'total');
    assert.match(nav.option.tooltip.formatter([parameter]),/Final section total.*100.*sections are not added together/);
    nav.update();
    const anchor = nav.option.xAxis.data[nav.option.dataZoom[0].startValue];
    nav.resize(350);
    assert.equal(nav.option.xAxis.data[nav.option.dataZoom[0].startValue],anchor);
    nav.useNative(null); full.dispose();
}
console.log('Waterfall layout, calculated subtotals, absolute sections, global scales and navigation verified.');
