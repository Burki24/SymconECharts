// Local, synthetic Graph stress fixture. No Symcon connection or installation data.
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { gzipSync } from 'node:zlib';

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
function state(layout, updated = false, zigbee = false) {
    const nodes = Array.from({ length: 300 }, (_, index) => ({
        id: 'object-' + (1000 + index), name: 'Sensor mit langem Namen ' + (index % 50),
        kind: zigbee ? (index === 0 ? 'Coordinator' : index % 3 === 0 ? 'EndDevice' : 'Router') : 'variable',
        displayValue: (updated ? '23' : '21') + ' °C'
    }));
    const links = [];
    for (let distance = 1; links.length < 1000; distance++) {
        for (let index = 0; index < nodes.length && links.length < 1000; index++) {
            links.push({ source: nodes[index].id, target: nodes[(index + distance) % nodes.length].id,
                lqi: (index * 13) % 256 });
        }
    }
    return { status: 'ready', chart: { family: 'graph', theme: 'auto', graph: {
        title: '300 Knoten · 1.000 Verbindungen', nodes, links,
        totalLinks: 1000, capturedAt: 1800000000,
        style: { graphLayout: layout, nodeSize: 12, roamEnabled: true,
            showLabels: true, showValues: true, animationEnabled: false }
    } } };
}
function page(mode, layout, zigbee) {
    const module = zigbee ? 'EChartsGraphZ2M' : 'EChartsGraph';
    const chartID = zigbee ? 'echarts-zigbee-graph-chart' : 'echarts-graph-chart';
    const bootstrap = { mode, state: null,
        translations: JSON.parse(read(module + '/locale.json')).translations.de,
        options: { tileHeaderVisible: true, echartsThemes: {},
            compressedStateGzipBase64: gzipSync(JSON.stringify(state(layout, false, zigbee))).toString('base64') } };
    const replacements = { HTML_LANGUAGE: 'de', HTML_CLASSES: '', ROOT_FONT_SIZE: '16px',
        VIEWPORT_CONTENT: 'width=device-width, initial-scale=1', DOCUMENT_TITLE: 'Graph browser fixture',
        VISUALIZATION_THEME: ':root{--symc-background:#303236;--symc-text:#f4f5f7;--symc-text-muted:#969aa2}',
        MODULE_STYLE: read(module + '/visualization/style.css'), IPSVIEW_STYLE: '',
        ECHARTS_SCRIPT: read('libs/echarts/6.1.0/echarts.graph.min.js'), ECHARTS_THEME_SCRIPT: '',
        ECHARTS_DESIGN_SCRIPT: read('libs/echarts-design.js') + '\n' + read('libs/echarts-graph.js'),
        BOOTSTRAP_JSON: JSON.stringify(bootstrap), MODULE_SCRIPT: read(module + '/visualization/app.js'),
        IPSVIEW_TRANSPORT_SCRIPT: '' };
    let html = read(module + '/visualization/index.html').replace(/\{\{([A-Z_]+)\}\}/g, (_, key) => replacements[key] ?? '');
    const script = `function graphChart(){return echarts.getInstanceByDom(document.getElementById('${chartID}'));}
        function report(){const s=graphChart().getModel().getSeriesByIndex(0);
        document.getElementById('result').textContent='Zoom '+Number(s.get('zoom')||1).toFixed(2)+' · '+s.getData().count()+' Knoten · '+s.getEdgeData().count()+' Kanten';}
        function roam(){graphChart().dispatchAction({type:'graphRoam',seriesId:'graph',dx:20,dy:10});
        graphChart().dispatchAction({type:'graphRoam',seriesId:'graph',zoom:1.5,originX:200,originY:200});report();}
        function refresh(){window.handleMessage(${JSON.stringify(state(layout, true, zigbee))});report();}`;
    html = html.replace('</head>', '<style>.echarts-graph-root,.echarts-zigbee-graph-root{height:calc(100% - 36px)}</style></head>');
    html = html.replace('</body>', '<div style="position:absolute;left:12px;bottom:4px;z-index:5;font:10px Arial">'
        + '<button onclick="roam()">Verschieben / Zoom</button><button onclick="refresh()">Werte aktualisieren</button>'
        + '<span id="result"></span></div><script>' + script + '</script></body>');
    if (mode === 'symcon') {
        html = html.replace('<body>', '<body><div style="position:absolute;top:16px;left:20px;font:18px Arial;z-index:4">' + module + '</div>');
    }
    return html;
}
http.createServer((req, res) => {
    const url = new URL(req.url, 'http://127.0.0.1');
    const layout = url.searchParams.get('layout') === 'circular' ? 'circular' : 'force';
    const zigbee = url.searchParams.get('family') === 'z2m';
    const family = zigbee ? 'z2m' : 'manual';
    res.setHeader('Content-Type', 'text/html; charset=utf-8');
    if (url.pathname === '/chart') {
        res.end(page(url.searchParams.get('mode') === 'ipsview' ? 'ipsview' : 'symcon', layout, zigbee)); return;
    }
    res.end('<!doctype html><html lang="de"><head><title>Graph · lokale Browserprüfung</title></head>'
        + '<body style="background:#101114;color:white;font:16px Arial;margin:18px"><h1 style="font-size:20px">'
        + (zigbee ? 'GraphZ2M' : 'Graph') + ' · 300 Knoten / 1.000 Verbindungen · ' + layout + '</h1>'
        + '<nav><a style="color:#55cbb5" href="/?layout=force&family=' + family + '">Kräftebasiert</a> · '
        + '<a style="color:#55cbb5" href="/?layout=circular&family=' + family + '">Kreisförmig</a> · '
        + '<a style="color:#55cbb5" href="/?layout=' + layout + '&family=' + (zigbee ? 'manual' : 'z2m') + '">Familie wechseln</a></nav>'
        + '<div style="display:flex;gap:18px;flex-wrap:wrap"><section><h2>Kachel</h2><iframe title="Kachel" src="/chart?mode=symcon&layout='
        + layout + '&family=' + family + '" style="border:0;width:400px;height:520px"></iframe></section><section><h2>IPSView</h2>'
        + '<iframe title="IPSView" src="/chart?mode=ipsview&layout=' + layout
        + '&family=' + family + '" style="border:0;width:720px;height:520px"></iframe></section></div></body></html>');
}).listen(8814, '127.0.0.1', () => console.log('Graph browser fixture: http://127.0.0.1:8814'));
