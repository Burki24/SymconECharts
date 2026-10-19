// Local native Canvas fixture with synthetic data only. No Symcon connection.
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { gzipSync } from 'node:zlib';

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
const palette = { background: '#303236', text: '#F4F5F7', muted: '#969AA2', border: '#A5A9B0',
    accent: '#55CBB5', seriesColors: ['#5470c6', '#91cc75', '#fac858', '#ee6666', '#73c0de'] };
function state(updated = false, reordered = false) {
    const series = Array.from({ length: 32 }, (_, index) => ({
        id: 'variable-' + (10000 + index), variableID: 10000 + index,
        label: index < 2 ? 'Klima · gleicher Name' : 'Synthetischer Raum mit langem Namen ' + (index + 1),
        unit: index % 2 ? '%' : '°C', decimals: 1, axisIndex: index % 2,
        initiallyVisible: index < 4,
        points: Array.from({ length: 1000 }, (_, point) => [1800000000 + point * 60 + index,
            (index % 2 ? 50 : 20) + index * 0.2 + Math.sin(point / 23 + index) * 3 + (updated ? 0.5 : 0)])
    }));
    return { status: 'ready', chart: { family: 'bar-history', theme: 'auto',
        bar: { title: '32 Reihen · 32.000 Archivpunkte',
            style: { enableZoom: true, animationEnabled: false, barWidthPercent: 70 } },
        axes: [{ unit: '°C', position: 'left' }, { unit: '%', position: 'right' }],
        series: reordered ? series.reverse() : series,
        range: { key: 'day', dataMode: 'raw', startTimestamp: 1800000000, endTimestamp: 1800060000 },
        truncated: false } };
}
function page(mode) {
    const module = 'EChartsBarHistory';
    const bootstrap = { mode, state: null,
        translations: JSON.parse(read(module + '/locale.json')).translations.de,
        options: { tileHeaderVisible: true, echartsThemes: { auto: palette },
            compressedStateGzipBase64: gzipSync(JSON.stringify(state())).toString('base64') } };
    const replacements = { HTML_LANGUAGE: 'de', HTML_CLASSES: '', ROOT_FONT_SIZE: '16px',
        VIEWPORT_CONTENT: 'width=device-width, initial-scale=1', DOCUMENT_TITLE: 'BarHistory synthetic browser fixture',
        VISUALIZATION_THEME: ':root{--symc-background:#303236;--symc-text:#f4f5f7;--symc-text-muted:#969aa2;--symc-border:#a5a9b0}',
        MODULE_STYLE: read(module + '/visualization/style.css'), IPSVIEW_STYLE: '',
        ECHARTS_SCRIPT: read('libs/echarts/6.1.0/echarts.cartesian.min.js'), ECHARTS_THEME_SCRIPT: '',
        ECHARTS_DESIGN_SCRIPT: read('libs/echarts-design.js') + '\n' + read('libs/echarts-series-selection.js')
            + '\n' + read('libs/echarts-startup.js'),
        ECHARTS_ZOOM_SCRIPT: read('libs/echarts-zoom.js'), ECHARTS_PATTERN_SCRIPT: read('libs/echarts-pattern.js'),
        BOOTSTRAP_JSON: JSON.stringify(bootstrap), MODULE_SCRIPT: read(module + '/visualization/app.js'),
        IPSVIEW_TRANSPORT_SCRIPT: '' };
    let html = read(module + '/visualization/index.html').replace(/\{\{([A-Z_]+)\}\}/g, (_, key) => replacements[key] ?? '');
    const script = `function chart(){return echarts.getInstanceByDom(document.getElementById('echarts-bar-history-chart'));}
        function report(){const o=chart().getOption();document.getElementById('result').textContent=
        o.series.length+' Reihen · '+Object.values(o.legend[0].selected).filter(Boolean).length+' sichtbar · Zoom '+o.dataZoom[0].start.toFixed(0)+'%';}
        function select(){chart().dispatchAction({type:'legendToggleSelect',name:'variable-10000'});
        chart().dispatchAction({type:'legendScroll',scrollDataIndex:8});
        chart().dispatchAction({type:'dataZoom',dataZoomIndex:0,start:20,end:30});report();}
        function refresh(){window.handleMessage(${JSON.stringify(state(true, true))});report();}`;
    html = html.replace('</head>', '<style>.echarts-bar-history-root{height:calc(100% - 38px)}</style></head>');
    html = html.replace('</body>', '<div style="position:absolute;left:12px;bottom:4px;z-index:5;font:10px Arial">'
        + '<button onclick="select()">Auswahl / Zoom</button><button onclick="refresh()">Aktualisieren / umsortieren</button>'
        + '<span id="result"></span></div><script>' + script + '</script></body>');
    if (mode === 'symcon') {
        html = html.replace('<body>', '<body><div style="position:absolute;top:16px;left:20px;font:18px Arial;z-index:4">EChartsBarHistory</div>');
    }
    return html;
}
http.createServer((req, res) => {
    const url = new URL(req.url, 'http://127.0.0.1');
    res.setHeader('Content-Type', 'text/html; charset=utf-8');
    if (url.pathname === '/chart') { res.end(page(url.searchParams.get('mode') === 'ipsview' ? 'ipsview' : 'symcon')); return; }
    res.end('<!doctype html><html lang="de"><head><title>BarHistory · lokale Browserprüfung</title></head>'
        + '<body style="background:#101114;color:white;font:16px Arial;margin:18px"><h1 style="font-size:20px">'
        + 'BarHistory · 32 Reihen / 32.000 Punkte</h1><div style="display:flex;gap:18px;flex-wrap:wrap">'
        + '<section><h2>Kachel</h2><iframe title="Kachel" src="/chart?mode=symcon" style="border:0;width:400px;height:520px"></iframe></section>'
        + '<section><h2>IPSView</h2><iframe title="IPSView" src="/chart?mode=ipsview" style="border:0;width:720px;height:520px"></iframe></section>'
        + '</div></body></html>');
}).listen(8817, '127.0.0.1', () => console.log('BarHistory browser fixture: http://127.0.0.1:8817'));
