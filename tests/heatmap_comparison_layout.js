'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.join(__dirname, '..');
const script = fs.readFileSync(path.join(root, 'EChartsHeatmap/visualization/app.js'), 'utf8');
const design = fs.readFileSync(path.join(root, 'libs/echarts-design.js'), 'utf8');

function model(shared = true, offset = 0) {
    const panels = [0, 1, 2, 3].map(index => ({ theme: 'dark', truncated: false,
        heatmap: { title: '<Room & ' + index + '>', range: '1m', metric: 'average', unit: index === 3 ? '%' : '°C',
            decimals: 1, windowOffset: offset, timezone: 'Europe/Berlin', startDate: offset ? '2026-08-01' : '2026-09-01',
            endDate: offset ? '2026-08-31' : '2026-09-30',
            style: { showColorScale: true, showValues: true, axisFontSizePercent: 200, titleFontSizePercent: 200 },
            ...(shared ? { valueRange: { min: 0, max: 100 } } : {}) },
        cells: index === 2 ? [] : [{ hour: 8, weekday: 0, value: index * 25, count: 2 },
            { hour: 9, weekday: 1, value: index * 25 + 5, count: 1 }] }));
    return { theme: 'dark', heatmap: { ...panels[0].heatmap, title: 'Comparison', comparisonPanels: true,
        sharedColorScale: shared, viewKey: shared ? 'shared' : 'separate', maxOffset: 120 }, panels, cells: [] };
}

function harness(mode, initial, fetchImpl) {
    let width = 640;
    const nodes = {};
    const listeners = {};
    function node() {
        return { style: {}, children: [], hidden: false, textContent: '', attributes: {}, parent: null,
            get clientWidth() { return Math.max(width, parseFloat(this.style.minWidth) || 0,
                this.parent ? this.parent.clientWidth : 0); },
            get clientHeight() { return parseFloat(this.style.height) || 420; },
            appendChild(child) { child.parent = this; this.children.push(child); },
            remove() { if (this.parent) { this.parent.children.splice(this.parent.children.indexOf(this), 1); } },
            setAttribute(key, value) { this.attributes[key] = value; },
            addEventListener(type, listener) { this[type] = listener; }
        };
    }
    ['root', 'chart', 'warning', 'empty', 'error', 'class-legend', 'class-min', 'class-segments', 'class-max',
        'period', 'period-label', 'previous', 'next', 'today', 'comparisons', 'comparison-title', 'comparison-list']
        .forEach(id => { nodes['echarts-heatmap-' + id] = node(); });
    nodes['echarts-heatmap-comparisons'].hidden = true;
    const charts = [];
    const window = {
        SYMC_VISUALIZATION: { mode, state: { status: 'ready', chart: initial }, translations: {},
            options: { tileHeaderInset: mode === 'symcon' ? 58 : 0, heatmapStatePath: '/hook/SymconECharts/state/5000/0123456789abcdef0123456789abcdef' } },
        location: { protocol: 'https:', host: 'symcon.example.test' },
        getComputedStyle: () => ({ color: '' }), addEventListener: (name, listener) => { listeners[name] = listener; },
        echarts: { init(surface) {
            const chart = { width: surface.clientWidth, height: surface.clientHeight, disposed: false,
                setOption(option) { this.option = option; }, clear() { this.option = null; },
                resize() { this.width = surface.clientWidth; this.height = surface.clientHeight; },
                getWidth() { return this.width; }, getHeight() { return this.height; },
                dispose() { this.disposed = true; } };
            charts.push(chart);
            return chart;
        } }
    };
    const document = { body: node(), createElement: node, getElementById: id => nodes[id] };
    vm.runInNewContext(design, { window });
    vm.runInNewContext(script, { window, document, fetch: fetchImpl || (() => Promise.reject(new Error('offline'))) });
    return { nodes, charts, window, listeners, resize() { width = 360; listeners.resize(); } };
}

async function verify() {
    for (const mode of ['symcon', 'ipsview']) {
        const urls = [];
        const view = harness(mode, model(), url => {
            urls.push(url);
            const offset = Number(new URL(url).searchParams.get('offset'));
            return Promise.resolve({ ok: true, json: () => Promise.resolve({ status: 'ready', chart: model(true, offset) }) });
        });
        assert.equal(view.charts.length, 4);
        assert.equal(view.nodes['echarts-heatmap-chart'].hidden, true);
        assert.equal(view.nodes['echarts-heatmap-comparisons'].hidden, false);
        assert.equal(view.nodes['echarts-heatmap-comparisons'].style.top, mode === 'symcon' ? '88px' : '38px');
        assert.equal(view.nodes['echarts-heatmap-comparison-title'].textContent, 'Comparison');
        for (const [index, chart] of view.charts.entries()) {
            assert.equal(chart.option.visualMap.min, 0);
            assert.equal(chart.option.visualMap.max, 100);
            assert.equal(chart.option.grid.top, 68, 'Panel-local title spacing must not repeat the tile header inset.');
            assert.equal(chart.option.title.text, '<Room & ' + index + '>');
            assert.equal(chart.option.series[0].data.length, index === 2 ? 0 : 2);
        }
        assert.match(view.charts[0].option.tooltip.formatter({ data: { value: [8, 0, 0], count: 2 } }), /&lt;Room &amp; 0&gt;.*0[.,]0 °C/);
        const sections = view.nodes['echarts-heatmap-comparison-list'].children;
        assert.match(sections[0].children[0].textContent, /01\.09\.2026.*30\.09\.2026.*Europe\/Berlin.*Shared color scale/);
        assert.equal(sections[2].children[2].hidden, false, 'An empty view stays visible with an explicit no-data message.');
        view.nodes['echarts-heatmap-comparisons'].scrollTop = 230;
        view.nodes['echarts-heatmap-previous'].click();
        await new Promise(resolve => setImmediate(resolve));
        assert.match(urls[0], mode === 'symcon' ? /offset=1&output=tile$/ : /offset=1$/);
        assert.match(sections[0].children[0].textContent, /01\.08\.2026/);
        assert.equal(view.nodes['echarts-heatmap-comparisons'].scrollTop, 230);
        view.window.handleMessage({ status: 'error', error: 'The Heatmap values could not be loaded.' });
        view.window.handleMessage({ status: 'error', error: 'The Heatmap values could not be loaded.' });
        assert.equal(view.nodes['echarts-heatmap-warning'].hidden, false);
        assert.equal(view.charts[0].option.series[0].data.length, 2, 'Transient failures retain all last good comparison views.');
        view.resize();
        assert.equal(view.charts.length, 4, 'Resize updates existing surfaces without losing scroll position or creating duplicate charts.');
        view.listeners.beforeunload();
        assert.ok(view.charts.every(chart => chart.disposed));
    }
    const independent = harness('ipsview', model(false));
    assert.equal(independent.charts[0].option.visualMap.max, 5);
    assert.equal(independent.charts[3].option.visualMap.min, 75);
    assert.match(independent.charts[3].option.visualMap.text[0], /%$/);
    let resolveOld;
    const changed = harness('symcon', model(), () => new Promise(resolve => { resolveOld = resolve; }));
    changed.nodes['echarts-heatmap-previous'].click();
    changed.window.handleMessage({ status: 'ready', chart: model(false) });
    resolveOld({ ok: true, json: () => Promise.resolve({ status: 'ready', chart: model(true, 1) }) });
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(changed.charts[0].option.visualMap.max, 5, 'A stale response cannot replace changed comparison settings.');
    // Actual pinned ECharts SVG rendering exercises native heatmaps and visual maps.
    const echarts = require('../.tools/echarts-runtime/node_modules/echarts');
    for (const chart of independent.charts) {
        const native = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 720, height: 520 });
        native.setOption({ ...chart.option, animation: false });
        assert.match(native.renderToSVGString(), /<svg/);
        assert.equal(native.getModel().getSeries()[0].getData().count(), chart.option.series[0].data.length);
        native.dispose();
    }
    independent.listeners.beforeunload();
    changed.listeners.beforeunload();
    console.log('Heatmap comparison layout, native runtime, refresh and navigation verified.');
}

verify().catch(error => { console.error(error); process.exitCode = 1; });
