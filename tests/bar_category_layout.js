'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(
    path.join(__dirname, '..', 'EChartsBarCategory', 'visualization', 'app.min.js'),
    'utf8'
);
const esbuild = require('../.tools/echarts-runtime/node_modules/esbuild');
const editableSource = fs.readFileSync(path.join(__dirname, '..', 'EChartsBarCategory/visualization/app.js'), 'utf8');
assert.equal(source.replace(/\r\n/g, '\n'), esbuild.transformSync(editableSource, {minifyWhitespace: true, target: 'es2018'}).code,
    'The shipped Category Bar script must be reproducible and current with its readable source.');
const gradientStyle = { barFillMode: 'gradient', colorMinimum: 0, colorMaximum: 100,
    lowColor: '#ff0000', middleColor: '#ffff00', highColor: '#00ff00', animationEnabled: false };
const designSource = fs.readFileSync(
    path.join(__dirname, '..', 'libs', 'echarts-design.js'),
    'utf8'
);
const patternSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-pattern.js'), 'utf8');

function render(orientation, sortOrder, outputMode = 'symcon', barMode = 'simple', items, styleOverrides = {}, enableImages = false, dimensions = {}) {
    const chartElement = { hidden: false, clientWidth: dimensions.width || 800, clientHeight: dimensions.height || 500 };
    const errorElement = { hidden: true, textContent: '' };
    const images = [];
    const scheduled = [];
    function TestImage() { images.push(this); }
    Object.defineProperty(TestImage.prototype, 'src', { set(value) { this.source = value; } });
    let option, nativeChart = null;
    const handlers = {};
    const windowHandlers = {};
    const chart = {
        setOption: next => { option = next; }, clear: () => {}, resize: () => {}, dispose: () => {},
        on: (event, handler) => { handlers[event] = handler; },
        getOption: () => nativeChart ? nativeChart.getOption() : ({ ...option, xAxis: [option.xAxis], yAxis: [option.yAxis] })
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
        addEventListener: (name, handler) => { windowHandlers[name] = handler; },
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
        refresh: { value: () => window.handleMessage(window.SYMC_VISUALIZATION.state) },
        resize: { value: (width, height) => {
            chartElement.clientWidth = width;
            chartElement.clientHeight = height;
            windowHandlers.resize();
        } },
        useNativeChart: { value: engine => {
            nativeChart = engine;
            if (engine) { Object.entries(handlers).forEach(([event, handler]) => engine.on(event, handler)); }
        } },
        flush: { value: () => { while (scheduled.length > 0) { scheduled.shift()(); } } }
    });
    return option;
}

const horizontal = render('horizontal', 'descending');
const gradientBattery = render('horizontal', 'configured', 'symcon', 'simple', [
    { id: 'battery', category: 'Battery', value: 16, order: 0, color: '#123456' }
], gradientStyle);
assert.equal(gradientBattery.series[0].data[0].itemStyle.color.type, 'linear',
    'Opting into gradient fill must paint inside each bar rather than choose a flat value color.');
assert.deepEqual(Array.from(gradientBattery.series[0].data[0].itemStyle.color.colorStops, stop => [stop.offset, stop.color]),
    [[0, '#ff0000'], [1, '#ff5200']], 'A 16% bar ends at 16% of the full configured ramp, not green.');
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

const styled = render('vertical', 'configured', 'symcon', 'grouped', undefined, {
    showGrid: true, titleFontSizePercent: 150, legendFontSizePercent: 125,
    axisFontSizePercent: 150, valueFontSizePercent: 125,
    titleColor: '#123456', legendColor: '#234567', axisColor: '#345678',
    valueColor: '#456789', gridColor: '#56789a'
});
assert.equal(styled.title.textStyle.fontSize, 27);
assert.equal(styled.title.textStyle.color, '#123456');
assert.equal(styled.legend.textStyle.fontSize, 15);
assert.equal(styled.legend.textStyle.color, '#234567');
assert.equal(styled.xAxis.axisLabel.fontSize, 18);
assert.equal(styled.yAxis.nameTextStyle.fontSize, 18);
assert.equal(styled.xAxis.axisLabel.color, '#345678');
assert.equal(styled.yAxis.splitLine.lineStyle.color, '#56789a');
assert.equal(styled.series[0].label.fontSize, 15);
assert.equal(styled.series[0].data[0].label, undefined);
assert.equal(styled.series[0].label.color, '#456789');

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
const styledStack = render('horizontal', 'configured', 'symcon', 'stacked', matrix, { valueColor: '#123456' });
assert.equal(styledStack.series[0].data[0].label.color, '#123456',
    'A configured value color must override automatic stacked contrast.');
const positionedBars = render('vertical', 'configured', 'symcon', 'grouped', matrix,
    { showValues: true, valueLabelPosition: 'insideTop', valueLabelDistance: 12 });
assert.equal(positionedBars.series[0].label.position, 'insideTop');
assert.equal(positionedBars.series[0].label.distance, 12);
assert.equal(positionedBars.series[0].data[0].label.color, '#f4f5f7',
    'Manually placed inside labels must preserve readable contrast.');

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

const timedBar = render('vertical', 'configured', 'ipsview', 'simple', undefined, {
    animationEnabled: true, animationDuration: 800, animationDurationUpdate: 1300,
    animationEasing: 'bounceOut', animationEasingUpdate: 'linear',
    animationDelay: 120, animationDelayUpdate: 240
});
assert.equal(timedBar.animationDuration, 800);
assert.equal(timedBar.animationDurationUpdate, 1300);
assert.equal(timedBar.animationEasing, 'bounceOut');
assert.equal(timedBar.animationEasingUpdate, 'linear');
assert.equal(timedBar.animationDelay, 120);
assert.equal(timedBar.animationDelayUpdate, 240);
const stoppedBar = render('vertical', 'configured', 'symcon', 'simple', undefined, {
    animationEnabled: false, animationDuration: 800, animationDurationUpdate: 1300
});
assert.equal(stoppedBar.animation, false);
assert.equal(stoppedBar.animationDurationUpdate, 0);

// Exercise the shipped selective runtime: adding SVG only for headless rendering
// must not supply BarChart, Grid or DataZoom from the full package.
const nativeWindow = {};
vm.runInNewContext(fs.readFileSync(path.join(__dirname, '..', 'libs/echarts/6.1.0/echarts.bar-category.min.js'), 'utf8'),
    { window: nativeWindow, navigator: { userAgent: 'Node.js' }, console, setTimeout, clearTimeout });
nativeWindow.echarts.use(require('../.tools/echarts-runtime/node_modules/echarts/renderers').SVGRenderer);
const fullEcharts = require('../.tools/echarts-runtime/node_modules/echarts');

function stopPairs(fill) {
    return Array.from(fill.colorStops, stop => [stop.offset, stop.color]);
}
for (const orientation of ['horizontal', 'vertical']) {
    for (const output of ['symcon', 'ipsview']) {
        const signed = render(orientation, 'configured', output, 'stacked', [
            { id: 'p1', category: 'A', series: 'One', value: 20, order: 0 },
            { id: 'p2', category: 'A', series: 'Two', value: 30, order: 1 },
            { id: 'n1', category: 'A', series: 'Three', value: -20, order: 2 },
            { id: 'n2', category: 'A', series: 'Four', value: -30, order: 3 },
            { id: 'zero', category: 'B', series: 'One', value: 0, order: 4 }
        ], { ...gradientStyle, colorMinimum: -50, colorMaximum: 50 });
        const fills = signed.series.map(series => series.data[0].itemStyle.color);
        assert.deepEqual(stopPairs(fills[0]), [[0, '#ffff00'], [1, '#99ff00']]);
        assert.deepEqual(stopPairs(fills[1]), [[0, '#99ff00'], [1, '#00ff00']],
            'Positive stack segments must continue the absolute-axis ramp.');
        assert.deepEqual(stopPairs(fills[2]), [[0, '#ff9900'], [1, '#ffff00']]);
        assert.deepEqual(stopPairs(fills[3]), [[0, '#ff0000'], [1, '#ff9900']],
            'Negative stacks must accumulate independently and retain ascending-axis colors.');
        assert.equal(signed.series[0].data[1].itemStyle.color, '#ffff00', 'Zero values have a finite flat color.');
        assert.equal(signed.series[1].data[1].value, null, 'Sparse cells stay absent, never zero-filled.');
        assert.equal(signed.series[0].data[0].label.color, '#101114', 'Inside labels use the segment midpoint contrast.');
        const engine = nativeWindow.echarts.init(null, null, { renderer: 'svg', ssr: true, width: 800, height: 500 });
        engine.setOption(signed);
        const negativeGraphic = engine.getModel().getSeriesByIndex(3).getData().getItemGraphicEl(0);
        assert.deepEqual(stopPairs(negativeGraphic.style.fill), [[0, '#ff0000'], [1, '#ff9900']]);
        assert.equal(negativeGraphic.style.fill.x2, orientation === 'horizontal' ? 1 : 0);
        assert.equal(negativeGraphic.style.fill.y, orientation === 'vertical' ? 1 : 0);
        const rect = negativeGraphic.getBoundingRect();
        assert.ok(Number.isFinite(rect.x) && Number.isFinite(rect.y) && rect.width > 0 && rect.height > 0,
            'The shipped runtime must lay out a nonempty signed gradient segment.');
        // The compiled bundle owns different Path classes than an externally
        // loaded SVG painter. Its geometry above is genuine, but SVG markup is
        // checked with the official complete runtime sharing its own painter.
        const svgEngine = fullEcharts.init(null, null, { renderer: 'svg', ssr: true, width: 800, height: 500 });
        svgEngine.setOption(signed);
        const svg = svgEngine.renderToSVGString();
        assert.ok(svg.includes('<linearGradient') && svg.includes('stop-color='), 'Official SVG rendering must preserve gradient fills.');
        assert.equal(/NaN|Infinity/.test(svg), false);
        svgEngine.dispose();
        engine.dispose();
        const override = render(orientation, 'configured', output, 'simple', undefined,
            { ...gradientStyle, valueLabelPosition: 'inside', valueColor: '#123456' });
        assert.equal(override.series[0].data[0].label.color, '#123456');
        const lqi = render(orientation, 'configured', output, 'simple', [
            { id: 'lqi', category: 'LQI', value: 127.5, order: 0 }
        ], { ...gradientStyle, colorMaximum: 255 });
        assert.deepEqual(stopPairs(lqi.series[0].data[0].itemStyle.color), [[0, '#ff0000'], [1, '#ffff00']]);
        const fractional = render(orientation, 'configured', output, 'simple', [
            { id: 'fraction', category: 'Fraction', value: 0.3, order: 0 }
        ], { ...gradientStyle, colorMinimum: 0.1, colorMaximum: 0.5 });
        const fractionalFill = fractional.series[0].data[0].itemStyle.color;
        assert.equal(fractionalFill.colorStops.length, 3);
        assert.ok(Math.abs(fractionalFill.colorStops[1].offset - 1 / 3) < 1e-12);
        assert.equal(fractionalFill.colorStops[2].color, '#ffff00');
        const huge = render(orientation, 'configured', output, 'simple', [
            { id: 'small', category: 'Small', value: 0.00001, order: 0 }
        ], { ...gradientStyle, colorMinimum: -1e12, colorMaximum: 1e12 });
        const hugeFill = huge.series[0].data[0].itemStyle.color;
        assert.equal(hugeFill.global, false);
        assert.ok(hugeFill.colorStops.every(stop => Number.isFinite(stop.offset) && stop.offset >= 0 && stop.offset <= 1),
            'Extreme fixed ranges must never create huge gradient coordinates.');
    }
}

for (const count of [72, 100]) {
for (const output of ['symcon', 'ipsview']) {
    for (const orientation of ['horizontal', 'vertical']) {
        for (const mode of ['simple', 'grouped', 'stacked']) {
            const items = Array.from({ length: count }, (_, index) => ({
                id: 'source-' + index, variableID: 88000 + index,
                category: mode === 'simple' ? 'Battery' : 'Category ' + Math.floor(index / 2),
                series: 'Series ' + index % 2, seriesKey: 'series-' + index % 2,
                value: index === count - 1 ? -250 : index + 1, order: index
            }));
            const large = render(orientation, 'configured', output, mode, items, gradientStyle);
            assert.equal(large.dataZoom.length, 2, 'Large category lists need native slider and inside navigation.');
            assert.equal(large.dataZoom[0].id, 'category-scroll');
            assert.ok(large.dataZoom[0].endValue < 16, 'Initial category windows remain readable.');
            const categoryAxis = orientation === 'horizontal' ? large.yAxis : large.xAxis;
            const valueAxis = orientation === 'horizontal' ? large.xAxis : large.yAxis;
            assert.equal(categoryAxis.data.length, mode === 'simple' ? count : count / 2);
            assert.equal(valueAxis.min, -250, 'Offscreen negative values remain part of the shared value scale.');
            large.dataZoom[0].startValue = 5;
            large.refresh();
            assert.equal(large.getUpdatedOption().dataZoom[0].startValue, 5,
                'A current-value update must preserve the category viewport.');
            const engine = nativeWindow.echarts.init(null, null, { renderer: 'svg', ssr: true, width: 800, height: 500 });
            large.useNativeChart(engine);
            const currentOption = large.getUpdatedOption();
            engine.setOption(currentOption);
            const visibleCount = currentOption.dataZoom[0].endValue - currentOption.dataZoom[0].startValue + 1;
            assert.equal(engine.getModel().getSeriesByIndex(0).getData().count(), visibleCount,
                'The actual supplied runtime must filter offscreen categories, not squeeze every bar into the chart.');
            const categoryCount = categoryAxis.data.length;
            engine.dispatchAction({ type: 'dataZoom', dataZoomId: 'category-scroll',
                startValue: categoryCount - visibleCount, endValue: categoryCount - 1 });
            const tail = engine.getModel().getSeriesByIndex(engine.getModel().getSeries().length - 1).getData();
            assert.equal(tail.getRawIndex(tail.count() - 1), categoryCount - 1,
                'Every source must be reachable at the end of the category window.');
            const rawFill = currentOption.series[currentOption.series.length - 1].data[categoryCount - 1].itemStyle.color;
            assert.equal(rawFill.type, 'linear');
            assert.deepEqual(stopPairs(tail.getItemGraphicEl(tail.count() - 1).style.fill), stopPairs(rawFill),
                'Native scrolling must retain full-model gradient endpoints instead of using filtered indexes.');
            tail.each(index => {
                const rawIndex = tail.getRawIndex(index);
                const originalFill = currentOption.series[currentOption.series.length - 1].data[rawIndex].itemStyle.color;
                assert.deepEqual(stopPairs(tail.getItemGraphicEl(index).style.fill), stopPairs(originalFill),
                    'Every positive and negative tail segment retains its original gradient domain.');
            });
            const visibleAxis = engine.getModel().getComponent(orientation === 'horizontal' ? 'yAxis' : 'xAxis').axis;
            const expectedLabels = mode === 'simple' ? Array(visibleCount).fill('Battery')
                : Array.from({ length: visibleCount }, (_, index) => 'Category ' + (categoryCount - visibleCount + index));
            assert.deepEqual(Array.from(visibleAxis.getViewLabels(), entry => entry.formattedLabel), expectedLabels,
                'Scrolled native tick texts must resolve actual category keys, not filtered local indexes.');
            const lastParameter = engine.getModel().getSeriesByIndex(engine.getModel().getSeries().length - 1)
                .getDataParams(categoryCount - 1 - (categoryCount - visibleCount));
            const tooltip = large.tooltip.formatter([lastParameter]);
            assert.ok(tooltip.includes('-250') && !tooltip.includes('source-'),
                'Scrolled tooltips retain signed source values and visible names without internal IDs.');
            large.refresh();
            const updated = large.getUpdatedOption();
            assert.equal(updated.dataZoom[0].startValue, categoryCount - visibleCount,
                'Real native scroll state must survive a current-value refresh.');
            engine.setOption(updated, true);
            const axis = engine.getModel().getComponent(orientation === 'horizontal' ? 'xAxis' : 'yAxis').axis;
            assert.equal(axis.scale.getExtent()[0], -250, 'Scrolling must not rescale away offscreen negatives.');
            assert.equal(/NaN|Infinity/.test(engine.renderToSVGString()), false);
            large.useNativeChart(null);
            engine.dispose();
        }
    }
}
}


const reorderingItems = Array.from({ length: 100 }, (_, index) => ({
    id: 'source-' + index, category: 'Duplicate label', value: index, order: index
}));
const reordering = render('horizontal', 'descending', 'symcon', 'simple', reorderingItems, gradientStyle);
const reorderingEngine = nativeWindow.echarts.init(null, null, { renderer: 'svg', ssr: true, width: 800, height: 500 });
reordering.useNativeChart(reorderingEngine);
reorderingEngine.setOption(reordering);
reorderingEngine.dispatchAction({ type: 'dataZoom', dataZoomId: 'category-scroll', startValue: 10, endValue: 20 });
const anchor = reordering.yAxis.data[10];
reorderingItems[0].value = 200;
reordering.refresh();
const reordered = reordering.getUpdatedOption();
assert.equal(reordered.yAxis.data[reordered.dataZoom[0].startValue], anchor,
    'Resorting must retain the visible source ID even when every visible category name is identical.');
assert.equal(reordered.yAxis.axisLabel.formatter(anchor, reordered.dataZoom[0].startValue), 'Duplicate label');
assert.deepEqual(stopPairs(reordered.series[0].data[0].itemStyle.color),
    [[0, '#ff0000'], [0.25, '#ffff00'], [0.5, '#00ff00'], [1, '#00ff00']],
    'Updated and resorted values retain the same fixed ramp; values above its maximum stay green.');
reorderingEngine.dispose();

const selectable = render('horizontal', 'configured', 'ipsview', 'stacked', [
    { id: 'first', category: 'Room', series: 'A', seriesKey: 'a', value: 30, order: 0 },
    { id: 'second', category: 'Room', series: 'B', seriesKey: 'b', value: 40, order: 1 }
], gradientStyle);
const selectionEngine = nativeWindow.echarts.init(null, null, { renderer: 'svg', ssr: true, width: 800, height: 500 });
selectable.useNativeChart(selectionEngine);
selectionEngine.setOption(selectable);
selectionEngine.dispatchAction({ type: 'legendToggleSelect', name: selectable.series[0].name });
const withoutFirst = selectable.getUpdatedOption();
assert.equal(withoutFirst.legend.selected[selectable.series[0].name], false);
assert.deepEqual(stopPairs(withoutFirst.series[1].data[0].itemStyle.color), [[0, '#ff0000'], [1, '#ffcc00']],
    'Hiding a stack series must move the remaining gradient to its visible axis position.');
selectionEngine.setOption(withoutFirst, true);
selectable.refresh();
assert.equal(selectable.getUpdatedOption().legend.selected[selectable.series[0].name], false,
    'A gradient update must preserve the legend selection.');
selectionEngine.dispose();

const densityItems = Array.from({ length: 72 }, (_, index) => ({id: 'density-' + index,
    category: 'Battery ' + index, series: 'Sensor ' + index % 2, seriesKey: 'sensor-' + index % 2,
    value: index + 1, order: index}));
function visibleCategories(option, orientation) {
    const slider = option.dataZoom.find(entry => entry.id === 'category-scroll');
    return slider ? slider.endValue - slider.startValue + 1 : (orientation === 'horizontal' ? option.yAxis : option.xAxis).data.length;
}
for (const output of ['symcon', 'ipsview']) {
    for (const orientation of ['horizontal', 'vertical']) {
        for (const mode of ['simple', 'grouped', 'stacked']) {
            const items = densityItems.map((item, index) => ({...item,
                category: mode === 'simple' ? item.category : 'Room ' + Math.floor(index / 2)}));
            const dimensions = {width: 1800, height: 1200};
            const thin = render(orientation, 'configured', output, mode, items,
                {...gradientStyle, barWidthPercent: 20}, false, dimensions);
            const thick = render(orientation, 'configured', output, mode, items,
                {...gradientStyle, barWidthPercent: 100}, false, dimensions);
            assert.ok(visibleCategories(thin, orientation) > visibleCategories(thick, orientation),
                'Thinner bars must expose more categories in both orientations, outputs and modes.');
            if (mode !== 'grouped') {
                assert.ok(visibleCategories(thin, orientation) > 16,
                    'A tall or wide chart must not retain the arbitrary sixteen-category viewport cap.');
            }
            const largeText = render(orientation, 'configured', output, mode, items,
                {...gradientStyle, barWidthPercent: 20, axisFontSizePercent: 200, valueFontSizePercent: 200}, false, dimensions);
            assert.ok(visibleCategories(largeText, orientation) < visibleCategories(thin, orientation),
                'Larger category/value text must protect a larger minimum category span.');
            const engine = nativeWindow.echarts.init(null, null, {renderer: 'svg', ssr: true, ...dimensions});
            engine.setOption(thin);
            const axisName = orientation === 'horizontal' ? 'yAxis' : 'xAxis';
            const thinBand = engine.getModel().getComponent(axisName).axis.getBandWidth();
            assert.equal(engine.getModel().getSeriesByIndex(0).getData().count(), visibleCategories(thin, orientation));
            engine.setOption(thick, true);
            const thickBand = engine.getModel().getComponent(axisName).axis.getBandWidth();
            assert.ok(thinBand < thickBand, 'Native category centers, not just painted thickness, must move closer together.');
            assert.ok(thinBand >= 12 * 1.5, 'Normal-font category rows must not overlap.');
            engine.dispose();
        }
        const smallSet = render(orientation, 'configured', output, 'simple', densityItems.slice(0, 13),
            {barWidthPercent: 100, showValues: true}, false, {width: 420, height: 340});
        assert.ok(visibleCategories(smallSet, orientation) < 13 && smallSet.dataZoom.length === 2,
            'Small charts must navigate even fewer than sixteen categories when text/bars need more room.');
    }
}

for (const orientation of ['horizontal', 'vertical']) {
    const dense = render(orientation, 'configured', 'ipsview', 'simple', densityItems,
        {...gradientStyle, barWidthPercent: 20}, false, {width: 900, height: 900});
    const engine = nativeWindow.echarts.init(null, null, {renderer: 'svg', ssr: true, width: 900, height: 900});
    dense.useNativeChart(engine);
    engine.setOption(dense);
    const initialCount = visibleCategories(dense, orientation);
    engine.dispatchAction({type: 'dataZoom', dataZoomId: 'category-scroll', startValue: 10, endValue: 10 + initialCount - 1});
    const categoryKeys = orientation === 'horizontal' ? dense.yAxis.data : dense.xAxis.data;
    const anchorID = categoryKeys[10];
    dense.resize(450, 400);
    const resized = dense.getUpdatedOption();
    assert.ok(visibleCategories(resized, orientation) < initialCount, 'Resize must recompute category density.');
    const updatedKeys = orientation === 'horizontal' ? resized.yAxis.data : resized.xAxis.data;
    assert.equal(updatedKeys[resized.dataZoom[0].startValue], anchorID, 'Resize must retain the visible source ID.');
    engine.resize({width: 450, height: 400});
    engine.setOption(resized, true);
    const count = visibleCategories(resized, orientation);
    engine.dispatchAction({type: 'dataZoom', dataZoomId: 'category-scroll', startValue: 72 - count, endValue: 71});
    const last = engine.getModel().getSeriesByIndex(0).getData();
    assert.equal(last.getRawIndex(last.count() - 1), 71, 'Every category remains reachable at the new density.');
    dense.refresh();
    assert.equal(dense.getUpdatedOption().dataZoom[0].startValue, 72 - count);
    engine.dispose();
}

console.log('Category Bar renderer layout verified.');
