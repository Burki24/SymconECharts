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

function render(mode, width, height, count, title = '', preset = 'multi-title', tileHeaderVisible = true) {
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
            options: { echartsThemes: { dark: palette }, tileHeaderVisible }
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
const tallTileWithoutHeader = render('symcon', 416, 1048, 3, '', 'multi-title', false);
assert.ok(topOfFirstGauge(tallTile) >= 64, 'The native header must remain clear.');
assert.ok(topOfFirstGauge(tallIPSView) < 64, 'IPSView must not reserve the native header.');
assert.ok(topOfFirstGauge(tallTileWithoutHeader) < 64, 'A tile without a title must use the released space.');

const titledTile = render('symcon', 416, 1048, 3, 'Climate');
const titledIPSView = render('ipsview', 416, 1048, 3, 'Climate');
assert.ok(titledTile.title.top >= 64, 'The chart title must follow the native header.');
assert.equal(titledIPSView.title.top, 6, 'The IPSView chart title must retain its original position.');
assert.equal(render('symcon', 416, 1048, 3, 'Climate', 'multi-title', false).title.top, 6);

for (const preset of ['multi-title', 'ring-grid', 'ring-concentric', 'weather-station', 'tacho']) {
    const withHeader = render('symcon', 416, 720, 3, 'Climate', preset);
    const withoutHeader = render('symcon', 416, 720, 3, 'Climate', preset, false);
    assert.ok(topOfFirstGauge(withHeader) >= 64, `${preset} must clear a visible Symcon header.`);
    assert.ok(topOfFirstGauge(withoutHeader) < topOfFirstGauge(withHeader), `${preset} must use hidden-header space.`);
    assert.equal(withoutHeader.title.top, 6);
}

const crowdedTile = render('symcon', 320, 192, 16);
assert.ok(topOfFirstGauge(crowdedTile) >= 64, 'A crowded tile must not overlap the header.');

for (const mode of ['symcon', 'ipsview']) {
    const ring = render(mode, 1260, 310, 3, '', 'ring-grid', false);
    const dial = render(mode, 1260, 630, 3, '', 'multi-title', false);
    assert.ok(ring.series[0].radius > 118, `${mode} rings should use more of a wide tile.`);
    assert.ok(dial.series[0].radius > 175, `${mode} dials should use more of a wide tile.`);
    for (const [option, height] of [[ring, 310], [dial, 630]]) {
        for (const series of option.series) {
            assert.ok(series.center[0] - series.radius >= 0);
            assert.ok(series.center[0] + series.radius <= 1260);
            assert.ok(series.center[1] - series.radius >= 0);
            assert.ok(series.center[1] + series.radius <= height);
        }
    }
}

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

for (const count of [2, 3, 16]) {
    for (const [mode, width, height] of [['symcon', 1120, 600], ['ipsview', 416, 720]]) {
        const tacho = render(mode, width, height, count, 'Cockpit', 'tacho');
        assert.equal(tacho.series.length, count, 'Tacho must retain all configured sources.');
        assert.ok(tacho.series[0].radius > tacho.series[1].radius, 'Tacho needs a dominant middle dial.');
        assert.ok(tacho.series[1].center[0] < tacho.series[0].center[0], 'The second source goes left.');
        if (count >= 3) {
            assert.ok(tacho.series[2].center[0] > tacho.series[0].center[0], 'The third source goes right.');
            assert.equal(tacho.series[1].radius, tacho.series[2].radius);
        }
        assert.equal(tacho.graphic.length, count * 2, 'Every dial needs a frame.');
        tacho.series.forEach((series, index) => {
            assert.equal(series.id, `variable-${index}`);
            assert.equal(series.min, index * 10);
            assert.equal(series.max, index * 10 + 100);
            assert.equal(series.data[0].value, index * 10 + 50);
            assert.equal(series.pointer.show, true);
            assert.equal(series.pointer.itemStyle.color, '#F0442D');
            assert.equal(series.axisLabel.color, palette.text);
            assert.match(series.detail.formatter(), new RegExp(`u${index}$`));
            assert.ok(series.center[0] - series.radius * 1.08 >= 0);
            assert.ok(series.center[0] + series.radius * 1.08 <= width);
            assert.ok(series.center[1] - series.radius * 1.08 >= (mode === 'symcon' ? 64 : 0));
            assert.ok(series.center[1] + series.radius * 1.08 <= height);
        });
        if (count > 3) {
            assert.ok(tacho.series[3].center[1] > tacho.series[0].center[1], 'Additional sources go below the cockpit.');
        }
    }
}
const compactTacho = render('symcon', 320, 192, 16, 'Cockpit', 'tacho');
assert.equal(compactTacho.series.length, 16);
assert.ok(compactTacho.series.every(series => series.radius > 0));

process.stdout.write('Gauge Multi dial, ring, weather-station and tacho layouts verified.\n');
