// Local synthetic HTTP/WebSocket fixture. No Symcon access or installation data.
import http from 'node:http';
import {createHash} from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const read = p => fs.readFileSync(path.join(root, p), 'utf8');
const prefix = '/hook/SymconECharts/';
const channel = '5000/0123456789abcdef0123456789abcdef';
const start = 1780000000;
function state(fresh) {
    return {status: 'ready', chart: {
        theme: 'auto', chart: {title: 'data:-Einbettung · Live-Transport', enableZoom: false,
            design: {showSymbols: true, showGrid: true, lineWidthPercent: 150, legendPosition: 'bottom'}},
        range: {key: 'custom', dataMode: 'realtime', acceptLiveUpdates: true, durationSeconds: 300,
            startTimestamp: start - 180, endTimestamp: start + (fresh ? 30 : 0), pointLimitPerSeries: 32},
        axes: [{unit: '°C', position: 'left', positionIndex: 0}],
        series: [{id: 'variable-4711', variableID: 4711, label: 'Synthetischer Prüfwert', unit: '°C', decimals: 1,
            color: '#55CBB5', style: 'line', design: [], axisIndex: 0,
            points: fresh ? [[start, 10], [start + 30, 12]] : [[start, 10]]}], annotations: [], truncated: false
    }};
}
function page(url) {
    const bootstrap = {mode: 'ipsview', language: 'de', state: state(false), translations: {}, options: {
        adaptToBackground: false,
        echartsThemes: {auto: {background: '#303236', text: '#f4f5f7', muted: '#969aa2', border: '#777b83', accent: '#55CBB5'}},
        ipsViewTransport: {statePath: prefix + 'state/' + channel, socketPath: prefix + 'WS/' + channel}
    }};
    const proof = `
        <script>
        const original = window.handleMessage;
        let count = 1;
        window.handleMessage = function(message) {
            original(message);
            count = message.messageType === 'append' ? count + 1 : message.chart.series[0].points.length;
            document.getElementById('proof').textContent = 'Live-Verbindung: ' + count + ' Punkte';
        };
        </script>`;
    const replacements = {HTML_LANGUAGE: 'de', HTML_CLASSES: 'ipsview-mode', ROOT_FONT_SIZE: '16px', VIEWPORT_CONTENT: 'width=device-width, initial-scale=1',
        DOCUMENT_TITLE: 'Synthetischer IPSView-Transporttest',
        VISUALIZATION_THEME: ':root{--symc-background:#303236;--symc-text:#f4f5f7;--symc-text-muted:#969aa2;--symc-border:#777b83}',
        MODULE_STYLE: read('EChartsTimeSeries/visualization/style.css') + '\n.echarts-timeseries-root{height:calc(100% - 32px)}',
        IPSVIEW_STYLE: '', ECHARTS_SCRIPT: read('libs/echarts/6.1.0/echarts.cartesian.min.js'), ECHARTS_THEME_SCRIPT: '',
        ECHARTS_DESIGN_SCRIPT: read('libs/echarts-design.js'), ECHARTS_ZOOM_SCRIPT: read('libs/echarts-zoom.js'),
        ECHARTS_PATTERN_SCRIPT: read('libs/echarts-pattern.js'), BOOTSTRAP_JSON: JSON.stringify(bootstrap),
        MODULE_SCRIPT: read('EChartsTimeSeries/visualization/app.js'), IPSVIEW_TRANSPORT_SCRIPT: read('libs/echarts/ipsview-transport.js')};
    let html = read('EChartsTimeSeries/visualization/index.html').replace(/\{\{([A-Z_]+)\}\}/g, (_, key) => replacements[key] ?? '');
    html = html.replace('<script>' + replacements.IPSVIEW_TRANSPORT_SCRIPT,
        '<p id="proof" role="status" style="margin:0;text-align:center">Startzustand: 1 Punkt</p>' + proof + '<script>' + replacements.IPSVIEW_TRANSPORT_SCRIPT);
    // no-referrer exercises ancestorOrigins; an explicit base also covers native IPSView wrappers.
    if (url.searchParams.get('base') === 'yes') {
        html = html.replace('<head>', '<head><base href="http://127.0.0.1:8803/">');
    }
    const src = url.searchParams.get('encoding') === 'uri'
        ? 'data:text/html;charset=utf-8,' + encodeURIComponent(html) : 'data:text/html;base64,' + Buffer.from(html).toString('base64');
    return '<!doctype html><html lang="de"><meta charset="utf-8"><title>IPSView transport regression</title>'
        + '<body style="background:#151619;color:white;font:16px Arial"><h1 style="font-size:20px">Opaque data:-Frame · synthetische Werte</h1>'
        + '<iframe id="opaque" title="Eingebettete Zeitreihe" style="width:630px;height:620px;border:0" '
        + (url.searchParams.get('referrer') === 'none' ? 'referrerpolicy="no-referrer" ' : '') + 'src="' + src + '"></iframe></body></html>';
}
function frame(message) {
    const data = Buffer.from(JSON.stringify(message));
    const header = Buffer.alloc(data.length < 126 ? 2 : 4);
    header[0] = 0x81;
    header[1] = data.length < 126 ? data.length : 126;
    if (data.length >= 126) {header.writeUInt16BE(data.length, 2);}
    return Buffer.concat([header, data]);
}
const server = http.createServer((req, res) => {
    const url = new URL(req.url, 'http://127.0.0.1:8803');
    if (url.pathname === prefix + 'state/' + channel) {
        setTimeout(() => {
            res.writeHead(200, {'Content-Type': 'application/json', 'Access-Control-Allow-Origin': '*', 'Cache-Control': 'no-store'});
            res.end(JSON.stringify(state(true)));
        }, 200);
        return;
    }
    res.setHeader('Content-Type', 'text/html; charset=utf-8');
    res.end(page(url));
});
server.on('upgrade', (req, socket) => {
    if (req.url !== prefix + 'WS/' + channel || typeof req.headers['sec-websocket-key'] !== 'string') {socket.destroy(); return;}
    const accept = createHash('sha1').update(req.headers['sec-websocket-key'] + '258EAFA5-E914-47DA-95CA-C5AB0DC85B11').digest('base64');
    socket.write('HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Accept: ' + accept + '\r\n\r\n');
    const timers = [60, 600, 1200].map((delay, i) => setTimeout(() => {
        if (!socket.destroyed) {socket.write(frame({messageType: 'append', variableID: 4711, timestamp: start + 60 + i * 30, value: 14 + i * 2}));}
    }, delay));
    socket.on('data', bytes => {if ((bytes[0] & 0x0f) === 8) {socket.end(Buffer.from([0x88, 0]));}});
    socket.on('error', () => {});
    socket.on('close', () => timers.forEach(clearTimeout));
});
server.listen(8803, '127.0.0.1', () => console.log('IPSView transport fixture: http://127.0.0.1:8803/'));
