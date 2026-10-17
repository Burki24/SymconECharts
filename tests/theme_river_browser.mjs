// Local, synthetic browser fixture. Never connects to Symcon or reads installation data.
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
import {spawnSync} from 'node:child_process';

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const read = p => fs.readFileSync(path.join(root, p), 'utf8');
const translations = JSON.parse(read('EChartsThemeRiver/locale.json')).translations.de;
const palette = {background: '#303236', text: '#f4f5f7', muted: '#969aa2', border: '#777b83', accent: '#55CBB5',
    seriesColors: ['#5070DD', '#B6D634', '#505372', '#FF994D', '#0CA8DF', '#FFD10A', '#FB628B', '#785DB0']};
const start = 1792101600;
function state(testCase, extended, days, font) {
    const count = testCase === 'dense' ? 720 : days * 24;
    const sources = ['Büro', 'Bad', 'Küche', 'Wohnbereich', 'Terrasse', 'Keller', 'Server', 'Werkstatt'].slice(0, extended ? 8 : 4)
        .map((label, i) => ({id: 'source-' + i, label, unit: 'W', decimals: 1, color: ''}));
    const rows = Array.from({length: count}, (_, i) => ({startTimestamp: start + i * 3600, endTimestamp: start + (i + 1) * 3600 - 1,
        label: new Date((start + i * 3600) * 1000).toLocaleString('de-DE', {timeZone: 'Europe/Berlin', timeZoneName: 'shortOffset'}),
        values: testCase === 'empty' || ((testCase === 'dense' ? i % 2 : testCase === 'gaps' && i >= 9 && i < 12) && testCase !== 'zero') ? null
            : sources.map((_, j) => testCase === 'zero' ? 0 : 10 + 25 * (1 + Math.sin(i * .4 + j)))}));
    return {schemaVersion: 1, family: 'themeRiver', status: 'ready', error: null, chart: {
        schemaVersion: 1, family: 'themeRiver', theme: 'auto', range: {startTimestamp: start, endTimestamp: start + count * 3600 - 1, timezone: 'Europe/Berlin'},
        themeRiver: {title: 'Verbraucher · archivierte Leistung (W)', unit: 'W', aggregation: 'hour', sources, rows,
            missingIntervals: rows.filter(r => r.values === null).length,
            style: {showTooltip: true, showLegend: true, showLabels: extended, showAxisLabels: true, showAxisTicks: true,
                bandOpacityPercent: extended ? 75 : 85, borderWidth: extended ? 1 : 0, borderColor: '',
                animationEnabled: false, titleFontSizePercent: 100, axisFontSizePercent: font, legendFontSizePercent: font, labelFontSizePercent: 100}}}};
}
function page(url) {
    const mode = url.searchParams.get('mode') === 'ipsview' ? 'ipsview' : 'symcon';
    const light = url.searchParams.get('theme') === 'light';
    const days = url.searchParams.get('range') === '30d' ? 30 : url.searchParams.get('range') === '7d' ? 7 : 1;
    const font = url.searchParams.get('font') === '200' ? 200 : 100;
    const bootstrap = {mode, language: 'de', state: state(url.searchParams.get('case') || 'gaps', url.searchParams.get('design') === 'extended', days, font),
        options: {tileHeaderVisible: url.searchParams.get('header') !== 'hidden', adaptToBackground: false, echartsThemes: {auto: palette}}, translations};
    const variables = light
        ? ':root{--symc-background:#ffffff;--symc-text:#222222;--symc-text-muted:#666666;--symc-border:#cccccc;--symc-surface:#eeeeee}'
        : ':root{--symc-background:#303236;--symc-text:#f4f5f7;--symc-text-muted:#969aa2;--symc-border:#555860;--symc-surface:#25272b}';
    const replacements = {HTML_LANGUAGE: 'de', HTML_CLASSES: '', ROOT_FONT_SIZE: '16px', VIEWPORT_CONTENT: 'width=device-width, initial-scale=1',
        DOCUMENT_TITLE: 'ThemeRiver · lokale Browserprüfung', VISUALIZATION_THEME: variables, MODULE_STYLE: read('EChartsThemeRiver/visualization/style.css'),
        IPSVIEW_STYLE: '', ECHARTS_SCRIPT: read('libs/echarts/6.1.0/echarts.theme-river.min.js'), ECHARTS_THEME_SCRIPT: '',
        ECHARTS_DESIGN_SCRIPT: read('libs/echarts-design.js'), BOOTSTRAP_JSON: JSON.stringify(bootstrap),
        MODULE_SCRIPT: read('EChartsThemeRiver/visualization/app.js'), IPSVIEW_TRANSPORT_SCRIPT: ''};
    let html = read('EChartsThemeRiver/visualization/index.html').replace(/\{\{([A-Z_]+)\}\}/g, (_, key) => replacements[key] ?? '');
    if (mode === 'symcon' && bootstrap.options.tileHeaderVisible) {
        html = html.replace('<body>', '<body><div style="position:absolute;top:16px;left:20px;font:18px Arial;z-index:4;color:var(--symc-text)">EChartsThemeRiver <span style="position:fixed;right:18px">⤢</span></div>');
    }
    return html;
}
http.createServer((req, res) => {
    const url = new URL(req.url, 'http://127.0.0.1');
    if (url.pathname === '/preview.svg') {
        const preview = spawnSync('php', ['tests/gateway_gauges.php', '--theme-river-preview', JSON.stringify({
            Title: 'ThemeRiver · Vorschau', ShowLabels: true, BorderWidth: 1, Sources: [],
            Range: url.searchParams.get('range') === '24h' ? '24h' : '7d',
            AxisFontSizePercent: url.searchParams.get('font') === '200' ? 200 : 100,
            LegendFontSizePercent: url.searchParams.get('font') === '200' ? 200 : 100
        })], {cwd: root, encoding: 'utf8', maxBuffer: 1048576});
        res.statusCode = preview.status === 0 ? 200 : 500;
        res.setHeader('Content-Type', 'image/svg+xml; charset=utf-8');
        res.end(preview.status === 0 ? preview.stdout : '');
        return;
    }
    res.setHeader('Content-Type', 'text/html; charset=utf-8');
    if (url.pathname === '/chart') {res.end(page(url)); return;}
    res.end('<!doctype html><html lang="de"><head><title>ThemeRiver – lokale Browserprüfung</title></head><body style="background:#0b0c0e;color:white;font:16px Arial;margin:20px">'
        + '<h1 style="font-size:20px">ThemeRiver · synthetische Beispieldaten</h1><div style="display:flex;gap:16px;align-items:start;flex-wrap:wrap">'
        + '<section><h2 style="font-size:16px">Kachel · sieben Tage</h2><iframe title="Kachel" src="/chart?mode=symcon&range=7d&case=complete" style="border:0;width:400px;height:600px"></iframe></section>'
        + '<section><h2 style="font-size:16px">IPSView · große Schrift</h2><iframe title="IPSView" src="/chart?mode=ipsview&range=7d&font=200" style="border:0;width:790px;height:440px"></iframe></section>'
        + '<section><h2 style="font-size:16px">PHP-Vorschau</h2><img src="/preview.svg" alt="ThemeRiver PHP SVG" width="720"></section></div></body></html>');
}).listen(8802, '127.0.0.1', () => console.log('ThemeRiver fixture: http://127.0.0.1:8802'));
