'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(
    path.join(__dirname, '..', 'EChartsTimeSeries', 'visualization', 'app.js'),
    'utf8'
);
const palette = {
    background: '#202020', text: '#ffffff', muted: '#aaaaaa', border: '#cccccc', track: '#444444',
    accent: '#55cbb5', seriesColors: ['#111111', '#222222', '#333333']
};

function render(
    design,
    axes,
    width = 750,
    chartSeries,
    theme = 'dark',
    resolvedColors = {},
    mode = 'symcon',
    adaptToBackground = false
) {
    const listeners = {};
    const chartElement = {
        hidden: false,
        clientWidth: width,
        addEventListener: (type, listener) => { listeners[type] = listener; },
        getBoundingClientRect: () => ({ left: 0, width })
    };
    const warningElement = { hidden: true, textContent: '' };
    const errorElement = { hidden: true, textContent: '' };
    let option;
    let dispatchedAction;
    const chart = {
        setOption: next => { option = next; },
        getOption: () => option,
        dispatchAction: action => { dispatchedAction = action; },
        clear: () => {},
        resize: () => {},
        dispose: () => {}
    };
    const window = {
        SYMC_VISUALIZATION: {
            mode,
            state: {
                status: 'ready',
                chart: {
                    theme,
                    chart: { title: 'Climate', enableZoom: true, design },
                    range: { startTimestamp: 1000, endTimestamp: 2000 },
                    axes: axes || [{ unit: '°C' }, { unit: '%' }],
                    series: chartSeries || [
                        {
                            id: 'temperature', label: 'Temperature', axisIndex: 0, decimals: 1,
                            unit: '°C', color: '#E5754F', style: 'area', points: [[1000, 20], [2000, 21]]
                        },
                        {
                            id: 'humidity', label: 'Humidity', axisIndex: 1, decimals: 0,
                            unit: '%', color: '', style: 'line', points: [[1000, 50], [2000, 55]]
                        }
                    ],
                    truncated: false
                }
            },
            translations: {},
            options: {
                echartsThemes: { auto: palette, dark: palette },
                tileHeaderVisible: true,
                adaptToBackground
            }
        },
        echarts: { init: () => chart },
        getComputedStyle: probe => ({
            color: resolvedColors[probe.variable] || probe.fallback || ''
        }),
        addEventListener: () => {}
    };
    const document = {
        documentElement: { classList: { contains: () => false } },
        body: { appendChild: () => {} },
        createElement: () => ({
            style: {
                set color(value) {
                    const match = /^var\((--[^,]+),\s*(.+)\)$/.exec(value);
                    this.owner.variable = match ? match[1] : '';
                    this.owner.fallback = match ? match[2] : value;
                },
                get color() { return ''; },
                display: ''
            },
            variable: '',
            fallback: '',
            remove: () => {}
        }),
        getElementById: id => ({
            'echarts-timeseries-chart': chartElement,
            'echarts-timeseries-warning': warningElement,
            'echarts-timeseries-error': errorElement
        })[id]
    };
    const originalCreateElement = document.createElement;
    document.createElement = () => {
        const probe = originalCreateElement();
        probe.style.owner = probe;
        return probe;
    };

    vm.runInNewContext(source, { window, document });
    assert.ok(option, 'The time-series chart should be rendered.');
    Object.defineProperties(option, {
        testListeners: { value: listeners },
        getDispatchedAction: { value: () => dispatchedAction }
    });
    return option;
}

const custom = render({
    legendPosition: 'bottom', lineWidthPercent: 150, smoothLines: true,
    showSymbols: true, symbolSizePercent: 125, areaOpacityPercent: 40,
    showGrid: false, showXAxis: false, showYAxis: true
});
assert.equal(custom.legend.show, true);
assert.equal(custom.legend.bottom, 6);
assert.equal(custom.dataZoom[1].bottom, 38);
assert.equal(custom.series[0].lineStyle.width, 3);
assert.equal(custom.series[0].lineStyle.color, '#E5754F');
assert.equal(custom.series[0].itemStyle.color, '#E5754F');
assert.equal(custom.series[1].lineStyle.color, '#222222');
assert.equal(custom.series[1].itemStyle.color, '#222222');
assert.equal(custom.series[0].smooth, true);
assert.equal(custom.series[0].showSymbol, true);
assert.equal(custom.series[0].symbolSize, 7.5);
assert.equal(custom.series[0].areaStyle.opacity, 0.4);
assert.equal(custom.xAxis.axisLine.show, false);
assert.equal(custom.xAxis.splitLine.show, false);
assert.equal(custom.yAxis[0].axisLine.show, true);
assert.equal(custom.yAxis[0].axisLine.lineStyle.color, '#E5754F');
assert.equal(custom.yAxis[0].axisTick.lineStyle.color, '#E5754F');
assert.equal(custom.yAxis[0].axisLabel.color, '#E5754F');
assert.equal(custom.yAxis[0].nameTextStyle.color, '#E5754F');
assert.equal(custom.yAxis[1].axisLine.lineStyle.color, '#222222');
assert.equal(custom.yAxis[1].axisTick.lineStyle.color, '#222222');
assert.equal(custom.yAxis[1].axisLabel.color, '#222222');
assert.equal(custom.yAxis[1].nameTextStyle.color, '#222222');
assert.equal(custom.yAxis[0].splitLine.show, false);
assert.equal(custom.yAxis[0].position, 'left');
assert.equal(custom.yAxis[1].position, 'right');
assert.equal(custom.yAxis[0].offset, 0);
assert.equal(custom.yAxis[1].offset, 0);

const hidden = render({ legendPosition: 'hidden', showGrid: true, showXAxis: true, showYAxis: false });
assert.equal(hidden.legend.show, false);
assert.equal(hidden.xAxis.axisLine.show, true);
assert.equal(hidden.xAxis.splitLine.show, false);
assert.equal(hidden.yAxis[0].axisLine.show, false);
assert.equal(hidden.yAxis[0].splitLine.show, true);
assert.equal(hidden.yAxis[1].splitLine.show, false);
assert.equal(hidden.grid.left, 22);
assert.equal(hidden.grid.right, 22);

const compatible = render(undefined);
assert.equal(compatible.legend.show, true);
assert.equal(compatible.legend.top, 92);
assert.equal(compatible.series[0].lineStyle.width, 2);
assert.equal(compatible.series[0].smooth, false);
assert.equal(compatible.series[0].showSymbol, false);
assert.equal(compatible.series[0].areaStyle.opacity, 0.22);
assert.equal(compatible.xAxis.axisLine.show, true);
assert.equal(compatible.yAxis[0].axisLine.show, true);

const automaticTheme = render(undefined, undefined, 750, undefined, 'auto', {
    '--symc-background': '#ffffff',
    '--symc-text': '#202124',
    '--symc-muted': '#5f6368',
    '--symc-border': '#dadce0',
    '--symc-surface': '#f1f3f4',
    '--symc-accent': '#55cbb5'
});
assert.equal(automaticTheme.backgroundColor, '#ffffff');
assert.equal(automaticTheme.title.textStyle.color, '#202124');

const positioned = render(undefined, [
    { unit: '°C', position: 'left', positionIndex: 0 },
    { unit: '%', position: 'right', positionIndex: 0 },
    { unit: 'hPa', position: 'left', positionIndex: 1 },
    { unit: 'm/s', position: 'right', positionIndex: 1 },
    { unit: 'W/m²', position: 'left', positionIndex: 2 },
    { unit: 'mm', position: 'right', positionIndex: 2 }
], 600);
assert.deepEqual(
    Array.from(positioned.yAxis, axis => axis.position),
    ['left', 'right', 'left', 'right', 'left', 'right']
);
assert.equal(positioned.yAxis[0].offset, 0);
assert.equal(positioned.yAxis[1].offset, 0);
assert.ok(positioned.yAxis[2].offset > 0);
assert.equal(positioned.yAxis[2].offset, positioned.yAxis[3].offset);
assert.ok(positioned.yAxis[4].offset > positioned.yAxis[2].offset);
assert.equal(positioned.grid.left, positioned.grid.right);
assert.ok(positioned.grid.left <= 192);
assert.equal(positioned.yAxis.filter(axis => axis.splitLine.show).length, 1);

const sharedAxis = render(undefined, [{ unit: '°C', position: 'left', positionIndex: 0 }], 750, [
    {
        id: 'inside', label: 'Inside', axisIndex: 0, decimals: 1,
        unit: '°C', color: '#AABBCC', style: 'line', points: [[1000, 20], [2000, 21]]
    },
    {
        id: 'outside', label: 'Outside', axisIndex: 0, decimals: 1,
        unit: '°C', color: '#DDEEFF', style: 'line', points: [[1000, 10], [2000, 11]]
    }
]);
assert.equal(sharedAxis.yAxis[0].axisLine.lineStyle.color, '#AABBCC');
assert.equal(sharedAxis.series[1].lineStyle.color, '#DDEEFF');

const ipsViewZoom = render(undefined, undefined, 750, undefined, 'dark', {}, 'ipsview');
assert.equal(ipsViewZoom.dataZoom[0].zoomOnMouseWheel, false);
assert.equal(typeof ipsViewZoom.testListeners.wheel, 'function');
let prevented = false;
let stopped = false;
ipsViewZoom.testListeners.wheel({
    deltaY: -100,
    clientX: 375,
    preventDefault: () => { prevented = true; },
    stopPropagation: () => { stopped = true; }
});
const zoomAction = ipsViewZoom.getDispatchedAction();
assert.equal(prevented, true);
assert.equal(stopped, true);
assert.equal(zoomAction.type, 'dataZoom');
assert.equal(zoomAction.dataZoomIndex, 0);
assert.equal(zoomAction.start, 10);
assert.equal(zoomAction.end, 90);

const adaptedIPSView = render(undefined, undefined, 750, undefined, 'dark', {}, 'ipsview', true);
assert.equal(adaptedIPSView.backgroundColor, 'transparent');
assert.equal(compatible.backgroundColor, '#202020');

process.stdout.write('Time Series tile design layout verified.\n');
