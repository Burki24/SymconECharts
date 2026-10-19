// Synthetic local browser fixture only; no Symcon data or live connection.
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
function page(query) {
    const mode = query.get('mode') === 'ipsview' ? 'ipsview' : 'symcon';
    const shape = query.get('shape') === 'tangential' ? 'tangential' : 'radial';
    const count = Math.max(1, Math.min(100, Number(query.get('count')) || 100));
    const font = Math.max(8, Math.min(24, Number(query.get('font')) || 12));
    const palette = {background: '#303236', text: '#f4f5f7', muted: '#969aa2', border: '#777b83',
        accent: '#55cbb5', seriesColors: ['#5070dd', '#b6d634', '#505174', '#f59d56', '#48a0d8', '#e26391']};
    const items = Array.from({length: count}, (_, index) => ({id: 'fixture-source-' + index,
        label: index === 99 ? 'Letzte Quelle · Maximum' : 'Gerät ' + String(index + 1).padStart(2, '0'),
        value: index === 99 ? 200 : index === 98 ? -50 : 10 + index % 80, order: index,
        initiallyVisible: index < 98, decimals: 0}));
    const bootstrap = {mode, options: {tileHeaderVisible: true, echartsThemes: {auto: palette}},
        translations: {'Sources': 'Quellen', 'All': 'Alle', 'Inverse': 'Umkehren',
            'Previous page': 'Vorherige Seite', 'Next page': 'Nächste Seite',
            'Category navigation': 'Kategoriennavigation', 'No sources selected': 'Keine Quellen ausgewählt'},
        state: {status: 'ready', chart: {theme: 'auto', items,
            polar: {title: '100 Quellen · vollständige Skala −50 bis 200', unit: '%', decimals: 0,
                enableSourceSelection: query.get('selection') !== 'no', style: {mode: shape, sortOrder: 'configured',
                    showCategoryLabels: true, showValues: true, showGrid: true, barWidthPercent: 60,
                    innerRadiusPercent: shape === 'tangential' ? 10 : 0, outerRadiusPercent: 85,
                    startAngle: 90, angularSpan: 360, barHighlightMode: 'outline',
                    categoryLabelFontSize: font, valueLabelFontSize: font, scaleLabelFontSize: 10,
                    animationEnabled: false}}}}};
    const replacements = {HTML_LANGUAGE: 'de', HTML_CLASSES: '', ROOT_FONT_SIZE: '16px',
        VIEWPORT_CONTENT: 'width=device-width, initial-scale=1', DOCUMENT_TITLE: 'Polar browser fixture',
        VISUALIZATION_THEME: ':root{--symc-background:#303236;--symc-text:#f4f5f7;--symc-text-muted:#969aa2;--symc-muted:#969aa2;--symc-border:#777b83;--symc-surface:#25272b}',
        MODULE_STYLE: read('EChartsBarPolar/visualization/style.css'), IPSVIEW_STYLE: '',
        ECHARTS_SCRIPT: read('libs/echarts/6.1.0/echarts.polar.min.js'), ECHARTS_THEME_SCRIPT: '',
        ECHARTS_DESIGN_SCRIPT: read('libs/echarts-design.js') + '\n' + read('libs/echarts-series-selection.js')
            + '\n' + read('libs/echarts-category-navigation.js'),
        BOOTSTRAP_JSON: JSON.stringify(bootstrap), MODULE_SCRIPT: read('EChartsBarPolar/visualization/app.js'),
        IPSVIEW_TRANSPORT_SCRIPT: ''};
    let html = read('EChartsBarPolar/visualization/index.html').replace(/\{\{([A-Z_]+)\}\}/g, (_, key) => replacements[key] ?? '');
    if (mode === 'symcon') {
        html = html.replace('<body>', '<body><div style="position:absolute;top:16px;left:20px;font:18px Arial;z-index:4">EChartsBarPolar <span style="position:fixed;right:18px">⤢</span></div>');
    }
    return html.replace('</body>', `<script>
window.fixtureAction = function (action) {
    if (action === 'last') {
        var next = document.getElementById('echarts-bar-polar-next');
        for (var i = 0; i < 100 && !next.disabled; i++) { next.click(); }
    }
    if (action === 'refresh') { SYMC_VISUALIZATION.state.chart.items[99].value += 50; window.handleMessage(SYMC_VISUALIZATION.state); }
    if (action === 'inverse') { document.getElementById('echarts-bar-polar-inverse').click(); }
    if (action === 'all') { document.getElementById('echarts-bar-polar-all').click(); }
};
</script></body>`);
}
const port = Number(process.env.PORT || 8807);
http.createServer((request, response) => {
    const url = new URL(request.url, 'http://localhost');
    response.writeHead(200, {'Content-Type': 'text/html; charset=utf-8'});
    if (url.pathname === '/' && !url.searchParams.has('mode')) {
        response.end('<!doctype html><html lang="de"><body style="margin:12px;background:#101114;color:white;font:16px Arial">'
            + '<h1>Polar · synthetische Beispieldaten</h1><div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">'
            + ['radial', 'tangential'].flatMap(shape => ['symcon', 'ipsview'].map(mode => {
                const id = shape + '-' + mode;
                return '<section style="min-width:0"><h2>' + mode + ' · ' + shape + '</h2>'
                    + [['all', 'Alle'], ['inverse', 'Umkehren'], ['last', 'Letzte Seite'], ['refresh', 'Aktualisieren']]
                        .map(([action, label]) => '<button onclick="document.getElementById(\'' + id
                            + '\').contentWindow.fixtureAction(\'' + action + '\')">' + label + '</button>').join('')
                    + '<iframe id="' + id + '" style="border:0;width:100%;height:570px" src="/chart?mode='
                    + mode + '&shape=' + shape + '&font=' + (url.searchParams.get('font') || 12) + '"></iframe></section>';
            })).join('') + '</div></body></html>');
        return;
    }
    response.end(page(url.searchParams));
}).listen(port, '127.0.0.1', () => console.log('Polar browser fixture: http://127.0.0.1:' + port));
