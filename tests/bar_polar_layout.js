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
assert.equal(radial.radiusAxis.min, undefined, 'Existing Polar Tiles must retain automatic scale by default.');
assert.equal(radial.series[0].showBackground, false, 'Background tracks must be off by default.');
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

const fixedStyle = {
    ...state.chart.polar.style,
    valueAxisRangeMode: 'manual', valueAxisMinimum: -5, valueAxisMaximum: 25,
    showBarBackground: true, barBackgroundColor: '#778899', barBackgroundOpacityPercent: 40
};
window.handleMessage({
    status: 'ready', chart: {
        ...state.chart,
        polar: { ...state.chart.polar, style: fixedStyle }
    }
});
const fixedRadial = options.at(-1);
assert.equal(fixedRadial.radiusAxis.min, -5);
assert.equal(fixedRadial.radiusAxis.max, 25);
assert.equal(fixedRadial.radiusAxis.startValue, -5,
    'Radial bars must also start at the configured scale minimum.');
assert.equal(fixedRadial.series[0].showBackground, true);
assert.equal(fixedRadial.series[0].backgroundStyle.color, '#778899');
assert.equal(fixedRadial.series[0].backgroundStyle.opacity, 0.4);
window.handleMessage({
    status: 'ready', chart: {
        ...state.chart,
        polar: { ...state.chart.polar, style: { ...fixedStyle, mode: 'tangential' } }
    }
});
const fixedTangential = options.at(-1);
assert.equal(fixedTangential.angleAxis.min, -5);
assert.equal(fixedTangential.angleAxis.max, 25);
assert.equal(fixedTangential.angleAxis.startValue, -5);
assert.equal(fixedTangential.series[0].showBackground, true);
const offsetItems = [
    { id: 'outside', label: 'Außen', value: 12.1, decimals: 1, color: '', order: 0 },
    { id: 'bath', label: 'Bad', value: 22.9, decimals: 1, color: '', order: 1 },
    { id: 'office', label: 'Büro', value: 24.3, decimals: 1, color: '', order: 2 }
];
window.handleMessage({
    status: 'ready', chart: {
        ...state.chart,
        polar: {
            ...state.chart.polar,
            style: {
                ...fixedStyle, mode: 'tangential', sortOrder: 'configured',
                valueAxisMinimum: 5, valueAxisMaximum: 100
            }
        },
        items: offsetItems
    }
});
const offsetTangential = options.at(-1);
assert.equal(offsetTangential.angleAxis.min, 5);
assert.equal(offsetTangential.angleAxis.max, 100);
assert.equal(offsetTangential.angleAxis.startValue, 5,
    'Concentric arcs must start at the configured scale minimum instead of zero.');
assert.deepEqual(Array.from(offsetTangential.series[0].data, item => item.value), [12.1, 22.9, 24.3],
    'The rendered data and displayed values must retain their real values.');
assert.equal(offsetTangential.series[0].label.formatter({ value: 12.1, dataIndex: 0 }),
    Number(12.1).toLocaleString(undefined, { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + ' °C',
    'Concentric value labels must display the original values.');
window.handleMessage({
    status: 'ready', chart: {
        ...state.chart,
        polar: {
            ...state.chart.polar,
            style: {
                ...fixedStyle,
                valueAxisRangeMode: 'auto',
                barBackgroundColor: '',
                barBackgroundOpacityPercent: 0
            }
        }
    }
});
const automaticTracks = options.at(-1);
assert.equal(automaticTracks.radiusAxis.min, undefined, 'Automatic scale must not keep fixed limits.');
assert.equal(automaticTracks.series[0].backgroundStyle.color, palette.border,
    'Automatic track color must follow the active theme.');
assert.equal(automaticTracks.series[0].backgroundStyle.opacity, 0,
    'Zero track opacity must stay fully transparent.');

chartElement.clientWidth = 1000;
chartElement.clientHeight = 700;
resizeCallback();
assert.equal(resized, 1, 'A size change must resize the ECharts instance.');
assertProtectedGeometry({
    ...automaticTracks,
    ...options.at(-1),
    angleAxis: { ...automaticTracks.angleAxis, ...options.at(-1).angleAxis }
}, 1000, 700, 70);
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
    for (const option of [radial, tangential, fixedRadial, fixedTangential]) {
        const realChart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 625, height: 560 });
        realChart.setOption(option);
        assert.ok(realChart.renderToSVGString().includes('<svg'), 'Both polar modes must render in ECharts.');
        if (option === fixedRadial || option === fixedTangential) {
            const tracks = realChart.getZr().storage.getDisplayList().filter(
                element => element.type === 'sector' && element.style.fill === '#778899'
            );
            assert.equal(tracks.length, 3, 'Each Polar value must have one ECharts background track.');
            assert.ok(tracks.every(element => element.style.opacity === 0.4),
                'The configured background-track opacity must reach the rendered Polar sectors.');
        }
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
    const offsetChart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 625, height: 560 });
    offsetChart.setOption(offsetTangential);
    const scaleAxis = offsetChart.getModel().getComponent('angleAxis').axis;
    const expectedStart = -scaleAxis.dataToCoord(5) * Math.PI / 180;
    const offsetData = offsetChart.getModel().getSeriesByIndex(0).getData();
    for (let index = 0; index < offsetData.count(); index++) {
        const sector = offsetData.getItemLayout(index);
        assert.ok(Math.abs(sector.startAngle - expectedStart) < 0.000001,
            'Every concentric arc must start at the 5-unit scale tick.');
    }
    offsetChart.dispose();
    const radialChart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 625, height: 560 });
    radialChart.setOption(fixedRadial);
    const radiusAxis = radialChart.getModel().getComponent('radiusAxis').axis;
    const expectedRadiusStart = radiusAxis.dataToCoord(-5);
    const radialData = radialChart.getModel().getSeriesByIndex(0).getData();
    for (let index = 0; index < radialData.count(); index++) {
        assert.ok(Math.abs(radialData.getItemLayout(index).r0 - expectedRadiusStart) < 0.000001,
            'Every radial bar must start at its fixed-scale minimum.');
    }
    radialChart.dispose();
}

window.handleMessage({ status: 'error', error: 'Configure valid Polar sources.' });
assert.equal(chartElement.hidden, true);
assert.equal(errorElement.hidden, false);

console.log('Polar Bar layout verified.');
