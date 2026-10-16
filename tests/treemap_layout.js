'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '..', 'EChartsTreemap', 'visualization', 'app.js'), 'utf8');
const designSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-design.js'), 'utf8');
const palette = {
    background: '#101114', text: '#f4f5f7', muted: '#969aa2', accent: '#55cbb5',
    seriesColors: ['#5070dd', '#b6d634']
};

function render(model, width = 1000, mode = 'symcon') {
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
            options: { echartsThemes: { auto: palette, dark: palette }, tileHeaderVisible: true }
        },
        echarts: { init: () => chart }, addEventListener: () => {}, getComputedStyle: () => ({ color: '' })
    };
    const document = {
        body: { appendChild: () => {} },
        createElement: () => ({ style: {}, remove: () => {} }),
        getElementById: id => ({
            'echarts-treemap-chart': chartElement,
            'echarts-treemap-error': errorElement,
            'echarts-treemap-empty': emptyElement,
            'echarts-treemap-warning': warningElement
        })[id]
    };
    vm.runInNewContext(designSource, { window });
    vm.runInNewContext(source, { window, document });
    return { option, chartElement, errorElement, emptyElement };
}

const model = {
    family: 'treemap', theme: 'dark', treemap: {
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
assert.equal(result.option.series[0].type, 'treemap');
assert.equal(result.option.series[0].data[0].value, 7);
assert.equal(result.option.series[0].data[0].children[0].value, 5);
assert.equal(result.option.series[0].data[0].itemStyle.color, '#5070dd');
assert.equal(result.option.series[0].data[1].itemStyle.color, '#b6d634');
assert.equal(result.option.series[0].data[0].upperLabel.color, '#101114');
assert.equal(result.option.series[0].data[1].upperLabel.color, '#101114');
assert.equal(result.option.series[0].data[0].itemStyle.borderColor, '#5070dd');
assert.equal(result.option.series[0].data[1].itemStyle.borderColor, '#b6d634');
assert.equal(result.option.series[0].nodeClick, 'zoomToNode');
assert.equal(result.option.series[0].roam, true);
assert.equal(result.option.series[0].breadcrumb.show, true);
assert.equal(result.option.series[0].levels[0].itemStyle.gapWidth, 0);
assert.equal(result.option.series[0].levels[1].itemStyle.borderWidth, 3);
assert.equal(result.option.series[0].levels[1].itemStyle.gapWidth, 3);
assert.equal(result.option.series[0].levels[2].itemStyle.borderWidth, 2);
assert.equal(result.option.series[0].levels[2].itemStyle.gapWidth, 1);
assert.equal(result.option.series[0].label.show, true);
assert.match(result.option.tooltip.formatter({ name: '<Battery>', value: 5, treePathInfo: [
    { name: 'Room A' }, { name: '<Battery>' }
] }), /&lt;Battery&gt;/);
assert.doesNotMatch(result.option.tooltip.formatter({ name: '<Battery>', value: 5 }), /<Battery>/);

const chosenLabels = render({ ...model, treemap: { ...model.treemap, style: {
    ...model.treemap.style, labelColor: '#123456', labelFontSizePercent: 150
} } });
assert.equal(chosenLabels.option.series[0].label.color, '#123456');
assert.equal(chosenLabels.option.series[0].label.fontSize, 18);
assert.equal(chosenLabels.option.series[0].upperLabel.fontSize, 18);
assert.equal(chosenLabels.option.series[0].data[0].upperLabel.color, '#123456');
assert.equal(chosenLabels.option.series[0].data[0].children[0].label.color, '#123456');

const custom = render({ ...model, treemap: { ...model.treemap, style: {
    ...model.treemap.style, showValues: true,
    groupColors: { 'Room A': '#112233' },
    groupLabelColor: '#ffffff', subgroupLabelColor: '#000000',
    groupLabelFontSizePercent: 150, subgroupLabelFontSizePercent: 125
} } });
assert.equal(custom.option.series[0].data[0].itemStyle.color, '#112233');
assert.equal(custom.option.series[0].data[1].itemStyle.color, '#b6d634');
assert.equal(custom.option.series[0].data[0].upperLabel.color, '#ffffff');
assert.equal(custom.option.series[0].data[0].upperLabel.fontSize, 18);
assert.match(custom.option.series[0].data[0].children[0].label.formatter({ name: 'Battery', value: 5 }), /Battery\n5/);

const framed = render({ ...model, treemap: { ...model.treemap, style: {
    ...model.treemap.style, frameColor: '#112233',
    groupGapWidth: 9, level1BorderWidth: 7, level1GapWidth: 8,
    level2BorderWidth: 5, level2GapWidth: 6,
    level3BorderWidth: 2,
    allowPanZoom: false, clickToZoom: false, showBreadcrumb: false
} } });
assert.equal(framed.option.series[0].roam, false);
assert.equal(framed.option.series[0].nodeClick, false);
assert.equal(framed.option.series[0].breadcrumb.show, false);
assert.equal(framed.option.series[0].bottom, 8);
assert.equal(framed.option.series[0].levels[0].itemStyle.gapWidth, 9);
assert.equal(framed.option.series[0].levels[1].itemStyle.borderWidth, 7);
assert.equal(framed.option.series[0].levels[1].itemStyle.gapWidth, 8);
assert.equal(framed.option.series[0].levels[2].itemStyle.borderWidth, 5);
assert.equal(framed.option.series[0].levels[2].itemStyle.gapWidth, 6);
assert.equal(framed.option.series[0].levels[3].itemStyle.borderWidth, 2);
assert.equal(framed.option.series[0].levels[3].itemStyle.gapWidth, undefined);
assert.equal(framed.option.series[0].data[0].itemStyle.borderColor, '#112233');
assert.equal(framed.option.series[0].data[0].children[0].itemStyle.borderColor, '#112233');

const nestedModel = { ...model, treemap: { ...model.treemap, data: [{
    id: 'group-a', name: 'Room A', value: 5, children: [{
        id: 'subgroup-a', name: 'Lighting', value: 5, children: [
            { id: 'variable-1', name: 'Lamp', value: 5 }
        ]
    }]
}] } };
const nested = render(nestedModel, 1000, 'ipsview');
assert.equal(nested.option.series[0].data[0].children[0].itemStyle.borderColor, '#5070dd');
assert.equal(nested.option.series[0].data[0].children[0].upperLabel.color,
    nested.option.series[0].data[0].upperLabel.color);
const nestedCustom = render({ ...nestedModel, treemap: { ...nestedModel.treemap, style: {
    ...model.treemap.style, subgroupLabelColor: '#123456', subgroupLabelFontSizePercent: 150
} } }, 1000, 'ipsview');
assert.equal(nestedCustom.option.series[0].data[0].children[0].upperLabel.color, '#123456');
assert.equal(nestedCustom.option.series[0].data[0].children[0].upperLabel.fontSize, 18);
const nestedFrame = render({ ...nestedModel, treemap: { ...nestedModel.treemap, style: {
    ...model.treemap.style, frameColor: '#112233'
} } }, 1000, 'ipsview');
assert.equal(nestedFrame.option.series[0].data[0].children[0].itemStyle.borderColor, '#112233');
assert.equal(nestedFrame.option.series[0].data[0].children[0].children[0].itemStyle.borderColor, '#112233');

const echarts = require(path.join(__dirname, '..', '.tools', 'echarts-runtime', 'node_modules', 'echarts'));
const chart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 1000, height: 400 });
chart.setOption(chosenLabels.option);
assert.equal(chart.getModel().getSeriesByIndex(0).type, 'series.treemap');
assert.match(chart.renderToSVGString(), /Room A/);
chart.setOption(custom.option, true);
assert.match(chart.renderToSVGString(), /Room A/);
chart.setOption(framed.option, true);
assert.match(chart.renderToSVGString(), /Room A/);
chart.dispose();

const compact = render(model, 400);
assert.equal(compact.option.series[0].label.show, false);
assert.equal(compact.option.series[0].breadcrumb.show, false);
const zeros = render({ ...model, treemap: { ...model.treemap, positiveCount: 0 } });
assert.equal(zeros.option, undefined);
assert.equal(zeros.emptyElement.hidden, false);

console.log('Treemap layout verified.');
