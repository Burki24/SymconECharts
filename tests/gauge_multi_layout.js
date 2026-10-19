'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '..', 'EChartsGaugeMulti', 'visualization', 'app.js'), 'utf8');
const designSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-design.js'), 'utf8');
const palette = {
    background: '#202020',
    text: '#ffffff',
    muted: '#aaaaaa',
    border: '#cccccc',
    track: '#444444',
    accent: '#55cbb5'
};

function render(mode, width, height, count, title = '', preset = 'multi-title', tileHeaderVisible = true, style = {}, selection = false) {
    const chartElement = { clientWidth: width, clientHeight: height, hidden: false };
    const errorElement = { hidden: true, textContent: '' };
    const warningElement = { hidden: true, textContent: '' };
    let option;
    let clearCount = 0;
    const window = {
        SYMC_VISUALIZATION: {
            mode,
            state: {
                status: 'ready',
                chart: {
                    theme: 'dark',
                    gauge: { title, preset, style, enableSourceSelection: selection },
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
            options: { echartsThemes: { dark: palette, night: { ...palette, background: '#111111' } }, tileHeaderVisible }
        },
        echarts: {
            init: () => ({ setOption: next => { option = next; }, clear: () => { clearCount++; },
                resize: () => {}, dispose: () => {} })
        },
        addEventListener: () => {}
    };
    function element(tag) {
        const node = { tag, style: {}, children: [], hidden: true, listeners: {},
            addEventListener: (name, callback) => { node.listeners[name] = callback; },
            appendChild: child => { node.children.push(child); },
            setAttribute: (name, value) => { node[name] = value; }, remove: () => {},
            querySelectorAll: tagName => node.children.flatMap(child => [
                ...(child.tag === tagName ? [child] : []), ...child.querySelectorAll(tagName)
            ]) };
        Object.defineProperty(node, 'textContent', { get: () => node.text || '', set: value => {
            node.text = value; node.children = [];
        } });
        return node;
    }
    const elements = {
            'echarts-gauge-chart': chartElement,
            'echarts-gauge-warning': warningElement,
            'echarts-gauge-error': errorElement
    };
    for (const id of ['controls', 'selection', 'selection-title', 'sources', 'pages', 'previous', 'next', 'range', 'all', 'inverse']) {
        elements['echarts-gauge-' + id] = element('div');
    }
    const document = {
        getElementById: id => elements[id], createElement: element,
        body: { appendChild: () => {} }
    };

    const context = { window, document };
    vm.runInNewContext(designSource, context);
    for (const file of ['echarts-series-selection.js', 'echarts-category-navigation.js', 'echarts-source-controls.js']) {
        vm.runInNewContext(fs.readFileSync(path.join(__dirname, '..', 'libs', file), 'utf8'), context);
    }
    vm.runInNewContext(source, context);
    assert.ok(option, 'The chart should be rendered.');
    Object.defineProperties(option, {
        handleMessage: { value: window.handleMessage },
        getState: { value: () => window.SYMC_VISUALIZATION.state },
        getOption: { value: () => option },
        element: { value: suffix => elements['echarts-gauge-' + suffix] },
        resize: { value: (width, height) => {
            chartElement.clientWidth = width; chartElement.clientHeight = height;
            window.handleMessage(window.SYMC_VISUALIZATION.state);
        } },
        getRecoveryView: { value: () => ({ chartElement, warningElement, errorElement, clearCount }) }
    });
    return option;
}

const retainedGauge = render('symcon', 600, 400, 3);
retainedGauge.handleMessage({ status: 'error', chart: null, error: 'The Multi Gauge values could not be loaded.' });
assert.equal(retainedGauge.getRecoveryView().chartElement.hidden, false,
    'A transient Gauge read failure must preserve the last rendered gauges.');
assert.equal(retainedGauge.getRecoveryView().clearCount, 0);
retainedGauge.handleMessage({ status: 'error', chart: null, error: 'The Multi Gauge values could not be loaded.' });
assert.equal(retainedGauge.getRecoveryView().warningElement.hidden, false,
    'Repeated Gauge read failures must identify stale values.');
retainedGauge.handleMessage({ ...retainedGauge.getState() });
assert.equal(retainedGauge.getRecoveryView().warningElement.hidden, true,
    'A successful Gauge refresh must clear the stale-values warning.');
retainedGauge.handleMessage({ status: 'error', chart: null, error: 'Configure 2 to 16 unique numeric sources.' });
assert.equal(retainedGauge.getRecoveryView().chartElement.hidden, true,
    'A source configuration error must not leave the prior gauge chart visible.');

function topOfFirstGauge(option) {
    return option.series[0].center[1] - option.series[0].radius;
}

const tallTile = render('symcon', 416, 1048, 3);
const shiftedLabels = render('symcon', 416, 1048, 3, '', 'multi-title', true, {
    scaleLabelDistanceOffset: 7, titleOffsetXPercent: 4, titleOffsetYPercent: -3,
    valueOffsetXPercent: -5, valueOffsetYPercent: 6
});
assert.equal(shiftedLabels.series[0].axisLabel.distance, tallTile.series[0].axisLabel.distance + 7);
assert.equal(parseFloat(shiftedLabels.series[0].title.offsetCenter[0]),
    parseFloat(tallTile.series[0].title.offsetCenter[0]) + 4);
assert.equal(parseFloat(shiftedLabels.series[0].title.offsetCenter[1]),
    parseFloat(tallTile.series[0].title.offsetCenter[1]) - 3);
assert.equal(parseFloat(shiftedLabels.series[0].detail.offsetCenter[0]),
    parseFloat(tallTile.series[0].detail.offsetCenter[0]) - 5);
assert.equal(parseFloat(shiftedLabels.series[0].detail.offsetCenter[1]),
    parseFloat(tallTile.series[0].detail.offsetCenter[1]) + 6);
const tallIPSView = render('ipsview', 416, 1048, 3);
const tallTileWithoutHeader = render('symcon', 416, 1048, 3, '', 'multi-title', false);
assert.ok(topOfFirstGauge(tallTile) >= 64, 'The native header must remain clear.');
assert.ok(topOfFirstGauge(tallIPSView) < 64, 'IPSView must not reserve the native header.');
assert.ok(topOfFirstGauge(tallTileWithoutHeader) < 64, 'A tile without a title must use the released space.');

const titledTile = render('symcon', 416, 1048, 3, 'Climate');
const titledIPSView = render('ipsview', 416, 1048, 3, 'Climate');
assert.ok(titledTile.title.top >= 64, 'The chart title must follow the native header.');
assert.equal(titledIPSView.title.top, 6, 'The IPSView chart title must retain its original position.');
const styledChartTitle = render('ipsview', 720, 560, 3, 'Climate', 'multi-title', true, {
    chartTitleFontSizePercent: 150, chartTitleColor: '#123456',
    titleFontSizePercent: 50, titleColor: '#ABCDEF'
});
assert.equal(styledChartTitle.title.textStyle.color, '#123456', 'Chart title color must be independent of instrument labels.');
assert.equal(styledChartTitle.title.textStyle.fontSize, 36, 'Chart title size must follow the configured percent.');
assert.equal(titledIPSView.title.textStyle.color, '#ffffff', 'Automatic chart title color must follow the theme.');
assert.equal(render('symcon', 416, 1048, 3, 'Climate', 'multi-title', false).title.top, 6);

for (const preset of ['multi-title', 'ring-grid', 'ring-concentric', 'weather-station', 'tacho', 'chronograph']) {
    const withHeader = render('symcon', 416, 720, 3, 'Climate', preset);
    const withoutHeader = render('symcon', 416, 720, 3, 'Climate', preset, false);
    assert.ok(topOfFirstGauge(withHeader) >= 64, `${preset} must clear a visible Symcon header.`);
    assert.ok(topOfFirstGauge(withoutHeader) < topOfFirstGauge(withHeader), `${preset} must use hidden-header space.`);
    assert.equal(withoutHeader.title.top, 6);
}

const crowdedTile = render('symcon', 320, 192, 16);
assert.ok(topOfFirstGauge(crowdedTile) >= 64, 'A crowded tile must not overlap the header.');
const extendedTile = render('symcon', 625, 560, 32, 'Thirty-two instruments');
assert.ok(extendedTile.series.length <= 16,
    'Thirty-two instruments must be projected onto readable pages, not a crowded single raster.');

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

for (const count of [2, 3, 4, 5, 6]) {
    for (const [mode, width, height] of [['symcon', 720, 560], ['ipsview', 720, 560], ['symcon', 320, 192]]) {
        const option = render(mode, width, height, count, 'Climate', 'chronograph');
        const main = option.series[0];
        assert.equal(option.series.length, count);
        assert.equal(option.graphic.length, count, 'Main and embedded dials need their bezels.');
        assert.ok(main.radius > option.series[1].radius * 3, 'The first source must dominate the dial.');
        assert.deepEqual(Array.from(main.detail.offsetCenter), [0, '74%'],
            'The primary value must use the lower Single Gauge position.');
        assert.ok(option.series.slice(1).every(series => series.detail.offsetCenter[1] === '40%'),
            'Every embedded value must remain inside its own dial.');
        assert.ok(option.series.slice(1).every(series => main.z > series.z),
            'The main pointer layer must remain above every embedded dial.');
        assert.ok(option.graphic.slice(1).every(element => main.z > element.z),
            'The main pointer layer must remain above every embedded bezel.');
        assert.ok(main.center[1] - main.radius * 1.06 >= (mode === 'symcon' ? 64 : 0));
        assert.ok(main.center[1] + main.radius * 1.06 <= height);
        option.series.forEach((series, index) => {
            assert.equal(series.id, `variable-${index}`);
            assert.equal(series.min, index * 10);
            assert.equal(series.max, index * 10 + 100);
            assert.equal(series.data[0].value, index * 10 + 50);
            assert.equal(series.pointer.show, true);
            assert.match(series.detail.formatter(), new RegExp(`u${index}$`));
            if (index > 0) {
                const distance = Math.hypot(series.center[0] - main.center[0], series.center[1] - main.center[1]);
                assert.ok(distance + series.radius * 1.08 < main.radius,
                    'Every secondary scale must remain inside the main dial.');
            }
        });
        for (let left = 1; left < count; left += 1) {
            for (let right = left + 1; right < count; right += 1) {
                const a = option.series[left];
                const b = option.series[right];
                assert.ok(Math.hypot(a.center[0] - b.center[0], a.center[1] - b.center[1])
                    > (a.radius + b.radius) * 1.08, 'Subdial bezels must not overlap.');
            }
        }
        assert.equal(new Set(option.series.map(series => series.itemStyle.color)).size, count);
    }
}

const customStyle = {
    pointerShape: 'arrow', pointerWidthPercent: 150, pointerLengthPercent: 120,
    anchorShape: 'ring', anchorSizePercent: 130, anchorBorderWidthPercent: 150,
    majorSplitCount: 12, minorSplitCount: 3, colorMode: 'custom',
    pointerColor: '#112233', progressColor: '#223344', anchorColor: '#334455',
    anchorBorderColor: '#445566', ringColor: '#556677', scaleColor: '#667788',
    valueColor: '#778899', titleColor: '#8899AA', plateMode: 'custom',
    plateSizePercent: 110, plateBorderWidthPercent: 125,
    plateColor: '#99AABB', plateBorderColor: '#AABBCC'
};
const customChronograph = render('symcon', 720, 560, 3, 'Climate', 'chronograph', true, customStyle);
const defaultChronograph = render('symcon', 720, 560, 3, 'Climate', 'chronograph');
customChronograph.series.forEach((series, index) => {
    assert.match(series.pointer.icon, /^path:\/\//);
    assert.ok(series.pointer.width > defaultChronograph.series[index].pointer.width);
    assert.equal(series.splitNumber, 12);
    assert.equal(series.axisTick.splitNumber, 3);
    assert.equal(series.pointer.itemStyle.color, '#112233');
    assert.equal(series.axisLine.lineStyle.color[0][1], '#556677');
    assert.equal(series.axisLabel.color, '#667788');
    assert.equal(series.detail.color, '#778899');
    assert.equal(series.title.color, '#8899AA');
    assert.equal(series.anchor.itemStyle.color, palette.background);
    assert.equal(series.anchor.itemStyle.borderColor, '#445566');
});
assert.equal(customChronograph.graphic.length, 3);
assert.ok(customChronograph.graphic.every(element => String(element.id).startsWith('custom-plate-')));
assert.ok(customChronograph.graphic.every(element => element.style.fill === '#99AABB'));
assert.ok(customChronograph.graphic.every(element => element.style.stroke === '#AABBCC'));
const customSvgStyle = {
    pointerShape: 'custom', pointerWidthPercent: 100, pointerLengthPercent: 100,
    pointerPath: 'M50 0L60 90L50 100L40 90Z', pointerViewBox: '0 0 100 100',
    pointerPivotX: 50, pointerPivotY: 90, pointerShowAnchor: true,
    plateMode: 'custom', plateBackgroundEnabled: true,
    plateBackgroundImage: `data:image/svg+xml;base64,${Buffer.from(
        '<svg viewBox="0 0 200 100"><rect width="200" height="100" fill="#123456"/></svg>'
    ).toString('base64')}`,
    plateBackgroundAspectRatio: 2, plateBackgroundFit: 'contain',
    plateBackgroundSizePercent: 120, plateBackgroundOffsetXPercent: 10,
    plateBackgroundOffsetYPercent: -5, plateBackgroundOpacityPercent: 60,
    plateBackgroundRotation: 15
};
const svgChronograph = render('symcon', 720, 560, 3, 'Climate', 'chronograph', true, customSvgStyle);
svgChronograph.series.forEach(series => {
    assert.equal(series.pointer.icon, 'path://M50 0L60 90L50 100L40 90Z');
    assert.ok(Array.isArray(series.pointer.offsetCenter));
    assert.equal(series.anchor.show, true);
});
assert.equal(svgChronograph.graphic.filter(element => String(element.id).startsWith('custom-plate-')).length, 9);
assert.equal(svgChronograph.graphic.filter(
    element => String(element.id).startsWith('custom-plate-background-')
).length, 3);
assert.ok(svgChronograph.graphic.filter(
    element => String(element.id).startsWith('custom-plate-background-')
).every(element => element.children[0].style.opacity === 0.6));
const hiddenChronograph = render('symcon', 720, 560, 3, 'Climate', 'chronograph', true, { plateMode: 'hidden' });
assert.equal(hiddenChronograph.graphic.length, 0, 'Hidden plates must remove every chronograph bezel.');
const customRing = render('symcon', 720, 560, 3, '', 'ring-grid', true, customStyle);
assert.equal(customRing.graphic.length, 0, 'Plate settings must not replace ring layouts.');
assert.equal(customRing.series[0].progress.itemStyle.color, '#223344');

const timedGauge = render('ipsview', 720, 560, 3, 'Climate', 'multi-title', true, {
    animationEnabled: true, animationDuration: 900, animationDurationUpdate: 1400,
    animationEasing: 'bounceOut', animationEasingUpdate: 'linear',
    animationDelay: 120, animationDelayUpdate: 240
});
assert.equal(timedGauge.animationDuration, 900);
assert.equal(timedGauge.animationDurationUpdate, 1400);
assert.equal(timedGauge.animationEasing, 'bounceOut');
assert.equal(timedGauge.animationEasingUpdate, 'linear');
assert.equal(timedGauge.animationDelay, 120);
assert.equal(timedGauge.animationDelayUpdate, 240);
assert.ok(timedGauge.series.every(series => series.detail.valueAnimation === true));
const stoppedGauge = render('symcon', 720, 560, 3, 'Climate', 'multi-title', true, {
    animationEnabled: false, animationDuration: 900, animationDurationUpdate: 1400
});
assert.equal(stoppedGauge.animation, false);
assert.ok(stoppedGauge.series.every(series => series.detail.valueAnimation === false));

const nativePages = [];
const presets = ['multi-title', 'ring-grid', 'ring-concentric', 'weather-station'];
for (const mode of ['symcon', 'ipsview']) {
    for (const preset of presets) {
        const many = render(mode, 625, 560, 32, 'Thirty-two instruments', preset, true,
            { animationEnabled: false }, true);
        const press = suffix => many.element(suffix).listeners.click();
        const original = many.getState();
        const items = original.chart.items;
        assert.equal(many.element('controls').hidden, false);
        assert.equal(many.element('sources').querySelectorAll('input').length, 32);
        const firstPageIds = many.getOption().series.map(series => series.id);
        assert.ok(firstPageIds.length > 0 && firstPageIds.length <= 16);
        const colors = new Map(many.getOption().series.map(series => [series.id, series.itemStyle?.color]));
        const visited = new Set();
        for (let page = 0; page < 32; page++) {
            const option = many.getOption();
            nativePages.push({ option, width: 625, height: 560 });
            for (const series of option.series) {
                visited.add(series.id);
                const item = items.find(item => item.id === series.id);
                assert.equal(series.min, item.gauge.minimum);
                assert.equal(series.max, item.gauge.maximum);
                assert.equal(series.data[0].value, item.value);
                assert.match(option.tooltip.formatter({ seriesId: series.id, seriesIndex: 0 }),
                    new RegExp(`${item.gauge.unit}$`), 'Tooltip ID must survive page position changes.');
                assert.ok(series.center[1] - series.radius >= (mode === 'symcon' ? 64 : 0));
                assert.ok(series.center[1] + series.radius <= 516, 'Gauge geometry must clear the controls.');
            }
            if (many.element('next').disabled) { break; }
            press('next');
        }
        assert.equal(visited.size, 32, `${mode}/${preset} must make all sources reachable.`);
        while (!many.element('previous').disabled) { press('previous'); }
        for (const series of many.getOption().series) {
            assert.equal(series.itemStyle?.color, colors.get(series.id), 'Paging must preserve source colors.');
        }
        const smaller = many.getOption().series.length;
        many.handleMessage({ ...original, chart: { ...original.chart, gauge: { ...original.chart.gauge,
            style: { animationEnabled: false, scaleFontSizePercent: 150,
                titleFontSizePercent: 150, valueFontSizePercent: 150 } } } });
        assert.ok(many.getOption().series.length <= smaller, 'Large fonts must not crowd more gauges into a page.');
        press('next');
        const anchor = many.getOption().series[0].id;
        const refresh = { ...original, chart: { ...original.chart, items: items.map(item => ({ ...item,
            gauge: { ...item.gauge, label: '<Same> & room', unit: '<u>' }, value: item.gauge.maximum + 50 })) } };
        many.handleMessage(refresh);
        assert.ok(many.getOption().series.some(series => series.id === anchor));
        assert.match(many.getOption().tooltip.formatter({ seriesIndex: 0 }), /&lt;Same&gt; &amp; room/);
        assert.match(many.getOption().tooltip.formatter({ seriesIndex: 0 }), /&lt;u&gt;/);
        assert.ok(many.getOption().series.every(series => series.data[0].value > series.max),
            'Out-of-range source values must remain original model values, with only geometry clamped by ECharts.');
        const boxes = many.element('sources').querySelectorAll('input');
        boxes[0].checked = false; boxes[0].listeners.change();
        many.handleMessage(refresh);
        assert.equal(many.element('sources').querySelectorAll('input')[0].checked, false);
        const beforeReorderAnchor = many.getOption().series[0].id;
        many.handleMessage({ ...refresh, chart: { ...refresh.chart, theme: 'night',
            items: refresh.chart.items.slice().reverse() } });
        assert.ok(many.getOption().series.some(series => series.id === beforeReorderAnchor),
            'Theme replacement and reorder must retain the surviving ID page anchor.');
        assert.equal(many.element('sources').querySelectorAll('input').find(input => input.value === 'variable-0').checked, false,
            'Selection must be keyed by ID across reorder and duplicate labels.');
        const removed = many.getOption().series[0].id;
        many.handleMessage({ ...refresh, chart: { ...refresh.chart,
            items: refresh.chart.items.filter(item => item.id !== removed) } });
        assert.ok(many.getOption().series.length > 0 && !many.getOption().series.some(series => series.id === removed));
        press('all'); press('inverse');
        assert.equal(many.getOption().series.length, 0, 'An empty selection must have no invented instruments.');
        assert.ok(many.getOption().graphic.some(graphic => graphic.style?.text === 'No sources selected'));
        nativePages.push({ option: many.getOption(), width: 625, height: 560 });
        assert.equal(many.element('next').disabled, true);
        assert.equal(many.element('previous').disabled, true);
        const one = many.element('sources').querySelectorAll('input')[0];
        one.checked = true; one.listeners.change();
        assert.equal(many.getOption().series.length, 1, 'A single selected source must work in every preset.');
        nativePages.push({ option: many.getOption(), width: 625, height: 560 });
        press('all');
        many.resize(240, 300);
        assert.ok(many.getOption().series.length >= 1 && many.getOption().series.length <= 4,
            'A narrow widget must retain a small reachable page of real sources.');
        nativePages.push({ option: many.getOption(), width: 240, height: 300 });
        many.handleMessage(original);
        assert.ok(many.getOption().series.length <= 4, 'Small size still constrains a refreshed full model.');
    }
}

const legacyInitial = render('ipsview', 625, 560, 16);
legacyInitial.handleMessage({ ...legacyInitial.getState(), chart: { ...legacyInitial.getState().chart,
    items: legacyInitial.getState().chart.items.map(item => ({ ...item, initiallyVisible: false })) } });
assert.equal(legacyInitial.getOption().series.length, 16, 'Disabled selection ignores initial visibility.');
assert.equal(legacyInitial.element('controls').hidden, true, 'Legacy small models must not gain a footer.');
const initiallyHidden = render('ipsview', 625, 560, 32, '', 'ring-grid', false, {}, true);
const hiddenState = initiallyHidden.getState();
initiallyHidden.handleMessage({ ...hiddenState, chart: { ...hiddenState.chart,
    items: hiddenState.chart.items.map((item, index) => ({ ...item, initiallyVisible: index === 31 })) } });
assert.equal(initiallyHidden.getOption().series.length, 1);
assert.equal(initiallyHidden.getOption().series[0].id, 'variable-31');
const disabledMany = render('ipsview', 625, 560, 32, '', 'weather-station');
disabledMany.handleMessage({ ...disabledMany.getState(), chart: { ...disabledMany.getState().chart,
    items: disabledMany.getState().chart.items.map(item => ({ ...item, initiallyVisible: false })) } });
assert.match(disabledMany.element('range').textContent, /32$/);
assert.equal(disabledMany.element('selection').hidden, true);
const customPaged = render('ipsview', 625, 560, 32, '', 'multi-title', false,
    { ...customSvgStyle, animationEnabled: false }, true);
assert.ok(customPaged.series.every(series => series.pointer.icon === 'path://M50 0L60 90L50 100L40 90Z'),
    'Custom SVG pointers must survive the paged renderer.');
assert.equal(customPaged.graphic.filter(graphic => String(graphic.id).startsWith('custom-plate-background-')).length,
    customPaged.series.length, 'Every projected source retains its configured SVG plate.');
nativePages.push({ option: customPaged, width: 625, height: 560 });

// Native geometry uses the shipped Gauge build, not an independent approximation.
const shipped = {};
vm.runInNewContext(fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts', '6.1.0', 'echarts.gauge.min.js'), 'utf8'),
    { window: shipped, navigator: { userAgent: 'Node.js' }, console, setTimeout, clearTimeout });
shipped.echarts.use(require('../.tools/echarts-runtime/node_modules/echarts/renderers').SVGRenderer);
for (const { option, width, height } of nativePages) {
    const native = shipped.echarts.init(null, null, { renderer: 'svg', ssr: true, width, height });
    native.setOption(option, true);
    assert.equal(native.getModel().getSeries().length, option.series.length);
    const svg = native.renderToSVGString();
    assert.match(svg, /<svg/);
    assert.ok(!svg.includes('NaN'), 'Native Gauge geometry must remain finite.');
    native.getModel().getSeries().forEach((series, index) => {
        assert.equal(series.get('min'), option.series[index].min);
        assert.equal(series.get('max'), option.series[index].max);
        assert.equal(series.getData().get('value', 0), option.series[index].data[0].value);
    });
    native.dispose();
}

process.stdout.write('Gauge Multi legacy layouts, 32-source views, selection, pages and native geometry verified.\n');
