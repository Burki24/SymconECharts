'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const zlib = require('node:zlib');

const source = fs.readFileSync(path.join(__dirname, '..', 'EChartsGraph', 'visualization', 'app.js'), 'utf8');
const designSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-design.js'), 'utf8');
const graphSupportSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-startup.js'), 'utf8') + '\n'
    + fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-graph.js'), 'utf8');
const zigbeeSource = fs.readFileSync(path.join(__dirname, '..', 'EChartsGraphZ2M', 'visualization', 'app.js'), 'utf8');
const palette = {
    background: '#101114', text: '#f4f5f7', muted: '#969aa2',
    accent: '#55cbb5', seriesColors: ['#5070dd']
};
const graph = {
    title: 'Test network', nodes: [
        { id: 'object-101', name: 'Room', kind: 'category', displayValue: '' },
        { id: 'object-102', name: 'Sensor', kind: 'variable', displayValue: '21 °C' },
        { id: 'object-103', name: 'Sensor', kind: 'variable', displayValue: '22 °C' }
    ], links: [
        { source: 'object-101', target: 'object-102' },
        { source: 'object-101', target: 'object-103' }
    ]
};
const baseStyle = {
    graphLayout: 'force', nodeSize: 16, roamEnabled: true, showLabels: true,
    showValues: false, titleFontSizePercent: 100, labelFontSizePercent: 100
};

function render(style, mode = 'symcon', width = 800, network = graph, height = 400,
    actualChart = null, rendererSource = source, compressed = false) {
    const chartElement = { hidden: false, clientWidth: width, clientHeight: height, style: {} };
    const rootElement = { style: {}, appendChild: () => {} };
    const errorElement = { hidden: true, textContent: '' };
    const warningElement = { hidden: true, textContent: '' };
    let option;
    const chart = actualChart || {
        setOption: next => { option = next; }, clear: () => {}, resize: () => {}, dispose: () => {}
    };
    if (actualChart) {
        const nativeResize = actualChart.resize.bind(actualChart);
        actualChart.resize = () => nativeResize({ width: chartElement.clientWidth, height: chartElement.clientHeight });
    }
    const model = { family: 'graph', theme: 'dark', graph: { ...network, style } };
    let resize;
    const window = {
        SYMC_VISUALIZATION: {
            mode, state: compressed ? null : { status: 'ready', chart: model }, translations: {},
            options: { echartsThemes: { auto: palette, dark: palette }, tileHeaderVisible: mode === 'symcon', tileHeaderInset: mode === 'symcon' ? 58 : 0,
                compressedStateGzipBase64: compressed ? (compressed === 'broken' ? 'not gzip'
                    : zlib.gzipSync(JSON.stringify({ status: 'ready', chart: model })).toString('base64')) : null }
        },
        echarts: { init: () => chart }, addEventListener: () => {}, getComputedStyle: () => ({ color: '' }),
        ResizeObserver: class { constructor(callback) { resize = callback; } observe() {} }
    };
    window.atob = value => Buffer.from(value, 'base64').toString('binary');
    window.Blob = class {
        constructor(parts) { this.bytes = parts[0]; }
        stream() { return { pipeThrough: () => this.bytes }; }
    };
    window.DecompressionStream = compressed === 'unsupported' ? undefined : class {
        constructor(format) { assert.equal(format, 'gzip'); }
    };
    window.Response = class {
        constructor(bytes) { this.bytes = bytes; }
        text() { return Promise.resolve(zlib.gunzipSync(Buffer.from(this.bytes)).toString('utf8')); }
    };
    const document = {
        body: { appendChild: () => {} },
        createElement: () => ({ style: {}, remove: () => {}, setAttribute: () => {} }),
        getElementById: id => ({
            'echarts-graph-root': rootElement,
            'echarts-graph-chart': chartElement,
            'echarts-graph-error': errorElement,
            'echarts-graph-warning': warningElement,
            'echarts-zigbee-graph-root': rootElement,
            'echarts-zigbee-graph-chart': chartElement,
            'echarts-zigbee-graph-meta': {}, 'echarts-zigbee-graph-legend': {},
            'echarts-zigbee-graph-error': errorElement,
            'echarts-zigbee-graph-warning': warningElement
        })[id]
    };
    vm.runInNewContext(designSource, { window });
    vm.runInNewContext(graphSupportSource, { window, document });
    vm.runInNewContext(rendererSource, { window, document, ResizeObserver: window.ResizeObserver });
    return { get option() { return option; }, errorElement, warningElement, chartElement, rootElement,
        resize: () => resize(), refresh: next => window.handleMessage({ status: 'ready', chart: next || model }),
        message: state => window.handleMessage(state), model };
}

const force = render(baseStyle);
assert.equal(force.option.title.show, false, 'The chart title is a physical DOM strip outside force painting.');
assert.equal(force.rootElement.__symcGraphTitle.hidden, false);
assert.ok(parseInt(force.rootElement.__symcGraphTitle.style.top) >= 66
    && parseInt(force.chartElement.style.top) > parseInt(force.rootElement.__symcGraphTitle.style.top) + 18,
    'The Graph title and nodes must clear the Symcon tile header.');
assert.equal(force.chartElement.style.height, 'calc(100% - ' + force.chartElement.style.top + ')');
assert.equal(force.rootElement.style.backgroundColor, force.option.backgroundColor);
assert.equal(force.option.series[0].type, 'graph');
assert.equal(force.option.series[0].layout, 'force');
assert.equal(force.option.series[0].roam, true);
assert.equal(force.option.series[0].data[1].id, 'object-102');
assert.equal(force.option.series[0].data[2].id, 'object-103');
assert.equal(force.option.series[0].links[1].target, 'object-103');
assert.equal(force.option.series[0].label.formatter({ data: force.option.series[0].data[1] }), 'Sensor');

const circular = render({ ...baseStyle, graphLayout: 'circular', showValues: true,
    nodeColor: '#123456', edgeColor: '#abcdef', labelFontSizePercent: 150 }, 'ipsview', 380);
assert.equal(circular.option.series[0].layout, 'circular');
assert.equal(circular.option.series[0].data[0].itemStyle.color, '#123456');
assert.equal(circular.option.series[0].lineStyle.color, '#abcdef');
assert.match(circular.option.series[0].label.formatter({ data: circular.option.series[0].data[1] }), /21 °C/);
assert.ok(circular.option.series[0].label.fontSize > force.option.series[0].label.fontSize);
assert.ok(circular.option.series[0].symbolSize < force.option.series[0].symbolSize);

for (const layout of ['force', 'circular']) {
    for (const mode of ['symcon', 'ipsview']) {
        const directed = render({ ...baseStyle, graphLayout: layout }, mode, 800, {
            ...graph, directed: true, links: [
                { ...graph.links[0], reciprocal: true },
                { source: 'object-102', target: 'object-101', reciprocal: true },
                { ...graph.links[1], reciprocal: false }
            ]
        });
        const edges = directed.option.series[0].links;
        assert.deepEqual(Array.from(directed.option.series[0].edgeSymbol), ['none', 'arrow']);
        assert.ok(edges[0].lineStyle.curveness > 0 && edges[0].lineStyle.curveness === edges[1].lineStyle.curveness,
            'Opposite directions must bend to opposite physical sides.');
        assert.equal(edges[2].lineStyle.curveness, 0);
        assert.equal(directed.option.tooltip.formatter({ dataType: 'edge', data: edges[1] }), 'Sensor → Room');
        assert.match(directed.option.aria.description, /Directed connections/);
    }
}
assert.deepEqual(Array.from(force.option.series[0].edgeSymbol), ['none', 'none'], 'Old models remain undirected.');

const tooltip = force.option.tooltip.formatter({ data: { name: '<script>', displayValue: '<b>' } });
assert.equal(tooltip, '&lt;script&gt;<br>&lt;b&gt;');
assert.equal(force.option.tooltip.confine, true);
assert.match(force.option.tooltip.extraCssText, /overflow-wrap/);
assert.equal(force.option.series[0].labelLayout.hideOverlap, true);
assert.equal(force.option.series[0].emphasis.label.show, true);
assert.equal(render({ ...baseStyle, showLabels: false }).option.series[0].emphasis.label.show, false);

const echarts = require(path.join(__dirname, '..', '.tools', 'echarts-runtime', 'node_modules', 'echarts'));
const chart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 800, height: 400 });
chart.setOption(force.option);
const series = chart.getModel().getSeriesByIndex(0);
assert.equal(series.type, 'series.graph');
assert.equal(series.getData().count(), 3);
assert.equal(series.getEdgeData().count(), 2);
chart.dispose();

const dense = { title: 'Large graph', nodes: Array.from({ length: 300 }, (_, i) => ({
    id: 'object-' + i, name: 'Repeated long category label <' + (i % 4) + '>',
    kind: 'variable', displayValue: '21.3 °C'
})), links: [] };
for (let span = 1; dense.links.length < 1000; span++) {
    for (let i = 0; i + span < dense.nodes.length && dense.links.length < 1000; i++) {
        dense.links.push({ source: dense.nodes[i].id, target: dense.nodes[i + span].id, lqi: 128 });
    }
}
for (const rendererSource of [source, zigbeeSource]) {
    for (const graphLayout of ['force', 'circular']) {
        for (const mode of ['symcon', 'ipsview']) {
            const native = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 800, height: 500 });
            const initial = render({ ...baseStyle, graphLayout, animationEnabled: false }, mode,
                800, dense, 500, native, rendererSource);
            let series = native.getModel().getSeriesByIndex(0);
            assert.equal(series.getData().count(), 300);
            assert.equal(series.getEdgeData().count(), 1000);
            native.dispatchAction({ type: 'graphRoam', seriesIndex: 0, dx: 17, dy: 11 });
            native.dispatchAction({ type: 'graphRoam', seriesIndex: 0, zoom: 1.5, originX: 400, originY: 250 });
            const center = Array.from(series.get('center'));
            const zoom = series.get('zoom');
            const changed = { ...initial.model, graph: { ...initial.model.graph,
                nodes: dense.nodes.map(node => ({ ...node, displayValue: '22.5 °C' })) } };
            const previousCoordinates = Object.fromEntries(dense.nodes.map((node, i) =>
                [node.id, Array.from(series.getData().getItemLayout(i))]));
            // Observe the actual pinned engine's update boundary: it must receive the
            // preceding ID-keyed coordinates rather than new random force seeds.
            const nativeSetOption = native.setOption.bind(native);
            let seeded;
            native.setOption = (...args) => {
                const old = native.getModel().getSeriesByIndex(0);
                seeded = old && old.preservedPoints ? JSON.parse(JSON.stringify(old.preservedPoints)) : null;
                return nativeSetOption(...args);
            };
            initial.refresh(changed);
            series = native.getModel().getSeriesByIndex(0);
            assert.equal(series.get('zoom'), zoom, rendererSource + ': refresh must retain native zoom.');
            assert.deepEqual(Array.from(series.get('center')), center);
            if (rendererSource === source) { assert.equal(series.getData().getRawDataItem(1).displayValue, '22.5 °C'); }
            if (graphLayout === 'force') { assert.deepEqual(seeded, previousCoordinates); }
            initial.chartElement.clientWidth = 900;
            initial.chartElement.clientHeight = 550;
            initial.resize();
            assert.equal(native.getModel().getSeriesByIndex(0).get('zoom'), zoom);
            assert.deepEqual(Array.from(native.getModel().getSeriesByIndex(0).get('center')), center);
            const removed = { ...changed, graph: { ...changed.graph,
                nodes: changed.graph.nodes.slice(1),
                links: changed.graph.links.filter(link => link.source !== 'object-0' && link.target !== 'object-0') } };
            initial.refresh(removed);
            series = native.getModel().getSeriesByIndex(0);
            assert.equal(series.getData().count(), 299);
            assert.equal(series.get('zoom'), zoom, 'Membership changes must preserve the surviving graph view.');
            if (graphLayout === 'force') {
                assert.equal(Object.keys(series.preservedPoints).length, 299);
                assert.ok(!Object.hasOwn(series.preservedPoints, 'object-0'));
            }
            assert.ok(!native.renderToSVGString().includes('NaN'), 'Large native graph geometry must remain finite.');
            initial.refresh({ ...removed, graph: { ...removed.graph,
                style: { ...removed.graph.style, graphLayout: graphLayout === 'force' ? 'circular' : 'force' } } });
            assert.equal(native.getModel().getSeriesByIndex(0).get('zoom'), 1,
                'An explicit layout change resets the incompatible view.');
            native.dispose();
        }
    }
}

const crowded = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 500, height: 300 });
crowded.setOption({ animation: false, series: [{ ...force.option.series[0], layout: 'none', links: [],
    data: [{ id: 'a', name: 'Alpha', x: 500, y: 100 }, { id: 'b', name: 'Beta', x: 502, y: 100 },
        { id: 'c', name: 'Left', x: 0, y: 0 }, { id: 'd', name: 'Right', x: 1000, y: 200 }] }] });
const crowdedSVG = crowded.renderToSVGString();
assert.equal((crowdedSVG.match(/>Alpha<\/text>/g) || []).length
    + (crowdedSVG.match(/>Beta<\/text>/g) || []).length, 1,
    'A dense manual Graph must suppress overlapping names without losing their nodes.');
crowded.dispatchAction({ type: 'graphRoam', seriesIndex: 0, zoom: 80, originX: 250, originY: 150 });
const zoomedSVG = crowded.renderToSVGString();
assert.equal((zoomedSVG.match(/>Alpha<\/text>/g) || []).length
    + (zoomedSVG.match(/>Beta<\/text>/g) || []).length, 2,
    'Native zoom must reveal formerly overlapping names.');
crowded.dispose();

// Evaluate the actual shipped Canvas bundle, not just the full development
// package: an omitted bundled feature must not be hidden by the SSR tests above.
const runtimeWindow = {};
// Control only the bundled test engine's force seeds, not production randomness.
let forceSeed = 1;
const seededMath = Object.create(Math);
seededMath.random = () => {
    forceSeed = (Math.imul(1664525, forceSeed) + 1013904223) >>> 0;
    return forceSeed / 4294967296;
};
vm.runInNewContext(fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts', '6.1.0',
    'echarts.graph.min.js'), 'utf8'), { Math: seededMath, window: runtimeWindow, navigator: { userAgent: 'Node.js' },
    console, setTimeout, clearTimeout });
const bundled = runtimeWindow.echarts;
assert.equal(bundled.version, '6.1.0');
function canvas() {
    const element = { nodeName: 'CANVAS', style: {}, width: 800, height: 500,
        setAttribute: () => {}, getAttribute: () => null, addEventListener: () => {} };
    const noop = () => {};
    const context = new Proxy({ canvas: element, measureText: text => ({ width: String(text).length * 7 }) },
        { get: (target, key) => key in target ? target[key] : noop });
    element.getContext = () => context;
    return element;
}
bundled.setPlatformAPI({ createCanvas: canvas });
// Check the shipped engine's geometry and actual arrow symbols, including
// diametric endpoints where native circular curvature would collapse.
for (const graphLayout of ['force', 'circular']) {
    for (const mode of ['symcon', 'ipsview']) {
        for (const count of [2, 3]) {
            const native = bundled.init(canvas(), null, { renderer: 'canvas', ssr: true, width: 800, height: 500 });
            const instance = render({ ...baseStyle, graphLayout, animationEnabled: false }, mode, 800, {
                ...graph, directed: true, nodes: graph.nodes.slice(0, count), links: [
                    { source: 'object-101', target: 'object-102', reciprocal: true },
                    { source: 'object-102', target: 'object-101', reciprocal: true }
                ]
            }, 500, native);
            let series = native.getModel().getSeriesByIndex(0);
            const roamRect = series.coordinateSystem.getViewRect();
            assert.ok(Math.min(roamRect.width, roamRect.height) > 200,
                'The usable native roam area must not collapse to a thin line for two nodes.');
            const edges = series.getEdgeData();
            assert.equal(edges.count(), 2, 'Native engine must retain both directions.');
            const curves = [0, 1].map(i => edges.getItemLayout(i));
            assert.ok(curves.flat(2).every(Number.isFinite));
            assert.notDeepEqual(Array.from(curves[0][2]), Array.from(curves[1][2]),
                'Reciprocal native control points must be physically distinct.');
            for (const index of [0, 1]) {
                assert.equal(edges.getItemVisual(index, 'toSymbol'), 'arrow');
                const el = edges.getItemGraphicEl(index);
                assert.ok(el.childOfName('toSymbol'), 'The shipped runtime must actually paint each target arrow.');
            }
            native.dispatchAction({ type: 'graphRoam', seriesIndex: 0, zoom: 1.3, originX: 400, originY: 250 });
            const zoom = series.get('zoom');
            instance.refresh({ ...instance.model, graph: { ...instance.model.graph,
                nodes: instance.model.graph.nodes.map(n => ({ ...n, displayValue: '24 °C' })) } });
            series = native.getModel().getSeriesByIndex(0);
            assert.equal(series.get('zoom'), zoom);
            assert.equal(series.getEdgeData().count(), 2);
            // Explicit arrow removal must survive a compatible setOption merge.
            instance.refresh({ ...instance.model, graph: { ...instance.model.graph, directed: false,
                links: [graph.links[0]] } });
            assert.equal(native.getModel().getSeriesByIndex(0).getEdgeData().getItemVisual(0, 'toSymbol'), 'none');
            native.dispose();
        }
    }
}
const bundledChart = bundled.init(canvas(), null, { renderer: 'canvas', ssr: true, width: 800, height: 500 });
const sharedWindow = {};
vm.runInNewContext(graphSupportSource, { window: sharedWindow });
const support = sharedWindow.SYMC_ECHARTS_GRAPH;
const overlapOption = { animation: false, series: [{ ...force.option.series[0], layout: 'none', links: [],
    data: [{ id: 'a', name: 'Alpha', x: 500, y: 100 }, { id: 'b', name: 'Beta', x: 502, y: 100 },
        { id: 'c', name: 'Left', x: 0, y: 0 }, { id: 'd', name: 'Right', x: 1000, y: 200 }] }] };
support.updateChart(bundledChart, overlapOption);
function visiblePair() {
    const data = bundledChart.getModel().getSeriesByIndex(0).getData();
    return [0, 1].filter(index => !data.getItemGraphicEl(index).getSymbolPath().getTextContent().ignore).length;
}
assert.equal(visiblePair(), 1, 'The shipped Canvas Graph bundle must really hide overlapping names.');
bundledChart.dispatchAction({ type: 'graphRoam', seriesIndex: 0, zoom: 80, originX: 400, originY: 250 });
assert.equal(visiblePair(), 2, 'The shipped Canvas Graph must reveal names after native zoom.');
assert.equal(bundledChart.__symcGraphUserRoamed, true);

const settledOption = render({ ...baseStyle, animationEnabled: false }, 'ipsview', 800, dense, 500).option;
settledOption.series[0].bottom = 60; // The Z2M family reserves this space for its legend and snapshot.
settledOption.series[0].force.layoutAnimation = false; // Test only: deterministically finish native ticks.
const settleStarted = performance.now();
support.updateChart(bundledChart, settledOption);
const settledTime = performance.now() - settleStarted;
support.finishLayout(bundledChart);
const settledSeries = bundledChart.getModel().getSeriesByIndex(0);
assert.equal(bundledChart.__symcGraphNeedsLabels, false);
assert.equal(bundledChart.__symcGraphOverviewDone, true);
assert.equal(bundledChart.__symcGraphUserRoamed, false, 'Automatic fitting must not look like user navigation.');
function assertOverviewPoint(point) {
    assert.ok(point.every(Number.isFinite), 'Overview coordinates must remain finite.');
    assert.ok(point[0] >= 23 && point[0] <= 777 && point[1] >= 23 && point[1] <= 477,
        'The initial dense-force overview must fit every node inside the physical plot.');
    // Native affine transforms may round the exact boundary one ULP upwards.
    // A billionth of a pixel absorbs arithmetic noise, not visible overflow.
    assert.ok(point[1] <= 416 + 1e-9,
        'The shared overview must respect the family-specific 60px footer and 24px safety margin; y=' + point[1]);
}
assert.doesNotThrow(() => assertOverviewPoint([400, 416.00000000000006]));
assert.throws(() => assertOverviewPoint([400, 416 + 1e-6]), /60px footer/,
    'The tolerance must still reject a genuine one-millionth-pixel footer violation.');
assert.throws(() => assertOverviewPoint([400, NaN]), /finite/);
assert.throws(() => assertOverviewPoint([Infinity, 100]), /finite/);
settledSeries.getData().each(index => {
    assertOverviewPoint(settledSeries.coordinateSystem.dataToPoint(settledSeries.getData().getItemLayout(index)));
});
const fittedZoom = settledSeries.get('zoom');
support.finishLayout(bundledChart);
assert.equal(settledSeries.get('zoom'), fittedZoom, 'Post-force label processing must run only once per update.');
bundledChart.dispatchAction({ type: 'graphRoam', seriesIndex: 0, zoom: 1.2, originX: 400, originY: 250 });
const userZoom = settledSeries.get('zoom');
support.updateChart(bundledChart, settledOption);
support.finishLayout(bundledChart);
assert.equal(bundledChart.getModel().getSeriesByIndex(0).get('zoom'), userZoom,
    'Subsequent settling must not refit an existing user view.');
bundledChart.dispose();
bundledChart.__symcGraphNeedsLabels = true;
assert.doesNotThrow(() => support.finishLayout(bundledChart),
    'A late rendered callback must not touch an already disposed chart.');
assert.ok(Number.isFinite(settledTime));

// Exact fixed positions reproduce the native affine-rounding boundary without
// depending on a random force layout. The last y becomes 416.00000000000006.
const boundaryChart = bundled.init(canvas(), null, { renderer: 'canvas', ssr: true, width: 800, height: 500 });
try {
    const boundaryOption = render({ ...baseStyle, animationEnabled: false }, 'ipsview', 800, dense, 500).option;
    boundaryOption.series[0].bottom = 60;
    boundaryOption.series[0].force.layoutAnimation = false;
    boundaryOption.series[0].data = Array.from({ length: 81 }, (_, index) => ({
        id: 'boundary-' + index, name: 'Boundary ' + index, fixed: true, x: index * 13.17, y: index * 7.35
    }));
    boundaryOption.series[0].links = [];
    support.updateChart(boundaryChart, boundaryOption);
    support.finishLayout(boundaryChart);
    const boundarySeries = boundaryChart.getModel().getSeriesByIndex(0);
    boundarySeries.getData().each(index => {
        assertOverviewPoint(boundarySeries.coordinateSystem.dataToPoint(boundarySeries.getData().getItemLayout(index)));
    });
} finally {
    boundaryChart.dispose();
}
if (process.env.GRAPH_BENCHMARK) {
    console.log('Test-only synchronous 300-node/1000-link settle: ' + Math.round(settledTime) + ' ms');
    const maximum = { ...dense, nodes: Array.from({ length: 500 }, (_, i) => ({
        id: 'device-' + i, name: 'Device ' + i, kind: 'Router'
    })), links: [] };
    for (let span = 1; maximum.links.length < 2000; span++) {
        for (let i = 0; i + span < 500 && maximum.links.length < 2000; i++) {
            maximum.links.push({ source: maximum.nodes[i].id, target: maximum.nodes[i + span].id, lqi: 128 });
        }
    }
    const option = render({ ...baseStyle, animationEnabled: false }, 'ipsview', 800, maximum,
        500, null, zigbeeSource).option;
    option.series[0].force.layoutAnimation = false;
    const stress = bundled.init(canvas(), null, { renderer: 'canvas', ssr: true, width: 800, height: 500 });
    const began = performance.now();
    support.updateChart(stress, option);
    console.log('Test-only synchronous 500-node/2000-link settle: ' + Math.round(performance.now() - began) + ' ms');
    stress.dispose();
}

async function verifyStartup() {
    for (const rendererSource of [source, zigbeeSource]) {
        const full = { ...graph, nodes: graph.nodes.map(node => ({ ...node,
            name: '<Original & full name>', displayValue: '123,45 °C · <Original & full formatted value>' })) };
        const compressed = render(baseStyle, 'symcon', 800, full, 400, null, rendererSource, true);
        assert.equal(compressed.option, undefined, 'Compressed startup must not briefly publish an empty-state error.');
        assert.equal(compressed.errorElement.hidden, true);
        await new Promise(resolve => setImmediate(resolve));
        assert.equal(compressed.option.series[0].data[0].name, full.nodes[0].name);
        const tooltip = compressed.option.tooltip.formatter({ data: compressed.option.series[0].data[0] });
        assert.match(tooltip, /&lt;Original &amp; full name&gt;/);
        if (rendererSource === source) {
            assert.match(tooltip, /123,45 °C/);
            assert.match(tooltip, /&lt;Original &amp; full formatted value&gt;/);
        }
        const live = render(baseStyle, 'symcon', 800, full, 400, null, rendererSource, true);
        live.refresh({ ...live.model, graph: { ...live.model.graph,
            nodes: live.model.graph.nodes.map(node => ({ ...node, name: 'New live name' })) } });
        await new Promise(resolve => setImmediate(resolve));
        assert.equal(live.option.series[0].data[0].name, 'New live name',
            'An asynchronous startup must never overwrite a newer live message.');
        for (const failure of ['broken', 'unsupported']) {
            const broken = render(baseStyle, 'symcon', 800, full, 400, null, rendererSource, failure);
            await new Promise(resolve => setImmediate(resolve));
            assert.equal(broken.errorElement.hidden, false);
            assert.equal(broken.option, undefined, 'Corrupt or unsupported compressed startup must fail safely.');
        }
    }
}
verifyStartup().then(() => console.log('Graph renderer layout verified.')).catch(error => {
    console.error(error); process.exitCode = 1;
});
