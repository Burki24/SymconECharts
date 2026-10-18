// Local browser fixture only; no Symcon connection or installation data.
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
import {execFileSync} from 'node:child_process';

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
function page(query) {
    const mode = query.get('mode') === 'ipsview' ? 'ipsview' : 'symcon';
    const barMode = ['grouped', 'stacked'].includes(query.get('bar')) ? query.get('bar') : 'simple';
    const orientation = query.get('orientation') === 'vertical' ? 'vertical' : 'horizontal';
    const count = Math.min(100, Math.max(1, Number(query.get('count')) || 100));
    const fill = query.get('fill') === 'gradient' ? 'gradient' : 'solid';
    const signed = query.get('signed') === '1' || fill !== 'gradient';
    const requestedMinimum = Number(query.get('min') ?? 0), requestedMaximum = Number(query.get('max') ?? 100);
    const colorMinimum = Number.isFinite(requestedMinimum) ? requestedMinimum : 0;
    const colorMaximum = Number.isFinite(requestedMaximum) && requestedMaximum > colorMinimum ? requestedMaximum : colorMinimum + 100;
    const items = Array.from({length: count}, (_, index) => ({
        id: 'source-' + index, variableID: 88000 + index,
        category: barMode === 'simple' ? 'Batterie ' + String(index + 1).padStart(3, '0')
            : 'Bereich ' + String(Math.floor(index / 2) + 1).padStart(2, '0'),
        series: index % 2 ? 'Sensor B' : 'Sensor A', seriesKey: 'series-' + index % 2,
        value: signed && index === count - 1 ? -25 : (index * 7) % 101, order: index
    }));
    const palette = {background: '#303236', text: '#f4f5f7', muted: '#969aa2', border: '#777b83',
        accent: '#55cbb5', surface: '#25272b', seriesColors: ['#5070dd', '#b6d634', '#ff994d']};
    const bootstrap = {mode, options: {tileHeaderVisible: true, echartsThemes: {auto: palette}}, translations: {},
        state: {status: 'ready', chart: {theme: 'auto', bar: {
            title: 'Aktuelle Vergleichswerte · ' + count + ' Quellen', mode: barMode, orientation,
            sortOrder: 'configured', unit: '%', decimals: 0,
            style: {showValues: true, showGrid: true, roundedBars: false,
                barWidthPercent: Math.max(20, Math.min(100, Number(query.get('width')) || 70)),
                axisFontSizePercent: Math.max(50, Math.min(200, Number(query.get('font')) || 100)),
                valueFontSizePercent: Math.max(50, Math.min(200, Number(query.get('font')) || 100)), animationEnabled: false,
                barFillMode: fill, colorMinimum, colorMaximum, lowColor: '#ff0000', middleColor: '#ffff00', highColor: '#00ff00'}
        }, items}}};
    const replacements = {HTML_LANGUAGE: 'de', HTML_CLASSES: '', ROOT_FONT_SIZE: '16px',
        VIEWPORT_CONTENT: 'width=device-width, initial-scale=1', DOCUMENT_TITLE: 'Category Bar browser fixture',
        VISUALIZATION_THEME: ':root{--symc-background:#303236;--symc-text:#f4f5f7;--symc-text-muted:#969aa2;--symc-border:#777b83;--symc-surface:#25272b}',
        MODULE_STYLE: read('EChartsBarCategory/visualization/style.css'), IPSVIEW_STYLE: '',
        ECHARTS_SCRIPT: read('libs/echarts/6.1.0/echarts.bar-category.min.js'), ECHARTS_THEME_SCRIPT: '',
        ECHARTS_DESIGN_SCRIPT: read('libs/echarts-design.js'), ECHARTS_PATTERN_SCRIPT: read('libs/echarts-pattern.js'),
        BOOTSTRAP_JSON: JSON.stringify(bootstrap), MODULE_SCRIPT: read('EChartsBarCategory/visualization/app.min.js'),
        IPSVIEW_TRANSPORT_SCRIPT: ''};
    let html = read('EChartsBarCategory/visualization/index.html').replace(/\{\{([A-Z_]+)\}\}/g, (_, key) => replacements[key] ?? '');
    if (mode === 'symcon') {
        html = html.replace('<body>', '<body><div style="position:absolute;top:16px;left:20px;font:18px Arial;z-index:4">EChartsBarCategory <span style="position:fixed;right:18px">⤢</span></div>');
    }
    return html;
}
const port = Number(process.env.PORT || 8802);
http.createServer((request, response) => {
    const url = new URL(request.url, 'http://localhost');
    if (url.pathname === '/preview.svg') {
        response.writeHead(200, {'Content-Type': 'image/svg+xml; charset=utf-8'});
        response.end(execFileSync('php', ['tests/gateway_gauges.php', '--bar-category-preview', JSON.stringify({
            Orientation: url.searchParams.get('orientation') || 'horizontal',
            BarWidthPercent: Number(url.searchParams.get('width')) || 70,
            AxisFontSizePercent: Number(url.searchParams.get('font')) || 100,
            ValueFontSizePercent: Number(url.searchParams.get('font')) || 100,
            BarFillMode: 'gradient', BarMode: 'simple'
        })], {cwd: root, windowsHide: true, encoding: 'utf8', maxBuffer: 2097152}));
        return;
    }
    response.writeHead(200, {'Content-Type': 'text/html; charset=utf-8'});
    if (url.searchParams.get('compare') === '1') {
        url.searchParams.delete('compare');
        response.end('<!doctype html><html><body style="background:#101114;color:white;font:16px Arial"><div style="display:flex;gap:16px">'
            + [100, 20].map(width => {
                url.searchParams.set('width', String(width));
                return '<section style="width:50%"><h2>Balkenbreite ' + width + ' %</h2><iframe style="border:0;width:100%;height:590px" src="/?'
                    + url.searchParams.toString() + '"></iframe></section>';
            }).join('') + '</div></body></html>');
        return;
    }
    response.end(page(url.searchParams));
}).listen(port, '127.0.0.1', () => console.log('Category Bar browser fixture: http://127.0.0.1:' + port));
