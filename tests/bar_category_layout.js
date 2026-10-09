'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(
    path.join(__dirname, '..', 'EChartsBarCategory', 'visualization', 'app.js'),
    'utf8'
);
const designSource = fs.readFileSync(
    path.join(__dirname, '..', 'libs', 'echarts-design.js'),
    'utf8'
);
const patternSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-pattern.js'), 'utf8');

function render(orientation, sortOrder, outputMode = 'symcon', barMode = 'simple', items, styleOverrides = {}, enableImages = false) {
    const chartElement = { hidden: false, clientWidth: 800 };
    const errorElement = { hidden: true, textContent: '' };
    const images = [];
    const scheduled = [];
    function TestImage() { images.push(this); }
    Object.defineProperty(TestImage.prototype, 'src', { set(value) { this.source = value; } });
    let option;
    const chart = {
        setOption: next => { option = next; }, clear: () => {}, resize: () => {}, dispose: () => {}
    };
    const palette = {
        background: '#101114', text: '#f4f5f7', muted: '#969aa2', border: '#a5a9b0',
        accent: '#55cbb5', surface: '#25272b', seriesColors: ['#111111', '#222222', '#333333']
    };
    const window = {
        SYMC_VISUALIZATION: {
            mode: outputMode,
            state: {
                status: 'ready',
                chart: {
                    theme: 'dark',
                    bar: {
                        title: 'Temperatures', mode: barMode, orientation, sortOrder, unit: '°C', decimals: 1,
                        style: { showValues: true, showGrid: false, roundedBars: true,
                            barWidthPercent: 60, ...styleOverrides }
                    },
                    items: items || [
                        { category: 'A', series: 'A', value: 10, order: 0, color: '#ff0000' },
                        { category: 'B', series: 'B', value: 30, order: 1, color: '' },
                        { category: 'C', series: 'C', value: 20, order: 2, color: '' }
                    ]
                }
            },
            translations: {},
            options: { echartsThemes: { auto: palette, dark: palette }, tileHeaderVisible: true }
        },
        echarts: { init: () => chart },
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
            'echarts-bar-category-chart': chartElement,
            'echarts-bar-category-error': errorElement
        })[id]
    };
    vm.runInNewContext(designSource, { window });
    vm.runInNewContext(patternSource, { window, document });
    vm.runInNewContext(source, { window, document });
    assert.ok(option, 'The Category Bar chart should render.');
    Object.defineProperties(option, {
        testImages: { value: images },
        getUpdatedOption: { value: () => option },
        flush: { value: () => { while (scheduled.length > 0) { scheduled.shift()(); } } }
    });
    return option;
}

const horizontal = render('horizontal', 'descending');
assert.equal(horizontal.xAxis.type, 'value');
assert.equal(horizontal.yAxis.type, 'category');
assert.deepEqual(Array.from(horizontal.yAxis.data), ['B', 'C', 'A']);
assert.equal(horizontal.series[0].label.position, 'right');
assert.equal(horizontal.series[0].barWidth, '60%');
assert.deepEqual(Array.from(horizontal.series[0].data[0].itemStyle.borderRadius), [0, 7, 7, 0]);
assert.equal(horizontal.series[0].data[2].itemStyle.color, '#ff0000');

const vertical = render('vertical', 'configured');
assert.equal(vertical.xAxis.type, 'category');
assert.equal(vertical.yAxis.type, 'value');
assert.deepEqual(Array.from(vertical.xAxis.data), ['A', 'B', 'C']);
assert.equal(vertical.series[0].label.position, 'top');
assert.equal(vertical.yAxis.splitLine.show, false);

const repeatedSimple = render('vertical', 'configured', 'symcon', 'simple', [
    { id: 'variable-4920', variableID: 4920, category: 'Temperatur', series: 'Temperatur', value: 8.5, order: 0 },
    { id: 'variable-4921', variableID: 4921, category: 'Temperatur', series: 'Temperatur', value: 21, order: 1 }
]);
assert.deepEqual(Array.from(repeatedSimple.xAxis.data), [
    'Temperatur', 'Temperatur'
]);
const reservedCategoryCollision = render('vertical', 'configured', 'symcon', 'simple', [
    { id: 'variable-4920', variableID: 4920, category: 'Temperatur', value: 8.5, order: 0 },
    { id: 'variable-4921', variableID: 4921, category: 'Temperatur', value: 21, order: 1 },
    { id: 'variable-4922', variableID: 4922, category: 'Temperatur (#4920)', value: 20.5, order: 2 }
]);
assert.deepEqual(Array.from(reservedCategoryCollision.xAxis.data), [
    'Temperatur', 'Temperatur', 'Temperatur (#4920)'
]);

const repeatedGrouped = render('vertical', 'configured', 'symcon', 'grouped', [
    { id: 'variable-4920', seriesKey: 'variable-4920', category: 'Temperatur', series: 'Temperatur', value: 8.5, order: 0 },
    { id: 'variable-4921', seriesKey: 'variable-4921', category: 'Temperatur', series: 'Temperatur', value: 21, order: 1 }
]);
assert.equal(repeatedGrouped.series.length, 2);
assert.deepEqual(Array.from(repeatedGrouped.legend.data), ['variable-4920', 'variable-4921']);
assert.deepEqual(Array.from(repeatedGrouped.legend.data, repeatedGrouped.legend.formatter), ['Temperatur', 'Temperatur']);
const repeatedGroupedTooltip = repeatedGrouped.tooltip.formatter([
    { seriesIndex: 0, name: 'Temperatur', marker: '', value: 8.5 },
    { seriesIndex: 1, name: 'Temperatur', marker: '', value: 21 }
]);
assert.equal(repeatedGroupedTooltip.includes('variable-'), false);
assert.equal((repeatedGroupedTooltip.match(/Temperatur/g) || []).length, 3);
assert.deepEqual(Array.from(repeatedGrouped.series, item => item.id), ['variable-4920', 'variable-4921']);
assert.deepEqual(Array.from(repeatedGrouped.series, item => item.data[0].value), [8.5, 21]);

const reservedLabelCollision = render('vertical', 'configured', 'symcon', 'grouped', [
    { id: 'variable-4900', variableID: 4900, seriesKey: 'variable-4900', category: 'Kitchen', series: 'Today', value: 18, order: 0 },
    { id: 'variable-4901', variableID: 4901, seriesKey: 'variable-4901', category: 'Kitchen', series: 'Today', value: 20, order: 1 },
    { id: 'variable-4902', variableID: 4902, seriesKey: 'series-Today (#4900)', category: 'Office', series: 'Today (#4900)', value: 24, order: 2 }
]);
assert.equal(reservedLabelCollision.series.length, 3);
assert.deepEqual(Array.from(reservedLabelCollision.legend.data), [
    'variable-4900', 'variable-4901', 'series-Today (#4900)'
]);
assert.deepEqual(Array.from(reservedLabelCollision.legend.data, reservedLabelCollision.legend.formatter), [
    'Today', 'Today', 'Today (#4900)'
]);

const repeatedStacked = render('vertical', 'configured', 'symcon', 'stacked', [
    { id: 'variable-4920', seriesKey: 'variable-4920', category: 'Temperatur', series: 'Temperatur', value: 8.5, order: 0 },
    { id: 'variable-4921', seriesKey: 'variable-4921', category: 'Temperatur', series: 'Temperatur', value: 21, order: 1 }
]);
assert.equal(repeatedStacked.series.length, 2);
assert.deepEqual(Array.from(repeatedStacked.series, item => item.data[0].value), [8.5, 21]);

const matrix = [
    { category: 'Kitchen', series: 'Today', value: 18, order: 0, color: '#aa0000' },
    { category: 'Kitchen', series: 'Yesterday', value: 20, order: 1, color: '#0000aa' },
    { category: 'Office', series: 'Today', value: 24, order: 2, color: '#aa0000' },
    { category: 'Office', series: 'Yesterday', value: 22, order: 3, color: '#0000aa' }
];
const grouped = render('vertical', 'descending', 'symcon', 'grouped', matrix);
assert.equal(grouped.legend.show, true);
assert.deepEqual(Array.from(grouped.xAxis.data), ['Office', 'Kitchen']);
assert.equal(grouped.series.length, 2);
assert.equal(grouped.series[0].name, 'Today');
assert.deepEqual(Array.from(grouped.series[0].data, item => item.value), [24, 18]);
assert.equal(grouped.series[0].stack, undefined);
assert.equal(grouped.series[0].barCategoryGap, '40%');

const stacked = render('horizontal', 'configured', 'symcon', 'stacked', matrix);
assert.deepEqual(Array.from(stacked.yAxis.data), ['Kitchen', 'Office']);
assert.equal(stacked.series[0].stack, 'total');
assert.equal(stacked.series[1].stack, 'total');
assert.equal(stacked.series[0].label.position, 'inside');
assert.equal(stacked.series[0].data[0].label.color, '#f4f5f7');
assert.equal(stacked.series[1].data[0].label.color, '#f4f5f7');

const automaticStack = render('vertical', 'configured', 'symcon', 'stacked', [
    { category: '', series: 'Room', value: 91, order: 0, color: '#aa0000' },
    { category: '', series: 'Floor', value: 82, order: 1, color: '#00aa00' },
    { category: '', series: 'Wall', value: 73, order: 2, color: '#0000aa' }
]);
assert.deepEqual(Array.from(automaticStack.xAxis.data), ['']);
assert.deepEqual(Array.from(automaticStack.legend.data), ['Room', 'Floor', 'Wall']);
assert.equal(automaticStack.series.length, 3);
assert.equal(automaticStack.series[0].stack, 'total');
assert.equal(automaticStack.series[0].data[0].value, 91);
assert.equal(automaticStack.series[2].data[0].itemStyle.color, '#0000aa');
assert.equal(automaticStack.series[0].data[0].label.color, '#f4f5f7');
assert.equal(automaticStack.series[1].data[0].label.color, '#101114');
assert.equal(automaticStack.series[2].data[0].label.color, '#f4f5f7');

const sparse = render('vertical', 'configured', 'symcon', 'grouped', [
    { category: 'Kitchen', series: 'Today', value: 18, order: 0, color: '#aa0000' },
    { category: 'Kitchen', series: 'Yesterday', value: 20, order: 1, color: '#0000aa' },
    { category: 'Office', series: 'Today', value: 24, order: 2, color: '#aa0000' }
]);
assert.deepEqual(Array.from(sparse.xAxis.data), ['Kitchen', 'Office']);
assert.deepEqual(Array.from(sparse.legend.data), ['Today', 'Yesterday']);
assert.deepEqual(Array.from(sparse.series[0].data, item => item.value), [18, 24]);
assert.deepEqual(Array.from(sparse.series[1].data, item => item.value), [20, null]);

const svgStyle = {
    barFillMode: 'svg', barPatternImage: 'data:image/svg+xml;base64,PHN2Zy8+',
    barPatternAspectRatio: 2, barSVGSizePercent: 150
};
const patterned = render('vertical', 'configured', 'symcon', 'simple', undefined, svgStyle, true);
assert.equal(patterned.testImages.length, 1);
assert.equal(patterned.series[0].data[0].itemStyle.color, '#ff0000',
    'The source color remains visible until the SVG has loaded.');
patterned.testImages[0].onload();
patterned.flush();
assert.equal(patterned.getUpdatedOption().series[0].data[0].itemStyle.color.repeat, 'repeat');
assert.equal(patterned.getUpdatedOption().series[0].data[0].itemStyle.color.image.width, 96);
assert.equal(patterned.getUpdatedOption().series[0].data[0].itemStyle.color.image.height, 48);

const patternedStacked = render('horizontal', 'configured', 'symcon', 'stacked', matrix, svgStyle, true);
patternedStacked.testImages[0].onload();
patternedStacked.flush();
assert.equal(patternedStacked.getUpdatedOption().series[0].data[0].itemStyle.color.repeat, 'repeat');
assert.equal(patternedStacked.getUpdatedOption().series[1].data[0].itemStyle.color.repeat, 'repeat');

const failedPattern = render('vertical', 'configured', 'symcon', 'simple', undefined, svgStyle, true);
failedPattern.testImages[0].onerror();
failedPattern.flush();
assert.equal(failedPattern.getUpdatedOption().series[0].data[0].itemStyle.color, '#ff0000',
    'A failed SVG load must retain the original bar color.');

console.log('Category Bar renderer layout verified.');
