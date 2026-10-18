// Local synthetic fixture. No live Symcon installation, personal variables or archive data.
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
import {execFileSync} from 'node:child_process';

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const read = name => fs.readFileSync(path.join(root, name), 'utf8');
const palette = {background: '#303236', text: '#f4f5f7', muted: '#969aa2', border: '#555860', surface: '#25272b',
    seriesColors: ['#5070DD', '#B6D634', '#FF994D', '#FB628B', '#0CA8DF']};
const sourceCount = url => Math.min(5, Math.max(1, Math.trunc(Number(url.searchParams.get('sources'))) || 5));
function state(url) {
    const mode = ['average', 'counter', 'states'].includes(url.searchParams.get('data')) ? url.searchParams.get('data') : 'average';
    const normalized = mode !== 'states' && url.searchParams.get('normalize') === 'yes';
    const offset = Math.min(120, Math.max(0, Number(url.searchParams.get('offset')) || 0));
    const date = new Date(Date.UTC(2024, 9 - offset, 1));
    const month = date.toISOString().slice(0, 7);
    const length = new Date(Date.UTC(date.getUTCFullYear(), date.getUTCMonth() + 1, 0)).getUTCDate();
    const categories = mode === 'states' ? [{id: 'variable-1', label: 'Läuft', color: '#5070DD'},
        {id: 'variable-2', label: mode === 'states' ? 'Bereitschaft' : 'Bereich B', color: '#B6D634'},
        {id: 'variable-3', label: 'Aus', color: '#FF994D'}]
        : Array.from({length: sourceCount(url)}, (_, index) => ({id: 'variable-' + (index + 1),
            label: normalized ? (index % 2 ? 'Luftfeuchtigkeit' : 'Temperatur') : 'Bereich ' + String.fromCharCode(65 + index), color: palette.seriesColors[index]}));
    if (mode === 'states') {categories.push({id: 'unknown', label: 'Unbekannt', color: '#777B83'});}
    const days = Array.from({length}, (_, index) => {
        const day = month + '-' + String(index + 1).padStart(2, '0');
        const daySeconds = day === '2024-10-27' ? 90000 : 86400;
        const status = ['complete', 'complete', 'complete', 'partial', 'missing', 'zero', 'pending'][index % 7];
        let values = categories.map((_, i) => normalized ? (i % 2 ? 60 : (index === 7 ? -5 : 20)) + index % 5
            : 5 + (i * 11 + index * (3 + i * 4)) % 40);
        let slices = values.map((value, i) => ({...categories[i], value}));
        if (mode === 'states' && status !== 'pending') {
            values = status === 'missing' ? [0, 0, 0, daySeconds]
                : [daySeconds * 0.4, daySeconds * 0.2, daySeconds * (status === 'partial' ? 0.25 : 0.4), status === 'partial' ? daySeconds * 0.15 : 0];
            slices = categories.map((category, i) => ({...category, value: values[i]}));
        } else if (status === 'pending' || status === 'missing') {slices = [];}
        else if (status === 'zero') {slices = categories.map(category => ({...category, value: 0}));}
        else if (status === 'partial') {slices = slices.slice(0, -1);}
        if (normalized) {
            slices = slices.map((slice, i) => ({...slice, originalValue: slice.value,
                unit: i % 2 ? '%' : '°C', decimals: i % 2 ? 0 : 1, minimum: 0, maximum: i % 2 ? 100 : 40,
                value: Math.max(0, Math.min(i % 2 ? 100 : 40, slice.value)) / (i % 2 ? 100 : 40) * 100}));
        }
        return {date: day, status: mode === 'states' && status === 'zero' ? 'complete' : status, slices,
            missing: status === 'partial' ? [mode === 'states' ? 'unknown' : categories.at(-1).id] : [], daySeconds};
    });
    return {status: 'ready', chart: {schemaVersion: 1, family: 'calendarPie', theme: 'auto', calendarPie: {
        title: mode === 'states' ? 'Betriebszeiten · Tagesanteile' : mode === 'counter' ? 'Tagesverbrauch nach Bereich' : 'Tagesmittelwerte nach Bereich',
        mode, normalized, month, baseMonth: '2024-10', windowOffset: offset, maxOffset: 120, timezone: 'Europe/Berlin',
        unit: mode === 'states' ? 'h' : mode === 'counter' ? 'kWh' : 'W', decimals: mode === 'states' ? 2 : 1,
        categories, days, style: {showTooltip: true, showLegend: true, showDayNumbers: true,
            showValues: url.searchParams.get('values') === 'yes', firstWeekday: 1, pieRadiusPercent: 70,
            innerRadiusPercent: url.searchParams.get('ring') === 'yes' ? 50 : 0,
            startAngle: 90, clockwise: true, cellBorderWidth: 1, sliceBorderWidth: 1, cellOpacityPercent: 100,
            titleFontSizePercent: 100, axisFontSizePercent: url.searchParams.get('font') === '200' ? 200 : 100,
            valueFontSizePercent: 100, unknownColor: '#777B83', animationEnabled: false}}}};
}
function page(url) {
    const mode = url.searchParams.get('mode') === 'ipsview' ? 'ipsview' : 'symcon';
    const light = url.searchParams.get('theme') === 'light';
    const bootstrap = {mode, language: 'de', state: state(url), options: {tileHeaderVisible: url.searchParams.get('header') !== 'hidden',
        adaptToBackground: false, echartsThemes: {auto: palette}, calendarPieStatePath: '/hook/SymconECharts/state/5000/' + 'a'.repeat(32)},
        translations: {'Daily means': 'Tagesmittelwerte', 'Daily consumption': 'Tagesverbrauch', 'State duration': 'Zustandsdauer',
            'Normalized comparison (0–100)': 'Normalisierter Vergleich (0–100)', Normalized: 'Normalisiert', Range: 'Bereich', 'Circle share': 'Kreisanteil',
            'Missing data': 'Fehlende Daten', 'Incomplete data': 'Unvollständige Daten', 'No positive values': 'Keine positiven Werte',
            'Not completed': 'Nicht abgeschlossen', Unknown: 'Unbekannt', 'Previous month': 'Voriger Monat', 'Next month': 'Nächster Monat',
            'Selected month': 'Ausgewählter Monat', October: 'Oktober', September: 'September', August: 'August', July: 'Juli',
            Sun: 'So', Mon: 'Mo', Tue: 'Di', Wed: 'Mi', Thu: 'Do', Fri: 'Fr', Sat: 'Sa'}};
    const variables = light ? ':root{--symc-background:#ffffff;--symc-text:#222222;--symc-text-muted:#666666;--symc-border:#cccccc;--symc-surface:#eeeeee}'
        : ':root{--symc-background:#303236;--symc-text:#f4f5f7;--symc-text-muted:#969aa2;--symc-border:#555860;--symc-surface:#25272b}';
    const values = {HTML_LANGUAGE: 'de', HTML_CLASSES: '', ROOT_FONT_SIZE: '16px', VIEWPORT_CONTENT: 'width=device-width, initial-scale=1',
        DOCUMENT_TITLE: 'CalendarPie · lokale Browserprüfung', VISUALIZATION_THEME: variables, MODULE_STYLE: read('EChartsCalendarPie/visualization/style.css'),
        IPSVIEW_STYLE: '', ECHARTS_SCRIPT: read('libs/echarts/6.1.0/echarts.calendar-pie.min.js'), ECHARTS_THEME_SCRIPT: '',
        ECHARTS_DESIGN_SCRIPT: read('libs/echarts-design.js'), BOOTSTRAP_JSON: JSON.stringify(bootstrap),
        MODULE_SCRIPT: read('EChartsCalendarPie/visualization/app.js'), IPSVIEW_TRANSPORT_SCRIPT: ''};
    let html = read('EChartsCalendarPie/visualization/index.html').replace(/\{\{([A-Z_]+)\}\}/g, (_, key) => values[key] ?? '');
    if (mode === 'symcon' && bootstrap.options.tileHeaderVisible) {
        html = html.replace('<body>', '<body><div style="position:absolute;top:16px;left:20px;font:18px Arial;z-index:5;color:var(--symc-text)">EChartsCalendarPie <span style="position:fixed;right:18px">⤢</span></div>');
    }
    return html;
}
http.createServer((req, res) => {
    const url = new URL(req.url, 'http://127.0.0.1');
    if (url.pathname.startsWith('/hook/SymconECharts/state/')) {
        // Keep the selected fixture data model across local navigation.
        const referer = new URL(req.headers.referer || 'http://127.0.0.1');
        for (const key of ['data', 'sources', 'normalize']) {
            if (referer.searchParams.has(key)) {url.searchParams.set(key, referer.searchParams.get(key));}
        }
        res.setHeader('Content-Type', 'application/json'); res.end(JSON.stringify(state(url))); return;
    }
    if (url.pathname === '/preview.svg') {
        const values = {Title: 'Kalenderanteile · Vorschau', DataMode: url.searchParams.get('data') || 'average',
            NormalizeValues: url.searchParams.get('normalize') === 'yes',
            Sources: [4800, 4801, 4803, 4804, 4805].slice(0, sourceCount(url))
                .map((id, i) => ({VariableID: id, Label: 'Bereich ' + String.fromCharCode(65 + i), Color: -1, Minimum: 0, Maximum: i % 2 ? 100 : 40})),
            StateVariableID: 4802, States: [{Value: 0, Label: 'Aus', Color: 0xFF994D}, {Value: 1, Label: 'Ein', Color: 0x5070DD}]};
        try {
            res.setHeader('Content-Type', 'image/svg+xml; charset=utf-8');
            res.end(execFileSync('php', ['tests/gateway_gauges.php', '--calendar-pie-preview', JSON.stringify(values),
                url.searchParams.get('mode') === 'ipsview' ? 'ipsview' : 'symcon'], {cwd: root, encoding: 'utf8', windowsHide: true, maxBuffer: 2097152}));
        } catch (error) {res.statusCode = 500; res.end('Preview fixture failed.');}
        return;
    }
    res.setHeader('Content-Type', 'text/html; charset=utf-8');
    if (url.pathname === '/chart') {res.end(page(url)); return;}
    if (url.pathname === '/normalized') {
        res.end('<!doctype html><html lang="de"><head><title>Normalisierte CalendarPie-Prüfung</title></head>'
            + '<body style="background:#0b0c0e;color:white;font:16px Arial;margin:20px"><h1>Normalisierte Beispieldaten</h1>'
            + '<div style="display:flex;gap:16px;flex-wrap:wrap">'
            + '<iframe title="Normalisierte Kachel" src="/chart?normalize=yes&sources=2" style="border:0;width:400px;height:500px"></iframe>'
            + '<iframe title="Normalisiertes IPSView" src="/chart?normalize=yes&sources=2&mode=ipsview&theme=light&values=yes" style="border:0;width:790px;height:500px"></iframe>'
            + '</div><img alt="Normalisierte PHP-Vorschau" src="/preview.svg?normalize=yes&sources=2"></body></html>'); return;
    }
    res.end('<!doctype html><html lang="de"><head><title>CalendarPie · lokale Browserprüfung</title></head>'
        + '<body style="background:#0b0c0e;color:white;font:16px Arial;margin:20px"><h1 style="font-size:20px">CalendarPie · synthetische Archivtage</h1>'
        + '<div style="display:flex;gap:16px;align-items:start;flex-wrap:wrap">'
        + '<section><h2 style="font-size:16px">Kachel · Mittelwerte · 5 Quellen</h2><iframe title="Kachel" src="/chart?sources=5" style="border:0;width:400px;height:500px"></iframe></section>'
        + '<section><h2 style="font-size:16px">IPSView · Zustandsdauern</h2><iframe title="IPSView" src="/chart?mode=ipsview&data=states&ring=yes" style="border:0;width:790px;height:440px"></iframe></section>'
        + '<section><h2 style="font-size:16px">Hell · Verbrauch · 1 Quelle</h2><iframe title="Verbrauch" src="/chart?header=hidden&theme=light&data=counter&sources=1" style="border:0;width:320px;height:400px"></iframe></section>'
        + '</div></body></html>');
}).listen(8805, '127.0.0.1', () => console.log('CalendarPie fixture: http://127.0.0.1:8805'));
