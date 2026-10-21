// Local browser fixture only; no connection to Symcon and no real variable data.
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const read = p => fs.readFileSync(path.join(root, p), 'utf8');
const style = { orientation: 'vertical', showGrid: true, showAxisLabels: true, showTooltip: true,
    candleWidth: 40, riseColor:'#3FBE95', fallColor:'#E66B6B', borderWidth: 2, candleOpacityPercent: 70, titleFontSizePercent: 100, axisFontSizePercent: 100, animationEnabled: false };
function state() {
    return { schemaVersion: 1, family: 'candlestick', status: 'ready', error: null, chart: {
        schemaVersion: 1, family: 'candlestick', theme: 'auto', candlestick: { title: 'Temperatur · Archivintervalle', unit: '°C',
            sourceLabel: 'Außentemperatur',
            data: ['2026-10-10','2026-10-11','2026-10-12','2026-10-13','2026-10-14','2026-10-15','2026-10-16'].map((label, index) => ({
                id: 'variable-' + index, label, unit: '°C', decimals: 1, color: '',
                statistics: index === 3 ? null : index === 4 ? [20,20,20,20] : [18+index, index % 2 === 0 ? 22+index : 16+index, 14+index,24+index],
                count: 168 - index * 5, firstTimestamp: 1791504000, lastTimestamp: 1792108800
            })), style: { ...style } }
    } };
}
const variables = ':root{--symc-background:#303236;--symc-text:#f4f5f7;--symc-text-muted:#969aa2;--symc-border:#555860;--symc-surface:#25272b}';
const translations = JSON.parse(read('EChartsCandlestick/locale.json')).translations.de;
function page(mode, extended = false, light = false) {
    const initialState = state();
    if (mode === 'ipsview') {
        initialState.chart.candlestick.style.orientation = 'horizontal';
        initialState.chart.candlestick.style.axisColor = light ? '#333333' : '#A9DCD2';
    }
    if (extended) {
        Object.assign(initialState.chart.candlestick.style, {
            minCandleWidth: 20, borderType: 'dashed', borderColor: '#CBD6E8', shadowBlur: 8, shadowColor: '#000000',
            shadowOffsetX: 3, shadowOffsetY: 3, hoverFocus: 'self', blurOpacityPercent: 10,
            axisLineWidth: 2, showAxisTicks: false, axisLabelRotate: mode === 'ipsview' ? 0 : 45, axisLabelMaxWidth: 100,
            titleBold: false, axisLabelBold: true, splitNumber: 8, gridLineType: 'dotted', gridLineWidth: 1,
            gridOpacityPercent: 50, gridColor: '#85929E',
            fixedCandleWidth:22,riseHollow:true,fallHollow:false,riseBorderColor:'#46B897',fallBorderColor:'#E66B6B',
            hoverBorderWidth:5,hoverBorderColor:light?'#333333':'#ffffff',hoverOpacityPercent:100,
            axisLineColor:light?'#444444':'#B9C9D2',axisLineType:'dashed',axisLabelMargin:16,
            reverseTimeAxis:true,includeZero:true,fontFamily:'Georgia, serif',titleItalic:true,axisLabelItalic:true
        });
    }
    const bootstrap = { mode, state: initialState, options: { tileHeaderVisible: mode === 'symcon', tileHeaderInset: mode === 'symcon' ? 58 : 0, echartsThemes: {auto: {background:'#303236',text:'#f4f5f7',muted:'#969aa2',border:'#777b83',accent:'#55CBB5',seriesColors:['#5070dd','#b6d634','#ff994d','#fb628b']}},
        adaptToBackground: false,  }, translations };
    const replacements = { HTML_LANGUAGE: 'de', HTML_CLASSES: '', ROOT_FONT_SIZE: '16px', VIEWPORT_CONTENT: 'width=device-width, initial-scale=1',
        DOCUMENT_TITLE: 'Candlestick browser fixture', VISUALIZATION_THEME: light ? ':root{--symc-background:#ffffff;--symc-text:#222222;--symc-text-muted:#666666;--symc-border:#cccccc;--symc-surface:#eeeeee}' : variables, MODULE_STYLE: read('EChartsCandlestick/visualization/style.css'),
        IPSVIEW_STYLE: '', ECHARTS_SCRIPT: read('libs/echarts/6.1.0/echarts.candlestick.min.js'), ECHARTS_THEME_SCRIPT: '',
        ECHARTS_DESIGN_SCRIPT: read('libs/echarts-design.js'), BOOTSTRAP_JSON: JSON.stringify(bootstrap),
        MODULE_SCRIPT: read('EChartsCandlestick/visualization/app.js'), IPSVIEW_TRANSPORT_SCRIPT: '' };
    let html = read('EChartsCandlestick/visualization/index.html').replace(/\{\{([A-Z_]+)\}\}/g, (_, key) => replacements[key] ?? '');
    if (mode === 'symcon') { html = html.replace('<body>', '<body><div style="position:absolute;top:16px;left:20px;font:18px Arial;z-index:4">EChartsCandlestick <span style="position:fixed;right:18px">⤢</span></div>'); }
    return html;
}
http.createServer((req, res) => {
    const url = new URL(req.url, 'http://127.0.0.1');
    res.setHeader('Content-Type', 'text/html; charset=utf-8');
    if (url.pathname === '/chart') { res.end(page(url.searchParams.get('mode') === 'ipsview' ? 'ipsview' : 'symcon', url.searchParams.get('design') === 'extended', url.searchParams.get('theme') === 'light')); return; }
    res.end('<!doctype html><html lang="de"><head><title>Candlestick – lokale Browserprüfung</title></head><body style="background:#0b0c0e;color:white;font:16px Arial;margin:20px">'
        + '<h1 style="font-size:20px">Candlestick · synthetische Beispieldaten</h1><div style="display:flex;gap:16px;align-items:start;flex-wrap:wrap">'
        + '<section><h2 style="font-size:16px">Kachel · hoch</h2><iframe title="Kachel" src="/chart?mode=symcon&design=extended" style="border:0;width:400px;height:600px"></iframe></section>'
        + '<section><h2 style="font-size:16px">IPSView · breit</h2><iframe title="IPSView" src="/chart?mode=ipsview&design=extended" style="border:0;width:790px;height:440px"></iframe></section></div></body></html>');
}).listen(8800, '127.0.0.1', () => console.log('Candlestick browser fixture: http://127.0.0.1:8800'));
