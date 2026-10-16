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
for (const option of [render(model).option, custom.option]) {
    const chart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 900, height: 500 });
    chart.setOption(option);
    const series = chart.getModel().getSeriesByIndex(0);
    assert.equal(series.getData().count(), 2);
    const points = series.getData().getItemLayout(0);
    assert.ok(points.every(point => point.every(Number.isFinite)), 'Real Radar geometry must remain finite.');
    assert.match(chart.renderToSVGString(), /<path/);
    chart.dispose();
}
console.log('Radar layout and real ECharts geometry verified.');

