'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '..', 'EChartsGaugeSingle', 'visualization', 'app.js'), 'utf8');
const palette = {
    background: '#202020', text: '#ffffff', muted: '#aaaaaa', subtle: '#888888',
    border: '#cccccc', track: '#444444', surface: '#333333', accent: '#55cbb5'
};

function render(mode, width, height, preset, style = {}) {
    const chartElement = {
        clientWidth: width,
        clientHeight: height,
        hidden: false,
        setAttribute: () => {}
    };
    const errorElement = { hidden: true, textContent: '' };
    let option;
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
        echarts: { init: () => ({ setOption: next => { option = next; } }) },
        addEventListener: () => {}
    };
    const document = {
        getElementById: id => id === 'echarts-gauge-chart' ? chartElement : errorElement
    };
    vm.runInNewContext(source, { window, document });
    assert.ok(option, 'The Gauge Single option must be rendered.');
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
    }
}

const narrow = render('ipsview', 320, 240, 'simple', { plateShape: 'circle' });
assert.ok(narrow.series[0].radius > 0);
assert.ok(narrow.graphic[0].shape.cx - narrow.graphic[0].shape.r >= 0);
assert.ok(narrow.graphic[0].shape.cy - narrow.graphic[0].shape.r >= 0);

const customized = render('ipsview', 620, 630, 'simple', {
    plateShape: 'circle', gaugeRadiusPercent: 150, gaugeOffsetXPercent: 20
});
assert.ok(customized.series[0].radius > narrow.series[0].radius);
assert.ok(customized.series[0].radius < 300, 'An oversized custom design must not receive extra automatic zoom.');

process.stdout.write('Gauge Single responsive layout verified for tile and IPSView.\n');
