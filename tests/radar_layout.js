'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.join(__dirname, '..');
const source = fs.readFileSync(path.join(root, 'EChartsRadar/visualization/app.js'), 'utf8');
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
        getElementById: id => ({ 'echarts-radar-root': rootElement, 'echarts-radar-chart': chartElement,
            'echarts-radar-error': errorElement, 'echarts-radar-empty': emptyElement,
            'echarts-radar-warning': warningElement })[id]
    };
    vm.runInNewContext(designSource, { window });
    vm.runInNewContext(source, { window, document });
    return { option, chartElement, errorElement, emptyElement, warningElement, window };
}

const axes = [
    { Key: 'temperature', Label: '<Temperature>', Minimum: -20, Maximum: 40, Unit: '°C', Decimals: 1 },
    { Key: 'humidity', Label: 'Humidity', Minimum: 0, Maximum: 100, Unit: '%', Decimals: 0 },
    { Key: 'co2', Label: 'CO2', Minimum: 0, Maximum: 2000, Unit: 'ppm', Decimals: 0 }
];
const model = { family: 'radar', theme: 'dark', radar: {
    title: 'Current room comparison', axes, clippedCount: 1,
    data: [{ id: 'first', name: '<Room>', values: [-5, 0, 2500], percentValues: [25, 0, 100] },
        { id: 'second', name: 'Other room', values: [10, 50, 1000], percentValues: [50, 50, 50] }],
    style: { shape: 'polygon', startAngle: 90, clockwise: false, showLegend: true, showTooltip: true,
        showAxisNames: true, showAxisValues: false, showSplitArea: true, splitNumber: 5, axisNameGap: 15,
        lineWidth: 2, lineOpacityPercent: 100, areaOpacityPercent: 25, symbolSize: 4,
        titleFontSizePercent: 100, axisFontSizePercent: 100, animationEnabled: false }
} };
const tile = render(model);
assert.equal(tile.chartElement.style.top, '58px', 'Radar must reserve the tile header outside the canvas.');
assert.equal(tile.option.series[0].type, 'radar');
assert.equal(tile.option.series[0].data[0].value[0], 25);
assert.equal(tile.option.radar.indicator[0].min, 0);
assert.equal(tile.option.radar.indicator[0].max, 100);
assert.match(tile.option.radar.indicator[0].name, /-20 °C … 40 °C/);
assert.match(tile.option.tooltip.formatter({ data: tile.option.series[0].data[0] }), /&lt;Room&gt;.*&lt;Temperature&gt;: -5 °C.*CO2: 2[,.]500 ppm/);
assert.equal(tile.warningElement.hidden, false);
assert.equal(tile.option.animation, false);
assert.ok(tile.option.radar.center[1] - tile.option.radar.radius > tile.option.title.top + tile.option.title.textStyle.fontSize);
assert.equal(render(model, 'symcon', false).chartElement.style.top, '0px');

const custom = render({ ...model, radar: { ...model.radar, clippedCount: 0, style: { ...model.radar.style,
    shape: 'circle', clockwise: true, startAngle: 0, showLegend: false, showTooltip: false,
    showAxisNames: false, showAxisValues: true, showSplitArea: false, splitNumber: 8, axisNameGap: 0,
    lineWidth: 0, lineOpacityPercent: 0, areaOpacityPercent: 0, symbolSize: 0,
    axisColor: '#abcdef', titleColor: '#fedcba', gridColor: '#123456', axisFontSizePercent: 150
} } }, 'ipsview', true, 350);
assert.equal(custom.chartElement.style.top, '0px');
assert.equal(custom.option.backgroundColor, 'transparent');
assert.equal(custom.option.radar.shape, 'circle');
assert.equal(custom.option.radar.clockwise, true);
assert.equal(custom.option.radar.startAngle, 0);
assert.equal(custom.option.radar.axisNameGap, 0);
assert.equal(custom.option.radar.axisName.show, false);
assert.equal(custom.option.radar.axisLabel.show, true);
assert.equal(custom.option.radar.splitArea.show, false);
assert.equal(custom.option.radar.splitNumber, 8);
assert.equal(custom.option.radar.axisLine.lineStyle.color, '#123456');
assert.equal(custom.option.radar.axisName.color, '#abcdef');
assert.equal(custom.option.radar.axisName.fontSize, 15);
assert.equal(custom.option.title.textStyle.color, '#fedcba');
assert.equal(custom.option.legend.show, false);
assert.equal(custom.option.tooltip.show, false);
assert.equal(custom.option.series[0].lineStyle.width, 0);
assert.equal(custom.option.series[0].lineStyle.opacity, 0);
assert.equal(custom.option.series[0].areaStyle.opacity, 0);
assert.equal(custom.option.series[0].symbolSize, 0);
assert.equal(custom.warningElement.hidden, true);
tile.window.handleMessage({ status: 'error', error: 'The Radar values could not be loaded.' });
assert.equal(tile.chartElement.hidden, false, 'Transient errors must retain the last valid chart.');
tile.window.handleMessage({ status: 'error', error: 'Configure valid Radar axes and comparison values.' });
assert.equal(tile.errorElement.hidden, false);
assert.equal(tile.chartElement.hidden, true, 'Configuration errors must not retain misleading data.');
const echarts = require(path.join(root, '.tools/echarts-runtime/node_modules/echarts'));
const decorated = render({ ...model, radar: { ...model.radar, style: { ...model.radar.style,
    groupColors: { '<Room>': '#ff0000' }, lineType: 'dashed', symbol: 'diamond', symbolRotate: 30,
    symbolBorderWidth: 2, symbolBorderColor: '#00ff00', symbolOpacityPercent: 60,
    showValues: true, valueFontSizePercent: 150, valueColor: '#abcdef', valueLabelPosition: 'right', valueLabelDistance: 8,
    valueLabelBold: true, titleBold: false, axisLabelBold: true, showAxisRange: false,
    showAxisLines: false, axisLineWidth: 3, axisLineOpacityPercent: 75,
    showSplitLines: false, splitLineWidth: 2, splitLineType: 'dotted', splitLineOpacityPercent: 80,
    splitAreaColor1: '#112233', splitAreaColor2: '#445566', splitAreaOpacityPercent: 45,
    legendPosition: 'top', legendFontSizePercent: 150, legendColor: '#123456', hoverFocus: 'none',
    areaShadowBlur: 8, areaShadowColor: '#654321'
} } }).option;
assert.equal(decorated.series[0].lineStyle.type, 'dashed');
assert.equal(decorated.series[0].data[0].itemStyle.color, '#ff0000');
assert.equal(decorated.series[0].data[0].lineStyle.color, '#ff0000');
assert.equal(decorated.series[0].symbol, 'diamond');
assert.equal(decorated.series[0].symbolRotate, 30);
assert.equal(decorated.series[0].itemStyle.borderColor, '#00ff00');
assert.equal(decorated.series[0].itemStyle.borderWidth, 2);
assert.equal(decorated.series[0].itemStyle.opacity, 0.6);
assert.equal(decorated.series[0].label.fontSize, 18);
assert.equal(decorated.series[0].label.opacity, 1, 'Point opacity must not make independently configured labels unreadable.');
assert.equal(decorated.series[0].label.position, 'right');
assert.equal(decorated.series[0].label.distance, 8);
assert.equal(decorated.series[0].label.fontWeight, 'bold');
assert.match(decorated.series[0].label.formatter({ data: decorated.series[0].data[0], dimensionIndex: 2 }), /^2[,.]500 ppm$/);
assert.equal(decorated.series[0].emphasis.disabled, true);
assert.equal(decorated.series[0].areaStyle.shadowBlur, 8);
assert.equal(decorated.title.textStyle.fontWeight, 'normal');
assert.equal(decorated.radar.axisName.fontWeight, 'bold');
assert.equal(decorated.radar.indicator[0].name, '<Temperature>');
assert.equal(decorated.radar.axisLine.show, false);
assert.equal(decorated.radar.axisLine.lineStyle.width, 3);
assert.equal(decorated.radar.axisLine.lineStyle.opacity, 0.75);
assert.equal(decorated.radar.splitLine.show, false);
assert.equal(decorated.radar.splitLine.lineStyle.type, 'dotted');
assert.equal(decorated.radar.splitLine.lineStyle.width, 2);
assert.equal(decorated.radar.splitLine.lineStyle.opacity, 0.8);
assert.equal(decorated.radar.splitArea.areaStyle.color[0], '#112233');
assert.equal(decorated.radar.splitArea.areaStyle.opacity, 0.45);
assert.equal(decorated.legend.textStyle.fontSize, 18);
assert.equal(decorated.legend.textStyle.color, '#123456');
assert.ok(decorated.legend.top > decorated.title.top);
for (const hide of [true, false]) {
    const repeated = { ...model, radar: { ...model.radar, data: [model.radar.data[1], { ...model.radar.data[1], id: 'third', name: 'Same values' }],
        style: { ...model.radar.style, showValues: true, hideOverlappingValueLabels: hide } } };
    const option = render(repeated).option;
    assert.equal(option.series[0].labelLayout.hideOverlap, hide);
    const engine = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 900, height: 500 });
    engine.setOption(option);
    const actual = engine.getModel().getSeriesByIndex(0).getData();
    let visible = 0;
    for (let index = 0; index < actual.count(); index++) {
        actual.getItemGraphicEl(index).childAt(2).eachChild(symbol => {
            const label = symbol.getTextContent();
            if (label && !label.ignore) { visible++; }
        });
    }
    assert.equal(visible, hide ? 3 : 6, 'Real Radar labels must honor the overlap switch without losing the data points.');
    engine.dispose();
}
for (const option of [render(model).option, custom.option, decorated]) {
    const chart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 900, height: 500 });
    chart.setOption(option);
    const series = chart.getModel().getSeriesByIndex(0);
    assert.equal(series.getData().count(), 2);
    const points = series.getData().getItemLayout(0);
    assert.ok(points.every(point => point.every(Number.isFinite)), 'Real Radar geometry must remain finite.');
    assert.match(chart.renderToSVGString(), /<path/);
    if (option === decorated) {
        const symbols = series.getData().getItemGraphicEl(0).childAt(2);
        assert.match(symbols.childAt(2).getTextContent().style.text, /^2[,.]500 ppm$/);
        assert.equal(symbols.childAt(0).style.fill, '#ff0000');
        assert.equal(symbols.childAt(0).style.lineWidth, 2);
    }
    chart.dispose();
}
console.log('Radar layout and real ECharts geometry verified.');

