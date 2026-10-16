'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.join(__dirname, '..');
const source = fs.readFileSync(path.join(root, 'EChartsBoxplot/visualization/app.js'), 'utf8');
const designSource = fs.readFileSync(path.join(root, 'libs/echarts-design.js'), 'utf8');
const palette = { background: '#101114', text: '#f4f5f7', muted: '#969aa2', border: '#737780', accent: '#55cbb5' };

function render(model, mode = 'symcon', headerVisible = true, width = 900) {
    const chartElement = { style: {}, hidden: false, clientWidth: width, clientHeight: 500 };
    const rootElement = { style: {} };
    const errorElement = { hidden: true, textContent: '' };
    const emptyElement = { hidden: true, textContent: '' };
    const warningElement = { hidden: true, textContent: '' };
    let option;
    const chart = { setOption: next => { option = next; }, clear: () => {}, resize: () => {}, dispose: () => {} };
    const window = {
        SYMC_VISUALIZATION: { mode, state: { status: 'ready', chart: model }, translations: {},
            options: { echartsThemes: { auto: palette, dark: palette }, tileHeaderVisible: headerVisible,
                adaptToBackground: mode === 'ipsview' } },
        echarts: { init: () => chart }, addEventListener: () => {}, getComputedStyle: () => ({ color: '' })
    };
    const document = {
        body: { appendChild: () => {} }, createElement: () => ({ style: {}, remove: () => {} }),
        getElementById: id => ({ 'echarts-boxplot-root': rootElement, 'echarts-boxplot-chart': chartElement,
            'echarts-boxplot-error': errorElement, 'echarts-boxplot-empty': emptyElement,
            'echarts-boxplot-warning': warningElement })[id]
    };
    vm.runInNewContext(designSource, { window });
    vm.runInNewContext(source, { window, document });
    return { option, chartElement, errorElement, emptyElement, warningElement, window };
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
assert.equal(tile.chartElement.style.top, '58px');
assert.equal(tile.option.series[0].type, 'boxplot');
assert.equal(tile.option.series[0].data[0].value.join(','), '-4,-1,2,5,8');
assert.equal(tile.option.series[0].data[0].itemStyle.color, '#abcdef');
assert.equal(tile.option.xAxis.data.join(','), 'variable-1,variable-2,variable-3');
assert.equal(tile.option.xAxis.axisLabel.formatter('variable-1'), '<Room>');
assert.equal(tile.option.xAxis.axisLabel.formatter('variable-2'), '<Room>');
assert.equal(tile.option.yAxis.scale, true, 'Do not force a zero-baseline onto temperature distributions.');
assert.equal(tile.option.animation, false);
assert.match(tile.option.tooltip.formatter({ dataIndex: 0 }), /&lt;Room&gt;.*Minimum: -4 °C.*Median: 2 °C.*Hourly samples: 4/);
assert.equal(tile.option.tooltip.formatter({ dataIndex: 2 }), '');
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
assert.ok(crowded.option.series[0].boxWidth[1] <= (350 - crowded.option.grid.left - crowded.option.grid.right) / 10 * 0.65);
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
console.log('Boxplot renderer, sparse data and real ECharts geometry verified.');
