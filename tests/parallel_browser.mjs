// Local Canvas fixture with synthetic data only; never connects to Symcon.
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const read = name => fs.readFileSync(path.join(root, name), 'utf8');
const variables = ':root{--symc-background:#303236;--symc-text:#f4f5f7;--symc-text-muted:#969aa2;--symc-border:#555860;--symc-surface:#25272b;--symc-accent:#55cbb5}body{font-family:Arial,sans-serif}';
function page(mode, count, fontSize) {
    const axes = Array.from({ length: count }, (_, index) => ({ variableID: 10000 + index,
        label: ['Wohnbereich · Temperatur', 'Bad · Feuchtigkeit', 'Büro · Verbrauch', 'Schlafzimmer · Klima'][index % 4] + ' ' + (index + 1),
        unit: index % 2 ? '%' : '°C', decimals: 1 }));
    const chart = { schemaVersion: 1, family: 'parallel', theme: 'auto',
        range: { lineBudget: 1000, effectiveLineBudget: Math.min(1000, Math.floor(10000 / count)), aggregateBudget: 10000 },
        parallel: { title: 'Parallel · synthetische Archivintervalle', aggregation: 'hour', axes,
            rows: Array.from({ length: 24 }, (_, hour) => ({ timestamp: 1780000000 + hour * 3600,
                values: axes.map((_, index) => Math.round((50 + 40 * Math.sin(hour / 5 + index / 2)) * 10) / 10) })),
            truncated: false, style: { animationEnabled: false, lineOpacityPercent: 45, axisFontSizePercent: fontSize } } };
    const bootstrap = { mode, state: { status: 'ready', chart },
        translations: JSON.parse(read('EChartsParallel/locale.json')).translations.de,
        options: { tileHeaderVisible: true, echartsThemes: {}, adaptToBackground: mode === 'ipsview' } };
    const replacements = { HTML_LANGUAGE: 'de', HTML_CLASSES: '', ROOT_FONT_SIZE: '16px',
        VIEWPORT_CONTENT: 'width=device-width, initial-scale=1', DOCUMENT_TITLE: 'Parallel synthetic fixture',
        VISUALIZATION_THEME: variables, MODULE_STYLE: read('EChartsParallel/visualization/style.css'), IPSVIEW_STYLE: '',
        ECHARTS_SCRIPT: read('libs/echarts/6.1.0/echarts.parallel.min.js'), ECHARTS_THEME_SCRIPT: '',
        ECHARTS_DESIGN_SCRIPT: read('libs/echarts-design.js') + '\n' + read('libs/echarts-category-navigation.js'),
        BOOTSTRAP_JSON: JSON.stringify(bootstrap), MODULE_SCRIPT: read('EChartsParallel/visualization/app.js'), IPSVIEW_TRANSPORT_SCRIPT: '' };
    let html = read('EChartsParallel/visualization/index.html').replace(/\{\{([A-Z_]+)\}\}/g, (_, key) => replacements[key] || '');
    if (mode === 'symcon') {
        html = html.replace('<body>', '<body><div style="position:absolute;top:16px;left:20px;font:18px Arial;z-index:4">EChartsParallel <span style="position:fixed;right:18px">⤢</span></div>');
    }
    return html;
}
http.createServer((request, response) => {
    const url = new URL(request.url, 'http://127.0.0.1');
    const count = [2, 3, 10, 16].includes(Number(url.searchParams.get('axes'))) ? Number(url.searchParams.get('axes')) : 16;
    const font = url.searchParams.get('font') === '200' ? 200 : 100;
    response.setHeader('Content-Type', 'text/html; charset=utf-8');
    if (url.pathname === '/chart') {
        response.end(page(url.searchParams.get('mode') === 'ipsview' ? 'ipsview' : 'symcon', count, font));
    } else {
        response.end('<!doctype html><html lang="de"><body style="background:#101114;color:white;font:16px Arial">'
            + '<h1>Parallel · synthetische Beispieldaten</h1><div style="display:flex;gap:16px;flex-wrap:wrap">'
            + '<iframe title="Kachel" src="/chart?axes=' + count + '&font=' + font + '" style="border:0;width:400px;height:520px"></iframe>'
            + '<iframe title="IPSView" src="/chart?mode=ipsview&axes=' + count + '&font=' + font
            + '" style="border:0;width:790px;height:440px"></iframe></div></body></html>');
    }
}).listen(8803, '127.0.0.1', () => console.log('Parallel fixture: http://127.0.0.1:8803'));
