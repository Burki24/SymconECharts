// Local Canvas fixture with synthetic values only; never accesses Symcon.
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const read = name => fs.readFileSync(path.join(root, name), 'utf8');
const palette = { background: '#303236', text: '#f4f5f7', muted: '#b9bdc5', border: '#555860',
    track: '#25272b', accent: '#55cbb5', seriesColors: ['#5470c6', '#91cc75', '#fac858', '#ee6666', '#73c0de', '#3ba272'] };
function page(mode, count, font) {
    const groups = Array.from({ length: count }, (_, i) => ({ id: 'comparison-' + i, colorIndex: i,
        label: ['Wohnzimmer', 'Bad', 'Büro', 'Schlafzimmer'][i % 4] + ' · Vergleich ' + (i + 1),
        roles: { x: { label: 'Temperatur', unit: '°C', decimals: 1 }, y: { label: 'Luftfeuchtigkeit', unit: '%', decimals: 0 },
            ...(i % 3 ? { size: { label: 'Batterie', unit: '%', decimals: 0 } } : {}) },
        points: Array.from({ length: 14 }, (_, n) => [1780000000 + n * 3600,
            16 + i * 0.6 + n * 0.3, 40 + n * 2 + i % 6 * 2, ...(i % 3 ? [n * 7] : [])]) }));
    const chart = { family: 'scatter', variant: 'groups', theme: 'auto', groups,
        roles: { x: { label: 'X-Achse', unit: '°C', decimals: 1 }, y: { label: 'Y-Achse', unit: '%', decimals: 0 } },
        range: { key: '24h', pointBudget: 2000, startTimestamp: 1779900000, endTimestamp: 1780000000 },
        scatter: { title: 'Klima · benannte Vergleichsgruppen', style: { animationEnabled: false, enableZoom: true,
            enableBrush: true, showGrid: true, showValues: false, pointSize: 8, bubbleMaxSize: 34,
            bubbleRangeMode: 'manual', bubbleMinimum: 0, bubbleMaximum: 100,
            axisFontSizePercent: font, titleFontSizePercent: font } } };
    const bootstrap = { mode, state: { status: 'ready', chart },
        translations: JSON.parse(read('EChartsScatter/locale.json')).translations.de,
        options: { tileHeaderVisible: mode === 'symcon', tileHeaderInset: mode === 'symcon' ? 58 : 0,
            echartsThemes: { auto: palette }, adaptToBackground: false } };
    const replacements = { HTML_LANGUAGE: 'de', HTML_CLASSES: '', ROOT_FONT_SIZE: '16px',
        VIEWPORT_CONTENT: 'width=device-width, initial-scale=1', DOCUMENT_TITLE: 'Scatter synthetic fixture',
        VISUALIZATION_THEME: ':root{--symc-background:#303236;--symc-text:#f4f5f7;--symc-text-muted:#b9bdc5;--symc-border:#555860;--symc-accent:#55cbb5}body{font-family:Arial,sans-serif}',
        MODULE_STYLE: read('EChartsScatter/visualization/style.css'), IPSVIEW_STYLE: '',
        ECHARTS_SCRIPT: read('libs/echarts/6.1.0/echarts.scatter.min.js'), ECHARTS_THEME_SCRIPT: '',
        ECHARTS_DESIGN_SCRIPT: read('libs/echarts-design.js') + '\n' + read('libs/echarts-series-selection.js'),
        ECHARTS_ZOOM_SCRIPT: read('libs/echarts-zoom.js'), BOOTSTRAP_JSON: JSON.stringify(bootstrap),
        MODULE_SCRIPT: read('EChartsScatter/visualization/app.js'), IPSVIEW_TRANSPORT_SCRIPT: '' };
    let html = read('EChartsScatter/visualization/index.html').replace(/\{\{([A-Z_]+)\}\}/g, (_, key) => replacements[key] || '');
    if (mode === 'symcon') { html = html.replace('<body>', '<body><div style="position:absolute;top:16px;left:20px;font:18px Arial;z-index:4">EChartsScatter <span style="position:fixed;right:18px">⤢</span></div>'); }
    return html;
}
http.createServer((request, response) => {
    const url = new URL(request.url, 'http://127.0.0.1');
    const count = url.searchParams.get('groups') === '2' ? 2 : 16;
    const font = url.searchParams.get('font') === '150' ? 150 : 100;
    response.setHeader('Content-Type', 'text/html; charset=utf-8');
    if (url.pathname === '/chart') { response.end(page(url.searchParams.get('mode') === 'ipsview' ? 'ipsview' : 'symcon', count, font)); }
    else { response.end('<!doctype html><html lang="de"><body style="background:#101114;color:white;font:16px Arial"><h1>Scatter · synthetische Vergleiche</h1><div style="display:flex;gap:16px;flex-wrap:wrap">'
        + '<iframe title="Kachel" src="/chart?groups=' + count + '&font=' + font + '" style="border:0;width:400px;height:520px"></iframe>'
        + '<iframe title="IPSView" src="/chart?mode=ipsview&groups=' + count + '&font=' + font + '" style="border:0;width:790px;height:440px"></iframe></div></body></html>'); }
}).listen(8804, '127.0.0.1', () => console.log('Scatter fixture: http://127.0.0.1:8804'));
