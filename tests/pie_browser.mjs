// Synthetic local browser fixture only; no Symcon data, private IDs or live connection.
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
import {execFileSync} from 'node:child_process';

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
const palette = {background: '#303236', text: '#f4f5f7', muted: '#969aa2', accent: '#55cbb5',
    seriesColors: ['#5070dd', '#b6d634', '#505174', '#f59d56', '#48a0d8', '#e26391']};
function page(query) {
    const mode = query.get('mode') === 'ipsview' ? 'ipsview' : 'symcon';
    const normalized = query.get('normalized') === 'yes';
    const count = Math.max(1, Math.min(100, Number(query.get('count')) || 100));
    const font = Math.max(50, Math.min(200, Number(query.get('font')) || 100));
    const items = Array.from({length: count}, (_, index) => ({id: 'fixture-source-' + index,
        name: index === 99 ? 'Letzte Quelle' : 'Gerät ' + String(index + 1).padStart(2, '0'),
        value: 1, originalValue: normalized ? 1 : undefined, minimum: normalized ? 0 : undefined,
        maximum: normalized ? 100 : undefined, order: index, unit: normalized ? '%' : 'W', decimals: 1,
        initiallyVisible: index < 10}));
    const bootstrap = {mode, options: {tileHeaderVisible: true, echartsThemes: {auto: palette, dark: palette}},
        translations: {'All': 'Alle', 'Invert': 'Umkehren', 'No sources selected.': 'Keine Quellen ausgewählt.',
            'No positive values': 'Keine positiven Werte',
            'No positive values in the selected sources.': 'Keine positiven Werte in der Auswahl.',
            'Share of visible selection': 'Anteil an sichtbarer Auswahl', 'Share of full model': 'Anteil am vollständigen Modell',
            'Share of selected normalized weights': 'Anteil an ausgewählten normalisierten Gewichten',
            'Share of all normalized weights': 'Anteil an allen normalisierten Gewichten',
            'Normalized': 'Normalisiert', 'Range': 'Bereich', 'Share of normalized weights': 'Anteil normalisierter Gewichte'},
        state: {status: 'ready', chart: {family: 'pie', theme: 'auto', items,
            pie: {title: 'Aktuelle Anteile · ' + count + ' Quellen', normalized, enableSourceSelection: true,
                style: {mode: query.get('shape') || 'donut', sortOrder: 'descending', showLegend: true,
                    showLabels: true, showPercent: true, outerRadiusPercent: 90, innerRadiusPercent: 35,
                    labelPosition: 'outside', legendFontSizePercent: font, labelFontSizePercent: font,
                    animationEnabled: false}}}}};
    const replacements = {HTML_LANGUAGE: 'de', HTML_CLASSES: '', ROOT_FONT_SIZE: '16px',
        VIEWPORT_CONTENT: 'width=device-width, initial-scale=1', DOCUMENT_TITLE: 'Pie browser fixture',
        VISUALIZATION_THEME: ':root{--symc-background:#303236;--symc-text:#f4f5f7;--symc-muted:#969aa2;--symc-surface:#25272b}',
        MODULE_STYLE: read('EChartsPie/visualization/style.css'), IPSVIEW_STYLE: '',
        ECHARTS_SCRIPT: read('libs/echarts/6.1.0/echarts.pie.min.js'), ECHARTS_THEME_SCRIPT: '',
        ECHARTS_DESIGN_SCRIPT: read('libs/echarts-design.js') + '\n' + read('libs/echarts-series-selection.js'),
        BOOTSTRAP_JSON: JSON.stringify(bootstrap), MODULE_SCRIPT: read('EChartsPie/visualization/app.js'),
        IPSVIEW_TRANSPORT_SCRIPT: ''};
    let html = read('EChartsPie/visualization/index.html').replace(/\{\{([A-Z_]+)\}\}/g, (_, key) => replacements[key] ?? '');
    if (mode === 'symcon') {
        html = html.replace('<body>', '<body><div style="position:absolute;top:16px;left:20px;font:18px Arial;z-index:4">EChartsPie <span style="position:fixed;right:18px">⤢</span></div>');
    }
    return html.replace('</body>', `<script>
window.fixtureAction = function (action) {
    var c = echarts.getInstanceByDom(document.getElementById('echarts-pie-chart'));
    var model = SYMC_VISUALIZATION.state.chart;
    if (action === 'all') { c.dispatchAction({type: 'legendAllSelect'}); }
    if (action === 'invert') { c.dispatchAction({type: 'legendInverseSelect'}); }
    if (action === 'last') { c.dispatchAction({type: 'legendScroll', scrollDataIndex: Math.max(0, model.items.length - 6)}); }
    if (action === 'refresh') { model.items[0].value += 10; window.handleMessage(SYMC_VISUALIZATION.state); }
    if (action === 'sort') { model.items[model.items.length - 1].value += 20; window.handleMessage(SYMC_VISUALIZATION.state); }
    if (action === 'zero') { model.items.forEach(function (item) {item.value = 0;}); window.handleMessage(SYMC_VISUALIZATION.state); }
    if (action === 'recover') { model.items.forEach(function (item) {item.value = 10;}); window.handleMessage(SYMC_VISUALIZATION.state); }
};
</script></body>`);
}
const port = Number(process.env.PORT || 8806);
http.createServer((request, response) => {
    const url = new URL(request.url, 'http://localhost');
    if (url.pathname === '/preview.svg') {
        response.writeHead(200, {'Content-Type': 'image/svg+xml; charset=utf-8'});
        response.end(execFileSync('php', ['tests/gateway_gauges.php', '--pie-preview', JSON.stringify({
            PieMode: url.searchParams.get('shape') || 'donut', SortOrder: 'descending',
            LegendFontSizePercent: Number(url.searchParams.get('font')) || 100,
            LabelFontSizePercent: Number(url.searchParams.get('font')) || 100,
            EnableSourceSelection: true
        })], {cwd: root, windowsHide: true, encoding: 'utf8', maxBuffer: 2097152}));
        return;
    }
    response.writeHead(200, {'Content-Type': 'text/html; charset=utf-8'});
    if (url.pathname === '/' && !url.searchParams.has('mode')) {
        const parameters = new URLSearchParams(url.searchParams);
        response.end('<!doctype html><html lang="de"><body style="margin:12px;background:#101114;color:white;font:16px Arial">'
            + '<h1>Pie · synthetische Beispieldaten</h1><div style="display:flex;gap:16px">'
            + ['symcon', 'ipsview'].map(mode => '<section style="width:50%;min-width:0"><h2>' + mode + '</h2><div>'
                + [['all', 'Alle'], ['invert', 'Umkehren'], ['last', 'Letzte Legendenseite'], ['refresh', 'Aktualisieren'],
                    ['sort', 'Reihenfolge ändern'], ['zero', 'Nullwerte'], ['recover', 'Wiederherstellen']].map(([action, label]) =>
                    '<button style="margin:2px" onclick="document.getElementById(\'' + mode + '\').contentWindow.fixtureAction(\'' + action + '\')">' + label + '</button>').join('')
                + '</div><iframe id="' + mode + '" style="border:0;width:100%;height:570px" src="/chart?'
                + parameters.toString() + '&mode=' + mode + '"></iframe></section>').join('')
            + '</div><h2>PHP-Vorschau · 6/100 Quellen</h2><img style="width:720px;max-width:100%" src="/preview.svg?'
            + parameters.toString() + '"></body></html>');
        return;
    }
    response.end(page(url.searchParams));
}).listen(port, '127.0.0.1', () => console.log('Pie browser fixture: http://127.0.0.1:' + port));
