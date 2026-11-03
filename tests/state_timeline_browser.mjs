// Local synthetic browser fixture: no Symcon connection, archive IDs or installation data.
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
import {execFileSync} from 'node:child_process';

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const read = name => fs.readFileSync(path.join(root, name), 'utf8');
const palette = {background: '#303236', text: '#f4f5f7', muted: '#969aa2', border: '#777b83', accent: '#55cbb5',
    seriesColors: ['#5070DD', '#B6D634', '#FF994D', '#FB628B']};
const start = 1792144800;
function state(url) {
    const days = url.searchParams.get('range') === '7d' ? 7 : url.searchParams.get('range') === '30d' ? 30 : 1;
    const count = Math.max(1, Math.min(32, Number(url.searchParams.get('rows')) || 4));
    const names = ['Fenster mit langem Namen · Wohnzimmer', 'Heizkreispumpe', 'Alarmanlage', 'Fenster mit langem Namen · Wohnzimmer'];
    return {status: 'ready', chart: {schemaVersion: 1, family: 'stateTimeline', theme: 'auto',
        range: {key: days + 'd', startTimestamp: start, endTimestamp: start + days * 86400, timezone: 'Europe/Berlin'},
        stateTimeline: {title: 'Archivierte Zustände · Fenster, Pumpe und Alarmanlage',
            rows: Array.from({length: count}, (_, index) => ({id: 'variable-' + (index + 1),
                label: index === 31 ? 'Kontakt 32 · letzte Quelle' : names[index % names.length],
                segments: url.searchParams.get('case') === 'unknown' ? [
                    {startTimestamp: start, endTimestamp: start + days * 86400, value: null, label: 'Unbekannt', color: ''}]
                    : [
                        {startTimestamp: start, endTimestamp: start + 11000, value: null, label: 'Unbekannt', color: ''},
                        {startTimestamp: start + 11000, endTimestamp: start + 30000, value: 0, label: index === 2 ? 'Unscharf' : 'Geschlossen / aus', color: '#5070DD'},
                        {startTimestamp: start + 30000, endTimestamp: start + 30001, value: 1, label: 'Eine Sekunde', color: '#FF994D'},
                        {startTimestamp: start + 30001, endTimestamp: start + 60000, value: 1, label: index === 2 ? 'Scharf' : 'Offen / ein', color: '#B6D634'},
                        {startTimestamp: start + 60000, endTimestamp: start + days * 86400, value: 2, label: '<Störung> & Wartung', color: '#FB628B'}]})),
            style: {enableZoom: true, showTooltip: true, showLabels: true, showGrid: true, animationEnabled: false,
                rowHeightPercent: 65, barOpacityPercent: 90, borderWidth: 0, unknownColor: '#777B83',
                titleFontSizePercent: 110, axisFontSizePercent: url.searchParams.get('font') === '200' ? 200 : 100, labelFontSizePercent: 75,
                ...(url.searchParams.get('design') === 'extended' ? {
                    borderWidth: 2, borderType: 'dashed', borderRadius: 12, labelPosition: url.searchParams.get('position') === 'right' ? 'right' : 'left',
                    fontFamily: 'Georgia', titleBold: false, titleItalic: true, axisLabelBold: true,
                    labelBold: true, labelItalic: true, axisLineWidth: 2, axisLineType: 'dotted', showAxisTicks: true,
                    axisLabelMargin: 20, timeLabelFormat: 'dateTime', gridColor: '#999999', gridLineType: 'dotted',
                    gridLineWidth: 2, gridOpacityPercent: 35, emphasisFocus: 'self', blurOpacityPercent: 20,
                    emphasisEnabled: url.searchParams.get('hover') !== 'disabled'
                } : {})}}}};
}
function page(url) {
    const mode = url.searchParams.get('mode') === 'ipsview' ? 'ipsview' : 'symcon';
    const light = url.searchParams.get('theme') === 'light';
    const bootstrap = {mode, language: 'de', state: state(url),
        options: {tileHeaderVisible: mode === 'symcon' && (url.searchParams.get('header') !== 'hidden'), tileHeaderInset: mode === 'symcon' && (url.searchParams.get('header') !== 'hidden') ? 58 : 0, adaptToBackground: false, echartsThemes: {auto: palette}},
        translations: {Start: 'Beginn', End: 'Ende', Duration: 'Dauer', Value: 'Wert'}};
    const variables = light
        ? ':root{--symc-background:#ffffff;--symc-text:#222222;--symc-text-muted:#666666;--symc-border:#cccccc;--symc-surface:#eeeeee}'
        : ':root{--symc-background:#303236;--symc-text:#f4f5f7;--symc-text-muted:#969aa2;--symc-border:#555860;--symc-surface:#25272b}';
    const replacements = {HTML_LANGUAGE: 'de', HTML_CLASSES: '', ROOT_FONT_SIZE: '16px', VIEWPORT_CONTENT: 'width=device-width, initial-scale=1',
        DOCUMENT_TITLE: 'StateTimeline · lokale Browserprüfung', VISUALIZATION_THEME: variables, MODULE_STYLE: read('EChartsStateTimeline/visualization/style.css'),
        IPSVIEW_STYLE: '', ECHARTS_SCRIPT: read('libs/echarts/6.1.0/echarts.state-timeline.min.js'), ECHARTS_THEME_SCRIPT: '',
        ECHARTS_DESIGN_SCRIPT: read('libs/echarts-design.js'), ECHARTS_ZOOM_SCRIPT: read('libs/echarts-zoom.js'), BOOTSTRAP_JSON: JSON.stringify(bootstrap),
        MODULE_SCRIPT: read('EChartsStateTimeline/visualization/app.js'), IPSVIEW_TRANSPORT_SCRIPT: ''};
    let html = read('EChartsStateTimeline/visualization/index.html').replace(/\{\{([A-Z_]+)\}\}/g, (_, key) => replacements[key] ?? '');
    if (mode === 'symcon' && bootstrap.options.tileHeaderVisible) {
        html = html.replace('<body>', '<body><div style="position:absolute;top:16px;left:20px;font:18px Arial;z-index:4;color:var(--symc-text)">EChartsStateTimeline <span style="position:fixed;right:18px">⤢</span></div>');
    }
    return html;
}
http.createServer((req, res) => {
    const url = new URL(req.url, 'http://127.0.0.1');
    if (url.pathname === '/preview.svg') {
        const values = {Title: 'Zustandszeitdiagramm · Vorschau', Range: '24h', ShowLabels: true, RowHeightPercent: 80,
            TitleFontSizePercent: 110, AxisFontSizePercent: 100, LabelFontSizePercent: 100,
            IPSViewUseTileDesign: url.searchParams.get('independent') !== 'yes', IPSViewUnknownColor: 0x444466,
            ...(url.searchParams.get('design') === 'extended' ? {BorderWidth: 2, BorderType: 'dashed', BorderRadius: 12,
                LabelPosition: 'left', FontFamily: 'Georgia', TitleBold: false, TitleItalic: true, AxisLabelBold: true,
                LabelBold: true, LabelItalic: true, AxisLineWidth: 2, AxisLineType: 'dotted', ShowAxisTicks: true,
                AxisLabelMargin: 20, TimeLabelFormat: 'dateTime', GridColor: 0x999999, GridLineType: 'dotted',
                GridLineWidth: 2, GridOpacityPercent: 35} : {}),
            Sources: [4800,4801].map((id, index) => ({VariableID: id, Label: index === 0 ? 'Fenster <Wohnzimmer>' : 'Pumpe', States: [
                {Value: 0, Label: index === 0 ? 'Geschlossen' : 'Aus', Color: 0x5070DD},
                {Value: 1, Label: index === 0 ? 'Offen' : 'Ein', Color: 0xB6D634}]}))};
        res.setHeader('Content-Type', 'image/svg+xml; charset=utf-8');
        res.end(execFileSync('php', ['tests/gateway_gauges.php', '--state-timeline-preview', JSON.stringify(values),
            url.searchParams.get('mode') === 'ipsview' ? 'ipsview' : 'symcon'], {cwd: root, encoding: 'utf8', windowsHide: true, maxBuffer: 2097152}));
        return;
    }
    res.setHeader('Content-Type', 'text/html; charset=utf-8');
    if (url.pathname === '/chart') {res.end(page(url)); return;}
    res.end('<!doctype html><html lang="de"><head><title>StateTimeline – lokale Browserprüfung</title></head>'
        + '<body style="background:#0b0c0e;color:white;font:16px Arial;margin:20px"><h1 style="font-size:20px">StateTimeline · synthetische Zustandswechsel</h1>'
        + '<div style="display:flex;gap:16px;align-items:start;flex-wrap:wrap">'
        + '<section><h2 style="font-size:16px">Kachel · 32 Zustandsquellen</h2><iframe title="Kachel" src="/chart?mode=symcon&rows=32" style="border:0;width:400px;height:500px"></iframe></section>'
        + '<section><h2 style="font-size:16px">IPSView · 32 Zustandsquellen</h2><iframe title="IPSView" src="/chart?mode=ipsview&rows=32&range=7d&design=extended" style="border:0;width:790px;height:440px"></iframe></section>'
        + '<section><h2 style="font-size:16px">Ohne Kachelkopf · unbekannte Zustände</h2><iframe title="Unbekannte Zustände" src="/chart?header=hidden&theme=light&case=unknown" style="border:0;width:320px;height:400px"></iframe></section>'
        + '</div></body></html>');
}).listen(8804, '127.0.0.1', () => console.log('StateTimeline fixture: http://127.0.0.1:8804'));
