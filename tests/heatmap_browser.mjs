// Local Canvas fixture with synthetic data only; never connects to Symcon.
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
const metrics = ['average', 'minimum', 'maximum', 'consumption'];
const variables = ':root{--symc-background:#303236;--symc-text:#f4f5f7;--symc-text-muted:#969aa2;--symc-border:#555860;--symc-surface:#25272b}';
function state(metric, offset = 0, range = '1m') {
    const extended = ['custom', 'absolute'].includes(range);
    const duration = range === 'absolute' ? (15 * 24 * 60 + 270) * 60000 : 14 * 86400000;
    const until = Date.parse(range === 'absolute' ? '2026-10-13T12:45:00+02:00' : '2026-10-13T12:00:00+02:00') - offset * duration;
    const localLabel = timestamp => new Date(timestamp + 7200000).toISOString().slice(0, 19).replace('T', ' ') + ' +02:00';
    const cells = [];
    for (let weekday = 0; weekday < 7; weekday++) {
        for (let hour = 0; hour < 24; hour++) {
            if ((weekday * 24 + hour) % 19 === 0) { continue; }
            const base = Math.round((Math.sin(hour / 24 * Math.PI * 2) * 10 + weekday) * 10) / 10;
            const value = metric === 'consumption' ? (base + 12) * 4
                : metric === 'minimum' ? base - 4 : metric === 'maximum' ? base + 5 : base;
            cells.push({ weekday, hour, value, count: weekday === 0 ? 5 : 4 });
        }
    }
    return { status: 'ready', chart: { schemaVersion: 1, family: 'heatmap', theme: 'auto',
        heatmap: { title: 'Synthetische Archivstunden', metric, range,
            viewKey: metric + '-' + range, timezone: 'Europe/Berlin',
            ...(extended ? { requestedStartLabel: localLabel(until - duration), requestedEndLabel: localLabel(until) } : {}),
            unit: metric === 'consumption' ? 'kWh' : '°C', decimals: 1,
            startDate: offset ? '2026-08-01' : '2026-09-01', endDate: offset ? '2026-08-31' : '2026-09-30',
            windowOffset: offset, maxOffset: 1, days: [], style: { showColorScale: true,
                showValues: true, lowColor: '#5070dd', highColor: '#fb628b', animationEnabled: false } },
        cells, truncated: false } };
}
function page(mode, metric, range) {
    const bootstrap = { mode, state: state(metric, 0, range),
        translations: JSON.parse(read('EChartsHeatmap/locale.json')).translations.de,
        options: { tileHeaderVisible: true, echartsThemes: {}, heatmapStatePath:
            '/hook/SymconECharts/state/5000/' + 'a'.repeat(32) + '/metric/' + metric + '/range/' + range } };
    const replacements = { HTML_LANGUAGE: 'de', HTML_CLASSES: '', ROOT_FONT_SIZE: '16px',
        VIEWPORT_CONTENT: 'width=device-width, initial-scale=1', DOCUMENT_TITLE: 'Heatmap synthetic fixture',
        VISUALIZATION_THEME: variables, MODULE_STYLE: read('EChartsHeatmap/visualization/style.css'), IPSVIEW_STYLE: '',
        ECHARTS_SCRIPT: read('libs/echarts/6.1.0/echarts.heatmap.min.js'), ECHARTS_THEME_SCRIPT: '',
        ECHARTS_DESIGN_SCRIPT: read('libs/echarts-design.js'), BOOTSTRAP_JSON: JSON.stringify(bootstrap),
        MODULE_SCRIPT: read('EChartsHeatmap/visualization/app.js'), IPSVIEW_TRANSPORT_SCRIPT: '' };
    let html = read('EChartsHeatmap/visualization/index.html').replace(/\{\{([A-Z_]+)\}\}/g,
        (_, key) => replacements[key] ?? '');
    if (mode === 'symcon') {
        html = html.replace('<body>', '<body><div style="position:absolute;top:16px;left:20px;font:18px Arial;z-index:4">EChartsHeatmap <span style="position:fixed;right:18px">⤢</span></div>');
    }
    return html;
}
http.createServer((request, response) => {
    const url = new URL(request.url, 'http://127.0.0.1');
    const requested = url.searchParams.get('metric') || url.pathname.split('/metric/')[1]?.split('/')[0];
    const metric = metrics.includes(requested) ? requested : 'average';
    const requestedRange = url.searchParams.get('range') || url.pathname.split('/range/')[1];
    const range = ['custom', 'absolute'].includes(requestedRange) ? requestedRange : '1m';
    if (url.pathname.startsWith('/hook/SymconECharts/state/')) {
        response.setHeader('Content-Type', 'application/json');
        response.end(JSON.stringify(state(metric, Math.max(0, Math.min(1, Number(url.searchParams.get('offset')) || 0)), range)));
    } else if (url.pathname === '/chart') {
        response.setHeader('Content-Type', 'text/html; charset=utf-8');
        response.end(page(url.searchParams.get('mode') === 'ipsview' ? 'ipsview' : 'symcon', metric, range));
    } else {
        response.setHeader('Content-Type', 'text/html; charset=utf-8');
        response.end('<!doctype html><html lang="de"><body style="background:#101114;color:white;font:16px Arial">'
            + '<h1>Heatmap · synthetische Beispieldaten</h1><div style="display:flex;gap:16px;flex-wrap:wrap">'
            + '<iframe title="Kachel" src="/chart?metric=' + metric + '&range=' + range + '" style="border:0;width:400px;height:600px"></iframe>'
            + '<iframe title="IPSView" src="/chart?mode=ipsview&metric=' + metric + '&range=' + range + '" style="border:0;width:790px;height:440px"></iframe></div></body></html>');
    }
}).listen(8802, '127.0.0.1', () => console.log('Heatmap fixture: http://127.0.0.1:8802'));
