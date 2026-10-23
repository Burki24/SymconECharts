// Synthetic local browser fixture only; no Symcon data or live connection.
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
import {execFileSync} from 'node:child_process';

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
function page(query) {
    const mode = query.get('mode') === 'ipsview' ? 'ipsview' : 'symcon';
    if (query.has('sections')) {
        let html = execFileSync('php', ['tests/gateway_gauges.php', '--waterfall-sections', 'browser', mode],
            {cwd: root, windowsHide: true, encoding: 'utf8', maxBuffer: 2097152});
        if (mode === 'symcon') {
            html = html.replace('<body>', '<body><div style="position:absolute;top:16px;left:20px;z-index:4;color:white">EChartsBarWaterfall · Test</div>');
        }
        return controls(html, mode);
    }
    const count = Math.max(2, Math.min(100, Number(query.get('count')) || 100));
    let total = 0;
    const steps = Array.from({length: count}, (_, index) => {
        const change = index === 0 ? 50 : index === 1 ? 950 : index === 2 ? -2000 : index === 3 ? 1000 : index % 2 ? -2 : 3;
        const before = total;
        total += change;
        return {id: 'source-' + index, label: index === 0 ? 'Start' : 'Änderung ' + String(index).padStart(2, '0'),
            kind: index === 0 ? 'start' : 'change', change, before, after: total};
    });
    steps.push({id: 'total', label: 'Gesamt', kind: 'total', change: total, before: 0, after: total});
    const palette = {background: '#303236', text: '#f4f5f7', muted: '#969aa2', border: '#777b83',
        accent: '#55cbb5', surface: '#25272b', seriesColors: ['#5070dd', '#b6d634']};
    const bootstrap = {mode, options: {tileHeaderVisible: mode === 'symcon', tileHeaderInset: mode === 'symcon' ? 58 : 0, echartsThemes: {auto: palette}}, translations: {},
        state: {status: 'ready', chart: {theme: 'auto', steps, bar: {
            title: 'Bilanz · ' + count + ' Quellen', unit: '', decimals: 0,
            style: {showValues: query.get('values') !== 'no', showGrid: true, animationEnabled: false,
                barWidthPercent: Math.max(20, Math.min(100, Number(query.get('width')) || 60)),
                axisFontSizePercent: Math.max(50, Math.min(200, Number(query.get('font')) || 100)),
                valueFontSizePercent: Math.max(50, Math.min(200, Number(query.get('font')) || 100))}
        }}}};
    const replacements = {HTML_LANGUAGE: 'de', HTML_CLASSES: '', ROOT_FONT_SIZE: '16px',
        VIEWPORT_CONTENT: 'width=device-width, initial-scale=1', DOCUMENT_TITLE: 'Waterfall browser fixture',
        VISUALIZATION_THEME: ':root{--symc-background:#303236;--symc-text:#f4f5f7;--symc-text-muted:#969aa2;--symc-border:#777b83;--symc-surface:#25272b}',
        MODULE_STYLE: read('EChartsBarWaterfall/visualization/style.css'), IPSVIEW_STYLE: '',
        ECHARTS_SCRIPT: read('libs/echarts/6.1.0/echarts.cartesian.min.js'), ECHARTS_THEME_SCRIPT: '',
        ECHARTS_DESIGN_SCRIPT: read('libs/echarts-design.js') + '\n' + read('libs/echarts-category-navigation.js'),
        BOOTSTRAP_JSON: JSON.stringify(bootstrap), MODULE_SCRIPT: read('EChartsBarWaterfall/visualization/app.js'),
        IPSVIEW_TRANSPORT_SCRIPT: ''};
    let html = read('EChartsBarWaterfall/visualization/index.html').replace(/\{\{([A-Z_]+)\}\}/g, (_, key) => replacements[key] ?? '');
    if (mode === 'symcon') {
        html = html.replace('<body>', '<body><div style="position:absolute;top:16px;left:20px;font:18px Arial;z-index:4">EChartsBarWaterfall <span style="position:fixed;right:18px">⤢</span></div>');
    }
    return controls(html, mode);
}
function controls(html, mode) {
    return html.replace('</body>', '<div style="position:fixed;right:24px;top:' + (mode === 'symcon' ? 85 : 35)
        + 'px;z-index:5;font:11px Arial"><button onclick="var c=echarts.getInstanceByDom(document.getElementById(\'echarts-bar-waterfall-chart\'));var o=c.getOption();var z=o.dataZoom[0];if(z){var n=z.endValue-z.startValue+1;var e=SYMC_VISUALIZATION.state.chart.steps.length-1;c.dispatchAction({type:\'dataZoom\',dataZoomId:\'category-scroll\',startValue:Math.max(0,e-n+1),endValue:e});}">Letzte Schritte</button>'
        + '<button onclick="window.handleMessage(SYMC_VISUALIZATION.state)">Werte aktualisieren</button></div></body>');
}
const port = Number(process.env.PORT || 8805);
http.createServer((request, response) => {
    const url = new URL(request.url, 'http://localhost');
    if (url.pathname === '/hook/SymconECharts/assets/interaction.js') {
        response.writeHead(200, {'Content-Type': 'application/javascript; charset=utf-8'});
        response.end(read('libs/helper/visualization/interaction.min.js')); return;
    }
    if (url.pathname === '/preview.svg') {
        response.writeHead(200, {'Content-Type': 'image/svg+xml; charset=utf-8'});
        response.end(execFileSync('php', url.searchParams.has('sections')
            ? ['tests/gateway_gauges.php', '--waterfall-sections', 'browser', 'preview']
            : ['tests/gateway_gauges.php', '--waterfall-preview', JSON.stringify({
            BarWidthPercent: Number(url.searchParams.get('width')) || 60,
            AxisFontSizePercent: Number(url.searchParams.get('font')) || 100,
            ValueFontSizePercent: Number(url.searchParams.get('font')) || 100
        })], {cwd: root, windowsHide: true, encoding: 'utf8', maxBuffer: 2097152}));
        return;
    }
    response.writeHead(200, {'Content-Type': 'text/html; charset=utf-8'});
    if (url.pathname === '/' && !url.searchParams.has('mode')) {
        const sections = url.searchParams.has('sections') ? '&sections=1' : '';
        response.end('<!doctype html><html><body style="margin:12px;background:#101114;color:white;font:16px Arial"><h1>Waterfall · synthetische Beispieldaten</h1><div style="display:flex;gap:16px">'
            + ['symcon', 'ipsview'].map(mode => '<section style="width:50%"><h2>' + mode + '</h2><iframe title="' + mode + '" style="border:0;width:100%;height:570px" src="/chart?mode=' + mode + sections + '"></iframe></section>').join('')
            + '</div><h2>PHP-Vorschau mit vollständiger Endsumme</h2><img style="width:720px;max-width:100%" src="/preview.svg?' + sections + '"></body></html>');
        return;
    }
    response.end(page(url.searchParams));
}).listen(port, '127.0.0.1', () => console.log('Waterfall browser fixture: http://127.0.0.1:' + port));
