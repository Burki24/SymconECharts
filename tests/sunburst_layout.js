'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '..', 'EChartsSunburst', 'visualization', 'app.js'), 'utf8');
const designSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-design.js'), 'utf8');
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
            options: { echartsThemes: { auto: palette, dark: palette }, tileHeaderVisible: true,
                adaptToBackground }
        },
        echarts: { init: () => chart }, addEventListener: () => {}, getComputedStyle: () => ({ color: '' })
    };
    const document = {
        body: { appendChild: () => {} },
        createElement: () => ({ style: {}, remove: () => {} }),
        getElementById: id => ({
            'echarts-sunburst-chart': chartElement,
            'echarts-sunburst-error': errorElement,
            'echarts-sunburst-empty': emptyElement,
            'echarts-sunburst-warning': warningElement
        })[id]
    };
    vm.runInNewContext(designSource, { window });
    vm.runInNewContext(source, { window, document });
    return { option, chartElement, errorElement, emptyElement };
}

const model = {
    family: 'sunburst', theme: 'dark', sunburst: {
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
const series = result.option.series[0];
assert.equal(series.type, 'sunburst');
assert.equal(series.data[0].value, 7);
assert.equal(series.data[0].children[0].value, 5);
assert.equal(series.data[0].itemStyle.color, '#5070dd');
assert.equal(series.data[1].itemStyle.color, '#b6d634');
assert.equal(series.data[0].children[0].label.color, '#101114');
assert.equal(series.nodeClick, 'rootToNode');
assert.equal(series.sort, null);
assert.deepEqual(Array.from(series.radius), ['0%', '85%']);
assert.match(result.option.tooltip.formatter({ name: '<Battery>', value: 5 }), /&lt;Battery&gt;/);
assert.doesNotMatch(result.option.tooltip.formatter({ name: '<Battery>', value: 5 }), /<Battery>/);

const custom = render({ ...model, sunburst: { ...model.sunburst, style: {
    ...model.sunburst.style, showValues: true, groupColors: { 'Room A': '#112233' },
    innerRadiusPercent: 15, outerRadiusPercent: 90, borderWidth: 4,
    borderColor: '#ffffff', clickToRoot: false, labelColor: '#123456',
    labelFontSizePercent: 150
} } });
assert.equal(custom.option.series[0].data[0].itemStyle.color, '#112233');
assert.equal(custom.option.series[0].nodeClick, false);
assert.deepEqual(Array.from(custom.option.series[0].radius), ['15%', '90%']);
assert.equal(custom.option.series[0].itemStyle.borderWidth, 4);
assert.equal(custom.option.series[0].itemStyle.borderColor, '#ffffff');
assert.equal(custom.option.series[0].label.color, '#123456');
assert.equal(custom.option.series[0].label.fontSize, 18);
assert.match(custom.option.series[0].label.formatter({ name: 'Battery', value: 5, data: {} }), /Battery\n5/);
assert.equal(custom.option.series[0].label.formatter({
    name: 'Room A', value: 7, data: { children: [] }
}), 'Room A');

const transparent = render(model, 1000, 'ipsview', true);
assert.equal(transparent.option.backgroundColor, 'transparent');
const compact = render(model, 300);
assert.equal(compact.option.series[0].label.show, false);
const zeros = render({ ...model, sunburst: { ...model.sunburst, positiveCount: 0 } });
assert.equal(zeros.option, undefined);
assert.equal(zeros.emptyElement.hidden, false);

const echarts = require(path.join(__dirname, '..', '.tools', 'echarts-runtime', 'node_modules', 'echarts'));
const chart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 1000, height: 400 });
chart.setOption(result.option);
assert.equal(chart.getModel().getSeriesByIndex(0).type, 'series.sunburst');
assert.match(chart.renderToSVGString(), /Room A/);
chart.setOption(custom.option, true);
assert.match(chart.renderToSVGString(), /Room A/);
chart.dispose();

console.log('Sunburst layout verified.');
