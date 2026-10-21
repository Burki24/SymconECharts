// Local, synthetic browser fixture. Never connects to Symcon or reads installation data.
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
import {spawnSync} from 'node:child_process';
import {gzipSync} from 'node:zlib';

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const read = p => fs.readFileSync(path.join(root, p), 'utf8');
const translations = JSON.parse(read('EChartsThemeRiver/locale.json')).translations.de;
const palette = {background: '#303236', text: '#f4f5f7', muted: '#969aa2', border: '#777b83', accent: '#55CBB5',
    seriesColors: ['#5070DD', '#B6D634', '#505372', '#FF994D', '#0CA8DF', '#FFD10A', '#FB628B', '#785DB0']};
const start = 1792101600;
function state(testCase, extended, days, font, selection = false, sourceCount = null, metric = 'average') {
    const count = testCase === 'dense' ? 720 : days * 24;
    const consumption = metric === 'consumption';
    const unit = consumption ? 'kWh' : 'W';
    const sources = ['Gleicher Name', 'Gleicher Name', 'Küche', 'Wohnbereich', 'Terrasse', 'Keller', 'Server', 'Werkstatt',
        'Heizung', 'Lüftung', 'Ladegerät', 'Flur', 'Dachgeschoss', 'Garage', 'Nebengebäude', 'Synthetischer Bereich mit langem Namen']
        .slice(0, sourceCount || (extended ? 8 : 4))
        .map((label, i) => ({id: 'source-' + i, label, unit, decimals: consumption ? 3 : 1, color: '', initiallyVisible: i < 8}));
    const rows = Array.from({length: count}, (_, i) => ({startTimestamp: start + i * 3600, endTimestamp: start + (i + 1) * 3600 - 1,
        label: new Date((start + i * 3600) * 1000).toLocaleString('de-DE', {timeZone: 'Europe/Berlin', timeZoneName: 'shortOffset'}),
        values: testCase === 'empty' || ((testCase === 'dense' ? i % 2 : testCase === 'gaps' && i >= 9 && i < 12) && testCase !== 'zero') ? null
            : sources.map((_, j) => testCase === 'zero' ? 0 : (10 + 25 * (1 + Math.sin(i * .4 + j))) / (consumption ? 1000 : 1))}));
    return {schemaVersion: 1, family: 'themeRiver', status: 'ready', error: null, chart: {
        schemaVersion: 1, family: 'themeRiver', theme: 'auto', range: {startTimestamp: start, endTimestamp: start + count * 3600 - 1, timezone: 'Europe/Berlin'},
        themeRiver: {title: consumption ? 'Verbraucher · Intervallverbrauch (kWh)' : 'Verbraucher · archivierte Leistung (W)',
            metric, unit, aggregation: 'hour', sources, rows, enableBandSelection: selection,
            missingIntervals: rows.filter(r => r.values === null).length,
            style: {showTooltip: true, showLegend: true, showLabels: extended, showAxisLabels: true, showAxisTicks: true,
                bandOpacityPercent: extended ? 75 : 85, borderWidth: extended ? 1 : 0, borderColor: '',
                animationEnabled: false, titleFontSizePercent: 100, axisFontSizePercent: font, legendFontSizePercent: font, labelFontSizePercent: 100,
                ...(extended ? {bandHeightPercent: 65, borderType: 'dashed', shadowBlur: 8, shadowColor: '#000000',
                    shadowOffsetX: 3, shadowOffsetY: 3, showGrid: true, gridColor: '#999999', gridOpacityPercent: 30,
                    gridLineType: 'dotted', gridLineWidth: 1, axisLabelMargin: 20, timeLabelFormat: 'dateTime',
                    legendPosition: 'top', legendAlignment: 'center', legendIcon: 'diamond', legendItemWidth: 30,
                    legendItemHeight: 20, legendItemGap: 16, fontFamily: 'Georgia, serif', titleItalic: true,
                    axisLabelBold: true, labelBold: true, legendItalic: true, emphasisFocus: 'self', blurOpacityPercent: 20} : {})}}}};
}
function page(url) {
    const mode = url.searchParams.get('mode') === 'ipsview' ? 'ipsview' : 'symcon';
    const light = url.searchParams.get('theme') === 'light';
    const days = url.searchParams.get('range') === '30d' ? 30 : url.searchParams.get('range') === '7d' ? 7 : 1;
    const font = url.searchParams.get('font') === '200' ? 200 : 100;
    const selection = url.searchParams.get('selection') === 'on';
    const sourceCount = url.searchParams.get('count') === '16' ? 16 : null;
    const metric = url.searchParams.get('metric') === 'consumption' ? 'consumption' : 'average';
    const bootstrap = {mode, language: 'de', state: state(url.searchParams.get('case') || 'gaps', url.searchParams.get('design') === 'extended', days, font, selection, sourceCount, metric),
        options: {tileHeaderVisible: mode === 'symcon' && (url.searchParams.get('header') !== 'hidden'), tileHeaderInset: mode === 'symcon' && (url.searchParams.get('header') !== 'hidden') ? 58 : 0, adaptToBackground: false, echartsThemes: {auto: palette}}, translations};
    if (['top', 'bottom'].includes(url.searchParams.get('legend'))) {
        bootstrap.state.chart.themeRiver.style.legendPosition = url.searchParams.get('legend');
    }
    const initial = bootstrap.state;
    if (selection) {
        bootstrap.options.compressedStateGzipBase64 = gzipSync(JSON.stringify(initial)).toString('base64');
        bootstrap.state = null;
    }
    const variables = light
        ? ':root{--symc-background:#ffffff;--symc-text:#222222;--symc-text-muted:#666666;--symc-border:#cccccc;--symc-surface:#eeeeee}'
        : ':root{--symc-background:#303236;--symc-text:#f4f5f7;--symc-text-muted:#969aa2;--symc-border:#555860;--symc-surface:#25272b}';
    const replacements = {HTML_LANGUAGE: 'de', HTML_CLASSES: '', ROOT_FONT_SIZE: '16px', VIEWPORT_CONTENT: 'width=device-width, initial-scale=1',
        DOCUMENT_TITLE: 'ThemeRiver · lokale Browserprüfung', VISUALIZATION_THEME: variables, MODULE_STYLE: read('EChartsThemeRiver/visualization/style.css'),
        IPSVIEW_STYLE: '', ECHARTS_SCRIPT: read('libs/echarts/6.1.0/echarts.theme-river.min.js'), ECHARTS_THEME_SCRIPT: '',
        ECHARTS_DESIGN_SCRIPT: read('libs/echarts-design.js') + '\n' + read('libs/echarts-series-selection.js') + '\n' + read('libs/echarts-startup.js'),
        BOOTSTRAP_JSON: JSON.stringify(bootstrap),
        MODULE_SCRIPT: read('EChartsThemeRiver/visualization/app.js'), IPSVIEW_TRANSPORT_SCRIPT: ''};
    let html = read('EChartsThemeRiver/visualization/index.html').replace(/\{\{([A-Z_]+)\}\}/g, (_, key) => replacements[key] ?? '');
    if (selection) {
        const script = `const original=${JSON.stringify(initial)};
            function chart(){return echarts.getInstanceByDom(document.getElementById('echarts-theme-river-chart'));}
            function report(){const o=chart().getOption();document.getElementById('result').textContent=
                Object.values(o.legend[0].selected).filter(Boolean).length+'/16 sichtbar · Legendenanker '+o.legend[0].scrollDataIndex;}
            function select(){chart().dispatchAction({type:'legendToggleSelect',name:'source-0'});
                chart().dispatchAction({type:'legendScroll',scrollDataIndex:8});report();}
            function refresh(){const next=JSON.parse(JSON.stringify(original));
                next.chart.themeRiver.sources.reverse();next.chart.themeRiver.rows.forEach(r=>{if(r.values)r.values.reverse();});
                window.handleMessage(next);report();}
            function inverse(){chart().dispatchAction({type:'legendAllSelect'});chart().dispatchAction({type:'legendInverseSelect'});report();}`;
        html = html.replace('</head>', '<style>.echarts-theme-river-root{height:calc(100% - 38px)}</style></head>');
        html = html.replace('</body>', '<div style="position:absolute;left:8px;bottom:4px;z-index:5;font:10px Arial">'
            + '<button onclick="select()">Auswahl / blättern</button><button onclick="refresh()">Aktualisieren / umsortieren</button>'
            + '<button onclick="inverse()">Keine Bänder</button><span id="result"></span></div><script>' + script + '</script></body>');
    }
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
            Metric: url.searchParams.get('metric') === 'consumption' ? 'consumption' : 'average',
            Range: url.searchParams.get('range') === '24h' ? '24h' : '7d',
            AxisFontSizePercent: url.searchParams.get('font') === '200' ? 200 : 100,
            LegendFontSizePercent: url.searchParams.get('font') === '200' ? 200 : 100,
            ...(url.searchParams.get('design') === 'extended' ? {
                FontFamily: 'Georgia, serif', TitleItalic: true, BandHeightPercent: 65, BorderType: 'dashed',
                ShadowBlur: 8, ShadowOffsetX: 3, ShadowOffsetY: 3, ShowGrid: true, TimeLabelFormat: 'dateTime',
                LegendPosition: 'top', LegendAlignment: 'center', LegendIcon: 'diamond', LegendItemWidth: 30,
                LegendItemHeight: 20, LegendItemGap: 16, AxisLabelBold: true, LabelBold: true, LegendItalic: true
            } : {})
        })], {cwd: root, encoding: 'utf8', maxBuffer: 1048576});
        res.statusCode = preview.status === 0 ? 200 : 500;
        res.setHeader('Content-Type', 'image/svg+xml; charset=utf-8');
        res.end(preview.status === 0 ? preview.stdout : '');
        return;
    }
    res.setHeader('Content-Type', 'text/html; charset=utf-8');
    if (url.pathname === '/chart') {res.end(page(url)); return;}
    const metric = url.searchParams.get('metric') === 'consumption' ? 'consumption' : 'average';
    res.end('<!doctype html><html lang="de"><head><title>ThemeRiver – lokale Browserprüfung</title></head><body style="background:#0b0c0e;color:white;font:16px Arial;margin:20px">'
        + '<h1 style="font-size:20px">ThemeRiver · synthetische Beispieldaten</h1><div style="display:flex;gap:16px;align-items:start;flex-wrap:wrap">'
        + '<section><h2 style="font-size:16px">Kachel · 16 Quellen / 720 Intervalle</h2><iframe title="Kachel" src="/chart?mode=symcon&range=30d&selection=on&count=16&metric=' + metric + '" style="border:0;width:400px;height:600px"></iframe></section>'
        + '<section><h2 style="font-size:16px">IPSView · 16 Quellen / 720 Intervalle</h2><iframe title="IPSView" src="/chart?mode=ipsview&range=30d&selection=on&count=16&metric=' + metric + '" style="border:0;width:790px;height:600px"></iframe></section>'
        + '<section><h2 style="font-size:16px">PHP-Vorschau</h2><img src="/preview.svg?design=extended&metric=' + metric + '" alt="ThemeRiver PHP SVG" width="720"></section></div></body></html>');
}).listen(8802, '127.0.0.1', () => console.log('ThemeRiver fixture: http://127.0.0.1:8802'));
