// Local browser fixture only; no connection to Symcon and no real variable data.
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const read = p => fs.readFileSync(path.join(root, p), 'utf8');
const style = {
    orientation: 'auto', firstWeekday: 1, showTooltip: true, showMonthLabels: true, showWeekdayLabels: true,
    showDayNumbers: false, showValues: false, showColorScale: true, cellBorderWidth: 1,
    cellBorderColor: '', emptyCellColor: '', lowColor: '#5070DD', highColor: '#FB628B',
    titleColor: '', axisColor: '', valueColor: '', titleFontSizePercent: 100, axisFontSizePercent: 100,
    valueFontSizePercent: 100, animationEnabled: false
};
function state(offset = 0) {
    const year = 2024 - offset;
    const data = [];
    let index = 0;
    for (let date = new Date(Date.UTC(year, 0, 1)); date.getUTCFullYear() === year; date.setUTCDate(date.getUTCDate() + 1)) {
        if (index++ % 17) { data.push({ date: date.toISOString().slice(0, 10), value: Math.round((10 + Math.sin(index / 366 * Math.PI * 2 - 1.5) * 15) * 10) / 10 }); }
    }
    return { schemaVersion: 1, family: 'calendar', status: 'ready', error: null, chart: {
        schemaVersion: 1, family: 'calendar', theme: 'auto', calendar: { title: 'Tagesmitteltemperatur (°C)',
            sourceLabel: 'Außentemperatur', unit: '°C', decimals: 1, year, baseYear: 2024,
            windowOffset: offset, maxOffset: 53, data, style }
    } };
}
const variables = ':root{--symc-background:#303236;--symc-text:#f4f5f7;--symc-text-muted:#969aa2;--symc-border:#555860;--symc-surface:#25272b}';
const translations = JSON.parse(read('EChartsCalendar/locale.json')).translations.de;
function page(mode) {
    const bootstrap = { mode, state: state(), options: { tileHeaderVisible: true, echartsThemes: {},
        adaptToBackground: false, calendarStatePath: '/hook/SymconECharts/state/5000/' + 'a'.repeat(32) }, translations };
    const replacements = { HTML_LANGUAGE: 'de', HTML_CLASSES: '', ROOT_FONT_SIZE: '16px', VIEWPORT_CONTENT: 'width=device-width, initial-scale=1',
        DOCUMENT_TITLE: 'Calendar browser fixture', VISUALIZATION_THEME: variables, MODULE_STYLE: read('EChartsCalendar/visualization/style.css'),
        IPSVIEW_STYLE: '', ECHARTS_SCRIPT: read('libs/echarts/6.1.0/echarts.calendar.min.js'), ECHARTS_THEME_SCRIPT: '',
        ECHARTS_DESIGN_SCRIPT: read('libs/echarts-design.js'), BOOTSTRAP_JSON: JSON.stringify(bootstrap),
        MODULE_SCRIPT: read('EChartsCalendar/visualization/app.js'), IPSVIEW_TRANSPORT_SCRIPT: '' };
    let html = read('EChartsCalendar/visualization/index.html').replace(/\{\{([A-Z_]+)\}\}/g, (_, key) => replacements[key] ?? '');
    if (mode === 'symcon') { html = html.replace('<body>', '<body><div style="position:absolute;top:16px;left:20px;font:18px Arial;z-index:4">EChartsCalendar <span style="position:fixed;right:18px">⤢</span></div>'); }
    return html;
}
http.createServer((req, res) => {
    const url = new URL(req.url, 'http://127.0.0.1');
    if (url.pathname.startsWith('/hook/SymconECharts/state/')) {
        res.setHeader('Content-Type', 'application/json');
        res.end(JSON.stringify(state(Math.max(0, Math.min(53, Number(url.searchParams.get('offset')) || 0)))));
        return;
    }
    res.setHeader('Content-Type', 'text/html; charset=utf-8');
    if (url.pathname === '/chart') { res.end(page(url.searchParams.get('mode') === 'ipsview' ? 'ipsview' : 'symcon')); return; }
    res.end('<!doctype html><html lang="de"><head><title>Calendar – lokale Browserprüfung</title></head><body style="background:#0b0c0e;color:white;font:16px Arial;margin:20px">'
        + '<h1 style="font-size:20px">Calendar · synthetische Beispieldaten</h1><div style="display:flex;gap:16px;align-items:start;flex-wrap:wrap">'
        + '<section><h2 style="font-size:16px">Kachel · hoch</h2><iframe title="Kachel" src="/chart?mode=symcon" style="border:0;width:400px;height:650px"></iframe></section>'
        + '<section><h2 style="font-size:16px">IPSView · breit</h2><iframe title="IPSView" src="/chart?mode=ipsview" style="border:0;width:790px;height:440px"></iframe></section></div></body></html>');
}).listen(8796, '127.0.0.1', () => console.log('Calendar browser fixture: http://127.0.0.1:8796'));
