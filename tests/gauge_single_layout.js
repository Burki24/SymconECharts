'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '..', 'EChartsGaugeSingle', 'visualization', 'app.js'), 'utf8');
const designSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-design.js'), 'utf8');
const palette = {
    background: '#202020', text: '#ffffff', muted: '#aaaaaa', subtle: '#888888',
    border: '#cccccc', track: '#444444', surface: '#333333', accent: '#55cbb5'
};

function render(mode, width, height, preset, style = {}, reducedMotion = false, observeResize = false) {
    const chartElement = {
        clientWidth: width,
        clientHeight: height,
        hidden: false,
        setAttribute: () => {}
    };
    const errorElement = { hidden: true, textContent: '' };
    let option;
    let resizeCallback;
    let resized = 0;
    let optionUpdates = 0;
    let chartWidth = width;
    let chartHeight = height;
    const chart = {
        setOption: next => { option = next; optionUpdates++; },
        getWidth: () => chartWidth,
        getHeight: () => chartHeight,
        resize: () => {
            resized++;
            chartWidth = chartElement.clientWidth;
            chartHeight = chartElement.clientHeight;
        }
    };
    const window = {
        SYMC_VISUALIZATION: {
            mode,
            state: {
                status: 'ready',
                chart: {
                    theme: 'dark', value: 50,
                    gauge: { minimum: 0, maximum: 100, title: 'Wind speed', unit: 'm/s', decimals: 1, preset, style }
                }
            },
            options: { echartsThemes: { dark: palette } }
        },
        echarts: { init: () => chart },
        ResizeObserver: observeResize ? class {
            constructor(callback) { resizeCallback = callback; }
            observe() {}
        } : undefined,
        matchMedia: query => ({ matches: reducedMotion && query === '(prefers-reduced-motion: reduce)' }),
        addEventListener: () => {}
    };
    const document = {
        getElementById: id => id === 'echarts-gauge-chart' ? chartElement : errorElement
    };
    const context = { window, document, ResizeObserver: window.ResizeObserver };
    vm.runInNewContext(designSource, context);
    vm.runInNewContext(source, context);
    assert.ok(option, 'The Gauge Single option must be rendered.');
    if (observeResize) {
        Object.defineProperty(option, 'resizeTest', { value: {
            notify: () => resizeCallback(),
            resizeWidth: next => { chartElement.clientWidth = next; resizeCallback(); },
            get resized() { return resized; },
            get optionUpdates() { return optionUpdates; }
        } });
    }
    return option;
}

for (const mode of ['symcon', 'ipsview']) {
    for (const preset of ['basic', 'simple', 'progress', 'speed']) {
        const width = 620;
        const height = 630;
        const option = render(mode, width, height, preset, { plateShape: 'circle' });
        const series = option.series[0];
        const plate = option.graphic.find(element => element.id === 'gauge-plate');
        assert.ok(series.radius > (preset === 'speed' ? 225 : 215), `${mode} ${preset} should use more tile space.`);
        assert.ok(plate.shape.cx - plate.shape.r >= 8, `${mode} ${preset} plate must stay inside the left edge.`);
        assert.ok(plate.shape.cx + plate.shape.r <= width - 8, `${mode} ${preset} plate must stay inside the right edge.`);
        assert.ok(plate.shape.cy - plate.shape.r >= 8, `${mode} ${preset} plate must stay inside the top edge.`);
        assert.ok(plate.shape.cy + plate.shape.r <= height - 8, `${mode} ${preset} plate must stay inside the bottom edge.`);
        if (preset === 'speed') {
            assert.equal(series.pointer.itemStyle.shadowColor, 'rgba(85,203,181,0.45)',
                'Centralized color transparency must preserve the existing Gauge shadow.');
        }
    }
}

const narrow = render('ipsview', 320, 240, 'simple', { plateShape: 'circle' });
const resizeGauge = render('symcon', 620, 630, 'simple', {}, false, true).resizeTest;
resizeGauge.notify();
assert.equal(resizeGauge.resized, 0, 'An unchanged Gauge notification must not cancel animation.');
assert.equal(resizeGauge.optionUpdates, 1, 'An unchanged Gauge notification must not rebuild options.');
resizeGauge.resizeWidth(500);
assert.equal(resizeGauge.resized, 1, 'A genuine Gauge width change must resize.');
assert.equal(resizeGauge.optionUpdates, 2, 'A genuine Gauge width change must rebuild its responsive option.');
const reducedGauge = render('ipsview', 620, 630, 'simple', { plateShape: 'circle' }, true);
assert.equal(reducedGauge.animation, false, 'Gauge Single must keep honoring reduced-motion preferences.');
assert.equal(reducedGauge.animationDuration, 0);
assert.ok(narrow.series[0].radius > 0);
assert.ok(narrow.graphic[0].shape.cx - narrow.graphic[0].shape.r >= 0);
assert.ok(narrow.graphic[0].shape.cy - narrow.graphic[0].shape.r >= 0);

const customized = render('ipsview', 620, 630, 'simple', {
    plateShape: 'circle', gaugeRadiusPercent: 150, gaugeOffsetXPercent: 20
});
assert.ok(customized.series[0].radius > narrow.series[0].radius);
assert.ok(customized.series[0].radius < 300, 'An oversized custom design must not receive extra automatic zoom.');

const background = render('ipsview', 620, 630, 'simple', {
    plateShape: 'circle', plateBackgroundEnabled: true,
    plateBackgroundImage: `data:image/svg+xml;base64,${Buffer.from('<svg/>').toString('base64')}`,
    plateBackgroundAspectRatio: 1, plateBackgroundFit: 'contain', plateBackgroundOpacityPercent: 60
});
assert.equal(background.graphic.find(element => element.id === 'gauge-plate-background').children[0].style.opacity,
    0.6, 'Gauge Single must retain its configured plate opacity in IPSView.');

process.stdout.write('Gauge Single responsive layout verified for tile and IPSView.\n');
