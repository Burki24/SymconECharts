'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '..', 'EChartsPie', 'visualization', 'app.js'), 'utf8');
const designSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-design.js'), 'utf8');

const palette = {
    background: '#101114', text: '#f4f5f7', muted: '#969aa2',
    accent: '#55cbb5', seriesColors: ['#111111', '#222222', '#333333']
};

function render(style, items, width = 800, mode = 'symcon', height = 400, normalized = false) {
    const chartElement = { hidden: false, clientWidth: width, clientHeight: height };
    const errorElement = { hidden: true, textContent: '' };
    const emptyElement = { hidden: true, textContent: '' };
    const warningElement = { hidden: true, textContent: '' };
    let option;
    const chart = {
        setOption: next => { option = next; }, clear: () => {}, resize: () => {}, dispose: () => {}
    };
    const model = {
        family: 'pie', theme: 'dark', pie: { title: 'Shares', style, normalized }, items
    };
    const window = {
        SYMC_VISUALIZATION: {
            mode, state: { status: 'ready', chart: model },
            translations: { 'No positive values': 'Keine positiven Werte' },
            options: { echartsThemes: { auto: palette, dark: palette }, tileHeaderVisible: true }
        },
        echarts: { init: () => chart },
        addEventListener: () => {},
        getComputedStyle: () => ({ color: '' })
    };
    const document = {
        body: { appendChild: () => {} },
        createElement: () => ({ style: {}, remove: () => {} }),
        getElementById: id => ({
            'echarts-pie-chart': chartElement,
            'echarts-pie-error': errorElement,
            'echarts-pie-empty': emptyElement,
            'echarts-pie-warning': warningElement
        })[id]
    };
    vm.runInNewContext(designSource, { window });
    vm.runInNewContext(source, { window, document });
    return { option, window, chartElement, errorElement, emptyElement };
}

const sources = [
    { id: 'variable-1', name: 'Temperatur', value: 10, order: 0, unit: '°C', decimals: 1, color: '#aa0000' },
    { id: 'variable-2', name: 'Temperatur', value: 20, order: 1, unit: '°C', decimals: 1, color: '' }
];
const baseStyle = {
    mode: 'pie', sortOrder: 'configured', innerRadiusPercent: 35, outerRadiusPercent: 90,
    showLegend: true, showLabels: true, showPercent: true, labelPosition: 'outside'
};
const normalizedPie = render(baseStyle, [
    { ...sources[0], value: 50, originalValue: -10, minimum: -20, maximum: 0 },
    { ...sources[1], value: 75, originalValue: 75, minimum: 0, maximum: 100, unit: '%' }
], 800, 'symcon', 400, true);
const normalizedTooltip = normalizedPie.option.tooltip.formatter({ name: 'variable-1', dataIndex: 0, percent: 40 });
assert.match(normalizedTooltip, /-10/);
assert.match(normalizedTooltip, /°C/);
assert.match(normalizedTooltip, /Normalized/);
assert.match(normalizedTooltip, /normalized weights/);
assert.match(normalizedTooltip, /Range/);
const narrowNormalizedPie = render(baseStyle, sources.map(item => ({ ...item,
    originalValue: item.value, minimum: -20, maximum: 40 })), 320, 'symcon', 400, true);
assert.equal(narrowNormalizedPie.option.tooltip.confine, true,
    'Long normalization tooltips must stay inside a narrow embedded tile.');
assert.match(narrowNormalizedPie.option.tooltip.extraCssText, /max-width:296px.*overflow-wrap:anywhere/);
for (const outputMode of ['symcon', 'ipsview']) {
    const result = render({ ...baseStyle, showPercent: false }, [
        { ...sources[0], value: 100, originalValue: 140, minimum: 0, maximum: 100, unit: 'LQI' }
    ], 800, outputMode, 400, true);
    assert.equal(result.option.series[0].data[0].value, 100);
    assert.match(result.option.tooltip.formatter({ name: 'variable-1', percent: 100 }), /140/);
    assert.match(result.option.tooltip.formatter({ name: 'variable-1', percent: 100 }), /LQI/);
    assert.match(result.option.title.subtext, /normalized weights/);
    assert.match(result.option.aria.description, /normalized weights/);
}
const crowdedTile = render({ ...baseStyle, outerRadiusPercent: 100 }, [
    { ...sources[0], name: 'Feuchtigkeit' },
    { ...sources[1], name: 'Feuchtigkeit' },
    { ...sources[1], id: 'variable-3', name: 'Feuchtigkeit' },
    { ...sources[1], id: 'variable-4', name: 'Feuchtigkeit' }
], 586, 'symcon', 552);
const crowdedSeries = crowdedTile.option.series[0];
const crowdedCenter = crowdedSeries.center;
const crowdedRadius = crowdedSeries.radius[1];
assert.equal(crowdedTile.option.legend.orient, 'horizontal',
    'A medium-sized tile should place its legend below the Pie instead of beside the labels.');
assert.ok(crowdedCenter[1] - crowdedRadius >= 58,
    'The Pie must leave the visible Symcon tile header unobstructed.');
assert.ok(crowdedCenter[1] + crowdedRadius <= 552 - 45,
    'The Pie must not collide with its bottom legend.');
assert.ok(crowdedCenter[0] - crowdedRadius >= 60 && crowdedCenter[0] + crowdedRadius <= 586 - 60,
    'Outside labels need room on both sides of the Pie.');
const wideTile = render({ ...baseStyle, outerRadiusPercent: 100 }, sources, 1000, 'symcon', 560);
assert.equal(wideTile.option.legend.orient, 'vertical');
assert.ok(wideTile.option.series[0].center[0] + wideTile.option.series[0].radius[1]
    < 1000 - wideTile.option.legend.width,
    'A wide tile must reserve a separate column for the legend.');
const pie = render(baseStyle, sources);
assert.equal(pie.option.title.show, true);
assert.ok(pie.option.title.top >= 58
    && pie.option.series[0].center[1] - pie.option.series[0].radius[1]
        > pie.option.title.top + pie.option.title.textStyle.fontSize,
    'The Pie title and plot must clear the Symcon tile header.');
const routedLabels = render({ ...baseStyle, outsideLabelAlignment: 'labelLine',
    leaderLineLength: 24, leaderLineLength2: 16, labelLineGap: 7,
    labelEdgeDistance: 12 }, sources);
assert.equal(routedLabels.option.series[0].label.alignTo, 'labelLine');
assert.equal(routedLabels.option.series[0].label.distanceToLabelLine, 7);
assert.equal(routedLabels.option.series[0].labelLine.length, 24);
assert.equal(routedLabels.option.series[0].labelLine.length2, 16);
assert.ok(pie.option, 'Pie must render current positive values.');
assert.equal(pie.option.series[0].type, 'pie');
assert.equal(pie.option.series[0].radius[0], 0);
assert.ok(pie.option.series[0].radius[1] > 0);
assert.equal(pie.option.series[0].roseType, false);
assert.equal(pie.option.series[0].stillShowZeroSum, false);
assert.deepEqual(Array.from(pie.option.series[0].data, item => item.name), ['variable-1', 'variable-2']);
assert.equal(pie.option.legend.formatter('variable-1'), 'Temperatur');
assert.equal(pie.option.legend.formatter('variable-2'), 'Temperatur');
assert.ok(!pie.option.legend.formatter('variable-1').includes('variable-'));
assert.ok(!pie.option.tooltip.formatter({ name: 'variable-1', marker: '', percent: 33.3 }).includes('variable-'),
    'Variable IDs must remain internal even when visible names collide.');
assert.match(pie.option.series[0].label.formatter({ name: 'variable-2', percent: 66.7 }), /^Temperatur\n66[,.]7%$/);
assert.equal(pie.option.series[0].data[0].itemStyle.color, '#aa0000');
assert.equal(pie.option.series[0].data[1].itemStyle.color, '#222222');

const donut = render({ ...baseStyle, mode: 'donut', sortOrder: 'descending', labelPosition: 'inside',
    showPercent: false, legendFontSizePercent: 150, labelColor: '#123456' }, sources, 800, 'ipsview');
assert.ok(Math.abs(donut.option.series[0].radius[0] - donut.option.series[0].radius[1] * 0.35) < 0.001);
assert.deepEqual(Array.from(donut.option.series[0].data, item => item.name), ['variable-2', 'variable-1']);
assert.equal(donut.option.series[0].label.position, 'inside');
assert.equal(donut.option.series[0].label.color, '#123456');
assert.equal(donut.option.series[0].label.formatter({ name: 'variable-2', percent: 66.7 }), 'Temperatur');
assert.equal(donut.option.legend.textStyle.fontSize, 18);

const rose = render({ ...baseStyle, mode: 'nightingale', animationEnabled: true,
    animationDuration: 800, animationDurationUpdate: 1200, animationEasing: 'bounceOut' }, sources);
assert.equal(rose.option.series[0].roseType, 'area');
assert.equal(rose.option.animationDuration, 800);
assert.equal(rose.option.animationDurationUpdate, 1200);
assert.equal(rose.option.animationEasing, 'bounceOut');

const compact = render(baseStyle, sources, 320);
assert.equal(compact.option.legend.show, false, 'Compact tiles should reserve space for sectors.');
const zero = render(baseStyle, sources.map(item => ({ ...item, value: 0 })));
assert.equal(zero.option, undefined);
assert.equal(zero.emptyElement.textContent, 'Keine positiven Werte');
assert.equal(zero.emptyElement.hidden, false);

const hostile = render(baseStyle, [
    { ...sources[0], name: '<img src=x onerror=alert(1)>', unit: '<b>' }
]);
const tooltip = hostile.option.tooltip.formatter({ name: 'variable-1', marker: '', percent: 100 });
assert.ok(tooltip.includes('&lt;img'));
assert.ok(tooltip.includes('&lt;b&gt;'));
assert.ok(!tooltip.includes('<img'));

const localECharts = path.join(__dirname, '..', '.tools', 'echarts-runtime', 'node_modules', 'echarts');
if (fs.existsSync(localECharts)) {
    const echarts = require(localECharts);
    const chart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 800, height: 400 });
    chart.setOption(pie.option);
    let layout = chart.getModel().getSeriesByIndex(0).getData();
    assert.equal(layout.count(), 2, 'The bundled ECharts Pie series must keep equal visible names separate.');
    assert.equal(layout.getItemLayout(0).r0, 0);
    chart.setOption(donut.option, true);
    layout = chart.getModel().getSeriesByIndex(0).getData();
    assert.ok(layout.getItemLayout(0).r0 > 0, 'The Donut must leave a real inner opening.');
    chart.setOption(rose.option, true);
    layout = chart.getModel().getSeriesByIndex(0).getData();
    assert.equal(layout.getItemLayout(0).angle, layout.getItemLayout(1).angle,
        'The Nightingale rose must use equal sector angles.');
    assert.ok(layout.getItemLayout(0).r < layout.getItemLayout(1).r,
        'The Nightingale rose must use value-dependent radii.');
    chart.dispose();
}

console.log('Pie renderer layout verified.');
