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

function render(mode, width, height, count, title = '', preset = 'multi-title') {
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
                    gauge: { title, preset, style: {} },
                    items: Array.from({ length: count }, (_, index) => ({
                        id: `variable-${index}`,
                        gauge: {
                            minimum: index * 10,
                            maximum: index * 10 + 100,
                            label: `Source ${index}`,
                            unit: `u${index}`,
                            decimals: 1
                        },
                        value: index * 10 + 50
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

for (const preset of ['ring-grid', 'ring-concentric']) {
    for (const count of [2, 16]) {
        const tile = render('symcon', 416, 1048, count, '', preset);
        const ipsView = render('ipsview', 416, 1048, count, '', preset);
        assert.equal(tile.series.length, count, `${preset} must render all ${count} sources.`);
        assert.ok(topOfFirstGauge(tile) >= 64, `${preset} must clear the native header.`);
        assert.ok(topOfFirstGauge(ipsView) < topOfFirstGauge(tile), `${preset} must use the IPSView space.`);
        tile.series.forEach((series, index) => {
            assert.equal(series.id, `variable-${index}`);
            assert.equal(series.min, index * 10);
            assert.equal(series.max, index * 10 + 100);
            assert.equal(series.data[0].value, index * 10 + 50);
            assert.equal(series.progress.show, true);
            assert.equal(series.pointer.show, false);
        });
        assert.equal(
            new Set(tile.series.map(series => series.itemStyle.color)).size,
            count,
            `${preset} needs a distinct color per source.`
        );
        if (preset === 'ring-grid') {
            assert.notDeepEqual(
                Array.from(tile.series[0].center), Array.from(tile.series[1].center),
                'Grid rings need separate cells.'
            );
            assert.match(tile.series[1].detail.formatter(), /^60[,.]0 u1$/);
        } else {
            assert.equal(tile.series[0].center[0], tile.series[1].center[0], 'Concentric rings share a center.');
            assert.ok(tile.series[0].radius > tile.series[1].radius, 'Concentric radii must decrease.');
            assert.equal(tile.graphic.length, count * 3, 'Concentric rings need a value legend.');
            assert.ok(tile.graphic.some(element => element.style && /^60[,.]0 u1$/.test(element.style.text)));
        }
    }
    const titledRingTile = render('symcon', 416, 420, 3, 'Climate', preset);
    const titledRingIPSView = render('ipsview', 416, 420, 3, 'Climate', preset);
    assert.ok(titledRingTile.title.top >= 64, `${preset} title must clear the native header.`);
    assert.ok(topOfFirstGauge(titledRingTile) >= 64, `${preset} rings must clear the native header.`);
    assert.equal(titledRingIPSView.title.top, 6, `${preset} title must use the IPSView space.`);
    const crowdedRingTile = render('symcon', 320, 192, 16, '', preset);
    assert.ok(topOfFirstGauge(crowdedRingTile) >= 64, `${preset} must clear a small tile header.`);
    assert.ok(crowdedRingTile.series.every(series => series.radius > 0), `${preset} radii must remain positive.`);
}

for (const count of [2, 3, 16]) {
    for (const [mode, width, height] of [['symcon', 416, 720], ['ipsview', 900, 460]]) {
        const weather = render(mode, width, height, count, 'Weather', 'weather-station');
        assert.equal(weather.series.length, count, 'Weather station must render every source.');
        assert.ok(weather.series[0].radius > weather.series[1].radius, 'The first source needs the main instrument.');
        assert.ok(topOfFirstGauge(weather) >= (mode === 'symcon' ? 64 : 0), 'The weather dial must clear the header.');
        assert.equal(weather.graphic.length, count * 2, 'Each weather dial needs its bezel and inner frame.');
        weather.series.forEach((series, index) => {
            assert.equal(series.id, `variable-${index}`);
            assert.equal(series.min, index * 10);
            assert.equal(series.max, index * 10 + 100);
            assert.equal(series.data[0].value, index * 10 + 50);
            assert.equal(series.pointer.show, true);
            assert.equal(series.progress.show, false);
            assert.ok(series.radius > 0);
            assert.ok(series.center[0] - series.radius * 1.14 >= 0);
            assert.ok(series.center[0] + series.radius * 1.14 <= width);
            assert.ok(series.center[1] - series.radius * 1.14 >= (mode === 'symcon' ? 64 : 0));
            assert.ok(series.center[1] + series.radius * 1.14 <= height);
        });
        assert.equal(new Set(weather.series.map(series => series.itemStyle.color)).size, count);
    }
}
const compactWeather = render('symcon', 320, 192, 16, 'Weather', 'weather-station');
assert.equal(compactWeather.series.length, 16);
assert.ok(compactWeather.series.every(series => series.center[1] - series.radius * 1.14 >= 64));
assert.ok(compactWeather.series.every(series => series.axisLine.lineStyle.width <= series.radius));

process.stdout.write('Gauge Multi dial, ring and weather-station layouts verified.\n');
