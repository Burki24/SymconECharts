// Local browser fixture only; no Symcon connection or installation data.
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
function page(query) {
    const mode = query.get('mode') === 'ipsview' ? 'ipsview' : 'symcon';
    const barMode = ['grouped', 'stacked'].includes(query.get('bar')) ? query.get('bar') : 'simple';
    const orientation = query.get('orientation') === 'vertical' ? 'vertical' : 'horizontal';
    const count = Math.min(100, Math.max(1, Number(query.get('count')) || 100));
    const items = Array.from({length: count}, (_, index) => ({
        id: 'source-' + index, variableID: 88000 + index,
        category: barMode === 'simple' ? 'Batterie ' + String(index + 1).padStart(3, '0')
            : 'Bereich ' + String(Math.floor(index / 2) + 1).padStart(2, '0'),
        series: index % 2 ? 'Sensor B' : 'Sensor A', seriesKey: 'series-' + index % 2,
        value: index === count - 1 ? -25 : (index * 7) % 101, order: index
    }));
    const palette = {background: '#303236', text: '#f4f5f7', muted: '#969aa2', border: '#777b83',
        accent: '#55cbb5', surface: '#25272b', seriesColors: ['#5070dd', '#b6d634', '#ff994d']};
    const bootstrap = {mode, options: {tileHeaderVisible: true, echartsThemes: {auto: palette}}, translations: {},
        state: {status: 'ready', chart: {theme: 'auto', bar: {
            title: 'Aktuelle Vergleichswerte · ' + count + ' Quellen', mode: barMode, orientation,
            sortOrder: 'configured', unit: '%', decimals: 0,
            style: {showValues: true, showGrid: true, roundedBars: false, barWidthPercent: 70, animationEnabled: false}
        }, items}}};
    const replacements = {HTML_LANGUAGE: 'de', HTML_CLASSES: '', ROOT_FONT_SIZE: '16px',
        VIEWPORT_CONTENT: 'width=device-width, initial-scale=1', DOCUMENT_TITLE: 'Category Bar browser fixture',
        VISUALIZATION_THEME: ':root{--symc-background:#303236;--symc-text:#f4f5f7;--symc-text-muted:#969aa2;--symc-border:#777b83;--symc-surface:#25272b}',
        MODULE_STYLE: read('EChartsBarCategory/visualization/style.css'), IPSVIEW_STYLE: '',
        ECHARTS_SCRIPT: read('libs/echarts/6.1.0/echarts.bar-category.min.js'), ECHARTS_THEME_SCRIPT: '',
        ECHARTS_DESIGN_SCRIPT: read('libs/echarts-design.js'), ECHARTS_PATTERN_SCRIPT: read('libs/echarts-pattern.js'),
        BOOTSTRAP_JSON: JSON.stringify(bootstrap), MODULE_SCRIPT: read('EChartsBarCategory/visualization/app.js'),
        IPSVIEW_TRANSPORT_SCRIPT: ''};
    let html = read('EChartsBarCategory/visualization/index.html').replace(/\{\{([A-Z_]+)\}\}/g, (_, key) => replacements[key] ?? '');
    if (mode === 'symcon') {
        html = html.replace('<body>', '<body><div style="position:absolute;top:16px;left:20px;font:18px Arial;z-index:4">EChartsBarCategory <span style="position:fixed;right:18px">⤢</span></div>');
    }
    return html;
}
const port = Number(process.env.PORT || 8802);
http.createServer((request, response) => {
    response.writeHead(200, {'Content-Type': 'text/html; charset=utf-8'});
    response.end(page(new URL(request.url, 'http://localhost').searchParams));
}).listen(port, '127.0.0.1', () => console.log('Category Bar browser fixture: http://127.0.0.1:' + port));
