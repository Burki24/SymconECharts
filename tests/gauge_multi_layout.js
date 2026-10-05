'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '..', 'EChartsGaugeMulti', 'visualization', 'app.js'), 'utf8');
const palette = {
    background: '#202020',
    text: '#ffffff',
    muted: '#aaaaaa',
    border: '#cccccc',
    track: '#444444',
    accent: '#55cbb5'
};

function render(mode, width, height, count, title = '') {
    const chartElement = { clientWidth: width, clientHeight: height, hidden: false };
    const errorElement = { hidden: true, textContent: '' };
    let option;
    const window = {
        SYMC_VISUALIZATION: {
            mode,
            state: {
                status: 'ready',
                chart: {
                    theme: 'dark',
                    gauge: { title, style: {} },
                    items: Array.from({ length: count }, (_, index) => ({
                        id: `variable-${index}`,
                        gauge: { minimum: 0, maximum: 100, label: `Source ${index}` },
                        value: 50
                    }))
                }
            },
            options: { echartsThemes: { dark: palette } }
        },
        echarts: {
            init: () => ({ setOption: next => { option = next; } })
        },
        addEventListener: () => {}
    };
    const document = {
        getElementById: id => id === 'echarts-gauge-chart' ? chartElement : errorElement
    };

    vm.runInNewContext(source, { window, document });
    assert.ok(option, 'The chart should be rendered.');
    return option;
}

function topOfFirstGauge(option) {
    return option.series[0].center[1] - option.series[0].radius;
}

const tallTile = render('symcon', 416, 1048, 3);
const tallIPSView = render('ipsview', 416, 1048, 3);
assert.ok(topOfFirstGauge(tallTile) >= 64, 'The native header must remain clear.');
assert.ok(topOfFirstGauge(tallIPSView) < 64, 'IPSView must not reserve the native header.');

const titledTile = render('symcon', 416, 1048, 3, 'Climate');
const titledIPSView = render('ipsview', 416, 1048, 3, 'Climate');
assert.ok(titledTile.title.top >= 64, 'The chart title must follow the native header.');
assert.equal(titledIPSView.title.top, 6, 'The IPSView chart title must retain its original position.');

const crowdedTile = render('symcon', 320, 192, 16);
assert.ok(topOfFirstGauge(crowdedTile) >= 64, 'A crowded tile must not overlap the header.');

process.stdout.write('Gauge Multi tile header layout verified.\n');
