// Synthetic local fixture only. No Symcon connection or private installation data.
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
const presets = ['multi-title', 'ring-grid', 'ring-concentric', 'weather-station'];
function page(query) {
    const mode = query.get('mode') === 'ipsview' ? 'ipsview' : 'symcon';
    const preset = presets.includes(query.get('preset')) ? query.get('preset') : 'multi-title';
    const count = Math.max(2, Math.min(32, Number(query.get('count')) || 32));
    const scale = Math.max(50, Math.min(150, Number(query.get('font')) || 100));
    const palette = {background: '#303236', text: '#f4f5f7', muted: '#aeb1b8', border: '#777b83',
        track: '#44474d', accent: '#55cbb5'};
    const items = Array.from({length: count}, (_, index) => ({id: 'fixture-gauge-' + index,
        gauge: {label: 'Messpunkt ' + String(index + 1).padStart(2, '0'), minimum: index % 2 ? 0 : -20,
            maximum: index % 2 ? 100 : 40, unit: index % 2 ? '%' : '°C', decimals: index % 2 ? 0 : 1},
        value: index % 2 ? 20 + index * 2 : 8 + index / 2, initiallyVisible: index < 30}));
    const bootstrap = {mode, options: {tileHeaderVisible: true, echartsThemes: {auto: palette}},
        translations: {'Sources': 'Quellen', 'All': 'Alle', 'Inverse': 'Umkehren',
            'Previous page': 'Vorherige Seite', 'Next page': 'Nächste Seite',
            'Category navigation': 'Seitennavigation', 'No sources selected': 'Keine Quellen ausgewählt'},
        state: {status: 'ready', chart: {theme: 'auto', items,
            gauge: {title: '32 Messpunkte · individuelle Skalen', preset,
                enableSourceSelection: query.get('selection') !== 'no', style: {animationEnabled: false,
                    scaleFontSizePercent: scale, titleFontSizePercent: scale, valueFontSizePercent: scale}}}}};
    const replacements = {HTML_LANGUAGE: 'de', HTML_CLASSES: '', ROOT_FONT_SIZE: '16px',
        VIEWPORT_CONTENT: 'width=device-width, initial-scale=1', DOCUMENT_TITLE: 'Gauge Multi synthetic fixture',
        VISUALIZATION_THEME: ':root{--symc-background:#303236;--symc-text:#f4f5f7;--symc-muted:#aeb1b8;--symc-border:#777b83;--symc-surface:#44474d}',
        MODULE_STYLE: read('EChartsGaugeMulti/visualization/style.css') + '\n' + read('libs/echarts-source-controls.css'),
        IPSVIEW_STYLE: '', ECHARTS_SCRIPT: read('libs/echarts/6.1.0/echarts.gauge.min.js'), ECHARTS_THEME_SCRIPT: '',
        ECHARTS_DESIGN_SCRIPT: ['echarts-design.js', 'echarts-series-selection.js', 'echarts-category-navigation.js',
            'echarts-source-controls.js'].map(file => read('libs/' + file)).join('\n'),
        BOOTSTRAP_JSON: JSON.stringify(bootstrap), MODULE_SCRIPT: read('EChartsGaugeMulti/visualization/app.js'),
        IPSVIEW_TRANSPORT_SCRIPT: ''};
    let html = read('EChartsGaugeMulti/visualization/index.html').replace(/\{\{([A-Z_]+)\}\}/g, (_, key) => replacements[key] ?? '');
    if (mode === 'symcon') {
        html = html.replace('<body>', '<body><div style="position:absolute;top:16px;left:20px;font:18px Arial;z-index:4">EChartsGaugeMulti <span style="position:fixed;right:18px">⤢</span></div>');
    }
    return html.replace('</body>', `<script>
window.fixtureAction = function (action) {
    if (action === 'last') {
        var next = document.getElementById('echarts-gauge-next');
        for (var i = 0; i < 32 && !next.disabled; i++) { next.click(); }
    }
    if (action === 'refresh') {
        SYMC_VISUALIZATION.state.chart.items.forEach(function (item) { item.value += 1; });
        window.handleMessage(SYMC_VISUALIZATION.state);
    }
    if (action === 'inverse') { document.getElementById('echarts-gauge-inverse').click(); }
    if (action === 'error') { window.handleMessage({status: 'error', chart: null, error: 'The Multi Gauge values could not be loaded.'}); }
    if (action === 'all') { document.getElementById('echarts-gauge-all').click(); }
    if (action === 'first') {
        var previous = document.getElementById('echarts-gauge-previous');
        for (var i = 0; i < 32 && !previous.disabled; i++) { previous.click(); }
    }
};
</script></body>`);
}
const port = Number(process.env.PORT || 8808);
http.createServer((request, response) => {
    const url = new URL(request.url, 'http://localhost');
    response.writeHead(200, {'Content-Type': 'text/html; charset=utf-8'});
    if (url.pathname === '/' && !url.searchParams.has('mode')) {
        response.end('<!doctype html><html lang="de"><body style="margin:12px;background:#101114;color:white;font:16px Arial">'
            + '<h1>Gauge Multi · synthetische Beispieldaten</h1><div style="display:grid;grid-template-columns:'
            + (url.searchParams.has('narrow') ? '240px' : '1fr 1fr') + ';gap:16px">'
            + presets.flatMap(preset => ['symcon', 'ipsview'].map(mode => {
                const id = preset + '-' + mode;
                return '<section style="min-width:0"><h2>' + mode + ' · ' + preset + '</h2>'
                    + [['all', 'Alle'], ['inverse', 'Umkehren'], ['last', 'Letzte Seite'], ['refresh', 'Aktualisieren'], ['error', 'Ladefehler']]
                        .map(([action, label]) => '<button onclick="document.getElementById(\'' + id
                            + '\').contentWindow.fixtureAction(\'' + action + '\')">' + label + '</button>').join('')
                    + '<iframe id="' + id + '" style="border:0;width:100%;height:540px" src="/chart?mode='
                    + mode + '&preset=' + preset + '&font=' + (url.searchParams.get('font') || 100) + '"></iframe></section>';
            })).join('') + '</div></body></html>');
        return;
    }
    response.end(page(url.searchParams));
}).listen(port, '127.0.0.1', () => console.log('Gauge Multi browser fixture: http://127.0.0.1:' + port));
