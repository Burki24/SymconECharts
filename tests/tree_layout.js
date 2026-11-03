'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const zlib = require('node:zlib');

const source = fs.readFileSync(path.join(__dirname, '..', 'EChartsTree', 'visualization', 'app.js'), 'utf8');
const healthSource = fs.readFileSync(path.join(__dirname, '..', 'EChartsTreeSystemHealth', 'visualization', 'app.js'), 'utf8');
const designSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-design.js'), 'utf8');
const treeSupportSource = fs.readFileSync(path.join(__dirname, '..', 'libs', 'echarts-tree.js'), 'utf8');
const palette = {
    background: '#101114', text: '#f4f5f7', muted: '#969aa2',
    accent: '#55cbb5', seriesColors: ['#111111', '#222222', '#333333']
};
const root = {
    id: 'object-100', name: 'Home', kind: 'category', children: [
        { id: 'object-101', name: 'Sensor', kind: 'instance', children: [
            { id: 'object-102', name: 'Temperature', kind: 'variable', displayValue: '21.3 °C', children: [] },
            { id: 'object-103', name: 'Temperature', kind: 'variable', displayValue: '22.4 °C', children: [] }
        ] }
    ]
};

function render(style, data = root, width = 800, mode = 'symcon', height = 400, truncated = false,
    compressed = false, rendererSource = source, actualChart = null, healthScope = null) {
    const chartElement = { hidden: false, clientWidth: width, clientHeight: height };
    if (actualChart) {
        const nativeResize = actualChart.resize.bind(actualChart);
        actualChart.resize = () => nativeResize({ width: chartElement.clientWidth, height: chartElement.clientHeight });
    }
    const errorElement = { hidden: true, textContent: '' };
    const warningElement = { hidden: true, textContent: '' };
    let option;
    const listeners = {};
    let fakeExpanded = true;
    const chart = actualChart || {
        setOption: next => { option = next; }, clear: () => {}, resize: () => {}, dispose: () => {},
        on: (event, handler) => { listeners[event] = handler; },
        getModel: () => ({ getSeriesByIndex: () => ({ type: 'series.tree', get: () => undefined,
            getData: () => ({ tree: { root: { eachNode: callback => {
                callback({ getId: () => 'object-101', isExpand: fakeExpanded });
            } } } }), getDataParams: () => ({
            data: { id: 'object-101' }, collapsed: true
        }) }) })
    };
    let resize;
    const model = {
        family: 'tree', theme: 'dark', tree: { title: 'Home tree', style, root: data, count: 4, truncated, healthScope }
    };
    const state = { status: 'ready', chart: model };
    const window = {
        SYMC_VISUALIZATION: {
            mode, state: compressed ? null : state, translations: {},
            options: {
                echartsThemes: { auto: palette, dark: palette }, tileHeaderVisible: mode === 'symcon', tileHeaderInset: mode === 'symcon' ? 58 : 0,
                compressedStateGzipBase64: compressed ? zlib.gzipSync(JSON.stringify(state)).toString('base64') : null
            }
        },
        echarts: { init: () => chart }, addEventListener: () => {}, getComputedStyle: () => ({ color: '' }),
        atob: value => Buffer.from(value, 'base64').toString('binary'),
        Blob: class {
            constructor(parts) { this.bytes = parts[0]; }
            stream() { return { pipeThrough: () => this.bytes }; }
        },
        DecompressionStream: class {
            constructor(format) { assert.equal(format, 'gzip'); }
        },
        Response: class {
            constructor(bytes) { this.bytes = bytes; }
            text() { return Promise.resolve(zlib.gunzipSync(Buffer.from(this.bytes)).toString('utf8')); }
        }, ResizeObserver: class { constructor(callback) { resize = callback; } observe() {} }
    };
    const document = {
        body: { appendChild: () => {} },
        createElement: () => ({ style: {}, remove: () => {} }),
        getElementById: id => ({
            'echarts-tree-chart': chartElement, 'echarts-tree-error': errorElement,
            'echarts-tree-warning': warningElement
        })[id]
    };
    vm.runInNewContext(designSource, { window });
    vm.runInNewContext(treeSupportSource, { window });
    vm.runInNewContext(rendererSource, { window, document, ResizeObserver: window.ResizeObserver });
    return { get option() { return option; }, warningElement,
        chartElement, resize: () => resize(), refresh: next => window.handleMessage({ status: 'ready', chart: next || model }),
        message: state => window.handleMessage(state),
        collapseAndRefresh: () => {
            fakeExpanded = false;
            if (listeners.treeExpandAndCollapse) { listeners.treeExpandAndCollapse({ dataIndex: 1 }); }
            window.handleMessage({ status: 'ready', chart: model });
        } };
}

const baseStyle = { treeLayout: 'orthogonal', orientation: 'LR', edgeShape: 'curve',
    initialDepth: 2, roamEnabled: true, showLabels: true, showValues: false, nodeSize: 10 };
const tree = render(baseStyle);
assert.equal(render({ ...baseStyle, initialDepth: 12 }).option.series[0].initialTreeDepth, 12,
    'Tree must allow twelve initially open descendant levels.');
assert.equal(tree.option.title.show, true);
assert.ok(tree.option.title.top >= 58 && tree.option.series[0].top > tree.option.title.top + 18,
    'The Tree title and nodes must clear the Symcon tile header.');
assert.equal(tree.option.series[0].type, 'tree');
assert.equal(tree.option.series[0].layout, 'orthogonal');
assert.equal(tree.option.series[0].orient, 'LR');
assert.equal(tree.option.series[0].roam, true);
assert.equal(tree.option.series[0].initialTreeDepth, 2);
assert.equal(tree.option.series[0].data[0].children[0].children[0].id, 'object-102');
assert.equal(tree.option.series[0].data[0].children[0].children[1].id, 'object-103');
assert.equal(tree.option.series[0].data[0].children[0].children[0].name, 'Temperature');
assert.equal(tree.option.series[0].data[0].children[0].children[1].name, 'Temperature');
assert.equal(tree.option.series[0].data[0].children[0].itemStyle.color, '#222222');
tree.collapseAndRefresh();
assert.equal(tree.option.series[0].data[0].children[0].collapsed, true);

const radial = render({ ...baseStyle, treeLayout: 'radial', edgeShape: 'polyline',
    showValues: true, labelFontSizePercent: 150, variableColor: '#aabbcc' }, root, 380, 'ipsview');
assert.equal(radial.option.series[0].layout, 'radial');
assert.equal(radial.option.series[0].edgeShape, 'polyline');
assert.equal(radial.option.series[0].data[0].children[0].children[0].itemStyle.color, '#aabbcc');
assert.match(radial.option.series[0].label.formatter({ data: {
    name: 'Temperature', kind: 'variable', displayValue: '21.3 °C'
} }), /21\.3 °C/);
assert.ok(radial.option.series[0].label.fontSize > tree.option.series[0].label.fontSize);

const limited = render(baseStyle, root, 800, 'symcon', 400, true);
assert.equal(limited.warningElement.hidden, false);

const healthRoot = { ...root, health: 'critical', children: [{
    ...root.children[0], health: 'critical', children: [
        { ...root.children[0].children[0], health: 'warning' },
        { ...root.children[0].children[1], health: 'critical' }
    ]
}] };
const health = render({ ...baseStyle, healthyColor: '#008800', warningColor: '#cc9900',
    criticalColor: '#cc0000' }, healthRoot, 800, 'symcon', 400, false, false, healthSource);
assert.equal(health.option.title.show, true);
assert.ok(health.option.title.top >= 58 && health.option.series[0].top > health.option.title.top + 18,
    'The System Health title and nodes must clear the Symcon tile header.');
assert.equal(health.option.series[0].data[0].itemStyle.color, '#cc0000');
assert.equal(health.option.series[0].data[0].children[0].children[0].itemStyle.color, '#cc9900');
assert.match(health.option.tooltip.formatter({ data: health.option.series[0].data[0] }), /Status: Critical/);
assert.equal(render({ ...baseStyle, initialDepth: 12 }, healthRoot, 800, 'symcon', 400,
    false, false, healthSource).option.series[0].initialTreeDepth, 12,
    'System Health must share the twelve-level initial-depth contract after Tree.');
const partialHealth = render(baseStyle, healthRoot, 800, 'symcon', 400, true, false, healthSource,
    null, { ruleCount: 3, evaluatedRuleCount: 1 });
assert.equal(partialHealth.warningElement.hidden, false);
assert.match(partialHealth.warningElement.textContent, /Only the first configured nodes/);
assert.match(partialHealth.warningElement.textContent, /Status applies only to the selected subtree/);
assert.match(partialHealth.warningElement.textContent, /Some configured status rules are outside/);
assert.ok(partialHealth.option.series[0].bottom > 18, 'Partial-status notices must reserve space below the tree.');
partialHealth.message({ status: 'error', error: 'The Tree could not be loaded.' });
assert.equal(partialHealth.warningElement.hidden, false, 'A transient failure must not hide the partial-status warning.');
assert.match(partialHealth.warningElement.textContent, /Some configured status rules are outside/);
partialHealth.message({ status: 'error', error: 'The Tree could not be loaded.' });
assert.match(partialHealth.warningElement.textContent, /Showing the last available values/);
partialHealth.refresh();
assert.match(partialHealth.warningElement.textContent, /Some configured status rules are outside/);
assert.doesNotMatch(partialHealth.warningElement.textContent, /Showing the last available values/);
const plain = render(baseStyle, healthRoot);
assert.equal(plain.option.series[0].data[0].itemStyle.color, '#111111');
assert.equal(plain.option.series[0].data[0].health, undefined);

const hostile = render(baseStyle, { ...root, name: '<img src=x onerror=alert(1)>' });
const tooltip = hostile.option.tooltip.formatter({ data: { name: '<script>', displayValue: '<b>' } });
assert.ok(tooltip.includes('&lt;script&gt;'));
assert.ok(tooltip.includes('&lt;b&gt;'));
assert.ok(!tooltip.includes('<script>'));
assert.equal(hostile.option.tooltip.confine, true);
assert.match(hostile.option.tooltip.extraCssText, /overflow-wrap/);

let deepRoot = { id: 'deep-12', name: 'Descendant 12', kind: 'variable', displayValue: '12 %', children: [] };
for (let depth = 11; depth >= 0; depth--) {
    deepRoot = { id: 'deep-' + depth, name: 'Long descendant name level ' + depth,
        kind: 'category', children: [deepRoot] };
}
const echarts = require(path.join(__dirname, '..', '.tools', 'echarts-runtime', 'node_modules', 'echarts'));
for (const rendererSource of [source, healthSource]) {
    const small = render({ ...baseStyle, treeLayout: 'radial', initialDepth: 12,
        labelFontSizePercent: 200, showValues: true }, deepRoot, 300, 'symcon', 300,
        false, false, rendererSource, undefined, { ruleCount: 2, evaluatedRuleCount: 1 });
    assert.ok(Number(small.option.series[0].top) + Number(small.option.series[0].bottom) < 300,
        'Deep radial padding must leave a positive plot height in small high-font tiles.');
    const smallNative = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 300, height: 300 });
    render({ ...baseStyle, treeLayout: 'radial', initialDepth: 12,
        labelFontSizePercent: 200, showValues: true, animationEnabled: false }, deepRoot, 300, 'symcon', 300,
        false, false, rendererSource, smallNative, { ruleCount: 2, evaluatedRuleCount: 1 });
    const smallLayout = smallNative.getModel().getSeriesByIndex(0).getData().tree.getNodeById('deep-12').getLayout();
    assert.ok(Number.isFinite(smallLayout.x) && Number.isFinite(smallLayout.y),
        'Small high-font radial views must retain finite native leaf geometry without removing nodes.');
    assert.doesNotMatch(smallNative.renderToSVGString(), /NaN/);
    smallNative.dispose();
    for (const mode of ['symcon', 'ipsview']) {
        const native = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 400, height: 520 });
        const rendered = render({ ...baseStyle, treeLayout: 'radial', initialDepth: 12,
            showValues: true, animationEnabled: false }, deepRoot, 400, mode, 520, false, false,
            rendererSource, native, { ruleCount: 2, evaluatedRuleCount: 1 });
        const series = native.getModel().getSeriesByIndex(0);
        const leaf = series.getData().tree.getNodeById('deep-12');
        const symbol = series.getData().getItemGraphicEl(leaf.dataIndex).getSymbolPath();
        const text = symbol.getTextContent();
        const bounds = text.getBoundingRect().clone();
        bounds.applyTransform(text.getComputedTransform());
        const protectedTop = rendererSource === healthSource ? 520 - 44 - 10 : 520;
        assert.ok(bounds.y + bounds.height <= protectedTop - 8,
            'Actual radial terminal text must stay above the Health notice and within the plain Tree viewport.');
        native.dispose();
    }
}
for (const rendererSource of [source, healthSource]) {
    const expandedChart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 800, height: 500 });
    const expandedView = render({ ...baseStyle, animationEnabled: false }, deepRoot, 800, 'symcon', 500,
        false, false, rendererSource, expandedChart);
    for (let level = 2; level <= 11; level++) {
        const series = expandedChart.getModel().getSeriesByIndex(0);
        const item = series.getData().tree.getNodeById('deep-' + level);
        if (!item.isExpand) { expandedChart.dispatchAction({ type: 'treeExpandAndCollapse', seriesId: 'tree', dataIndex: item.dataIndex }); }
    }
    assert.ok(expandedChart.getModel().getSeriesByIndex(0).get('label.width') <= 50,
        'Label geometry must adapt immediately when a user expands beyond the initial depth.');
    expandedView.refresh();
    assert.ok(expandedChart.getModel().getSeriesByIndex(0).get('label.width') <= 50);
    expandedChart.dispose();
for (const mode of ['symcon', 'ipsview']) {
    for (const orientation of ['LR', 'RL', 'TB', 'BT', 'radial']) {
        const style = { ...baseStyle, initialDepth: 12, animationEnabled: false,
            orientation: orientation === 'radial' ? 'LR' : orientation,
            treeLayout: orientation === 'radial' ? 'radial' : 'orthogonal' };
        const native = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 800, height: 500 });
        const nativeRender = render(style, deepRoot, 800, mode, 500, false, false, rendererSource, native);
        function series() { return native.getModel().getSeriesByIndex(0); }
        function node(id) { return series().getData().tree.getNodeById(id); }
        assert.equal(series().get('initialTreeDepth'), 12);
        assert.equal(node('deep-12').depth, 13, 'The invisible ECharts root does not count as a descendant level.');
        assert.ok(node('deep-12').getLayout(), 'The twelfth descendant must have real engine geometry.');
        assert.match(native.renderToSVGString(), /<path/);
        if (orientation === 'TB' || orientation === 'BT') {
            const topNode = node(orientation === 'TB' ? 'deep-0' : 'deep-12');
            const text = series().getData().getItemGraphicEl(topNode.dataIndex).getSymbolPath().getTextContent();
            const bounds = text.getBoundingRect().clone();
            bounds.applyTransform(text.getComputedTransform());
            const title = native.getModel().getComponent('title');
            assert.ok(bounds.y >= Number(title.get('top')) + Number(title.get('textStyle.fontSize')) + 8,
                'Vertical deep Tree labels must remain below the diagram title, not overlap it.');
        }
        if (orientation === 'LR' || orientation === 'RL') {
            assert.ok(series().get('label.width') <= 50, 'Deep horizontal labels must fit their level spacing.');
        }
        let renderedLabels = 0;
        const formatter = series().get('label.formatter');
        series().getData().tree.root.eachNode(item => {
            if (item.dataIndex > 0 && formatter({ data: series().getData().getRawDataItem(item.dataIndex) })) { renderedLabels++; }
        });
        assert.ok(renderedLabels >= 2 && (orientation === 'TB' || orientation === 'BT' || renderedLabels < 13),
            'Deep layouts must stagger labels when there is insufficient space, using the shipped native formatter.');
        assert.match(formatter({ data: series().getData().getRawDataItem(node('deep-7').dataIndex),
            collapsed: true }), /level 7/, 'Collapsed branches must retain their identifying label.');
        native.dispatchAction({ type: 'treeExpandAndCollapse', seriesId: 'tree', dataIndex: node('deep-7').dataIndex });
        assert.equal(node('deep-7').isExpand, false);
        native.dispatchAction({ type: 'treeRoam', seriesId: 'tree', dx: 35, dy: 18 });
        native.dispatchAction({ type: 'treeRoam', seriesId: 'tree', zoom: 1.5, originX: 400, originY: 250 });
        const center = JSON.stringify(series().get('center'));
        const zoom = series().get('zoom');
        assert.ok(zoom > 1, 'The real engine must process a native zoom action.');
        nativeRender.refresh();
        assert.equal(node('deep-7').isExpand, false, 'A native collapsed ID must survive a data refresh.');
        assert.equal(JSON.stringify(series().get('center')), center, 'Panning must survive a data refresh.');
        assert.equal(series().get('zoom'), zoom, 'Zoom must survive a data refresh.');
        nativeRender.chartElement.clientWidth = 600;
        nativeRender.resize();
        assert.equal(node('deep-7').isExpand, false, 'A native collapsed ID must survive ResizeObserver updates.');
        assert.equal(JSON.stringify(series().get('center')), center);
        assert.equal(series().get('zoom'), zoom);
        native.dispatchAction({ type: 'treeExpandAndCollapse', seriesId: 'tree', dataIndex: node('deep-7').dataIndex });
        assert.equal(node('deep-7').isExpand, true);
        nativeRender.refresh();
        assert.equal(node('deep-7').isExpand, true, 'A reopened ID must survive a data refresh.');
        native.dispatchAction({ type: 'treeExpandAndCollapse', seriesId: 'tree', dataIndex: node('deep-7').dataIndex });
        const replaced = JSON.parse(JSON.stringify(deepRoot));
        let leaf = replaced;
        while (leaf.children.length) { leaf = leaf.children[0]; }
        leaf.id = 'new-leaf';
        nativeRender.refresh({ family: 'tree', theme: 'dark', tree: { title: 'Changed leaf', style,
            root: replaced, count: 13, truncated: false } });
        assert.equal(node('deep-12'), undefined);
        assert.ok(node('new-leaf'));
        assert.equal(node('deep-7').isExpand, false,
            'Membership changes must discard removed IDs while retaining surviving collapsed IDs.');
        assert.equal(series().get('zoom'), zoom);
        const moved = JSON.parse(JSON.stringify(replaced));
        let oldParent = moved;
        while (oldParent.children[0].children.length) { oldParent = oldParent.children[0]; }
        moved.children.push(oldParent.children.pop());
        nativeRender.refresh({ family: 'tree', theme: 'dark', tree: { title: 'Reparented leaf', style,
            root: moved, count: 13, truncated: false } });
        assert.equal(node('new-leaf').parentNode.getId(), 'deep-0');
        assert.equal(node('deep-7').isExpand, false, 'Reparenting must preserve surviving collapsed IDs.');
        assert.equal(series().get('zoom'), zoom, 'Reparenting must preserve the viewport.');
        nativeRender.refresh({ family: 'tree', theme: 'dark', tree: { title: 'Changed layout',
            style: { ...style, treeLayout: orientation === 'radial' ? 'orthogonal' : 'radial' },
            root: deepRoot, count: 13, truncated: false } });
        assert.equal(series().get('zoom'), 1, 'A changed layout must reset the old viewport.');
        assert.equal(node('deep-7').isExpand, true, 'A changed layout must honor configured initial depth.');
        nativeRender.refresh({ family: 'tree', theme: 'dark', tree: { title: 'Changed root', style,
            root, count: 4, truncated: false } });
        assert.equal(node('deep-7'), undefined, 'Removed IDs must not survive a new hierarchy.');
        assert.equal(series().get('zoom'), 1, 'A changed root must reset the old viewport.');
        native.dispose();
    }
}
}

const localECharts = path.join(__dirname, '..', '.tools', 'echarts-runtime', 'node_modules', 'echarts');
if (fs.existsSync(localECharts)) {
    const echarts = require(localECharts);
    const chart = echarts.init(null, null, { renderer: 'svg', ssr: true, width: 800, height: 400 });
    chart.setOption(tree.option);
    const series = chart.getModel().getSeriesByIndex(0);
    assert.equal(series.type, 'series.tree');
    assert.equal(series.getDataParams(1).data.id, 'object-100');
    chart.dispatchAction({ type: 'treeExpandAndCollapse', seriesId: 'tree', dataIndex: 1 });
    assert.equal(series.getDataParams(1).collapsed, true);
    chart.dispose();
}

const compressed = render(baseStyle, root, 800, 'symcon', 400, false, true);
assert.equal(compressed.option, undefined);
setImmediate(() => {
    assert.equal(compressed.option.series[0].data[0].children[0].id, 'object-101');
    console.log('Tree renderer layout verified.');
});
