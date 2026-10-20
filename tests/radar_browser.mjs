// Local browser fixture only; no connection to Symcon and no real variable data.
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const read = p => fs.readFileSync(path.join(root, p), 'utf8');
const style = { shape: 'polygon', startAngle: 90, clockwise: false, showLegend: true, showTooltip: true,
    showAxisNames: true, showAxisValues: false, showSplitArea: true, splitNumber: 5, axisNameGap: 15,
    lineWidth: 2, lineOpacityPercent: 100, areaOpacityPercent: 20, symbolSize: 4,
    titleFontSizePercent: 100, axisFontSizePercent: 100, animationEnabled: false };
function state(large = true, tenAxes = false) {
    const axes = [
        { Key: 'temperature', Label: 'Temperatur', Minimum: -10, Maximum: 40, Unit: '°C', Decimals: 1 },
        { Key: 'humidity', Label: 'Feuchtigkeit', Minimum: 0, Maximum: 100, Unit: '%', Decimals: 0 },
        { Key: 'co2', Label: 'Kohlendioxid', Minimum: 0, Maximum: 2000, Unit: 'ppm', Decimals: 0 },
        { Key: 'brightness', Label: 'Helligkeit', Minimum: 0, Maximum: 1000, Unit: 'lx', Decimals: 0 }
    ];
    if (tenAxes) {
        while (axes.length < 10) {
            const i = axes.length;
            axes.push({Key: 'axis' + i, Label: 'Messgröße ' + i, Minimum: 0, Maximum: 100, Unit: '%', Decimals: 0});
        }
    }
    return { schemaVersion: 1, family: 'radar', status: 'ready', error: null, chart: {
        schemaVersion: 1, family: 'radar', theme: 'auto', radar: { title: 'Raumvergleich · aktuelle Messwerte', axes,
            clippedCount: 0, enableGroupSelection: large, data: large ? Array.from({length: 16}, (_, i) => ({
                id: 'group-' + i, name: 'Bereich ' + String(i + 1).padStart(2, '0'), initiallyVisible: i < 4,
                values: axes.map((a, n) => a.Minimum + (a.Maximum - a.Minimum) * ((i * 7 + n * 11) % 80 + 10) / 100),
                percentValues: axes.map((_, n) => (i * 7 + n * 11) % 80 + 10)
            })) : [
                { id: 'office', name: 'Büro', values: [22, 45, 700, 850], percentValues: [64, 45, 35, 85] },
                { id: 'kitchen', name: 'Küche', values: [24, 60, 1400, 400], percentValues: [68, 60, 70, 40] },
                { id: 'living', name: 'Wohnbereich', values: [21, 50, 800, 600], percentValues: [62, 50, 40, 60] }
            ], style }
    } };
}
const variables = ':root{--symc-background:#303236;--symc-text:#f4f5f7;--symc-text-muted:#969aa2;--symc-border:#555860;--symc-surface:#25272b}';
const translations = JSON.parse(read('EChartsRadar/locale.json')).translations.de;
function page(mode, params) {
    const initialState = state(params.get('legacy') !== '1', params.get('axes') === '10');
    const font = Number(params.get('font')) || 100;
    initialState.chart.radar.style = {...style, titleFontSizePercent: font, axisFontSizePercent: font, legendFontSizePercent: font};
    if (mode === 'ipsview') {
        Object.assign(initialState.chart.radar.style, {
            shape: 'circle', showAxisValues: false, groupColors: { 'Büro': '#55CBB5', 'Küche': '#FF994D', 'Wohnbereich': '#FB628B' },
            lineType: 'dashed', symbol: 'diamond', symbolSize: 9, symbolRotate: 30,
            symbolBorderWidth: 1, symbolBorderColor: '#ffffff', symbolOpacityPercent: 90,
            showValues: true, valueFontSizePercent: 100, valueLabelPosition: 'right', valueLabelDistance: 8,
            legendPosition: 'top', legendFontSizePercent: font, legendColor: '#F4F5F7',
            axisLabelBold: true, splitLineType: 'dotted', splitLineWidth: 2, splitLineOpacityPercent: 70,
            splitAreaColor1: '#303236', splitAreaColor2: '#46494e', splitAreaOpacityPercent: 40,
            areaShadowBlur: 8, areaShadowColor: '#000000'
        });
    }
    const bootstrap = { mode, state: initialState, options: { tileHeaderVisible: true, echartsThemes: {},
        adaptToBackground: false,  }, translations };
    const replacements = { HTML_LANGUAGE: 'de', HTML_CLASSES: '', ROOT_FONT_SIZE: '16px', VIEWPORT_CONTENT: 'width=device-width, initial-scale=1',
        DOCUMENT_TITLE: 'Radar browser fixture', VISUALIZATION_THEME: variables, MODULE_STYLE: read('EChartsRadar/visualization/style.css'),
        IPSVIEW_STYLE: '', ECHARTS_SCRIPT: read('libs/echarts/6.1.0/echarts.radar.min.js'), ECHARTS_THEME_SCRIPT: '',
        ECHARTS_DESIGN_SCRIPT: read('libs/echarts-design.js') + '\n' + read('libs/echarts-series-selection.js'), BOOTSTRAP_JSON: JSON.stringify(bootstrap),
        MODULE_SCRIPT: read('EChartsRadar/visualization/app.js'), IPSVIEW_TRANSPORT_SCRIPT: '' };
    let html = read('EChartsRadar/visualization/index.html').replace(/\{\{([A-Z_]+)\}\}/g, (_, key) => replacements[key] ?? '');
    if (mode === 'symcon') { html = html.replace('<body>', '<body><div style="position:absolute;top:16px;left:20px;font:18px Arial;z-index:4">EChartsRadar <span style="position:fixed;right:18px">⤢</span></div>'); }
    html = html.replace('</body>', `<script>
        window.fixtureUpdate = function(action) {
            var state = JSON.parse(JSON.stringify(window.SYMC_VISUALIZATION.state));
            if (action === 'error') { window.handleMessage({status:'error',error:'The Radar values could not be loaded.'}); return; }
            if (action === 'reorder') { state.chart.radar.data.reverse(); }
            if (action === 'refresh') { state.chart.radar.data.forEach(function(group) {group.values = group.values.map(function(value) {return value + 1;});}); }
            window.SYMC_VISUALIZATION.state = state; window.handleMessage(state);
        };
    </script></body>`);
    return html;
}
http.createServer((req, res) => {
    const url = new URL(req.url, 'http://127.0.0.1');
    res.setHeader('Content-Type', 'text/html; charset=utf-8');
    if (url.pathname === '/chart') { res.end(page(url.searchParams.get('mode') === 'ipsview' ? 'ipsview' : 'symcon', url.searchParams)); return; }
    res.end('<!doctype html><html lang="de"><head><title>Radar – lokale Browserprüfung</title></head><body style="background:#0b0c0e;color:white;font:16px Arial;margin:20px">'
        + '<h1 style="font-size:20px">Radar · 16 synthetische Gruppen</h1><div style="margin-bottom:12px">'
        + ['refresh', 'reorder', 'error'].map(action => '<button onclick="document.querySelectorAll(\'iframe\').forEach(frame=>frame.contentWindow.fixtureUpdate(\'' + action + '\'))">' + action + '</button>').join(' ') + '</div><div style="display:flex;gap:16px;align-items:start;flex-wrap:wrap">'
        + '<section><h2 style="font-size:16px">Kachel · hoch</h2><iframe title="Kachel" src="/chart?mode=symcon" style="border:0;width:400px;height:600px"></iframe></section>'
        + '<section><h2 style="font-size:16px">IPSView · breit</h2><iframe title="IPSView" src="/chart?mode=ipsview" style="border:0;width:790px;height:440px"></iframe></section></div></body></html>');
}).listen(Number(process.env.PORT) || 8797, '127.0.0.1', () => console.log('Radar browser fixture: http://127.0.0.1:' + (Number(process.env.PORT) || 8797)));


