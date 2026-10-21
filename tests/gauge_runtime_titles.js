'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const root = path.join(__dirname, '..');
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
const runtime = read('libs/echarts/6.1.0/echarts.gauge.min.js');
const built = require('../.tools/echarts-runtime/node_modules/esbuild').buildSync({
    entryPoints: [path.join(root, '.tools/echarts-runtime/src/gauge-runtime.js')],
    bundle: true, minify: true, format: 'iife', target: 'es2018', legalComments: 'inline',
    write: false, logLevel: 'silent'
}).outputFiles[0].text;
assert.equal(runtime, built, 'The shipped Gauge runtime must match its pinned, reproducible build.');

const shipped = {};
vm.runInNewContext(runtime, { window: shipped, navigator: { userAgent: 'Node.js' }, console, setTimeout, clearTimeout });
// Exercise the shipped CanvasRenderer without registering any missing components.
// Only the browser's drawing surface is replaced; record the real fillText calls.
function canvasSurface(texts) {
    const canvas = { nodeName: 'CANVAS', style: {}, width: 720, height: 630, setAttribute() {} };
    const context = new Proxy({ canvas,
        measureText: text => ({ width: String(text).length * 8 }),
        fillText: text => { texts.push(String(text)); },
        getLineDash: () => [],
        createLinearGradient: () => ({ addColorStop() {} }),
        createRadialGradient: () => ({ addColorStop() {} })
    }, { get: (target, key) => key in target ? target[key] : () => {} });
    canvas.getContext = () => context;
    return canvas;
}
shipped.echarts.setPlatformAPI({ createCanvas: () => canvasSurface([]) });

function nativeChart() {
    const texts = [];
    const chart = shipped.echarts.init(canvasSurface(texts), null, { renderer: 'canvas', ssr: true, width, height });
    return { chart, draw: option => {
        texts.length = 0;
        chart.setOption(option, true);
        chart.getZr().flush();
        return texts;
    } };
}

const width = 720;
const height = 630;
const palette = { background: '#202020', text: '#ffffff', muted: '#aaaaaa', border: '#cccccc',
    track: '#444444', accent: '#55cbb5', surface: '#333333' };

function application(family, mode, preset, tileHeaderVisible) {
    const chartElement = { clientWidth: width, clientHeight: height, hidden: false, setAttribute() {} };
    const errorElement = { hidden: true, textContent: '' };
    const gauge = { title: 'Configured chart title', preset, minimum: -20, maximum: 40, unit: 'C', decimals: 1,
        style: { animationEnabled: false, chartTitleFontSizePercent: 150, chartTitleColor: '#123ABC' } };
    const chart = { theme: 'dark', gauge, value: 12.5,
        items: [0, 1, 2].map(index => ({ id: 'source-' + index, value: index * 10 + 30,
            gauge: { label: 'Instrument ' + index, minimum: index * 10, maximum: index * 10 + 100,
                unit: 'u' + index, decimals: 1 } })) };
    let option;
    const window = {
        SYMC_VISUALIZATION: { mode, state: { status: 'ready', chart },
            options: { tileHeaderVisible: mode === 'symcon' && tileHeaderVisible, tileHeaderInset: mode === 'symcon' && tileHeaderVisible ? 58 : 0, echartsThemes: { dark: palette } } },
        echarts: { init: () => ({ setOption: next => { option = next; }, clear() {}, dispose() {} }) },
        addEventListener() {}
    };
    const document = { getElementById: id => id === 'echarts-gauge-chart' ? chartElement : errorElement };
    const context = { window, document };
    vm.runInNewContext(read('libs/echarts-design.js'), context);
    vm.runInNewContext(read(family + '/visualization/app.js'), context);
    assert.ok(option, family + ' must provide its real renderer option.');
    return { getOption: () => option, setTitle: title => {
        window.handleMessage({ status: 'ready', chart: { ...chart, gauge: { ...gauge, title } } });
    } };
}

for (const [family, presets] of [
    ['EChartsGaugeMulti', ['multi-title', 'ring-grid', 'ring-concentric', 'weather-station']],
    ['EChartsGaugeTacho', ['tacho']],
    ['EChartsGaugeChronograph', ['chronograph']]
]) {
    for (const mode of ['symcon', 'ipsview']) {
        for (const tileHeaderVisible of mode === 'symcon' ? [true, false] : [false]) {
            for (const preset of presets) {
                const app = application(family, mode, preset, tileHeaderVisible);
                const { chart: native, draw } = nativeChart();
                try {
                    const drawn = draw(app.getOption());
                    const title = native.getModel().getComponent('title');
                    const description = `${family}/${preset}/${mode}/header=${tileHeaderVisible}`;
                    assert.ok(title, description + ' must register the configured chart title in the shipped runtime.');
                    assert.equal(title.get('text'), 'Configured chart title');
                    assert.equal(title.get('textStyle.color'), '#123ABC');
                    assert.equal(title.get('textStyle.fontSize'), 36);
                    assert.ok(title.get('top') >= (mode === 'symcon' && tileHeaderVisible ? 64 : 6),
                        description + ' must preserve the existing tile-header clearance.');
                    const view = native.getViewOfComponentModel(title);
                    const texts = [];
                    view.group.traverse(element => { if (element.type === 'text') { texts.push(element.style.text); } });
                    assert.ok(texts.includes('Configured chart title'), description + ' must create a real title display element.');
                    assert.ok(drawn.includes('Configured chart title'),
                        description + ' must draw the title, not merely retain an option.');
                    native.getModel().getSeries().forEach((series, index) => {
                        assert.equal(series.get('min'), index * 10);
                        assert.equal(series.get('max'), index * 10 + 100);
                        assert.equal(series.getData().get('value', 0), index * 10 + 30);
                    });
                    app.setTitle('');
                    assert.ok(!draw(app.getOption()).includes('Configured chart title'),
                        'Clearing a title must remove its previous display element.');
                    app.setTitle('Updated chart title');
                    native.resize({ width: 400, height: 500 });
                    assert.ok(draw(app.getOption()).includes('Updated chart title'),
                        'The title must also render after a data update and resize.');
                } finally {
                    native.dispose();
                }
            }
        }
    }
}

// GaugeSingle uses the instrument's own title instead of an outer chart-title component.
for (const mode of ['symcon', 'ipsview']) {
    const app = application('EChartsGaugeSingle', mode, 'simple', true);
    const { chart: native, draw } = nativeChart();
    try {
        assert.ok(draw(app.getOption()).includes('Configured chart title'));
        assert.equal(native.getModel().getSeries()[0].get('min'), -20);
        assert.equal(native.getModel().getSeries()[0].get('max'), 40);
    } finally {
        native.dispose();
    }
}

process.stdout.write('Pinned Gauge runtime draws configured titles for tile and IPSView without changing instrument scales.\n');
