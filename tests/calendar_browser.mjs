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
function state(offset = 0, metric = 'average', period = 'year', mode = 'symcon') {
    let start = new Date(Date.UTC(2024 - offset, 0, 1));
    let end = new Date(Date.UTC(2025 - offset, 0, 1));
    if (period === 'month') {
        start = new Date(Date.UTC(2024, 1 - offset, 1));
        end = new Date(Date.UTC(2024, 2 - offset, 1));
    } else if (period === 'quarter') {
        start = new Date(Date.UTC(2024, -3 * offset, 1));
        end = new Date(Date.UTC(2024, 3 - 3 * offset, 1));
    } else if (period === 'absolute') {
        start = new Date(Date.UTC(2023, 11, 20 - offset * 21));
        end = new Date(Date.UTC(2024, 0, 10 - offset * 21));
    }
    const year = start.getUTCFullYear();
    const iso = date => date.toISOString().slice(0, 10);
    const last = new Date(end.getTime() - 86400000);
    const dayCount = (end - start) / 86400000;
    const periodLabel = period === 'year' ? String(year) : period === 'month' ? iso(start).slice(0, 7)
        : period === 'quarter' ? year + ' Q' + (Math.floor(start.getUTCMonth() / 3) + 1) : iso(start) + ' – ' + iso(last);
    const data = [];
    let index = 0;
    for (let date = new Date(start); date < end; date.setUTCDate(date.getUTCDate() + 1)) {
        if (index++ % 17) {
            let value = Math.round((10 + Math.sin(index / dayCount * Math.PI * 2 - 1.5) * 15) * 10) / 10;
            if (metric === 'consumption') { value = Math.max(0, value + 5); }
            if (metric === 'minimum') { value -= 3; }
            if (metric === 'maximum') { value += 4; }
            data.push({ date: date.toISOString().slice(0, 10), value });
        }
    }
    const monthCount = (last.getUTCFullYear() - year) * 12 + last.getUTCMonth() - start.getUTCMonth() + 1;
    return { schemaVersion: 1, family: 'calendar', status: 'ready', error: null, chart: {
        schemaVersion: 1, family: 'calendar', theme: 'auto', calendar: { metric,
            title: metric === 'consumption' ? 'Tagesverbrauch (kWh)' : ({minimum: 'Tagesminimum', maximum: 'Tagesmaximum'}[metric] || 'Tagesmitteltemperatur') + ' (°C)',
            sourceLabel: metric === 'consumption' ? 'Beispielzähler' : 'Außentemperatur',
            unit: metric === 'consumption' ? 'kWh' : '°C', decimals: 1, year, baseYear: 2024,
            period, periodLabel, calendarRange: period === 'year' ? String(year) : [iso(start), iso(last)],
            startWeekday: start.getUTCDay(), dayCount, monthCount, viewKey: [period, metric, mode].join(':'),
            windowOffset: offset, maxOffset: period === 'year' ? 53 : 120, data, style: styleForMode(mode) }
    } };
}
const variables = ':root{--symc-background:#303236;--symc-text:#f4f5f7;--symc-text-muted:#969aa2;--symc-border:#555860;--symc-surface:#25272b}';
const translations = JSON.parse(read('EChartsCalendar/locale.json')).translations.de;
function styleForMode(mode) {
    const result = { ...style, showDayNumbers: true };
    if (mode === 'ipsview') {
        Object.assign(result, {
            monthLabelPosition: 'end', weekdayLabelPosition: 'end', monthLabelFormat: 'year-month',
            monthLabelMargin: 12, weekdayLabelMargin: 12, showYearLabel: true, yearLabelPosition: 'left',
            yearFontSizePercent: 125, yearLabelMargin: 20, yearColor: '#FF994D', labelBold: true,
            monthLineColor: '#ffffff', monthLineWidth: 2, monthLineType: 'dashed',
            cellOpacityPercent: 80, cellBorderType: 'dotted', reverseColorScale: true,
            useFixedColorRange: true, colorScaleMinimum: -10, colorScaleMaximum: 30
        });
    }
    return result;
}
function page(mode, metric, period) {
    const initialState = state(0, metric, period, mode);
    const bootstrap = { mode, state: initialState, options: { tileHeaderVisible: mode === 'symcon', tileHeaderInset: mode === 'symcon' ? 58 : 0, echartsThemes: {},
        adaptToBackground: false, calendarStatePath: '/hook/SymconECharts/state/5000/' + 'a'.repeat(32) + '/metric/' + metric + '/period/' + period + '/mode/' + mode }, translations };
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
    const requestedMetric = url.searchParams.get('metric') || url.pathname.split('/metric/')[1]?.split('/')[0];
    const metric = ['average', 'minimum', 'maximum', 'consumption'].includes(requestedMetric) ? requestedMetric : 'average';
    const requestedPeriod = url.searchParams.get('period') || url.pathname.split('/period/')[1]?.split('/')[0];
    const period = ['year', 'quarter', 'month', 'absolute'].includes(requestedPeriod) ? requestedPeriod : 'year';
    const mode = (url.searchParams.get('mode') || url.pathname.split('/mode/')[1]) === 'ipsview' ? 'ipsview' : 'symcon';
    if (url.pathname.startsWith('/hook/SymconECharts/state/')) {
        res.setHeader('Content-Type', 'application/json');
        res.end(JSON.stringify(state(Math.max(0, Math.min(period === 'year' ? 53 : 120, Number(url.searchParams.get('offset')) || 0)), metric, period, mode)));
        return;
    }
    res.setHeader('Content-Type', 'text/html; charset=utf-8');
    if (url.pathname === '/chart') { res.end(page(mode, metric, period)); return; }
    res.end('<!doctype html><html lang="de"><head><title>Calendar – lokale Browserprüfung</title></head><body style="background:#0b0c0e;color:white;font:16px Arial;margin:20px">'
        + '<h1 style="font-size:20px">Calendar · synthetische Beispieldaten · ' + period + '</h1>'
        + '<nav><a href="/?period=year">Jahr</a> · <a href="/?period=quarter">Quartal</a> · <a href="/?period=month">Monat</a> · <a href="/?period=absolute">Von/Bis</a></nav><div style="display:flex;gap:16px;align-items:start;flex-wrap:wrap">'
        + '<section><h2 style="font-size:16px">Kachel · hoch</h2><iframe title="Kachel" src="/chart?mode=symcon&period=' + period + '" style="border:0;width:400px;height:650px"></iframe></section>'
        + '<section><h2 style="font-size:16px">IPSView · breit</h2><iframe title="IPSView" src="/chart?mode=ipsview&period=' + period + '" style="border:0;width:790px;height:440px"></iframe></section></div></body></html>');
}).listen(8796, '127.0.0.1', () => console.log('Calendar browser fixture: http://127.0.0.1:8796'));
