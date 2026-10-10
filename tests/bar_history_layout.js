'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(
    path.join(__dirname, '..', 'EChartsBarHistory', 'visualization', 'app.js'),
    'utf8'
);
const designSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-design.js'), 'utf8');
const zoomSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-zoom.js'), 'utf8');
const patternSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-pattern.js'), 'utf8');

function render(truncated = false, mode = 'symcon', adaptToBackground = false, enableZoom = true, enableImages = false, width = 750, height = 420) {
    const listeners = {};
    const images = [];
    const scheduled = [];
    function TestImage() { images.push(this); }
    Object.defineProperty(TestImage.prototype, 'src', { set(value) { this.source = value; } });
    const chartElement = {
        hidden: false,
        clientWidth: width,
        clientHeight: height,
        addEventListener: (name, listener) => { listeners[name] = listener; },
        getBoundingClientRect: () => ({ left: 0, width })
    };
    const warningElement = { hidden: true, textContent: '' };
    const errorElement = { hidden: true, textContent: '' };
    let option;
    let initCount = 0;
    let updateCount = 0;
    let resizeCount = 0;
    let resizeCallback;
    let chartWidth = width;
    let chartHeight = height;
    let dispatchedAction;
    const chart = {
        setOption: next => { option = next; updateCount++; },
        getOption: () => option,
        getWidth: () => chartWidth,
        getHeight: () => chartHeight,
        dispatchAction: action => { dispatchedAction = action; },
        clear: () => {},
        resize: () => {
            resizeCount++;
            chartWidth = chartElement.clientWidth;
            chartHeight = chartElement.clientHeight;
        },
        dispose: () => {}
    };
    const palette = {
        background: '#101114', text: '#f4f5f7', muted: '#969aa2', border: '#a5a9b0',
        accent: '#55cbb5', seriesColors: ['#5070dd']
    };
    const window = {
        SYMC_VISUALIZATION: {
            mode,
            state: {
                status: 'ready',
                chart: {
                    theme: 'dark',
                    bar: {
                        title: 'History', timeAxisLabelFormat: 'date-time', unit: '°C', decimals: 1,
                        style: {
                            showValues: true, showGrid: false, roundedBars: true,
                            barWidthPercent: 60, enableZoom
                        }
                    },
                    range: { key: '24h', dataMode: 'auto', startTimestamp: 1780000000, endTimestamp: 1780000400 },
                    series: [{
                        label: 'Temperature', color: '#e5754f',
                        points: [[1780000100, 21.5], [1780000200, 22.0]]
                    }],
                    truncated
                }
            },
            translations: {},
            options: {
                echartsThemes: { auto: palette, dark: palette }, tileHeaderVisible: true,
                adaptToBackground
            }
        },
        echarts: { init: () => { initCount++; return chart; } },
        ResizeObserver: class {
            constructor(callback) { resizeCallback = callback; }
            observe() {}
        },
        Image: enableImages ? TestImage : undefined,
        setTimeout: callback => { scheduled.push(callback); },
        addEventListener: () => {},
        getComputedStyle: () => ({ color: '' })
    };
    const document = {
        body: { appendChild: () => {} },
        createElement: tag => tag === 'canvas'
            ? { width: 0, height: 0, getContext: () => ({ drawImage: () => {} }) }
            : { style: {}, remove: () => {} },
        getElementById: id => ({
            'echarts-bar-history-chart': chartElement,
            'echarts-bar-history-warning': warningElement,
            'echarts-bar-history-error': errorElement
        })[id]
    };
    const context = { window, document, ResizeObserver: window.ResizeObserver };
    vm.runInNewContext(designSource, context);
    vm.runInNewContext(zoomSource, context);
    vm.runInNewContext(patternSource, context);
    vm.runInNewContext(source, context);
    return {
        get option() { return option; },
        get initCount() { return initCount; },
        get updateCount() { return updateCount; },
        get resizeCount() { return resizeCount; },
        get dispatchedAction() { return dispatchedAction; },
        getState: () => window.SYMC_VISUALIZATION.state,
        listeners, warningElement, window, images,
        resizeTo: (nextWidth, nextHeight) => {
            chartElement.clientWidth = nextWidth;
            chartElement.clientHeight = nextHeight;
            resizeCallback();
        },
        flush: () => { while (scheduled.length > 0) { scheduled.shift()(); } }
    };
}

const ready = render();
assert.ok(ready.option, 'The Historical Bar chart should render.');
assert.equal(ready.option.xAxis.type, 'time');
assert.equal(ready.option.dataZoom.length, 2);
assert.equal(ready.option.dataZoom[0].zoomOnMouseWheel, true);
assert.equal(ready.option.dataZoom[1].height, 18,
    'The History zoom slider should remain slim in a regular tile.');
assert.equal(ready.option.xAxis.min, 1780000000000);
assert.equal(ready.option.yAxis.type, 'value');
assert.equal(ready.option.yAxis.name, '°C');
assert.equal(ready.option.yAxis.splitLine.show, false);
assert.equal(ready.option.series[0].type, 'bar');
assert.equal(ready.option.series[0].barMaxWidth, 29);
assert.equal(ready.option.series[0].itemStyle.color, '#e5754f');
assert.equal(ready.option.series[0].itemStyle.opacity, 1);
assert.equal(ready.option.title.textStyle.fontSize, 18);
assert.deepEqual(Array.from(ready.option.series[0].itemStyle.borderRadius), [6, 6, 0, 0]);
assert.deepEqual(Array.from(ready.option.series[0].data[0]), [1780000100000, 21.5]);
assert.equal(ready.warningElement.hidden, true);

const compact = render(false, 'symcon', false, true, false, 433, 213);
assert.ok(213 - compact.option.grid.top - compact.option.grid.bottom >= 70,
    'A short BarHistory tile must reserve a readable plotting area (top '
        + compact.option.grid.top + ', bottom ' + compact.option.grid.bottom + ').');
assert.equal(compact.option.xAxis.axisLabel.hideOverlap, true,
    'Dense time labels must not overlap in a narrow tile.');
assert.ok(compact.option.xAxis.splitNumber <= 3,
    'The time axis must request fewer ticks when horizontal space is limited.');
assert.ok(compact.option.dataZoom[1].height <= 14,
    'The compact zoom slider must not consume the plot height.');
compact.window.handleMessage({
    status: 'ready',
    chart: {
        ...compact.getState().chart,
        bar: { ...compact.getState().chart.bar, title: '' },
        axes: [
            { unit: '°C', position: 'left', positionIndex: 0 },
            { unit: '%', position: 'right', positionIndex: 0 }
        ],
        series: [
            { id: 'temperature', label: 'Temperatur', axisIndex: 0, points: [[1780000100, 21.5]] },
            { id: 'humidity', label: 'Feuchtigkeit', axisIndex: 1, points: [[1780000100, 49]] }
        ]
    }
});
assert.ok(213 - compact.option.grid.top - compact.option.grid.bottom >= 70,
    'A short two-axis History tile must keep enough height beneath the legend.');
assert.equal(compact.option.legend.show, true);
assert.equal(compact.option.yAxis[0].axisLabel.hideOverlap, true);
assert.equal(compact.option.yAxis[1].axisLabel.hideOverlap, true);
const localECharts = path.join(__dirname, '..', '.tools', 'echarts-runtime', 'node_modules', 'echarts');
if (fs.existsSync(localECharts)) {
    const echarts = require(localECharts);
    const actualChart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 433, height: 213 });
    actualChart.setOption(compact.option);
    const actualGrid = actualChart.getModel().getComponent('grid').coordinateSystem.getRect();
    assert.ok(actualGrid.height >= 45,
        'ECharts must retain a usable plot after reserving axis labels in the short tile.');
    assert.ok(actualChart.renderToSVGString().includes('<svg'));
    actualChart.dispose();
}

const resizedHistory = render();
resizedHistory.resizeTo(433, 213);
assert.equal(resizedHistory.resizeCount, 1);
assert.equal(resizedHistory.updateCount, 2,
    'A real tile resize must rebuild the responsive History option.');
assert.ok(213 - resizedHistory.option.grid.top - resizedHistory.option.grid.bottom >= 70);
resizedHistory.resizeTo(433, 213);
assert.equal(resizedHistory.updateCount, 2,
    'An unchanged observer notification must not restart the chart.');

ready.window.handleMessage({
    status: 'ready',
    chart: {
        theme: 'dark',
        bar: {
            title: 'Styled history', unit: '°C', decimals: 1,
            style: {
                showValues: true, showGrid: true, roundedBars: true,
                barWidthPercent: 60, barFillMode: 'gradient', barGradientColor: '#55ccaa',
                barOpacityPercent: 65, barCornerRadius: 10,
                titleFontSizePercent: 130, axisFontSizePercent: 115,
                valueFontSizePercent: 125, titleColor: '#ffeedd',
                axisColor: '#ddeeff', valueColor: '#112233', gridColor: '#445566',
                animationEnabled: true, animationEasing: 'bounceOut',
                animationEasingUpdate: 'linear', animationDelay: 120,
                animationDelayUpdate: 240
            }
        },
        series: [{ label: 'Temperature', color: '#e5754f', points: [[1780000300, 23.0]] }]
    }
});
assert.equal(ready.option.title.textStyle.color, '#ffeedd');
assert.equal(ready.option.animationEasing, 'bounceOut');
assert.equal(ready.option.animationEasingUpdate, 'linear');
assert.equal(ready.option.animationDelay, 120);
assert.equal(ready.option.animationDelayUpdate, 240);
assert.equal(ready.option.title.textStyle.fontSize, 23);
assert.equal(ready.option.xAxis.axisLabel.color, '#ddeeff');
assert.equal(ready.option.xAxis.axisLabel.fontSize, 14);
assert.equal(ready.option.xAxis.axisLine.lineStyle.color, '#ddeeff');
assert.equal(ready.option.yAxis.nameTextStyle.color, '#ddeeff');
assert.equal(ready.option.yAxis.splitLine.lineStyle.color, '#445566');
assert.equal(ready.option.yAxis.splitLine.lineStyle.opacity, 1);
assert.equal(ready.option.series[0].label.color, '#112233');
assert.equal(ready.option.series[0].label.fontSize, 15);
assert.equal(ready.option.series[0].itemStyle.opacity, 0.65);
assert.deepEqual(Array.from(ready.option.series[0].itemStyle.borderRadius), [10, 10, 0, 0]);
assert.equal(ready.option.series[0].itemStyle.color.type, 'linear');
assert.equal(ready.option.series[0].itemStyle.color.colorStops[0].color, '#e5754f');
assert.equal(ready.option.series[0].itemStyle.color.colorStops[1].color, '#55ccaa');

const patterned = render(false, 'symcon', false, true, true);
patterned.window.handleMessage({
    status: 'ready',
    chart: {
        theme: 'dark',
        bar: { title: 'Pattern', unit: '°C', decimals: 1, style: {
            barFillMode: 'svg', barPatternImage: 'data:image/svg+xml;base64,PHN2Zy8+',
            barPatternAspectRatio: 2, barSVGSizePercent: 125
        } },
        series: [{ label: 'Temperature', color: '#e5754f', points: [[1780000300, 23.0]] }]
    }
});
assert.equal(patterned.option.series[0].itemStyle.color, '#e5754f');
assert.equal(patterned.images.length, 1);
patterned.images[0].onload();
patterned.flush();
assert.equal(patterned.option.series[0].itemStyle.color.repeat, 'repeat');
assert.equal(patterned.option.series[0].itemStyle.color.image.width, 80);
assert.equal(patterned.option.series[0].itemStyle.color.image.height, 40);
patterned.window.handleMessage({
    status: 'ready', chart: { theme: 'dark', bar: { style: { barFillMode: 'solid' } },
        series: [{ label: 'Temperature', color: '#e5754f', points: [[1780000300, 23.0]] }] }
});
assert.equal(patterned.option.series[0].itemStyle.color, '#e5754f');

const truncated = render(true);
assert.equal(truncated.warningElement.hidden, false);
assert.match(truncated.warningElement.textContent, /truncated/);

const ipsView = render(false, 'ipsview', true);
assert.equal(ipsView.option.backgroundColor, 'transparent');
assert.equal(ipsView.initCount, 1);
ipsView.window.handleMessage({
    status: 'ready',
    chart: {
        theme: 'dark',
        bar: { unit: '°C', decimals: 1, style: {} },
        series: [{ label: 'Temperature', points: [[1780000300, 23.0]] }]
    }
});
assert.equal(ipsView.initCount, 1, 'An IPSView update must reuse the existing chart.');
assert.equal(ipsView.updateCount, 2);
assert.deepEqual(Array.from(ipsView.option.series[0].data[0]), [1780000300000, 23.0]);

const disabledZoom = render(false, 'symcon', false, false);
assert.equal(disabledZoom.option.dataZoom.length, 0);
assert.equal(disabledZoom.option.grid.bottom, 58);

const retainedZoom = render();
retainedZoom.option.dataZoom[0].start = 25;
retainedZoom.option.dataZoom[0].end = 75;
const refreshedState = retainedZoom.getState();
retainedZoom.window.handleMessage({
    status: 'ready',
    chart: {
        ...refreshedState.chart,
        range: { ...refreshedState.chart.range, startTimestamp: 1780000060, endTimestamp: 1780000460 }
    }
});
assert.equal(retainedZoom.option.dataZoom[0].start, 25,
    'An archive refresh must keep the selected zoom window.');
assert.equal(retainedZoom.option.dataZoom[1].end, 75);
retainedZoom.window.handleMessage({
    status: 'ready',
    chart: {
        ...refreshedState.chart,
        range: { ...refreshedState.chart.range, key: '7d' }
    }
});
assert.equal(retainedZoom.option.dataZoom[0].start, 0,
    'A changed configured range must reset the zoom window.');

const ipsViewZoom = render(false, 'ipsview');
assert.equal(ipsViewZoom.option.dataZoom[0].zoomOnMouseWheel, false);
let prevented = false;
ipsViewZoom.listeners.wheel({
    deltaY: -100, clientX: 375,
    preventDefault: () => { prevented = true; }, stopPropagation: () => {}
});
assert.equal(prevented, true);
assert.equal(ipsViewZoom.dispatchedAction.type, 'dataZoom');
assert.equal(ipsViewZoom.dispatchedAction.start, 10);
assert.equal(ipsViewZoom.dispatchedAction.end, 90);

const multi = render();
multi.window.handleMessage({
    status: 'ready',
    chart: {
        theme: 'dark',
        bar: { title: 'Several sources', style: { showValues: true } },
        axes: [
            { unit: '°C', position: 'left', positionIndex: 0 },
            { unit: '%', position: 'right', positionIndex: 0 },
            { unit: 'hPa', position: 'left', positionIndex: 1 }
        ],
        series: [
            { id: 'variable-4711', label: 'Climate', unit: '°C', decimals: 1,
                axisIndex: 0, points: [[1780000100, 21.5]] },
            { id: 'variable-4713', label: 'Climate', unit: '%', decimals: 0,
                axisIndex: 1, points: [[1780000100, 58]] },
            { id: 'variable-4714', label: 'Pressure', unit: 'hPa', decimals: 2,
                axisIndex: 2, points: [[1780000100, 1013.25]] }
        ]
    }
});
assert.equal(multi.option.legend.show, true);
assert.deepEqual(Array.from(multi.option.legend.data),
    ['variable-4711', 'variable-4713', 'variable-4714']);
assert.equal(multi.option.legend.formatter('variable-4713'), 'Climate');
assert.equal(multi.option.yAxis.length, 3);
assert.equal(multi.option.yAxis[2].position, 'left');
assert.ok(multi.option.yAxis[2].offset > 0);
assert.deepEqual(Array.from(multi.option.series, item => item.yAxisIndex), [0, 1, 2]);
assert.equal(multi.option.series[0].stack, undefined);
assert.equal(multi.option.series[1].barGap, '20%');
assert.ok(multi.option.series[0].barMaxWidth < ready.option.series[0].barMaxWidth);
const tooltip = multi.option.tooltip.formatter([
    { seriesIndex: 0, value: [1780000100000, 21.5], marker: '*' },
    { seriesIndex: 1, value: [1780000100000, 58], marker: '*' }
]);
assert.match(tooltip, /21.5 °C/);
assert.match(tooltip, /58 %/);

console.log('Historical Bar renderer layout verified.');
