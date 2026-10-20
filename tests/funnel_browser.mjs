// Synthetic local Canvas fixture. No Symcon connection or installation data.
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
function page(query) {
    const mode = query.get('mode') === 'ipsview' ? 'ipsview' : 'symcon';
    const horizontal = query.get('orientation') === 'horizontal';
    const count = Math.max(1, Math.min(32, Number(query.get('count')) || (query.get('design') === 'extended' ? 16 : 32)));
    const font = Math.max(50, Math.min(200, Number(query.get('font')) || 100));
    const light = query.get('theme') === 'light';
    const palette = {background: light ? '#ffffff' : '#303236', text: light ? '#222222' : '#f4f5f7',
        muted: light ? '#666666' : '#aeb1b8', border: light ? '#cccccc' : '#777b83',
        accent: '#55cbb5', seriesColors: ['#5470c6', '#91cc75', '#fac858', '#ee6666', '#73c0de', '#9a60b4']};
    const data = Array.from({length: count}, (_, index) => ({id: 'fixture-stage-' + index,
        label: 'Verbraucher ' + String(index + 1).padStart(2, '0'), value: index === 31 ? 100 : 12 + index * 2,
        unit: 'W', decimals: 1, initiallyVisible: index !== 31, color: ''}));
    const bootstrap = {mode, options: {tileHeaderVisible: true, echartsThemes: {auto: palette}, adaptToBackground: false},
        translations: {'Sources': 'Quellen', 'All': 'Alle', 'Inverse': 'Umkehren',
            'Previous page': 'Vorherige Seite', 'Next page': 'Nächste Seite',
            'Category navigation': 'Stufennavigation', 'No sources selected': 'Keine Quellen ausgewählt',
            'All selected stage values are zero.': 'Alle ausgewählten Stufenwerte sind null.'},
        state: {status: 'ready', chart: {theme: 'auto', family: 'funnel', funnel: {
            title: '32 Stufen · vollständige Breitenskala', data,
            enableSourceSelection: query.get('selection') !== 'no', style: {animationEnabled: false,
                orientation: horizontal ? 'horizontal' : 'vertical', gap: 3, sortOrder: 'descending',
                labelPosition: query.get('labels') === 'outside' ? 'outside' : 'inside',
                showLabels: true, showValues: true, labelFontSizePercent: font, titleFontSizePercent: 100}}}}};
    if (query.get('design') === 'polished') {
        Object.assign(bootstrap.state.chart.funnel.style, {minSizePercent: 20, maxSizePercent: 90,
            borderType: 'dashed', borderColor: light ? '#555555' : '#CBD6E8', shadowBlur: 8,
            shadowColor: '#000000', shadowOffsetX: 3, shadowOffsetY: 3, hoverFocus: 'self', blurOpacityPercent: 15,
            labelMaxWidth: 130, labelPosition: 'outside', outsideLabelSide: 'start', labelLineLength: 24,
            labelLineWidth: 2, labelLineType: 'dotted', labelLineColor: light ? '#555555' : '#CBD6E8',
            labelLineOpacityPercent: 70, fontFamily: 'Georgia, serif'});
    }
    if (query.get('design') === 'extended') {
        Object.assign(bootstrap.state.chart.funnel.style, {labelFontSizePercent: 150, gap: 30});
        if (!query.has('selection')) { bootstrap.state.chart.funnel.enableSourceSelection = false; }
    }
    const replacements = {HTML_LANGUAGE: 'de', HTML_CLASSES: '', ROOT_FONT_SIZE: '16px',
        VIEWPORT_CONTENT: 'width=device-width, initial-scale=1', DOCUMENT_TITLE: 'Funnel synthetic fixture',
        VISUALIZATION_THEME: ':root{--symc-background:' + palette.background + ';--symc-text:' + palette.text
            + ';--symc-text-muted:' + palette.muted + ';--symc-muted:' + palette.muted + ';--symc-border:'
            + palette.border + ';--symc-surface:' + (light ? '#eeeeee' : '#44474d') + '}',
        MODULE_STYLE: read('EChartsFunnel/visualization/style.css') + '\n' + read('libs/echarts-source-controls.css'),
        IPSVIEW_STYLE: '', ECHARTS_SCRIPT: read('libs/echarts/6.1.0/echarts.funnel.min.js'), ECHARTS_THEME_SCRIPT: '',
        ECHARTS_DESIGN_SCRIPT: ['echarts-design.js', 'echarts-series-selection.js', 'echarts-category-navigation.js',
            'echarts-source-controls.js'].map(file => read('libs/' + file)).join('\n'),
        BOOTSTRAP_JSON: JSON.stringify(bootstrap), MODULE_SCRIPT: read('EChartsFunnel/visualization/app.js'),
        IPSVIEW_TRANSPORT_SCRIPT: ''};
    let html = read('EChartsFunnel/visualization/index.html').replace(/\{\{([A-Z_]+)\}\}/g, (_, key) => replacements[key] ?? '');
    if (mode === 'symcon') {
        html = html.replace('<body>', '<body><div style="position:absolute;top:16px;left:20px;font:18px Arial;z-index:4">EChartsFunnel <span style="position:fixed;right:18px">⤢</span></div>');
    }
    return html.replace('</body>', `<script>
window.fixtureAction = function (action) {
    var prefix = 'echarts-funnel-';
    if (action === 'last') {
        var next = document.getElementById(prefix + 'next');
        for (var i = 0; i < 32 && !next.disabled; i++) { next.click(); }
    }
    if (action === 'refresh') {
        SYMC_VISUALIZATION.state.chart.funnel.data.forEach(function (item) { item.value += 1; });
        window.handleMessage(SYMC_VISUALIZATION.state);
    }
    if (action === 'inverse' || action === 'all') { document.getElementById(prefix + action).click(); }
    if (action === 'error') { window.handleMessage({status: 'error', chart: null, error: 'The Funnel values could not be loaded.'}); }
};
</script></body>`);
}
const port = Number(process.env.PORT || 8799);
http.createServer((request, response) => {
    const url = new URL(request.url, 'http://localhost');
    response.writeHead(200, {'Content-Type': 'text/html; charset=utf-8'});
    if (url.pathname === '/' && !url.searchParams.has('mode')) {
        response.end('<!doctype html><html lang="de"><body style="margin:12px;background:#101114;color:white;font:16px Arial">'
            + '<h1>Funnel · synthetische Beispieldaten</h1><div style="display:grid;grid-template-columns:'
            + (url.searchParams.has('narrow') ? '240px' : '1fr 1fr') + ';gap:16px">'
            + ['vertical', 'horizontal'].flatMap(orientation => ['symcon', 'ipsview'].map(mode => {
                const id = orientation + '-' + mode;
                return '<section style="min-width:0"><h2>' + mode + ' · ' + orientation + '</h2>'
                    + [['all', 'Alle'], ['inverse', 'Umkehren'], ['last', 'Letzte Seite'], ['refresh', 'Aktualisieren'], ['error', 'Ladefehler']]
                        .map(([action, label]) => '<button onclick="document.getElementById(\'' + id
                            + '\').contentWindow.fixtureAction(\'' + action + '\')">' + label + '</button>').join('')
                    + '<iframe id="' + id + '" style="border:0;width:100%;height:540px" src="/chart?mode='
                    + mode + '&orientation=' + orientation + '&font=' + (url.searchParams.get('font') || 100) + '"></iframe></section>';
            })).join('') + '</div></body></html>');
        return;
    }
    response.end(page(url.searchParams));
}).listen(port, '127.0.0.1', () => console.log('Funnel browser fixture: http://127.0.0.1:' + port));
