'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(
    path.join(__dirname, '..', 'EChartsBarPolar', 'visualization', 'app.js'), 'utf8'
);
const designSource = fs.readFileSync(
    path.join(__dirname, '..', 'libs', 'echarts-design.js'), 'utf8'
);
const chartElement = { hidden: false, clientWidth: 625, clientHeight: 560 };
const errorElement = { hidden: true, textContent: '' };
const options = [];
let initialized = 0;
let resizeCallback = null;
let resized = 0;
const chart = {
    setOption: next => { options.push(next); },
    clear: () => {}, resize: () => { resized++; }, dispose: () => {}
};
const palette = {
    background: '#111111', text: '#eeeeee', muted: '#aaaaaa', border: '#777777',
    accent: '#55cbb5', seriesColors: ['#123456', '#abcdef']
};
const state = {
    status: 'ready',
    chart: {
        theme: 'dark',
        polar: {
            title: 'Polar test', unit: '°C', decimals: 1,
            style: {
                mode: 'radial', sortOrder: 'descending', showCategoryLabels: true,
                showValues: true, showGrid: true, barWidthPercent: 60,
                innerRadiusPercent: 12, outerRadiusPercent: 76,
                startAngle: 90, clockwise: true, roundCaps: false
            }
        },
        items: [
            { id: 'variable-1', label: '<Room>', value: 5, decimals: 1, color: '', order: 0 },
            { id: 'variable-2', label: '<Room>', value: 10, decimals: 0, color: '#ff5500', order: 1 },
            { id: 'variable-3', label: 'Outside', value: -3, decimals: 2, color: '', order: 2 }
        ]
    }
};
const window = {
    SYMC_VISUALIZATION: {
        mode: 'symcon', state,
        options: { echartsThemes: { auto: palette, dark: palette }, tileHeaderVisible: true },
        translations: {}
    },
    echarts: { init: () => { initialized++; return chart; } },
    ResizeObserver: class {
        constructor(callback) { resizeCallback = callback; }
        observe() {}
    },
    addEventListener: () => {}
};
const document = {
    getElementById: id => id === 'echarts-bar-polar-chart' ? chartElement : errorElement,
    createElement: () => ({ style: {}, remove: () => {} }),
    body: { appendChild: () => {} }
};
vm.runInNewContext(designSource, { window, document, console });
vm.runInNewContext(source, { window, document, console, ResizeObserver: window.ResizeObserver });

assert.equal(initialized, 1);
function pixels(value, basis) {
    return typeof value === 'string' ? parseFloat(value) * basis / 100 : value;
}
function assertProtectedGeometry(option, width, height, topClearance) {
    const centerX = pixels(option.polar.center[0], width);
    const centerY = pixels(option.polar.center[1], height);
    const outerRadius = pixels(option.polar.radius[1], Math.min(width, height) / 2);
    const categoryAxis = option.angleAxis.type === 'category' ? option.angleAxis : option.radiusAxis;
    const sideClearance = categoryAxis.axisLabel.width + categoryAxis.axisLabel.margin + 8;
    assert.ok(centerY - outerRadius >= topClearance, 'Polar ring must clear the tile heading and scale.');
    assert.ok(centerY + outerRadius <= height - Math.min(45, height * 0.14),
        'Polar ring must leave room for bottom labels.');
    assert.ok(centerX - outerRadius >= sideClearance, 'Polar ring must leave room for left labels.');
    assert.ok(centerX + outerRadius <= width - sideClearance, 'Polar ring must leave room for right labels.');
}
const radial = options.at(-1);
assertProtectedGeometry(radial, 625, 560, 108);
assert.equal(radial.series[0].coordinateSystem, 'polar');
assert.equal(radial.angleAxis.type, 'category');
assert.equal(radial.radiusAxis.type, 'value');
assert.deepEqual(Array.from(radial.angleAxis.data), ['<Room>', '<Room>', 'Outside']);
assert.ok(radial.angleAxis.axisLabel.margin >= 20, 'Radial category labels need room outside the outer ring.');
assert.deepEqual(Array.from(radial.series[0].data, item => item.value), [10, 5, -3]);
assert.equal(radial.series[0].data[0].itemStyle.color, '#ff5500');
assert.equal(radial.series[0].data[1].itemStyle.color, '#abcdef');
assert.equal(radial.angleAxis.startAngle, 90);
assert.equal(radial.series[0].label.rotate, 0, 'Polar value labels must remain horizontally readable.');
assert.equal(radial.series[0].label.position, 'middle', 'Value anchors must be centered inside their bars.');
assert.equal(radial.series[0].data[1].label.color, '#111111', 'Light bars need dark value text.');
assert.equal(radial.series[0].data[2].label.color, '#eeeeee', 'Dark bars need light value text.');
assert.equal(
    radial.series[0].label.formatter({ value: 5, dataIndex: 1 }),
    Number(5).toLocaleString(undefined, { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + ' °C'
);
assert.equal(radial.series[0].label.formatter({ value: 10, dataIndex: 0 }), '10 °C');
assert.equal(
    radial.series[0].label.formatter({ value: -3, dataIndex: 2 }),
    Number(-3).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' °C'
);
assert.match(radial.tooltip.formatter({ dataIndex: 0 }), /&lt;Room&gt;/);
assert.doesNotMatch(radial.tooltip.formatter({ dataIndex: 0 }), /<Room>/);

const tangentialState = {
    status: 'ready',
    chart: {
        theme: 'dark',
        polar: {
            title: '', unit: '%', decimals: 0,
            style: {
                mode: 'tangential', sortOrder: 'configured', showCategoryLabels: true,
                showValues: false, showGrid: false, barWidthPercent: 80,
                innerRadiusPercent: 20, outerRadiusPercent: 90,
                startAngle: 180, clockwise: false, roundCaps: true
            }
        },
        items: state.chart.items
    }
};
window.handleMessage(tangentialState);
const tangential = options.at(-1);
assert.equal(initialized, 1, 'Live updates must reuse the chart instance.');
assert.equal(tangential.angleAxis.type, 'value');
assert.equal(tangential.radiusAxis.type, 'category');
assert.deepEqual(Array.from(tangential.radiusAxis.data), ['<Room>', '<Room>', 'Outside']);
assert.ok(tangential.radiusAxis.axisLabel.margin >= 20, 'Concentric category labels need axis spacing.');
assert.equal(tangential.angleAxis.clockwise, false);
assert.equal(tangential.series[0].roundCap, true);
assert.equal(tangential.series[0].label.show, false);
assert.equal(tangential.series[0].label.rotate, 0);
assert.equal(tangential.series[0].label.position, 'middle');
assertProtectedGeometry(tangential, 625, 560, 70);
assert.ok(tangential.polar.radius[0] > 0 && tangential.polar.radius[0] < tangential.polar.radius[1]);
assert.equal(tangential.angleAxis.splitLine.show, false);

chartElement.clientWidth = 1000;
chartElement.clientHeight = 700;
resizeCallback();
assert.equal(resized, 1, 'A size change must resize the ECharts instance.');
assertProtectedGeometry({ ...tangential, ...options.at(-1) }, 1000, 700, 70);
assert.ok(options.at(-1).polar.radius[1] > tangential.polar.radius[1], 'The ring must grow with the tile.');

chartElement.clientWidth = 300;
chartElement.clientHeight = 300;
window.handleMessage({
    status: 'ready', chart: {
        ...state.chart,
        polar: { ...state.chart.polar, title: '' }
    }
});
const compact = options.at(-1);
assertProtectedGeometry(compact, 300, 300, 70);
assert.ok(compact.polar.radius[1] >= 80,
    'Compact Symcon tiles must keep a legible Polar diameter instead of collapsing to a tiny ring.');
assert.ok(compact.angleAxis.axisLabel.width < radial.angleAxis.axisLabel.width,
    'Compact category labels must use less side clearance.');
assert.equal(compact.radiusAxis.axisLabel.show, false,
    'Compact Polar tiles must hide scale numbers that collide with bar values.');
window.handleMessage({
    status: 'ready', chart: {
        ...state.chart,
        polar: {
            ...state.chart.polar,
            title: '',
            style: { ...state.chart.polar.style, showValues: false }
        }
    }
});
assert.equal(options.at(-1).radiusAxis.axisLabel.show, true,
    'Compact Polar tiles without bar values may keep the numeric scale.');
window.handleMessage({
    status: 'ready', chart: {
        ...state.chart,
        polar: { ...state.chart.polar, title: '' },
        items: [
            { id: 'outside', label: 'Außen', value: 9.5, decimals: 1, color: '#586ee0', order: 0 },
            { id: 'bath', label: 'Bad', value: 22.6, decimals: 1, color: '#bed939', order: 1 },
            { id: 'office', label: 'Büro', value: 24.5, decimals: 1, color: '#555674', order: 2 }
        ]
    }
});
const compactUserValues = options.at(-1);

const localECharts = path.join(__dirname, '..', '.tools', 'echarts-runtime', 'node_modules', 'echarts');
if (fs.existsSync(localECharts)) {
    const echarts = require(localECharts);
    const compactChart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 300, height: 300 });
    compactChart.setOption(compact);
    const compactTexts = compactChart.getZr().storage.getDisplayList()
        .filter(element => element.type === 'tspan')
        .map(element => element.style.text);
    assert.ok(!compactTexts.includes('12'), 'Compact Polar tiles must not render overlapping value-axis ticks.');
    assert.ok(compactTexts.includes('10 °C'), 'Compact Polar tiles must keep the bar values readable.');
    compactChart.dispose();
    const userChart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 300, height: 300 });
    userChart.setOption(compactUserValues);
    const valueLabels = userChart.getZr().storage.getDisplayList()
        .filter(element => element.type === 'tspan' && /°C$/.test(element.style.text))
        .map(element => {
            const bounds = element.getBoundingRect().clone();
            bounds.applyTransform(element.getComputedTransform());
            return bounds;
        });
    assert.equal(valueLabels.length, 3, 'All three values from the compact user scenario must remain visible.');
    for (let left = 0; left < valueLabels.length; left++) {
        for (let right = left + 1; right < valueLabels.length; right++) {
            assert.equal(valueLabels[left].intersect(valueLabels[right]), false,
                'Compact user values must not overlap one another.');
        }
    }
    userChart.dispose();
    for (const option of [radial, tangential]) {
        const realChart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 625, height: 560 });
        realChart.setOption(option);
        assert.ok(realChart.renderToSVGString().includes('<svg'), 'Both polar modes must render in ECharts.');
        if (option === radial) {
            const categoryLabels = realChart.getZr().storage.getDisplayList().filter(
                element => element.type === 'tspan' && ['<Room>', 'Outside'].includes(element.style.text)
            );
            assert.equal(categoryLabels.length, 3);
            for (const element of categoryLabels) {
                const bounds = element.getBoundingRect();
                assert.ok(bounds.x >= 8 && bounds.x + bounds.width <= 617, 'Categories must stay inside tile sides.');
                assert.ok(bounds.y >= 100 && bounds.y + bounds.height <= 548, 'Categories must clear the title and tile bottom.');
            }
            const valueSectors = realChart.getZr().storage.getDisplayList().filter(
                element => element.type === 'sector' && element.getTextContent()
            );
            assert.ok(valueSectors.length >= 3, 'Radial values must render as polar bar labels.');
            assert.ok(
                valueSectors.every(element => element.textConfig.rotation === 0),
                'Rendered polar values must not follow the bar angle.'
            );
            assert.ok(
                valueSectors.every(element => element.textConfig.position === 'middle'),
                'Rendered polar values must be centered inside their bars.'
            );
            const renderedTextColors = valueSectors.map(element => element.getTextContent().style.fill);
            assert.ok(renderedTextColors.includes('#111111'), 'Light bars must render dark value text.');
            assert.ok(renderedTextColors.includes('#eeeeee'), 'Dark bars must render light value text.');
        }
        realChart.dispose();
    }
}

window.handleMessage({ status: 'error', error: 'Configure valid Polar sources.' });
assert.equal(chartElement.hidden, true);
assert.equal(errorElement.hidden, false);

console.log('Polar Bar layout verified.');
